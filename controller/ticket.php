<?php

require 'controller.php';

class Ticket extends Controller
{
    protected $image_server, $server;

    function __construct($request, $response, $app, $args)
    {
        parent::__construct($request, $response, $app, $args);
    }

    public function index()
    {
        $scripts[] = $this->server . 'dist/js/' . 'zepto.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'ticket.js';

        echo $this->app->template->render('ticket', ['server' => $this->server, 'item' => 'ticket', 'cartCount' => $this->cartCount, 'scripts' => $scripts]);
    }
}
