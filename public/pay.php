<?php

var_dump("Pay");

require __DIR__ . '/../vendor/autoload.php';

$server     = "https://api.mch.weixin.qq.com/pay/unifiedorder";
$randstr    = 'sdfew8j2f0g938fk5de825ddfgr2sxz6';
$orderNo    = md5(time());
$ip_address = '192.168.1.1';
$openid     = 'oNP02wK_vjLWB_iRRf6qbqmDXBiE';

$request = array(
    'appid' => 'wx3f57772b43b05ba5',
    'mch_id' => '1460504502',
    'device_info' => 'WEB',
    'nonce_str' => $randstr,
    'body' => '金宁文化旅游股份有限公司-支付测试',
    'out_trade_no' => $orderNo,
    'total_fee' => 1,
    'spbill_create_ip' => $ip_address,
    'notify_url' => 'http://mobie.hbdx.cc',
    'trade_type' => 'JSAPI',
    'openid' => $openid
);

var_dump($request);

$key = "a5xKnFv8n0IacRZlper2fJqQXK62Kq82";

ksort($request);

$str = urldecode(http_build_query($request));

$strTemp = $str . "&key=" . $key;

$sign = strtoupper(md5($strTemp));

var_dump($sign);

$xml = "<xml>
<appid>wxeb58e2715cd8a221</appid>
<mch_id>1276370801</mch_id>
<device_info>WEB</device_info>
<nonce_str>{$randstr}</nonce_str>
<body>南京商法通法律咨询服务有限公司-支付测试</body>
<out_trade_no>{$orderNo}</out_trade_no>
<total_fee>1</total_fee>
<spbill_create_ip>{$ip_address}</spbill_create_ip>
<notify_url>http://www.blb.com.cn/pay/callback.php</notify_url>
<trade_type>JSAPI</trade_type>
<openid>{$openid}</openid>
<sign>{$sign}</sign>
</xml>";

$client = new \GuzzleHttp\Client();
$res    = $client->request('POST', $server, ['body' => $xml]);
echo $res->getStatusCode();
// 200
echo $res->getHeaderLine('content-type');
// 'application/json; charset=utf8'
echo $res->getBody();
// '{"id": 1420053, "name": "guzzle", ...}'