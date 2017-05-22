<?php

require 'controller.php';

class Cart extends Controller
{
    protected $image_server, $server, $cart;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);

        $this->image_server = $this->app->get('settings')['default']['image_server'];
        $this->server       = $this->app->get('settings')['default']['server'];
    }

    public function index()
    {
        $products = array();
        $total    = 0;
        // 个性化需求，真人 CS 项目 10 元
        $cs    = array(14, 11);
        $is_cs = true;

        if ( isset($_SESSION['cart']) ) {
            $cart     = $_SESSION['cart'];
            foreach($cart as $key => $c) {

                if (! in_array($c['id'], $cs)) {
                    $is_cs = false;
                }

                $results        = $this->app->db->select('product', ['name', 'image', 'price'], ['id[=]' => $c['id']]);
                $price          = 0;
                $text           = array();
                if ( ! empty($c['option']) ) {
                    foreach ($c['option'] as $v_id) {
                        $o_val = $this->app->db->select('product_option_value', ['description', 'add_price'], ['id[=]' => $v_id]);
                        $price = $price + (int)$o_val[0]['add_price'];
                        $text[]= $o_val[0]['description'];
                    }
                }
                foreach ($results as $result) {
                    $price     += (int)$result['price'];
                    $total     += $price * (int)$c['quantity'];
                    $products[$key] = array(
                        'cover' => $this->image_server . $result['image'],
                        'name' => $result['name'],
                        'text' => $text,
                        'price' => number_format ($price, 2),
                    'quantity' => $c['quantity'],
                        'view' => 'product/view/' . $c['id'] . '.html'
                    );
                }
            }
        }

        $cartCount = 0;
        if ( isset($_SESSION['cartCount']) ) {
            $cartCount = $_SESSION['cartCount'];
        }

        $scripts[] = $this->server . 'dist/js/' . 'zepto.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'cart.js?20170522151600';

        $user        = $this->app->db->get('user', ['transaction'], ['uuid[=]' => $_SESSION['uuid']]);
        $user_transaction = number_format ((float)$user['transaction'], 2);
        $transaction = (float)$user['transaction'];

        $discount  = $total * $this->app->get('settings')['default']['discount'];
        $pay       = $total - $discount;

        if ($transaction >= $pay) {
            $transaction  = $pay;
        }

        $pay       = $pay - $transaction;

        $total     = number_format ($total, 2);
        $discount  = number_format ($discount, 2);
        $pay       = number_format ($pay, 2);

        // if ($is_cs) {
        //     $total     = number_format (10 * $cartCount, 2);
        //     $discount  = number_format (0, 2);
        //     $pay       = $total;
        // }

        $is_weixin = $this->app->tool->is_weixin();


        
        echo $this->app->template->render('cart', ['server' => $this->server, 'item' => 'cart', 'cartCount' => $cartCount, 'scripts' => $scripts, 'products' => $products, 'total' => $total, 'discount' => $discount, 'pay' => $pay, 'is_weixin' => $is_weixin, 'user_transaction' => $user_transaction, 'transaction' => $transaction]);
    }

    public function clean()
    {
        if ( isset($this->args['id']) ) {
            $_SESSION['cartCount'] = $_SESSION['cartCount'] - $_SESSION['cart'][$this->args['id']]['quantity'];
            unset( $_SESSION['cart'][$this->args['id']] );
        } else {
            unset($_SESSION['cart']);
            unset($_SESSION['cartCount']);
        }
    }

    public function add()
    {
        $json = array();
        $data = $this->request->getParsedBody();

        if ( ! isset($data['id']) || ! isset($data['quantity']) ) {
            $json['code'] = 1;
            $json['msg']  = 'Error：Args Miss.';
            $this->response = $this->response->withJson($json);
            echo $this->response;
        }

        $product             = array();
        $product['id']       = (int)$data['id'];
        $product['quantity'] = (int)$data['quantity'];
        $product['option']   = array();

        foreach ($data as $key => $value) {
            if ( $key != 'id' && $key != 'quantity' ) {
                $product['option'][] = $value;
            }
        }

        if ( ! isset($_SESSION['cart']) ) {
            $_SESSION['cart']      = array($product);
            $_SESSION['cartCount'] = $product['quantity'];
        } else {
            $cart = $_SESSION['cart'];
            $key  = -1;
            foreach ($cart as $k => $c) {
                if ( empty($c['option']) ) {
                    $is_option = 1;
                } else {
                    $is_option = 1;
                    foreach ($c['option'] as $o_v_id) {
                        if ( ! in_array($o_v_id, $product['option']) ) {
                            $is_option = 0;
                        }
                    }
                }
                
                if ( ($c['id'] == $product['id']) && $is_option ) {
                    $key = $k;
                }
            }

            if ($key < 0) {
                $_SESSION['cart'][] = $product;
            } else {  
                $_SESSION['cart'][$key]['quantity'] += $product['quantity'];
            }

            $_SESSION['cartCount'] += $product['quantity'];
        }

        $json['code']        = 0;
        $json['msg']         = 'Add Cart Success.';
        $this->response = $this->response->withJson($json);
        echo $this->response;
    }


}
