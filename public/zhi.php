<?php

require __DIR__ . '/../vendor/autoload.php';

Requests::register_autoloader();

if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false ) { // 支付宝浏览器
        $request = $_SERVER['REQUEST_SCHEME'];
        $name    = $_SERVER['SERVER_NAME'];
        $url     = $_SERVER['REQUEST_URI'];
        $back    = urlencode($request . '://' . $name . $url);

        $uri     = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=2017050207083850&scope=auth_userinfo&redirect_uri=" . $back;
        header('Location: ' . $uri); 
}

var_dump($_SERVER['QUERY_STRING']);

$query      = explode('&', $_SERVER['QUERY_STRING']);

if (empty($query) ) {
    foreach ($query as $q) {
        $str    = explode('=', $q);

        if($str[0]  == 'auth_code') {
            $auth_code = $str[1];
        }
    }

    if ( isset($auth_code) ) {
        $zhi       = "https://openapi.alipay.com/gateway.do";

        $data = array('key1' => 'value1', 'key2' => 'value2');
        $response = Requests::post($zhi, array(), $data);
        var_dump($response->body);
    }
}

var_dump(11111);

