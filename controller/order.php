<?php

require 'controller.php';

class Order extends Controller
{
    protected $image_server, $server, $cart, $reader;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);

        $this->image_server = $this->app->get('settings')['default']['image_server'];
        $this->server       = $this->app->get('settings')['default']['server'];

        $this->reader       = new Sabre\Xml\Reader();

        Requests::register_autoloader();
    }

    private function sign($data = array(), $key)
    {
        ksort($data);

        $str = urldecode(http_build_query($data));

        $strTemp = $str . "&key=" . $key;

        return strtoupper(md5($strTemp));
    }

    public function callback()
    {
        $data   = file_get_contents('php://input');

        $this->reader->xml($data);
        $result = $this->reader->parse();

        $info   = array();

        foreach ($result['value'] as $key => $value) {
            $k        = substr($value['name'], 2);
            $info[$k] = $value['value'];
        }

        $this->app->logger->addInfo("info:" , $info);

		if ($info['return_code'] == 'SUCCESS') {

			if ($info['result_code'] == 'SUCCESS') {
                $openid         = $info['openid'];
                $is_subscribe   = $info['is_subscribe']; //Y 已关注 N 未关注
                $transaction_id = $info['transaction_id']; //微信支付订单号
                $order_code     = $info['out_trade_no'];
                $total_fee      = (int)$info['total_fee'] / 100;
                $sign           = $info['sign'];

                $order = $this->app->db->select('order', ['id'], ['code[=]' => $order_code, 'uuid[=]' => $openid, 'status[=]' => 0]);

                if ( ! empty($order) ) {
                    // 更新订单 - sign 和 金额没有做验证 有安全问题
                    $this->app->db->update("order", [
                        "payment_number" => $transaction_id,
                        "status"         => 1,
                        "payed_time"     => time()
                    ], [
                        "code[=]" => $order_code
                    ]);
                    
                    $order_id = $order[0]['id'];
                    $this->app->logger->addInfo("order_id:" . $order_id);
                    // 生成票码
                    $product  = $this->app->db->select('order_product', ['id', 'product_id', 'product_name', 'product_price', 'product_count'], ['order_id[=]' => $order_id]);
                    $this->app->logger->addInfo("product:", $product);
                    foreach ($product as $pro) {
                        for ($i = 0; $i < $pro['product_count']; $i++) {
                            $code      = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号
                            $this->app->db->insert("ticket", [
                                         "code" => $code,
                                         "uuid" => $openid,
                                     "order_id" => $order_id,
                                   "product_id" => $pro['product_id'],
                                 "product_name" => $pro['product_name'],
                                "product_price" => $pro['product_price'],
                                  "create_time" => time(),
                                 "modifie_time" => time()
                            ]);
                        }
                    }
                    // 消息推送
                }

            }
        }

        $xml = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
        echo $xml;
    }


    public function add()
    {
        $json = array();

        if ( ! isset($_SESSION['cart']) ) {
            $json['code']        = 2;
            $json['msg']         = 'The Cart Is Empty.';
            echo json_encode($json);
            exit;     
        }

        $cart     = $_SESSION['cart'];
        $total    = 0;
        $order_product = array();
        foreach($cart as $c) {
            $results        = $this->app->db->select('product', ['name', 'image', 'price'], ['id[=]' => $c['id']]);
            $price          = 0;
            $product_option_name = '';
            if ( ! empty($c['option']) ) {
                foreach ($c['option'] as $v_id) {
                    $o_val = $this->app->db->select('product_option_value', ['description', 'add_price'], ['id[=]' => $v_id]);
                    $price = $price + (int)$o_val[0]['add_price'];
                    $product_option_name = $product_option_name . '-' . $o_val[0]['description'];
                }
            }

            foreach ($results as $result) {
                $price     += (int)$result['price'];
                $total     += $price * (int)$c['quantity'];
                $order_product[] = array(
                    'product_id' => $c['id'],
                    'product_name' => $result['name'] . $product_option_name,
                    'product_price' => (float)$price,
                    'product_count' => (int)$c['quantity']
                );
            }
        }

        $this->app->logger->addInfo("total:" . $total);

        $discount  = $total * $this->app->get('settings')['default']['discount'];
        $pay       = $total - $discount;
        $pay_fen   = $pay * 100;

        $code      = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号
        $this->app->logger->addInfo("code:" . $code);

        $this->app->db->insert("order", [
                    "code" => $code,
                    "uuid" => $_SESSION['uuid'],
                   "total" => $pay, //实际支付金额
               "sub_total" => $total, //订单小计
               "red_total" => $discount, //优惠的金额
            "payment_code" => "weixin_js",
             "create_time" => time(),
            "modifie_time" => time(),
              "payed_time" => time()
        ]);

        $order_id = $this->app->db->id();

        $this->app->logger->addInfo("order_id : " . $order_id);

        foreach ($order_product as $product) {
            $this->app->db->insert("order_product", [
                     "order_id" => $order_id,
                   "product_id" => $product['product_id'],
                 "product_name" => $product['product_name'],
                "product_price" => $product['product_price'],
                "product_count" => $product['product_count']
            ]);
        }

        $server     = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $randstr    = $this->GeraHash(32);
        $this->app->logger->addInfo($randstr);

        $ip_address = '';
        $serverParams = $this->request->getServerParams(); // 获取客户端 IP adress

        if (isset($serverParams['REMOTE_ADDR'])) {
            $ip_address = $serverParams['REMOTE_ADDR'];
        }

        $this->app->logger->addInfo("ip_address :" . $ip_address);

        $openid     = $_SESSION['uuid'];
        
        $this->app->logger->addInfo($openid);
        $body       = '金宁户外运动';

        $order = array(
            'appid' => $this->app->get('settings')['weixin']['appID'],
            'mch_id' => $this->app->get('settings')['weixin']['mch_id'],
            'device_info' => 'WEB',
            'nonce_str' => $randstr,
            'body' => $body,
            'out_trade_no' => $code,
            'total_fee' => $pay_fen,
            'spbill_create_ip' => $ip_address,
            'notify_url' => $this->app->get('settings')['weixin']['buck_url'],
            'trade_type' => 'JSAPI',
            'openid' => $openid
        );

        $sign = $this->sign($order, $this->app->get('settings')['weixin']['api_key']);
        $this->app->logger->addInfo($sign);

        $xml = "<xml>
        <appid>{$this->app->get('settings')['weixin']['appID']}</appid>
        <mch_id>{$this->app->get('settings')['weixin']['mch_id']}</mch_id>
        <device_info>WEB</device_info>
        <nonce_str>{$randstr}</nonce_str>
        <body>{$body}</body>
        <out_trade_no>{$code}</out_trade_no>
        <total_fee>{$pay_fen}</total_fee>
        <spbill_create_ip>{$ip_address}</spbill_create_ip>
        <notify_url>{$this->app->get('settings')['weixin']['buck_url']}</notify_url>
        <trade_type>JSAPI</trade_type>
        <openid>{$openid}</openid>
        <sign>{$sign}</sign>
        </xml>";

        $req = Requests::post($server, array(), $xml);
        $this->app->logger->addInfo($req->status_code);
        

        if ($req->status_code != 200) {
            $json['code']        = 1;
            $json['msg']         = 'Requests Fail.';
            echo json_encode($json);
            exit;
        }

        $this->reader->xml($req->body);
        $result = $this->reader->parse();
        $this->app->logger->addInfo('xml result', $result['value']);

        $prepay = "prepay_id=";

        foreach ($result['value'] as $key => $value) {
            if ($value['name'] == '{}prepay_id') {
                $prepay .= $value['value'];
            }
        }

        $this->app->logger->addInfo("prepay:" . $prepay);

        $data = array(
                'appId' => $this->app->get('settings')['weixin']['appID'],
            'timeStamp' => time(),
             'nonceStr' => $this->GeraHash(32),
              'package' => $prepay,
             'signType' => 'MD5'
        ); 

        $sign2           = $this->sign($data, $this->app->get('settings')['weixin']['api_key']);

        $this->app->logger->addInfo("sign2:" . $sign2);

        $data['paySign'] = $sign2;

        $json['code']    = 0;
        $json['msg']     = 'Requests Success.';
        $json['data']    = $data;

        echo json_encode($json);
    }
}
