<?php

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

    $priKey  = file_get_contents('rsa_private_key.pem');

    $res     = openssl_pkey_get_private($priKey);

    $openssl = openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);

    openssl_free_key($res);

    $sign = base64_encode($sign);

    return $sign;
}

$env = new Dotenv\Dotenv(__DIR__ . '/..');
$env->load();

$zhi = "https://openapi.alipay.com/gateway.do";

$data = array(
    'app_id' => getenv('ZHI_APPID'),
    'method' => 'alipay.trade.wap.pay',
    'charset' => 'utf-8',
    'sign_type' => 'RSA2',
    'timestamp' => date("Y-m-d H:i:s", time()),
    'version' => '1.0',
    'notify_url' => 'http://m.outatv.com/order/zhi',
    'biz_content' => array(
        'subject' => '金宁户外运动支付宝测试订单',
        'out_trade_no' => md5(time()),
        'total_amount' => 0.01,
        'product_code' => 'QUICK_WAP_PAY'
    )
)

$sign         = sign($data);
$data['sign'] = $sign;

Requests::register_autoloader();
$response = Requests::post($zhi, array(), $data);

var_dump($response->body);
