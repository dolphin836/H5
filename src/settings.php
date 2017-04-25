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
            'appSecret' => '98926008d074d0ead28018fa8c686d32'
        ],
        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        // default
        'default' => [
            'server' => 'http://h5.dolphin.com/',
            'image_server' => 'http://h5.dolphin.com/'
        ],
    ],
];
