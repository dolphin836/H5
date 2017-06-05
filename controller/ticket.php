<?php

require 'controller.php';

require 'phpqrcode/qrlib.php';

use OSS\OssClient;
use OSS\Core\OssException;

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
        $results        = $this->app->db->select('ticket', ['id', 'code', 'product_name', 'product_price', 'status', 'create_time', 'modifie_time'], ['uuid[=]' => $_SESSION['uuid']]);
        
        $ticket_open    = array();
        $ticket_close   = array();
        foreach ($results as $result) {
            if ($result['status'] == 0) {
                $ticket_open[]  = array(
                          'id'  => $result['id'],
                        'code'  => $result['code'],
                 'product_name' => $result['product_name'],
                'product_price' => $result['product_price'],
                       'status' => $result['status'],
                  'create_time' => date("Y-m-d H:i:s", $result['create_time'])
                );
            } else {
               $ticket_close[]  = array(
                          'id'  => $result['id'],
                        'code'  => $result['code'],
                 'product_name' => $result['product_name'],
                'product_price' => $result['product_price'],
                       'status' => $result['status'],
                  'modifie_time' => date("Y-m-d H:i:s", $result['modifie_time'])
                );
            }
        }

        $scripts[] = $this->server . 'dist/js/' . 'zepto.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'ticket.js';

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
        $filepath     = 'qrcode/' . date("Y", time()) . '/' . date("m", time()) . '/' . date("d", time()) . '/' . $code . '.png';
            
        QRcode::png($codeContents, $QR, QR_ECLEVEL_L, 10);

        try {
            $ossClient = new OssClient($this->app->get('settings')['oss']['OSS_ACCESS_ID'], $this->app->get('settings')['oss']['OSS_ACCESS_KEY'], $this->app->get('settings')['oss']['OSS_ENDPOINT'], true);
        } catch (OssException $e) {
            printf($e->getMessage());
        }

        try {
            $result = $ossClient->uploadFile($this->app->get('settings')['oss']['OSS_BUCKET'], $filepath, $QR);
            @unlink($QR);
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage());
        }

        $ticket = array(
             'product_name' => $results[0]['product_name'],
            'product_price' => $results[0]['product_price'],
                     'code' => $code,
                       'qr' => $this->image_server . $filepath
        );

        echo $this->app->template->render('ticket_view', ['server' => $this->server, 'item' => 'ticket', 'cartCount' => $this->cartCount, 'ticket' => $ticket]);
    }

    public function check()
    {
        $workuser   = $this->app->db->select('user', ['type'], ['uuid[=]' => $_SESSION['uuid']]);

        if ( empty($workuser) || $workuser[0]['type'] != '2' ) {
            var_dump("无效的验票人员");
            exit;
        }

        $data   = array();
        $code   = $this->args['code'];

        $ticket = $this->app->db->select('ticket', ['uuid', 'product_name', 'product_price', 'create_time'], ['code[=]' => $code, 'status[=]' => 0]);

        if ( empty($ticket) ) {
            var_dump("没有查询到有效的票码");
            exit;
        }

        $data['code']           = $code;
        $data['product_name']   = $ticket[0]['product_name'];
        $data['product_price']  = $ticket[0]['product_price'];
        $data['create_time']    = date("Y-m-d H:i:s", $ticket[0]['create_time']);

        $uuid   = $ticket[0]['uuid'];

        $user   = $this->app->db->select('user', ['nickname', 'telephone', 'image'], ['uuid[=]' => $uuid]);
        $data['user_name']      = $user[0]['nickname'];
        $data['user_telephone'] = $user[0]['telephone'];
        $data['user_image']     = $user[0]['image'];

        $default_user_image  = $this->image_server . 'default_user_image.png';

        echo $this->app->template->render('ticket_check', ['server' => $this->server, 'item' => 'ticket', 'cartCount' => $this->cartCount, 'data' => $data, 'default_user_image' => $default_user_image]);
    }

    public function pass()
    {
        $workuser   = $this->app->db->select('user', ['type'], ['uuid[=]' => $_SESSION['uuid']]);

        if ( empty($workuser) || $workuser[0]['type'] != '2' ) {
            var_dump("无效的验票人员");
            exit;
        }

        $data   = array();
        $code   = $this->args['code'];

        $ticket = $this->app->db->select('ticket', ['uuid', 'product_name', 'product_price', 'create_time'], ['code[=]' => $code, 'status[=]' => 0]);

        if ( empty($ticket) ) {
            var_dump("没有查询到有效的票码");
            exit;
        }

        $this->app->db->update("ticket", [
                    "suid" => $_SESSION['uuid'],
                  "status" => 1,
            "modifie_time" => time()
        ], [
            "code[=]" => $code
        ]);

        echo $this->app->template->render('ticket_success', ['server' => $this->server, 'item' => 'ticket', 'cartCount' => $this->cartCount]);
    }
}
