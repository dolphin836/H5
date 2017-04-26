<?php

require 'controller.php';

class Success extends Controller
{
    protected $image_server, $server;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);
    }

    public function index()
    {
        $cartCount = 0;
        if ( isset($_SESSION['cartCount']) ) {
            $cartCount = $_SESSION['cartCount'];
        }

        echo $this->app->template->render('success', ['server' => $this->server, 'item' => 'cart', 'cartCount' => $cartCount]);
    }
}
