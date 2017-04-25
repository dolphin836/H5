<?php

var_dump("Pay");

require __DIR__ . '/../vendor/autoload.php';

$json    = array();

Requests::register_autoloader();

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
    'body' => '支付测试',
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
<appid>wx3f57772b43b05ba5</appid>
<mch_id>1460504502</mch_id>
<device_info>WEB</device_info>
<nonce_str>{$randstr}</nonce_str>
<body>支付测试</body>
<out_trade_no>{$orderNo}</out_trade_no>
<total_fee>1</total_fee>
<spbill_create_ip>{$ip_address}</spbill_create_ip>
<notify_url>http://mobie.hbdx.cc</notify_url>
<trade_type>JSAPI</trade_type>
<openid>{$openid}</openid>
<sign>{$sign}</sign>
</xml>";

var_dump($xml);

$request = Requests::post($server, array(), $xml);

if ($request->status_code != 200) {
    $json['code'] = 2;
    $json['msg']  = '请求预支付订单失败';

    echo json_encode($json);
}

$reader = new Sabre\Xml\Reader();
$reader->xml($request->body);
$result = $reader->parse();

$prepay = "prepay_id=";

foreach ($result['value'] as $key => $value) {
    if ($value['name'] == '{}prepay_id') {
        $prepay .= $value['value'];
    }
}

$data = array(
    'appId' => 'wx3f57772b43b05ba5',
    'timeStamp' => time(),
    'nonceStr' => $randstr,
    'package' => $prepay,
    'signType' => 'MD5');

ksort($data);

$str2 = urldecode(http_build_query($data));

$strTemp = $str2 . "&key=" . $key;

$sign2 = strtoupper(md5($strTemp));

$data['paySign'] = $sign2;

$json['code'] = 0;
$json['msg']  = '请求预支付交易单成功';
$json['data'] = $data;

echo json_encode($json);
