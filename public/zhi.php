<?php

var_dump($_SERVER);

if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false && $_SERVER['QUERY_STRING'] == '' ) { // 支付宝浏览器
        $back    = urlencode("http://m.outatv.com/zhi.php");

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
        var_dump($auth_code);
    }
}

var_dump(11111);

