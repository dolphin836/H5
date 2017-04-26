<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'template' => [
            'template_path' => __DIR__ . '/../template/',
        ],
        // weixin
        'weixin' => [
            'appID' => 'wx3f57772b43b05ba5',
            'appSecret' => '98926008d074d0ead28018fa8c686d32',
            'mch_id' => '1460504502',
            'api_key' => 'a5xKnFv8n0IacRZlper2fJqQXK62Kq82',
            'buck_url' => 'http://mobie.hbdx.cc/'
        ],
        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        // default
        'default' => [
            'server' => 'http://mobie.hbdx.cc/',
            'image_server' => 'http://mobie.hbdx.cc/'
        ],
    ],
];
