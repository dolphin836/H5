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
        if ( isset($_SESSION['cart']) ) {
            $cart     = $_SESSION['cart'];
            foreach($cart as $c) {
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
                    $total     += $price;
                    $products[] = array(
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
        $scripts[] = $this->server . 'dist/js/' . 'cart.js';

        $discount  = $total * 0.1;
        $pay       = $total - $discount;

        $total     = number_format ($total, 2);
        $discount  = number_format ($discount, 2);
        $pay       = number_format ($pay, 2);

        echo $this->app->template->render('cart', ['server' => $this->server, 'cartCount' => $cartCount, 'scripts' => $scripts, 'products' => $products, 'total' => $total, 'discount' => $discount, 'pay' => $pay]);
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


}
