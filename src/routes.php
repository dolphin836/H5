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

    function sign($data = array(), $key)
    {
        ksort($data);
        $str = urldecode(http_build_query($data));
        $strTemp = $str . "&key=" . $key;
        return strtoupper(md5($strTemp));
    }

    function randstr($length = 8)
    {
        $base = "QWERTYUIPASDFGHJKLZXCVBNM123456789";
        $max = strlen($base) - 1;
        $encrypt_key = '';

        while (strlen($encrypt_key) < $length) {
            $encrypt_key .= $base{mt_rand(0, $max)};
        }
        
        return $encrypt_key;
    } 

    $json       = array();

    $server     = "https://api.mch.weixin.qq.com/pay/unifiedorder";
    $randstr    = randstr(32);
    $orderNo    = md5(time());
    $ip_address = '192.168.1.1';
    // $openid     = $_SESSION['uuid'];
    $openid     = '111';

    $request = array(
        'appid' => $this->get('settings')['weixin']['appID'],
        'mch_id' => $this->get('settings')['weixin']['mch_id'],
        'device_info' => 'WEB',
        'nonce_str' => $randstr,
        'body' => '支付测试',
        'out_trade_no' => $orderNo,
        'total_fee' => 1,
        'spbill_create_ip' => $ip_address,
        'notify_url' => $this->get('settings')['weixin']['buck_url'],
        'trade_type' => 'JSAPI',
        'openid' => $openid
    );

    $sign = sign($request, $this->get('settings')['weixin']['api_key']);

    $xml = "<xml>
<appid>{$this->get('settings')['weixin']['appID']}</appid>
<mch_id>{$this->get('settings')['weixin']['mch_id']}</mch_id>
<device_info>WEB</device_info>
<nonce_str>{$randstr}</nonce_str>
<body>支付测试</body>
<out_trade_no>{$orderNo}</out_trade_no>
<total_fee>1</total_fee>
<spbill_create_ip>{$ip_address}</spbill_create_ip>
<notify_url>{$this->get('settings')['weixin']['buck_url']}</notify_url>
<trade_type>JSAPI</trade_type>
<openid>{$openid}</openid>
<sign>{$sign}</sign>
</xml>";

    Requests::register_autoloader();

    $req = Requests::post($server, array(), $xml);

    if ($req->status_code != 200) {
        $json['code']        = 1;
        $json['msg']         = 'Requests Fail.';
        $response = $response->withJson($json);
        echo $response;
        exit;
    }

    $reader = new Sabre\Xml\Reader();
    $reader->xml($req->body);
    $result = $reader->parse();

    $prepay = "prepay_id=";

    foreach ($result['value'] as $key => $value) {
        if ($value['name'] == '{}prepay_id') {
            $prepay .= $value['value'];
        }
    }

    $data = array(
            'appId' => $this->get('settings')['weixin']['appID'],
        'timeStamp' => time(),
            'nonceStr' => randstr(32),
            'package' => $prepay,
            'signType' => 'MD5'
    ); 

    $sign2           = sign($data, $this->get('settings')['weixin']['api_key']);

    $data['paySign'] = $sign2;

    $json['code']    = 0;
    $json['msg']     = 'Requests Success.';
    $json['data']    = $data;
    $response = $response->withJson($json);
    echo $response;
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