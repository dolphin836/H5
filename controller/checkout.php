<?php

require 'controller.php';

class Checkout extends Controller
{
    protected $image_server, $server;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);
    }

    public function index()
    {
        echo $this->app->template->render('checkout', ['server' => $this->server]);
    }
}
