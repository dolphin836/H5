<?php

require 'controller.php';

class Account extends Controller
{
    protected $image_server, $server;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);
    }

    public function index()
    {
        echo $this->app->template->render('account', ['server' => $this->server]);
    }
}
