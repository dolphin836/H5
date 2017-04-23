<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['template'] = function ($c) {
    $settings = $c->get('settings')['template'];
    // return new Slim\Views\PhpRenderer($settings['template_path']);
    return new League\Plates\Engine($settings['template_path'], 'html');
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// db
$container['db'] = function($c) {
    return new Medoo\Medoo([
        'database_type' => 'mysql',
        'database_name' => 'tan',
        'server' => 'localhost',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
    ]);
};
