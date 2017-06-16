<?php

require 'controller.php';

require 'phpqrcode/qrlib.php';

use OSS\OssClient;
use OSS\Core\OssException;

class Recommend extends Controller
{
    protected $image_server, $server;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);

        $this->image_server = $this->app->get('settings')['default']['image_server'];
        $this->server       = $this->app->get('settings')['default']['server'];
    }

    public function share()
    {
        $is_weixin    = $this->app->tool->is_weixin();

        $is_weixin    = true;

        $codeContents = $this->server . 'recommend/share/' . $this->args['uuid'] . '.html?utm_source=' . $this->args['uuid'];

        $QR           = 'dist/share/'. $this->args['uuid'] . '.png';
        $filepath     = 'share/' . date("Y", time()) . '/' . date("m", time()) . '/' . date("d", time()) . '/' . $this->args['uuid'] . '.png';

        QRcode::png($codeContents, $QR, QR_ECLEVEL_L, 10);

        try {
            $ossClient = new OssClient($this->app->get('settings')['oss']['OSS_ACCESS_ID'], $this->app->get('settings')['oss']['OSS_ACCESS_KEY'], $this->app->get('settings')['oss']['OSS_ENDPOINT'], true);
        } catch (OssException $e) {
            printf($e->getMessage());
        }

        try {
            $result = $ossClient->uploadFile($this->app->get('settings')['oss']['OSS_BUCKET'], $filepath, $QR);
            @unlink($QR);
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage());
        }

        $qr = $this->image_server . $filepath;

        $recommend = $this->app->db->get('user', ['uuid', 'nickname', 'telephone', 'image'], ['uuid[=]' => $this->args['uuid']]);
        $default_user_image  = $this->image_server . 'default_user_image.png';

        echo $this->app->template->render('recommend', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'is_weixin' => $is_weixin, 'qr' => $qr, 'recommend' => $recommend, 'default_user_image' => $default_user_image]);
    }

    public function user()
    {
        $users = $this->app->db->select('user', ['nickname', 'telephone', 'image', 'register_time'], ['referee_uuid[=]' => $_SESSION['uuid'], 'ORDER' => ['register_time' => 'DESC']]);

        $default_user_image  = $this->image_server . 'default_user_image.png';

        echo $this->app->template->render('rec_user', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'users' => $users, 'default_user_image' => $default_user_image]);
    }

    public function income()
    {
        $user   = $this->app->db->get('user', ['commission'], ['uuid[=]' => $_SESSION['uuid']]);

        $incomes = $this->app->db->select('user_income', ['id', 'order_id', 'order_uuid', 'order_total', 'amount', 'status', 'source', 'remark', 'create_time', 'modifie_time'], ['uuid[=]' => $_SESSION['uuid']]);

        echo $this->app->template->render('rec_income', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'incomes' => $incomes, 'commission' => $user['commission']]);
    }

    public function take()
    {
        $user   = $this->app->db->get('user', ['commission'], ['uuid[=]' => $_SESSION['uuid']]);

        $commission = $user['commission']; // 当前账户收益

        $comm = intval($commission);

        $take = ($comm - $comm % 100); // 可提现金额

        $take = number_format($take, 2);

        $outs = $this->app->db->select('user_take_out', ['id', 'amount', 'tax', 'total', 'status', 'remark', 'create_time', 'modifie_time'], ['uuid[=]' => $_SESSION['uuid'], 'ORDER' => ['id' => 'DESC']]);
        
        $mode = array('transaction' => '账户余额', 'bank' => '银行');

        $status = array('未审核', '已审核', '已完成');
        // 入账中
        $wait  = $this->app->db->sum("user_take_out", "total", ['status' => [0, 1], 'uuid[=]' => $_SESSION['uuid']]);
        $wait = number_format($wait, 2);

        $scripts = array();
        if ((int)$take) {
            $scripts[] = 'https://res.wx.qq.com/open/libs/weuijs/1.1.1/weui.min.js';
            $scripts[] = 'https://unpkg.com/axios/dist/axios.min.js';
            $scripts[] = $this->server . 'dist/js/' . 'takeout.js';
        }

        echo $this->app->template->render('rec_takeout', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'scripts' => $scripts, 'commission' => $commission, 'take' => $take, 'outs' => $outs, 'mode' => $mode, 'status' => $status, 'wait' => $wait]);
    }

    public function out()
    {
        $body = $this->request->getParsedBody();
        $mode = $body['mode']; // 提现方式

        $json = array();

        $user = $this->app->db->get('user', ['commission'], ['uuid[=]' => $_SESSION['uuid']]);
        $commission = intval($user['commission']);
        $amount     = ($commission - $commission % 100);// 账户可提现金额
        $tax        = 0; // 税
        $total      = $amount - $tax; // 实际到账

        if ($mode == 'transaction') { // 提现到账户余额
            // 插入提现记录
            $this->app->db->insert("user_take_out", [
                        "uuid" => $_SESSION['uuid'],
                      "amount" => $amount,
                         "tax" => $tax,
                       "total" => $total,
                      "status" => 2,
                      "remark" => 'transaction',
                 "create_time" => time(),
                "modifie_time" => time()
            ]);
            // 插入余额记录
            $code = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号

            $this->app->db->insert("user_transaction", [
                        "code" => $code,
                        "uuid" => $_SESSION['uuid'],
                      "amount" => $total, //充值金额
                      "status" => 1,
                      "source" => 5,
                      "remark" => '推广收益提现',
                 "create_time" => time(),
                "modifie_time" => time()
            ]);
            // 更新用户账户余额
            $this->app->db->update("user", [
                "transaction[+]" => $total,
                "commission[-]" => $amount
            ], [
                "uuid[=]" => $_SESSION['uuid']
            ]);
        }

        $json['code'] = 0;

        echo json_encode($json);
    }
    // 计算税
    private function tax($amount = 0)
    {
        // if ($amount <= 800) {
        //     return 0;
        // } elseif() {
            
        // }
    }
    // 提现规则
    public function readme()
    {
        echo $this->app->template->render('rec_readme', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount]);
    }
}
