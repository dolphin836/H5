<?php

session_cache_expire(1);
session_start();

function checkEmpty($value) 
{
    if (!isset($value)) {
        return true;
    }
    if ($value === null) {
        return true;
    }
    if (trim($value) === "") {
        return true;
    }

    return false;
}

function sign($data = array())
{
    ksort($data);

    $stringToBeSigned = "";

    $i = 0;

    foreach ($data as $k => $v) {
        if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
            if ($i == 0) {
                $stringToBeSigned .= "$k" . "=" . "$v";
            } else {
                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
            }

            $i++;
        }
    }

    unset($k, $v);

    $priKey  = file_get_contents('rsa_private_key.pem');

    $res     = openssl_pkey_get_private($priKey);

    $openssl = openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);

    openssl_free_key($res);

    $sign = base64_encode($sign);

    return $sign;
}

if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false && ! isset($_SESSION['user'])) { //支付宝内置浏览器
    $back = urlencode('http://m.outatv.com');
    $url  = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=2017050207083850&scope=auth_userinfo&redirect_uri=" . $back;
    
    header('Location: ' . $url);
}

if (isset($_GET['auth_code'])) {
    $auth_code = $_GET['auth_code'];

    $server    = "https://openapi.alipay.com/gateway.do?";

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

    $sign         = sign($data);
    $data['sign'] = $sign;

    $data         = http_build_query($data);

    $response     = file_get_contents($server . $data);

    $json         = json_decode($response);

    $_SESSION['user'] = $json->alipay_system_oauth_token_response->user_id;
}


if ( isset($_SESSION['user']) ) {
    echo 'Hello ' . $_SESSION['user'];
} else {
    echo 'Please Login.';
}

?>
