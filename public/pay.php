<?php

require __DIR__ . '/../vendor/autoload.php';

var_dump("Pay");

$gateway    = Omnipay::create('WechatPay_Js');
$gateway->setAppId('wx3f57772b43b05ba5');
$gateway->setMchId('1460504502');
$gateway->setApiKey('a5xKnFv8n0IacRZlper2fJqQXK62Kq82');

$order = [
    'body'              => 'The test order',
    'out_trade_no'      => date('YmdHis') . mt_rand(1000, 9999),
    'total_fee'         => 1,
    'spbill_create_ip'  => '192.168.1.1',
    'fee_type'          => 'CNY'
];

$request  = $gateway->purchase($order);
$response = $request->send();

var_dump($response->isSuccessful());
var_dump($response->getData());
var_dump($response->getAppOrderData());
var_dump($response->getJsOrderData());
var_dump($response->getCodeUrl());