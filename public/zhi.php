<?php

header("Content-type: text/html; charset=utf-8"); 
// var_dump($_SERVER);
require __DIR__ . '/../vendor/autoload.php';

Requests::register_autoloader();

if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false && $_SERVER['QUERY_STRING'] == '' ) { // 支付宝浏览器
        $back    = urlencode("http://m.outatv.com/zhi.php");

        $uri     = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=2017050207083850&scope=auth_userinfo&redirect_uri=" . $back;
        header('Location: ' . $uri); 
}

var_dump($_SERVER['QUERY_STRING']);

$query      = explode('&', $_SERVER['QUERY_STRING']);

var_dump($query);

if (!empty($query) ) {
    foreach ($query as $q) {
        $str    = explode('=', $q);
        var_dump($str);

        if($str[0]  == 'auth_code') {
            $auth_code = $str[1];
        }
    }

    if ( isset($auth_code) ) {
        var_dump($auth_code);
        $zhi       = "https://openapi.alipay.com/gateway.do";

        $data = array('grant_type' => 'authorization_code', 'code' => $auth_code);
        $response = Requests::post($zhi, array(), $data);
        var_dump($response->status_code);
    }
}

var_dump(11111);

