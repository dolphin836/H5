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
        $results        = $this->app->db->select('ticket', ['id', 'code', 'product_name', 'product_price', 'status', 'create_time'], ['uuid[=]' => $_SESSION['uuid']]);
        $ticket_open    = array();
        $ticket_close   = array();
        foreach ($results as $result) {
            if ($result['status'] == 0) {
                $ticket_open[]  = array(
                        'code'  => $result['code'],
                 'product_name' => $result['product_name'],
                'product_price' => $result['product_price'],
                       'status' => $result['status'],
                  'create_time' => date("Y-m-d H:i:s", $result['create_time'])
                );
            } else {
               $ticket_close[]  = array(
                        'code'  => $result['code'],
                 'product_name' => $result['product_name'],
                'product_price' => $result['product_price'],
                       'status' => $result['status'],
                  'create_time' => date("Y-m-d H:i:s", $result['create_time'])
                );
            }
        }

        $scripts[] = $this->server . 'dist/js/' . 'zepto.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'ticket.js';

        echo $this->app->template->render('ticket', ['server' => $this->server, 'item' => 'ticket', 'cartCount' => $this->cartCount, 'scripts' => $scripts, 'ticket_open' => $ticket_open, 'ticket_close' => $ticket_close]);
    }
}
