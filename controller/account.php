<?php

require 'controller.php';

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
    }

}
