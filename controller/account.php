<?php

require 'controller.php';
require 'sms/sms.php';
require "wechat.class.php";

class Account extends Controller
{
    protected $image_server, $server, $reader;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);

        $this->image_server = $this->app->get('settings')['default']['image_server'];
        $this->server       = $this->app->get('settings')['default']['server'];

        $this->reader       = new Sabre\Xml\Reader();

        Requests::register_autoloader();
    }

    public function index()
    {
        $user   = $this->app->db->get('user', ['nickname', 'telephone', 'image', 'transaction', 'total', 'type', 'referee_uuid', 'commission'], ['uuid[=]' => $_SESSION['uuid']]);

        $default_user_image  = $this->image_server . 'default_user_image.png';

        echo $this->app->template->render('account', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'user' => $user, 'default_user_image' => $default_user_image]);
    }

    public function order()
    {
        $order   = $this->app->db->select('order', ['code', 'total', 'sub_total', 'red_total', 'payment_code', 'payment_number', 'status', 'create_time', 'payed_time'], ['uuid[=]' => $_SESSION['uuid'], 'status' => 1]);

        echo $this->app->template->render('order', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'order' => $order]);
    }

    public function login()
    {
        $scripts[] = 'https://res.wx.qq.com/open/libs/weuijs/1.1.1/weui.min.js';
        $scripts[] = 'https://unpkg.com/axios/dist/axios.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'login.js?20170526172900';

        echo $this->app->template->render('login', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'scripts' => $scripts]);
    }

    // 验证登录
    public function checklogin()
    {       
        $body  = $this->request->getParsedBody();
        $phone = $body['phone'];
        $code  = $body['code'];
        
        if ( ! isset($_SESSION['code']) || $_SESSION['code'] != $code ) {
            $json['code'] = 1;
            $json['msg']  = '验证码错误';  
        } else {
            unset($_SESSION['code']);

            $user   = $this->app->db->get('user', ['uuid'], ['telephone[=]' => $phone]);
            if ($user) {
                $_SESSION['uuid'] = $user['uuid'];
            } else {  //注册新用户
                $uuid         = $this->microtime_float() . $this->GeraHash(14, true);
                $password     = "12345678";
                $en_password  = password_hash($password, PASSWORD_DEFAULT);

                $query = $this->app->db->insert("user", [
                             "uuid" => $uuid,
                         "nickname" => $phone,
                        "telephone" => $phone,
                      'en_password' => $en_password,
                         'password' => $password,
                     'referee_uuid' => isset($_SESSION['utm_source']) ? $_SESSION['utm_source'] : '',
                             "type" => 1,
                           "source" => 3,
                    "register_time" => time(),
                       "login_time" => time()
                ]);

                $_SESSION['uuid'] = $uuid;
                if (isset($_SESSION['utm_source'])) {
                    unset($_SESSION['utm_source']);
                }
            }

            $json['code'] = 0;
            $json['msg']  = 'Success.';  
        }
      
        echo json_encode($json);
    }

    public function logout()
    {
        unset($_SESSION['uuid']);
    }

    public function info()
    {
        // $userinfo  = $this->app->db->get('user_bank', ['name'], ['uuid[=]' => $_SESSION['uuid']]);

        $scripts[] = 'https://res.wx.qq.com/open/libs/weuijs/1.1.1/weui.min.js';
        $scripts[] = 'https://unpkg.com/axios/dist/axios.min.js';
        // $scripts[] = $this->server . 'dist/js/' . 'phone.js?20170526173100';

        echo $this->app->template->render('info', ['server' => $this->server, 'item' => 'account', 'scripts' => $scripts, 'cartCount' => $this->cartCount]);
    }

    public function phone()
    {
        $user      = $this->app->db->get('user', ['telephone'], ['uuid[=]' => $_SESSION['uuid']]);

        $telephone = $user['telephone'];

        $scripts[] = 'https://res.wx.qq.com/open/libs/weuijs/1.1.1/weui.min.js';
        $scripts[] = 'https://unpkg.com/axios/dist/axios.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'phone.js?20170526173100';

        echo $this->app->template->render('phone', ['server' => $this->server, 'item' => 'account', 'scripts' => $scripts, 'cartCount' => $this->cartCount, 'telephone' => $telephone]);
    }
    // 发送验证码
    public function sendcode()
    {       
        $body  = $this->request->getParsedBody();
        $phone = $body['phone'];
        $where = $body['where'];

        switch ($where) {
            case 'login':
                $code  = $this->GeraHash(4, true);
                $send  = Sms::code($phone, 'SMS_63750903', array('code' => $code, 'product' => '金宁户外运动'));
                break;
            case 'phone':
                $code  = $this->GeraHash(4, true);
                $send  = Sms::code($phone, 'SMS_63750898', array('code' => $code, 'product' => '手机号码，属于'));
                break;
            default:
                $send  = 0;
                break;
        }

        if ($send) {
            $_SESSION['code'] = $code;
            $json['code'] = 0;
            $json['msg']  = $code;
        } else {
            $json['code'] = 1;
            $json['msg']  = 'Error';
        }

        echo json_encode($json);
    }
    // 绑定/变更手机号码
    public function savephone()
    {       
        $body  = $this->request->getParsedBody();
        $phone = $body['phone'];
        $code  = $body['code'];
        
        if ( ! isset($_SESSION['code']) || $_SESSION['code'] != $code ) {
            $json['code'] = 1;
            $json['msg']  = '验证码错误';  
        } else {
            //判断手机号是否存在
            $user = $this->app->db->get('user', [
                'id'
            ], [
                'telephone[=]' => $phone
            ]);

            if ($user) {
                $json['code'] = 2;
                $json['msg']  = '手机号已经被使用，请使用其他手机号。';  
            } else {
                $this->app->db->update("user", [
                        "telephone" => $phone
                ], [
                    "uuid[=]" => $_SESSION['uuid']
                ]);

                unset($_SESSION['code']);

                $json['code'] = 0;
                $json['msg']  = 'Success.';
            }
        }
      
        echo json_encode($json);
    }

    public function recharge()
    {
        $scripts[] = 'https://res.wx.qq.com/open/libs/weuijs/1.1.1/weui.min.js';
        $scripts[] = 'https://unpkg.com/axios/dist/axios.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'recharge.js';

        echo $this->app->template->render('recharge', ['server' => $this->server, 'item' => 'account', 'scripts' => $scripts, 'cartCount' => $this->cartCount]);
    }

    public function transaction()
    {
        $transaction   = $this->app->db->select('user_transaction', ['id', 'code', 'amount', 'status', 'source', 'remark', 'modifie_time'], ['uuid[=]' => $_SESSION['uuid'], 'status[=]' => 1]);
        $source        = array('微信充值', '支付宝充值', '充值赠送', '会员卡导入', '订单消费', '推广收益提现');
        $user          = $this->app->db->get('user', ['transaction'], ['uuid[=]' => $_SESSION['uuid']]);
        echo $this->app->template->render('transaction', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'transaction' => $transaction, 'source' => $source, 'amount' => $user['transaction']]);
    }

    // 余额充值 - 支付宝
    public function zhi()
    {
        $json    = array();

        $body    = $this->request->getParsedBody();
        $amount  = $body['amount']; // 充值金额
        // 创建充值记录
        $code    = $this->add_transaction(1, $amount);

        $zhi     = "https://openapi.alipay.com/gateway.do";

        $content = array(
                 'subject' => '金宁户外运动余额充值',
            'out_trade_no' => $code,
            'total_amount' => $amount,
            'product_code' => 'QUICK_WAP_PAY'
        );

        $data = array(
                 'app_id' => $this->app->get('settings')['zhi']['appID'],
                 'method' => 'alipay.trade.wap.pay',
                'charset' => 'utf-8',
              'sign_type' => 'RSA2',
              'timestamp' => date("Y-m-d H:i:s", time()),
                'version' => '1.0',
             'return_url' => $this->app->get('settings')['default']['server'] . "account/transaction.html",
             'notify_url' => $this->app->get('settings')['zhi']['t_back'],
            'biz_content' => json_encode($content)
        );

        $sign         = $this->app->tool->sign($data);

        $data['sign'] = $sign;

        $json['code'] = 0;
        $json['msg']  = 'Requests Success.';
        $json['data'] = $data;

        echo json_encode($json);
    }

    private function add_transaction($source, $amount)
    {
        $code      = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号

        $this->app->db->insert("user_transaction", [
                    "code" => $code,
                    "uuid" => $_SESSION['uuid'],
                  "amount" => $amount, //充值金额
                  "source" => $source, //来源
                  "remark" => '现金充值',
             "create_time" => time(),
            "modifie_time" => time()
        ]);

        return $code;
    }

    public function zcallback() // 支付宝充值异步通知
    {
        if ('TRADE_SUCCESS' == $_POST['trade_status']) {
            $uuid           = $_POST['buyer_id'];
            $code           = $_POST['out_trade_no'];
            $gmt_payment    = strtotime($_POST['gmt_payment']);

            $transaction    = $this->app->db->get('user_transaction', ['id', 'amount'], ['code[=]' => $code, 'uuid[=]' => $uuid, 'status[=]' => 0, 'source[=]' => 1]);

            if ($transaction) {
                $this->app->db->update("user_transaction", [
                    "status"         => 1,
                    "modifie_time"   => $gmt_payment
                ], [
                    "code[=]" => $code
                ]);
                //充值赠送
                $discounts = array(
                     '1000' => 300,
                     '2000' => 800,
                     '3000' => 1200,
                     '5000' => 2000,
                    '10000' => 4000
                );

                $amount    = (int)$transaction['amount'];

                $code1     = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号

                $this->app->db->insert("user_transaction", [
                            "code" => $code1,
                            "uuid" => $uuid,
                          "amount" => $discounts[$amount], //充值金额
                          "status" => 1,
                          "source" => 2, //来源
                          "remark" => '充值赠送',
                     "create_time" => time(),
                    "modifie_time" => time()
                ]);

                $transaction_id = $this->app->db->id();
                //更新余额
                $this->app->db->update("user", [
                    "transaction[+]"  => $amount + $discounts[$amount]
                ], [
                    "uuid[=]" => $uuid
                ]);
                // 佣金
                $user = $this->app->db->get('user', ['referee_uuid'], ['uuid[=]' => $uuid]);

                if ($user) {
                    $income_amount = $amount * 0.05;
                    $this->app->db->insert("user_income", [
                                        "uuid" => $user['referee_uuid'],
                                    "order_id" => $transaction_id,
                                  "order_uuid" => $uuid,
                                 "order_total" => $amount,
                                      "amount" => $income_amount,
                                      "status" => 1,
                                      "source" => 1,
                                 "create_time" => time(),
                                "modifie_time" => time()
                            ]);

                    // 更新 User 表
                    $this->app->db->update("user", [
                        "commission[+]"  => $income_amount
                    ], [
                        "uuid[=]" => $user['referee_uuid']
                    ]);
                }
                
                echo 'success';
            }
        }
    }
    // 余额充值 - 微信支付
    public function weixin()
    {
        $json    = array();

        $body    = $this->request->getParsedBody();
        $amount  = $body['amount']; // 充值金额
        // 创建充值记录
        $code    = $this->add_transaction(0, $amount);

        $server     = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $randstr    = $this->GeraHash(32);

        $ip_address   = '';
        $serverParams = $this->request->getServerParams(); // 获取客户端 IP adress

        if (isset($serverParams['REMOTE_ADDR'])) {
            $ip_address = $serverParams['REMOTE_ADDR'];
        }

        $openid     = $_SESSION['uuid'];
        
        $body       = '金宁户外运动余额充值';

        $pay_fen    = $amount * 100;

        $order = array(
            'appid' => $this->app->get('settings')['weixin']['appID'],
            'mch_id' => $this->app->get('settings')['weixin']['mch_id'],
            'device_info' => 'WEB',
            'nonce_str' => $randstr,
            'body' => $body,
            'out_trade_no' => $code,
            'total_fee' => $pay_fen,
            'spbill_create_ip' => $ip_address,
            'notify_url' => $this->app->get('settings')['weixin']['buck_url_2'],
            'trade_type' => 'JSAPI',
            'openid' => $openid
        );

        $sign = $this->sign($order, $this->app->get('settings')['weixin']['api_key']);

        $xml = "<xml>
        <appid>{$this->app->get('settings')['weixin']['appID']}</appid>
        <mch_id>{$this->app->get('settings')['weixin']['mch_id']}</mch_id>
        <device_info>WEB</device_info>
        <nonce_str>{$randstr}</nonce_str>
        <body>{$body}</body>
        <out_trade_no>{$code}</out_trade_no>
        <total_fee>{$pay_fen}</total_fee>
        <spbill_create_ip>{$ip_address}</spbill_create_ip>
        <notify_url>{$this->app->get('settings')['weixin']['buck_url_2']}</notify_url>
        <trade_type>JSAPI</trade_type>
        <openid>{$openid}</openid>
        <sign>{$sign}</sign>
        </xml>";

        $req = Requests::post($server, array(), $xml);
        
        if ($req->status_code != 200) {
            $json['code']        = 1;
            $json['msg']         = 'Requests Fail.';
            echo json_encode($json);
            exit;
        }

        $this->reader->xml($req->body);
        $result = $this->reader->parse();

        $prepay = "prepay_id=";

        foreach ($result['value'] as $key => $value) {
            if ($value['name'] == '{}prepay_id') {
                $prepay .= $value['value'];
            }
        }

        $data = array(
                'appId' => $this->app->get('settings')['weixin']['appID'],
            'timeStamp' => time(),
             'nonceStr' => $this->GeraHash(32),
              'package' => $prepay,
             'signType' => 'MD5'
        ); 

        $sign2           = $this->sign($data, $this->app->get('settings')['weixin']['api_key']);

        $data['paySign'] = $sign2;

        $json['code']    = 0;
        $json['msg']     = 'Requests Success.';
        $json['data']    = $data;

        echo json_encode($json);
    }

    public function wcallback()
    {
        $data   = file_get_contents('php://input');

        $this->reader->xml($data);
        $result = $this->reader->parse();

        $info   = array();

        foreach ($result['value'] as $key => $value) {
            $k        = substr($value['name'], 2);
            $info[$k] = $value['value'];
        }

		if ($info['return_code'] == 'SUCCESS') {

			if ($info['result_code'] == 'SUCCESS') {
                $openid         = $info['openid'];
                $is_subscribe   = $info['is_subscribe']; //Y 已关注 N 未关注
                $transaction_id = $info['transaction_id']; //微信支付订单号
                $code           = $info['out_trade_no'];
                $total_fee      = (int)$info['total_fee'] / 100;
                $sign           = $info['sign'];
                $modifie_time   = time();

                $transaction    = $this->app->db->get('user_transaction', ['id', 'amount'], ['code[=]' => $code, 'uuid[=]' => $openid, 'status[=]' => 0, 'source[=]' => 0]);

                if ($transaction) {
                    $this->app->db->update("user_transaction", [
                        "status"         => 1,
                        "modifie_time"   => $modifie_time
                    ], [
                        "code[=]" => $code
                    ]);
                    //充值赠送
                    $discounts = array(
                        '1000' => 300,
                        '2000' => 800,
                        '3000' => 1200,
                        '5000' => 2000,
                       '10000' => 4000
                    );
                    $amount    = (int)$transaction['amount'];
    
                    $code1     = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号

                    $this->app->db->insert("user_transaction", [
                                "code" => $code1,
                                "uuid" => $openid,
                              "amount" => $discounts[$amount], //充值金额
                              "status" => 1,
                              "source" => 2, //来源
                              "remark" => '充值赠送',
                         "create_time" => time(),
                        "modifie_time" => time()
                    ]);

                    $transaction_id = $this->app->db->id();
                    //更新余额
                    $this->app->db->update("user", [
                        "transaction[+]"  => $amount + $discounts[$amount]
                    ], [
                        "uuid[=]" => $openid
                    ]);

                    // 佣金
                    $user = $this->app->db->get('user', ['referee_uuid'], ['uuid[=]' => $openid]);

                    if ($user) {
                        $income_amount = $amount * 0.05;
                        $this->app->db->insert("user_income", [
                                            "uuid" => $user['referee_uuid'],
                                        "order_id" => $transaction_id,
                                      "order_uuid" => $openid,
                                     "order_total" => $amount,
                                          "amount" => $income_amount,
                                          "status" => 1,
                                          "source" => 1,
                                     "create_time" => time(),
                                    "modifie_time" => time()
                                ]);
                        // 更新 User 表
                        $this->app->db->update("user", [
                            "commission[+]"  => $income_amount
                        ], [
                            "uuid[=]" => $user['referee_uuid']
                        ]);
                    }           
                    // 消息推送
                    $options = array(
                            'token'=> $this->app->get('settings')['weixin']['token'],
                            'encodingaeskey'=> $this->app->get('settings')['weixin']['encodingaeskey'],
                            'appid'=> $this->app->get('settings')['weixin']['appID'],
                            'appsecret'=> $this->app->get('settings')['weixin']['appSecret']
                    );

                    $weObj = new Wechat($options);

                    $json = array(
                        "touser" => $openid,
                   "template_id" => "9-ZOLKYAqs-31qanynTFg5-D_uhnPFkJylh9PQ-rho4",
                           "url" => $this->app->get('settings')['default']['server'] . "account/transaction.html",
                          "data" => array(
                         "first" => array("value" => "您好，您已经充值成功", "color" => "#173177"),
                      "keyword1" => array("value" => $code, "color" => "#173177"),
                      "keyword2" => array("value" => date("Y-m-d H:i:s", time()), "color" => "#173177"),
                      "keyword3" => array("value" => '￥'. $total_fee, "color" => "#173177"),
                      "keyword4" => array("value" => "微信支付", "color" => "#173177"),
                        "remark" => array("value" => "感谢您的惠顾", "color" => "#173177"),
                        )
                    );

                    $weObj->sendTemplateMessage($json);
                }

            }
        }

        $xml = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
        echo $xml;
    }

    private function sign($data = array(), $key)
    {
        ksort($data);

        $str = urldecode(http_build_query($data));

        $strTemp = $str . "&key=" . $key;

        return strtoupper(md5($strTemp));
    }

}
