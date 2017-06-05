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
}
