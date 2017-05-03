<?php
return [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
        // Renderer settings
        'template' => [
            'template_path' => __DIR__ . '/../template/'
        ],
        // weixin
        'weixin' => [
                     'appID' => getenv('WEIXIN_APPID'),
                 'appSecret' => getenv('WEIXIN_SECRET'),
                    'mch_id' => getenv('WEIXIN_MCHID'),
                   'api_key' => getenv('WEIXIN_APIKEY'),
                  'buck_url' => getenv('WEIXIN_BACK'),
                     'token' => getenv('WEIXIN_TOKEN'),
            'encodingaeskey' => getenv('WEIXIN_ENCODE')
        ],
        // Monolog settings
        'logger' => [
             'name' => 'slim-app',
             'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG
        ],
        // default
        'default' => [
                  'server' => getenv('DOMAIN'),
            'image_server' => getenv('IMG_DOMAIN'),
                'discount' => 0
        ],
        // db
        'database' => [
              'server' => getenv('DB_SERVER'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
                'name' => getenv('DB_NAME')
        ],
    ],
];
