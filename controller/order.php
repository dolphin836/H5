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


    public function add()
    {
         $json       = array();

        if ( ! isset($_SESSION['cart']) ) {
            $json['code']        = 2;
            $json['msg']         = 'The Cart Is Empty.';
            echo json_encode($json);
            exit;     
        }

        $cart     = $_SESSION['cart'];
        $total    = 0;
        foreach($cart as $c) {
            $results        = $this->app->db->select('product', ['name', 'image', 'price'], ['id[=]' => $c['id']]);
            $price          = 0;
            if ( ! empty($c['option']) ) {
                foreach ($c['option'] as $v_id) {
                    $o_val = $this->app->db->select('product_option_value', ['description', 'add_price'], ['id[=]' => $v_id]);
                    $price = $price + (int)$o_val[0]['add_price'];
                }
            }
            foreach ($results as $result) {
                $price     += (int)$result['price'];
                $total     += $price;
            }
        }

        $discount  = $total * 0.1;
        $pay       = $total - $discount;
        // $pay_fen   = $pay * 100;
        $pay_fen   = 1;

        $code      = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号
        $this->app->logger->addInfo("code:" . $code);

        $order_id = $this->app->db->insert("order", [
                    "code" => $code,
                    // "uuid" => $_SESSION['uuid'],
                    "uuid" => '1111',
                   "total" => $pay, //实际支付金额
               "sub_total" => $total, //订单小计
               "red_total" => $discount, //优惠的金额
            "payment_code" => "weixin_js",
             "create_time" => time(),
            "modifie_time" => time(),
              "payed_time" => time()
        ]);

        $this->app->logger->addInfo("order_id" . $order_id);

        $server     = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $randstr    = $this->GeraHash(32);
        $this->app->logger->addInfo($randstr);

        $ip_address = '';
        $serverParams = $this->request->getServerParams(); // 获取客户端 IP adress

        if (isset($serverParams['REMOTE_ADDR'])) {
            $ip_address = $serverParams['REMOTE_ADDR'];
        }

        $openid     = $_SESSION['uuid'];
        
        $this->app->logger->addInfo($openid);
        $body       = '金宁户外运动';

        $order = array(
            'appid' => $this->get('settings')['weixin']['appID'],
            'mch_id' => $this->get('settings')['weixin']['mch_id'],
            'device_info' => 'WEB',
            'nonce_str' => $randstr,
            'body' => $body,
            'out_trade_no' => $code,
            'total_fee' => $pay_fen,
            'spbill_create_ip' => $ip_address,
            'notify_url' => $this->get('settings')['weixin']['buck_url'],
            'trade_type' => 'JSAPI',
            'openid' => $openid
        );

        $sign = $this->sign($order, $this->get('settings')['weixin']['api_key']);
        $this->app->logger->addInfo($sign);

        $xml = "<xml>
        <appid>{$this->get('settings')['weixin']['appID']}</appid>
        <mch_id>{$this->get('settings')['weixin']['mch_id']}</mch_id>
        <device_info>WEB</device_info>
        <nonce_str>{$randstr}</nonce_str>
        <body>{$body}</body>
        <out_trade_no>{$code}</out_trade_no>
        <total_fee>{$pay_fen}</total_fee>
        <spbill_create_ip>{$ip_address}</spbill_create_ip>
        <notify_url>{$this->get('settings')['weixin']['buck_url']}</notify_url>
        <trade_type>JSAPI</trade_type>
        <openid>{$openid}</openid>
        <sign>{$sign}</sign>
        </xml>";

        $req = Requests::post($server, array(), $xml);
        $this->app->logger->addInfo($req->status_code);
        $this->app->logger->addInfo($req->body);

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

        $data = array(
                'appId' => $this->get('settings')['weixin']['appID'],
            'timeStamp' => time(),
             'nonceStr' => randstr(32),
              'package' => $prepay,
             'signType' => 'MD5'
        ); 

        $sign2           = $this->sign($data, $this->get('settings')['weixin']['api_key']);

        $data['paySign'] = $sign2;

        $json['code']    = 0;
        $json['msg']     = 'Requests Success.';
        $json['data']    = $data;

        echo json_encode($json);
    }
}
