<?php

require 'controller.php';

class Account extends Controller
{
    protected $image_server, $server;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);

        $this->image_server = $this->app->get('settings')['default']['image_server'];
        $this->server       = $this->app->get('settings')['default']['server'];
    }

    public function index()
    {
        var_dump($_SESSION['uuid']);

        echo $this->app->template->render('account', ['server' => $this->server]);
    }

    public function login()
    {
        var_dump("Login Success.");
    }

    public function logout()
    {
        var_dump("Logout Success.");
    }
}
