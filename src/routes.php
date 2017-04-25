<?php

$routes = array(
    '', 'index.html', 'cart.html', 'cart/clean[/{id}]', 'account.html', 'checkout.html', 'product/view/{id:[0-9]+}.html', 'account/login.html', 'account/logout.html'
);

foreach ($routes as $route) {
    $app->get('/' . $route, function ($request, $response, $args) {
        $params = explode('.', $request->getUri()->getPath());
        $path   = explode('/', $params[0]);

        $c      = isset($path[1]) ? $path[1] : 'product';
        $m      = isset($path[2]) ? $path[2] : 'index';
        $c      = $c != 'index'   ? $c       : 'product'; // index.html
        $c      = $c != ''        ? $c       : 'product'; // defalut

        $class_file = __DIR__ . '/../controller/' . $c . '.php';

        if (file_exists($class_file)) {
            require_once $class_file;
        }

        $class = ucwords($c);

        $i = new $class($request, $response, $this, $args);

        $i->$m();
    });
}

$app->post('/addCart', function($request, $response, $args) {
    $json = array();
    $data = $request->getParsedBody();

    if ( ! isset($data['id']) || ! isset($data['quantity']) ) {
        $json['code'] = 1;
        $json['msg']  = 'Error：Args Miss.';
        $response = $response->withJson($json);
        echo $response;
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
    $response = $response->withJson($json);
    echo $response;
});