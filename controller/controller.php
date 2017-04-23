<?php

class Controller
{
    protected $request, $response, $app, $args;

    function __construct($request, $response, $app, $args)
    {
        $this->request  = $request;
        $this->response = $response;
        $this->app      = $app;
        $this->args     = $args;
    }
}

