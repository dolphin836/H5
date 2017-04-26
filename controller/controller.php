<?php

class Controller
{
    protected $request, $response, $app, $args, $cartCount;

    function __construct($request, $response, $app, $args)
    {
        $this->request   = $request;
        $this->response  = $response;
        $this->app       = $app;
        $this->args      = $args;

        $this->cartCount = 0;

        if ( isset($_SESSION['cartCount']) ) {
            $this->cartCount = $_SESSION['cartCount'];
        }
    }
}

