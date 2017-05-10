<?php
// DIC configuration

$container = $app->getContainer();

// // view renderer
// $container['template'] = function ($c) {
//     $settings = $c->get('settings')['template'];
//     return new League\Plates\Engine($settings['template_path'], 'html');
// };

// // monolog
// $container['logger'] = function ($c) {
//     $settings = $c->get('settings')['logger'];
//     $logger = new Monolog\Logger($settings['name']);
//     $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//     $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
//     return $logger;
// };

// // db
// $container['db'] = function($c) {
//     $settings = $c->get('settings')['database'];
//     return new Medoo\Medoo([
//         'database_type' => 'mysql',
//         'database_name' => $settings['name'],
//                'server' => $settings['server'],
//              'username' => $settings['username'],
//              'password' => $settings['password'],
//               'charset' => 'utf8',
//     ]);
// };

// csrf
// $container['csrf'] = function ($c) {
//     return new \Slim\Csrf\Guard('dolphin');
// };

// tool
// $container['tool'] = function ($c) {

//     class Tool {
//         protected $c;

//         function __construct($c)
//         {
//             $this->c = $c;
//         }
        
//         public function is_weixin()
//         {
//             $headers   = $this->c->get('request')->getHeader('HTTP_USER_AGENT');

//             $userAgent = $headers[0];
//             if ( strpos($userAgent, 'MicroMessenger') !== false ) {
//                 return true;
//             }

//             return false;
//         }

//         private function checkEmpty($value) 
//         {
//             if (!isset($value))
//                 return true;
//             if ($value === null)
//                 return true;
//             if (trim($value) === "")
//                 return true;

//             return false;
//         }

//         public function sign($data = array())
//         {
//             ksort($data);

//             $stringToBeSigned = "";

//             $i = 0;

//             foreach ($data as $k => $v) {
//                 if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
//                     if ($i == 0) {
//                         $stringToBeSigned .= "$k" . "=" . "$v";
//                     } else {
//                         $stringToBeSigned .= "&" . "$k" . "=" . "$v";
//                     }

//                     $i++;
//                 }
//             }

//             unset($k, $v);

//             $priKey  = file_get_contents('rsa_private_key.pem');

//             $res     = openssl_pkey_get_private($priKey);

//             $openssl = openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);

//             openssl_free_key($res);

//             $sign = base64_encode($sign);

//             return $sign;
//         }
//     }

//     $tool = new Tool($c);
//     return $tool;
// };

