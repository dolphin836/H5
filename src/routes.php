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

$app->post('/addOrder', function($request, $response, $args) {
    // file_get_contents('php://input')
    
    // $server     = "https://api.mch.weixin.qq.com/pay/unifiedorder";
    // $randstr    = 'sdfew8j2f0g938fk5de825ddfgr2sxz6';
    // $orderNo    = md5(time());
    // $ip_address = '192.168.1.1';
    // $openid     = $_SESSION['uuid'];

    // $request = array(
    //     'appid' => 'wx3f57772b43b05ba5',
    //     'mch_id' => '1460504502',
    //     'device_info' => 'WEB',
    //     'nonce_str' => $randstr,
    //     'body' => '金宁文化旅游股份有限公司-支付测试',
    //     'out_trade_no' => $orderNo,
    //     'total_fee' => 1,
    //     'spbill_create_ip' => $ip_address,
    //     'notify_url' => 'http://mobie.hbdx.cc',
    //     'trade_type' => 'JSAPI',
    //     'openid' => $openid
    // );

    // $key = "a5xKnFv8n0IacRZlper2fJqQXK62Kq82";

    // ksort($request);

    // $str = urldecode(http_build_query($request));

    // $strTemp = $str . "&key=" . $key;

    // $sign = strtoupper(md5($strTemp));

    // $xml = "<xml>
    // <appid>wxeb58e2715cd8a221</appid>
    // <mch_id>1276370801</mch_id>
    // <device_info>WEB</device_info>
    // <nonce_str>{$randstr}</nonce_str>
    // <body>南京商法通法律咨询服务有限公司-支付测试</body>
    // <out_trade_no>{$orderNo}</out_trade_no>
    // <total_fee>1</total_fee>
    // <spbill_create_ip>{$ip_address}</spbill_create_ip>
    // <notify_url>http://www.blb.com.cn/pay/callback.php</notify_url>
    // <trade_type>JSAPI</trade_type>
    // <openid>{$openid}</openid>
    // <sign>{$sign}</sign>
    // </xml>";


});

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