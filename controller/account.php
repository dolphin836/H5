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
        if ($this->app->session->isRegistered()) {
            // if ($this->app->session->isExpired()) {
            //     var_dump('Please Login 22.');
            //     $this->app->session->end();
            // } else {
            //     $this->app->session->renew();
            //     var_dump($this->app->session->get('username'));
            // }
        } else {
            var_dump('Please Login 11.');
        }

        echo $this->app->template->render('account', ['server' => $this->server]);
    }

    public function login()
    {
        $this->app->session->register(120);

        $this->app->session->set('username', 'dolphin');

        var_dump("Login Success.");
    }

    public function logout()
    {
        $this->app->session->end();

        var_dump("Logout Success.");
    }
}
