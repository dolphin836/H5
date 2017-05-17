<?php

require 'controller.php';
require 'sms/sms.php';

class Account extends Controller
{
    protected $image_server, $server;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);

        $this->image_server = $this->app->get('settings')['default']['image_server'];
        $this->server       = $this->app->get('settings')['default']['server'];
    }

    public function index()
    {
        if ( ! isset($_SESSION['uuid']) ) {
            var_dump("请先登录");
            exit;
        }

        $user   = $this->app->db->select('user', ['nickname', 'telephone', 'image', 'transaction', 'total', 'type'], ['uuid[=]' => $_SESSION['uuid']]);

        $default_user_image  = $this->image_server . 'default_user_image.png';

        echo $this->app->template->render('account', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'user' => $user[0], 'default_user_image' => $default_user_image]);
    }

    public function order()
    {
        if ( ! isset($_SESSION['uuid']) ) {
            var_dump("请先登录");
            exit;
        }

        $order   = $this->app->db->select('order', ['code', 'total', 'sub_total', 'red_total', 'payment_code', 'payment_number', 'status', 'create_time', 'payed_time'], ['uuid[=]' => $_SESSION['uuid']]);

        echo $this->app->template->render('order', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'order' => $order]);
    }

    public function login()
    {
        $_SESSION['uuid'] = 'oNP02wK_vjLWB_iRRf6qbqmDXBiE';
        echo $this->app->template->render('login', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount]);
    }

    public function logout()
    {
        unset($_SESSION['uuid']);
    }

    public function phone()
    {
        $user      = $this->app->db->get('user', ['telephone'], ['uuid[=]' => $_SESSION['uuid']]);

        $telephone = $user['telephone'];

        $scripts[] = 'https://res.wx.qq.com/open/libs/weuijs/1.1.1/weui.min.js';
        $scripts[] = 'https://unpkg.com/axios/dist/axios.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'phone.js?22333';

        echo $this->app->template->render('phone', ['server' => $this->server, 'item' => 'account', 'scripts' => $scripts, 'cartCount' => $this->cartCount, 'telephone' => $telephone]);
    }
    // 发送验证码
    public function sendcode()
    {       
        $body  = $this->request->getParsedBody();
        $phone = $body['phone'];
        $code  = $this->GeraHash(4, true);
        $send  = Sms::code($phone, $code);

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
            $this->app->db->update("user", [
                     "telephone" => $phone
            ], [
                "uuid[=]" => $_SESSION['uuid']
            ]);

            unset($_SESSION['code']);

            $json['code'] = 0;
            $json['msg']  = 'Success.';  
        }
      
        echo json_encode($json);
    }

    public function recharge()
    {
        $scripts[] = 'https://res.wx.qq.com/open/libs/weuijs/1.1.1/weui.min.js';
        $scripts[] = 'https://unpkg.com/axios/dist/axios.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'recharge.js?32252225';

        echo $this->app->template->render('recharge', ['server' => $this->server, 'item' => 'account', 'scripts' => $scripts, 'cartCount' => $this->cartCount]);
    }

    // 余额充值 - 支付宝
    public function zhi()
    {
        $this->app->logger->addInfo("zhi");
        $json    = array();

        $body    = $this->request->getParsedBody();
        $amount  = $body['amount']; // 充值金额
        $this->app->logger->addInfo("amount:" . $amount);
        // 创建充值记录
        $code    = $this->add_transaction(1, $amount);
        $this->app->logger->addInfo("code:" . $code);

        $zhi     = "https://openapi.alipay.com/gateway.do";

        $content = array(
                 'subject' => '金宁户外运动',
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
             'return_url' => 'http://m.outatv.com/account.html',
             'notify_url' => $this->app->get('settings')['zhi']['t_back'],
            'biz_content' => json_encode($content)
        );

        $sign         = $this->app->tool->sign($data);
        $this->app->logger->addInfo("sign:" . $sign);

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
            $this->app->logger->addInfo("transaction:", $transaction);
            if ($transaction) {
                $this->app->db->update("user_transaction", [
                    "status"         => 1,
                    "modifie_time"   => $gmt_payment
                ], [
                    "code[=]" => $code
                ]);
                //充值赠送
                $discounts = array(
                        '1' => 10,
                     '1000' => 300,
                     '2000' => 800,
                     '3000' => 1200,
                     '5000' => 2000,
                    '10000' => 4000
                );
                $amount    = (int)$transaction['amount'];
                $this->app->logger->addInfo("amount:" . $amount);
                $code      = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号

                $this->app->db->insert("user_transaction", [
                            "code" => $code,
                            "uuid" => $uuid,
                          "amount" => $discounts[$amount], //充值金额
                          "status" => 1,
                          "source" => 2, //来源
                          "remark" => '充值赠送',
                     "create_time" => time(),
                    "modifie_time" => time()
                ]);
                //更新余额
                $this->app->db->update("user", [
                    "transaction"  => $amount + $discounts[$amount]
                ], [
                    "uuid[=]" => $uuid
                ]);
                
                echo 'success';
            }
        }
    }

}
