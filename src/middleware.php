<?php

$app->add(function ($request, $response, $next) {

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



    $httpQuery      = $request->getUri()->getQuery(); // 获取微信的 code 或者推荐人的 code，做相应的处理
    var_dump($httpQuery);
    if ($httpQuery != '') {
        $query      = explode('&', $httpQuery);

        foreach ($query as $q) {
            $str    = explode('=', $q);

            if ($str[0]  == 'code' && ! isset($_SESSION['uuid']) ) { // 检测到微信网页授权的 code 参数并且未登录 
                $weixin     = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->get('settings')['weixin']['appID'] . "&secret=" . $this->get('settings')['weixin']['appSecret'] . "&code=" . $str[1] . "&grant_type=authorization_code"; 
                $data       = file_get_contents($weixin);
                $data       = json_decode($data);
                $access_token = $data->access_token;
                $open_id    = $data->openid;
                $user       = $this->db->select('user', ['uuid'], ['openid[=]' => $open_id]);
                
                if ( empty($user) ) { // 注册新用户
                    $weixin2    = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $open_id . "&lang=zh_CN";
                    $userinfo   = file_get_contents($weixin2);
                    $userinfo   = json_decode($userinfo);
                    $nickname   = $userinfo->nickname;
                    $headimgurl = $userinfo->headimgurl;

                    $password    = "12345678";
                    $en_password = password_hash($password, PASSWORD_DEFAULT);

                    $user_id = $this->db->insert("user", [
                                 "uuid" => $open_id,
                             "nickname" => $nickname,
                               "openid" => $open_id,
                          'en_password' => $en_password,
                             'password' => $password,
                                "image" => $headimgurl,
                                 "type" => 1,
                               "source" => 1,
                        "register_time" => time(),
                           "login_time" => time()
                    ]);
                }

                $_SESSION['uuid'] = $open_id;
            }

            if ($str[0]  == 'auth_code' && ! isset($_SESSION['uuid']) ) { // 检测到支付宝网页授权的 auth_code
                $auth_code = $str[1];
                var_dump($auth_code);
                $zhi       = "https://openapi.alipay.com/gateway.do?";

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
                var_dump($sign);
                $data['sign'] = $sign;

                $data         = http_build_query($data);

                $response     = file_get_contents($zhi . $data);

                var_dump($response);

                // $this->logger->addInfo("POST RETURN:" . $response);

                $json = json_decode($response);

                $access_token = $json->alipay_system_oauth_token_response->access_token;

                $user_id      = $json->alipay_system_oauth_token_response->user_id;

                // $user         = $this->db->select('user', ['id'], ['uuid[=]' => $user_id]);

                // if (empty($user)) { // 注册新用户
                //     $data = array(
                //             'app_id' => '2017050207083850',
                //             'method' => 'alipay.user.userinfo.share',
                //            'charset' => 'GBK',
                //          'sign_type' => 'RSA2',
                //          'timestamp' => date("Y-m-d H:i:s", time()),
                //            'version' => '1.0',
                //         'auth_token' => $access_token
                //     );

                //     $sign         = sign($data);
                //     $data['sign'] = $sign;

                //     $data         = http_build_query($data);

                //     $response     = file_get_contents($zhi . $data);

                //     // $this->logger->addInfo("POST RETURN:" . $response);

                //     $userinfo    = iconv('GBK', 'UTF-8', $response);

                //     $json        = json_decode($userinfo);
        
                //     $headimgurl  = $json->alipay_user_userinfo_share_response->avatar;
                //     $nick_name   = $json->alipay_user_userinfo_share_response->nick_name;
                //     $password    = "12345678";
                //     $en_password = password_hash($password, PASSWORD_DEFAULT);

                //     $query = $this->db->insert("user", [
                //                  "uuid" => $user_id,
                //              "nickname" => $nick_name,
                //           'en_password' => $en_password,
                //              'password' => $password,
                //                 "image" => $headimgurl,
                //                  "type" => 1,
                //                "source" => 2,
                //         "register_time" => time(),
                //            "login_time" => time()
                //     ]);
                // }

                $_SESSION['uuid'] = $user_id;
            }
        }
    }
    
    $headers   = $request->getHeader('HTTP_USER_AGENT'); // 根据 User Agent 识别微信内置浏览器，做身份验证
    $userAgent = $headers[0];

    if ( strpos($userAgent, 'MicroMessenger') !== false ) { // 微信浏览器
        if ( ! isset($_SESSION['uuid']) ) {
            $host = $request->getUri()->getHost();
            $path = $request->getUri()->getPath();
            $back = urlencode('http://' . $host . $path);
            $url  = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->get('settings')['weixin']['appID'] . "&redirect_uri=" . $back . "&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
            $newResponse = $response->withHeader('Location', $url);

            return $newResponse;
        }
    }

    if ( strpos($userAgent, 'AlipayClient') !== false ) { // 支付宝浏览器
        if ( ! isset($_SESSION['uuid']) ) {
            // $host = $request->getUri()->getHost();
            // $path = $request->getUri()->getPath();
            // $back = urlencode('http://' . $host . $path);
             $back = urlencode('http://m.outatv.com/test');
            // $url  = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=" . $this->get('settings')['zhi']['appID'] . "&scope=auth_userinfo&redirect_uri=" . $back;
            $url  = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=2017050207083850&scope=auth_userinfo&redirect_uri=" . $back;
            
            // $this->logger->addInfo("URL:" . $url);
            
            $newResponse = $response->withHeader('Location', $url);

            return $newResponse;
        }
    }

    $response = $next($request, $response);

    return $response;
});
