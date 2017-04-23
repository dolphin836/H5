<?php

require 'controller.php';

class Product extends Controller
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
        $results        = $this->app->db->select('product', ['id', 'name', 'image', 'price', 'abstract'], ['published[=]' => 1]);
        $products       = array();
        foreach ($results as $result) {
            $products[] = array(
                'cover' => $this->image_server . $result['image'],
                 'name' => $result['name'],
             'abstract' => $result['abstract'],
                'price' => $result['price'],
                 'view' => 'product/view/' . $result['id'] . '.html'
            );
        }

        echo $this->app->template->render('product', ['server' => $this->server, 'products' => $products]);
    }

    public function view()
    {
        $Parsedown = new Parsedown();
        $results   = $this->app->db->select('product', ['id', 'name', 'image', 'price', 'abstract', 'context'], ['id[=]' => $this->args['id']]);
        foreach ($results as $result) {
            $product  = array(
                'cover' => $this->image_server . $result['image'],
                 'name' => $result['name'],
             'abstract' => $result['abstract'],
                'price' => $result['price'],
              'context' => $Parsedown->text($result['context'])
            );
        }

        echo $this->app->template->render('view', ['server' => $this->server, 'product' => $product]);
    }
}
