<?php

require_once 'mns-autoloader.php';

use AliyunMNS\Client;
use AliyunMNS\Topic;
use AliyunMNS\Constants;
use AliyunMNS\Model\MailAttributes;
use AliyunMNS\Model\SmsAttributes;
use AliyunMNS\Model\BatchSmsAttributes;
use AliyunMNS\Model\MessageAttributes;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Requests\PublishMessageRequest;

class Sms
{
    // 发送验证码
    public static function code($phone = '', $code = '')
    {
        if ($phone == '' || $code == '') {
            return false;
        }

        $endPoint           = "http://1252091959816281.mns.cn-hangzhou.aliyuncs.com";
        $accessId           = getenv('OSS_ACCESS_ID');
        $accessKey          = getenv('OSS_ACCESS_KEY');
        $client             = new Client($endPoint, $accessId, $accessKey);
        $topicName          = "sms.topic-cn-hangzhou";
        $topic              = $client->getTopicRef($topicName);
        $batchSmsAttributes = new BatchSmsAttributes("金宁户外运动", "SMS_63750898");

        $batchSmsAttributes->addReceiver($phone, array('code' => $code, 'product' => '手机号码，属于'));
        $messageAttributes  = new MessageAttributes(array($batchSmsAttributes));

        $messageBody        = "smsmessage";
        $request            = new PublishMessageRequest($messageBody, $messageAttributes);

        try
        {
            $res = $topic->publishMessage($request);
            return $res->isSucceed();
        }
        catch (MnsException $e)
        {
            return $e;
        }
    }
}