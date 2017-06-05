<?php

require 'controller.php';
//市场推广
class Market extends Controller
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
        $user   = $this->app->db->get('user', ['referee_uuid'], ['uuid[=]' => $_SESSION['uuid']]);
        
        if ($user['referee_uuid'] == 'SnfNsBz0YWl5oV6Op4DvmZeXMktyxC9d') { // 4S 店推广活动，送卡丁车 1 圈
            $ticket    = $this->app->db->get('ticket', ['code'], ['uuid[=]' => $_SESSION['uuid'], 'product_id' => 1, 'product_price' => 40.00]);
            $style  = false;
            if ( ! $ticket) {
                $style  = true;
                // 订单
                $code      = $this->microtime_float() . $this->GeraHash(14, true); //生成订单号

                $this->app->db->insert("order", [
                            "code" => $code,
                            "uuid" => $_SESSION['uuid'],
                           "total" => 0,
                       "sub_total" => 40.00, //订单小计
                       "red_total" => 40.00, //优惠的金额
                    "payment_code" => "market",
                          "status" => 1,
                     "create_time" => time(),
                    "modifie_time" => time(),
                      "payed_time" => time()
                ]);

                $order_id = $this->app->db->id();

                $this->app->db->insert("order_product", [
                         "order_id" => $order_id,
                       "product_id" => 1,
                     "product_name" => '卡丁车(单人)-1圈',
                    "product_price" => 40.00,
                    "product_count" => 1
                ]);
                // 票码
                $tode      = $this->microtime_float() . $this->GeraHash(14, true); //生成编码
                $this->app->db->insert("ticket", [
                                 "code" => $tode,
                                 "uuid" => $_SESSION['uuid'],
                             "order_id" => $order_id,
                           "product_id" => 1,
                         "product_name" => '卡丁车(单人)-1圈',
                        "product_price" => 40.00,
                          "create_time" => time(),
                         "modifie_time" => time()
                ]);

                //更新销售量
                $this->app->db->update("product", [
                        "saled[+]" => 1,
                    "show_saled[+]" => rand(10, 50)
                ], [
                    "id[=]" => 1
                ]);
            }
        } else {
            exit('非法访问');
        }

        echo $this->app->template->render('market', ['server' => $this->server, 'item' => 'account', 'cartCount' => $this->cartCount, 'style' => $style]);
    }
}
