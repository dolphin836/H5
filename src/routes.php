<?php

$routes = array(
    '', 'index.html', 'cart.html', 'cart/add', 'cart/clean[/{id}]', 'account.html', 'checkout.html', 'product/view/{id:[0-9]+}.html', 'account/login.html', 'account/logout.html'
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

    if ( ! isset($_SESSION['cart']) ) {
        $json['code']        = 2;
        $json['msg']         = 'The Cart Is Empty.';
        echo json_encode($json);
        exit;     
    }

    $cart     = $_SESSION['cart'];
    $total    = 0;
    foreach($cart as $c) {
        $results        = $this->db->select('product', ['name', 'image', 'price'], ['id[=]' => $c['id']]);
        $price          = 0;
        if ( ! empty($c['option']) ) {
            foreach ($c['option'] as $v_id) {
                $o_val = $this->db->select('product_option_value', ['description', 'add_price'], ['id[=]' => $v_id]);
                $price = $price + (int)$o_val[0]['add_price'];
            }
        }
        foreach ($results as $result) {
            $price     += (int)$result['price'];
            $total     += $price;
        }
    }

    $discount  = $total * 0.1;
    $pay       = $total - $discount;
    // $pay_fen   = $pay * 100;
    $pay_fen   = 1;

    $code      = randstr(32); //生成订单号

    $order_id = $this->db->insert("order", [
                "code" => $code,
                "uuid" => $_SESSION['uuid'],
               "total" => $pay, //实际支付金额
           "sub_total" => $total, //订单小计
           "red_total" => $discount, //优惠的金额
        "payment_code" => "weixin_js",
         "create_time" => time(),
        "modifie_time" => time(),
          "payed_time" => time()
    ]);

    $server     = "https://api.mch.weixin.qq.com/pay/unifiedorder";
    $randstr    = randstr(32);
    $this->logger->addInfo($randstr);

    $ip_address = '';
    $serverParams = $request->getServerParams(); // 获取客户端 IP adress

    if (isset($serverParams['REMOTE_ADDR'])) {
        $ip_address = $serverParams['REMOTE_ADDR'];
    }

    $openid     = $_SESSION['uuid'];
    $this->logger->addInfo($openid);
    $body       = '金宁户外运动';

    $order = array(
        'appid' => $this->get('settings')['weixin']['appID'],
        'mch_id' => $this->get('settings')['weixin']['mch_id'],
        'device_info' => 'WEB',
        'nonce_str' => $randstr,
        'body' => $body,
        'out_trade_no' => $order_id,
        'total_fee' => $pay_fen,
        'spbill_create_ip' => $ip_address,
        'notify_url' => $this->get('settings')['weixin']['buck_url'],
        'trade_type' => 'JSAPI',
        'openid' => $openid
    );

    $sign = sign($order, $this->get('settings')['weixin']['api_key']);
    $this->logger->addInfo($sign);

    $xml = "<xml>
    <appid>{$this->get('settings')['weixin']['appID']}</appid>
    <mch_id>{$this->get('settings')['weixin']['mch_id']}</mch_id>
    <device_info>WEB</device_info>
    <nonce_str>{$randstr}</nonce_str>
    <body>{$body}</body>
    <out_trade_no>{$order_id}</out_trade_no>
    <total_fee>{$pay_fen}</total_fee>
    <spbill_create_ip>{$ip_address}</spbill_create_ip>
    <notify_url>{$this->get('settings')['weixin']['buck_url']}</notify_url>
    <trade_type>JSAPI</trade_type>
    <openid>{$openid}</openid>
    <sign>{$sign}</sign>
    </xml>";

    Requests::register_autoloader();

    $req = Requests::post($server, array(), $xml);
    $this->logger->addInfo($req->status_code);
    $this->logger->addInfo($req->body);

    if ($req->status_code != 200) {
        $json['code']        = 1;
        $json['msg']         = 'Requests Fail.';
        echo json_encode($json);
        exit;
    }

    $reader = new Sabre\Xml\Reader();
    $reader->xml($req->body);
    $result = $reader->parse();
    $this->logger->addInfo('xml result', $result['value']);

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

    echo json_encode($json);
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