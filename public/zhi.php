<?php

$str = '{"alipay_user_userinfo_share_response":{"user_type_value":"2","is_licence_auth":"F","is_certified":"T","is_certify_grade_a":"T","avatar":"https:\/\/tfs.alipayobjects.com\/images\/partner\/T1uJpXXoFaXXXXXXXX","city":"�Ϸ���","is_student_certified":"F","is_bank_auth":"T","is_mobile_auth":"T","nick_name":"������","alipay_user_id":"2088002116338312","user_id":"20880053158242791496244683117231","province":"����ʡ","user_status":"T","gender":"m","is_id_auth":"T"},"sign":"aue3cXvfxDbyrwunytuwcYjVJfwuUd1Gwa6026BJTIwF9BGaU7A0fIA/Qib8jZ2J44R/Ay7S9Jc5L5q2eN1xtfjPxvPDrcshZoDBDpxDSildDc7zmiRyEoSXmDSkSmnoohDRtuiPJnQ+fT8tr7sVEmkQlPa3VEZCDIyrWrJor7acwjdNQk/H4MOzK8wzudVM+G4Fy7RkgXBqGKVA3QduvnXGZxniKIAkiIh5DFGG1Ih4zHZkLgiKia+kAWZedyZEuHNf3VxW1uHYneFU0UlLFJT5p0SSlLrxwumYNW52HLjnO4evSFOUV9+l5zWIAGGSpIu224XPqFumwUhqtTNF1g=="}';

$json = json_decode($str);

var_dump($json);

exit;

header("Content-type: text/html; charset=utf-8"); 
// var_dump($_SERVER);
require __DIR__ . '/../vendor/autoload.php';

function checkEmpty($value) 
{
    if (!isset($value))
        return true;
    if ($value === null)
        return true;
    if (trim($value) === "")
        return true;

    return false;
}

function sign($data = array())
{
    ksort($data);

    $stringToBeSigned = "";

    $i = 0;

    foreach ($data as $k => $v) {
        if (false === checkEmpty($v) && "@" != substr($v, 0, 1)) {
            if ($i == 0) {
                $stringToBeSigned .= "$k" . "=" . "$v";
            } else {
                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
            }

            $i++;
        }
    }

    unset($k, $v);

    $priKey = file_get_contents('rsa_private_key.pem');

    $res    = openssl_pkey_get_private($priKey);

    $openssl = openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);

    openssl_free_key($res);

    $sign = base64_encode($sign);

    return $sign;
}

Requests::register_autoloader();

if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false && $_SERVER['QUERY_STRING'] == '' ) { // 支付宝浏览器
        $back    = urlencode("http://m.outatv.com/zhi.php");

        $uri     = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=2017050207083850&scope=auth_userinfo&redirect_uri=" . $back;
        header('Location: ' . $uri); 
}

$query      = explode('&', $_SERVER['QUERY_STRING']);

if (!empty($query) ) {
    foreach ($query as $q) {
        $str    = explode('=', $q);

        if($str[0]  == 'auth_code') {
            $auth_code = $str[1];
        }
    }

    if ( isset($auth_code) ) {

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

        $sign        = sign($data);
        $data['sign'] = $sign;

        $response = Requests::post($zhi, array(), $data);

        if ($response->status_code != 200) {
            var_dump("Request Error.");
        }

        $json = json_decode($response->body);

        $access_token = $json->alipay_system_oauth_token_response->access_token;

        $data2 = array(
            'app_id' => '2017050207083850',
            'method' => 'alipay.user.userinfo.share',
            'charset' => 'GBK',
            'sign_type' => 'RSA2',
            'timestamp' => date("Y-m-d H:i:s", time()),
            'version' => '1.0',
            'auth_token' => $access_token
        );

        $sign        = sign($data2);
        $data2['sign'] = $sign;

        $response2 = Requests::post($zhi, array(), $data2);

        if ($response2->status_code != 200) {
            var_dump("Request Error.");
        }

        var_dump($response2->body);

        $json2 = json_decode($response2->body);

        var_dump($json2);

        $image     = $json2->alipay_user_userinfo_share_response->avatar;
        $user_id   = $json2->alipay_user_userinfo_share_response->alipay_user_id;
        $nick_name = $json2->alipay_user_userinfo_share_response->nick_name;

        echo "<img src='" . $image . "'>";
        echo "User ID : " . $user_id;
        
    }
}

