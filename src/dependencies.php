<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['template'] = function ($c) {
    $settings = $c->get('settings')['template'];
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
    $settings = $c->get('settings')['database'];
    return new Medoo\Medoo([
        'database_type' => 'mysql',
        'database_name' => $settings['name'],
               'server' => $settings['server'],
             'username' => $settings['username'],
             'password' => $settings['password'],
              'charset' => 'utf8',
    ]);
};

// csrf
$container['csrf'] = function ($c) {
    return new \Slim\Csrf\Guard('dolphin');
};

// tool
$container['tool'] = function ($c) {

    class Tool {
        public function sign()
        {
            return 'xxxxx';
        }
    }

    $tool = new Tool();
    return $tool;
};

