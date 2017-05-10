<?php

$routes = array(
    array('get',  ''),
    array('get',  'cart.html'),
    array('get',  'index.html'),
    array('get',  'ticket.html'),
    array('get',  'success.html'),
    array('get',  'account.html'),
    array('get',  'cart/clean[/{id}]'),
    array('get',  'account/order.html'),
    array('get',  'account/login'),
    array('get',  'account/logout'),
    array('get',  'ticket/view/{id:[0-9]+}.html'),
    array('get',  'ticket/check/{code:[0-9]+}'),
    array('get',  'ticket/pass/{code:[0-9]+}'),
    array('get',  'product/view/{id:[0-9]+}.html'),
    array('any',  'order/callback'),
    array('post', 'cart/add'),
    array('post', 'order/add'),
    array('post', 'order/zhi'),
    array('post', 'order/zcallback')
);

foreach ($routes as $route) {
    $app->$route[0]('/' . $route[1], function ($request, $response, $args) {
        $params = explode('.', $request->getUri()->getPath());
        $path   = explode('/', $params[0]);

        $c      = isset($path[1]) ? $path[1] : 'product';
        $m      = isset($path[2]) ? $path[2] : 'index';
        $c      = $c != 'index'   ? $c       : 'product'; // index.html
        $c      = $c != ''        ? $c       : 'product'; // defalut

        $class_file = __DIR__ . '/../controller/' . $c . '.php';

        if (file_exists($class_file)) {
            include_once $class_file;
        }

        $class = ucwords($c);

        $i = new $class($request, $response, $this, $args);

        $i->$m();
    });
}



