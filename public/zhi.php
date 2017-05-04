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

        $data = array(
            'app_id' => '2017050207083850',
            'method' => 'alipay.system.oauth.token',
            'charset' => 'GBK',
            'sign_type' => 'RSA2',
            'timestamp' => date("Y-m-d H:i:s", time()),
            'version' => '1.0',
            'grant_type' => 'authorization_code', 
            'code' => $auth_code
        );

        var_dump($data);

        $priKey = file_get_contents('rsa_private_key.pem');
        var_dump($priKey);
        $res    = openssl_pkey_get_private($priKey);
        var_dump($res);
        
        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        
        openssl_free_key($res);
        
        $sign = base64_encode($sign);

        var_dump($sign);
        $data['sign'] = $sign;

        $response = Requests::post($zhi, array(), $data);
        var_dump($response->body);
    }
}


var_dump(11111);

