<?php

require 'controller.php';

require 'phpqrcode/qrlib.php';  

class Ticket extends Controller
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
        $scripts[] = $this->server . 'dist/js/' . 'ticket.js?5555';

        echo $this->app->template->render('ticket', ['server' => $this->server, 'item' => 'ticket', 'cartCount' => $this->cartCount, 'scripts' => $scripts, 'ticket_open' => $ticket_open, 'ticket_close' => $ticket_close]);
    }

    public function view()
    {
        $results = $this->app->db->select('ticket', ['id', 'code', 'product_name', 'product_price', 'status', 'create_time'], ['uuid[=]' => $_SESSION['uuid'], 'id[=]' => $this->args['id']]);
        
        if ( empty($results) ) {
            var_dump("非法访问");
            exit;
        }

        if ( $results[0]['status'] == '1' ) {
            var_dump("已经使用");
            exit;
        }

        $code         = $results[0]['code'];

        $codeContents = $this->server . 'ticket/check/' . $code; 
    
        $QR           = 'dist/qrcode/'. $code . '.png';
            
        QRcode::png($codeContents, $QR, QR_ECLEVEL_L, 10);

        $ticket = array(
             'product_name' => $results[0]['product_name'],
            'product_price' => $results[0]['product_price'],
                     'code' => $code,
                       'qr' => $this->server . $QR
        );

        echo $this->app->template->render('check', ['server' => $this->server, 'item' => 'ticket', 'cartCount' => $this->cartCount, 'ticket' => $ticket]);
    }

    public function check()
    {
        $code = $this->args['code'];
        var_dump($code);
    }
}
