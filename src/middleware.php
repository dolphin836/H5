<?php

$app->add(function ($request, $response, $next) {
    $httpQuery      = $request->getUri()->getQuery(); // 获取微信的 code 或者推荐人的 code，做相应的处理
    
    if ($httpQuery != '') {
        $query      = explode('&', $httpQuery);

        foreach ($query as $q) {
            $str    = explode('=', $q);
            if ($str[0]  == 'code' && ! isset($_SESSION['uuid']) ) { // 检测到 code 参数并且未登录 
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
                        "register_time" => time(),
                           "login_time" => time()
                    ]);
                }

                $_SESSION['uuid'] = $data->openid;
            }
        }
    }
    
    $headers   = $request->getHeader('HTTP_USER_AGENT'); // 根据 User Agent 识别微信内置浏览器，做身份验证
    $userAgent = $headers[0];

    $this->logger->addInfo($userAgent);

    if ( strpos($userAgent, 'MicroMessenger') !== false ) {
        $this->logger->addInfo('is weixin');
    }
    
    $serverParams = $request->getServerParams(); // 获取客户端 IP adress

    if (isset($serverParams['REMOTE_ADDR'])) {
        $ipAddress = $serverParams['REMOTE_ADDR'];
        $this->logger->addInfo("IP address is " . $ipAddress);
    }
    
    $response = $next($request, $response);

    return $response;
});
