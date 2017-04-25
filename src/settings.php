<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'template' => [
            'template_path' => __DIR__ . '/../template/',
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
