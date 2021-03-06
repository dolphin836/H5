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
        $results        = $this->app->db->select('product', ['id', 'name', 'image', 'price', 'abstract'], ['published[=]' => 1, 'ORDER' => ['sort' => 'DESC']]);
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

        echo $this->app->template->render('product', ['server' => $this->server, 'item' => 'discover', 'cartCount' => $this->cartCount, 'products' => $products]);
    }

    public function view()
    {
        $results        = $this->app->db->select('product', ['id', 'name', 'image', 'price', 'abstract', 'context'], ['id[=]' => $this->args['id']]);
        foreach ($results as $result) {
            //更新浏览量
            $this->app->db->update("product", [
                     "viewed[+]" => 1,
                "show_viewed[+]" => rand(1, 10)
            ], [
                "id[=]" => $result['id']
            ]);

            $Parsedown  = new Parsedown();
            $product_option = array();

            $options    = $this->app->db->select('product_option', ['id', 'name'], ['product_id[=]' => $this->args['id']]);
   
            foreach ($options as $option) {
                $value  = $this->app->db->select('product_option_value', ['id', 'description', 'add_price', 'is_checked'], ['option_id[=]' => $option['id'], 'ORDER' => ['sort' => 'ASC']]);
            
                $product_option[] = array(
                       'id' => $option['id'],
                     'name' => $option['name'],
                    'value' => $value
                );
            }   
         
            $product  = array(
                   'id' => $result['id'],
                'cover' => $this->image_server . $result['image'],
                 'name' => $result['name'],
             'abstract' => $result['abstract'],
                'price' => $result['price'],
             'discount' => $result['price'] * (1 - $this->app->get('settings')['default']['discount']),
              'context' => $Parsedown->text($result['context']),
              'options' => $product_option
            );
        }

        $scripts[] = $this->server . 'dist/js/' . 'zepto.min.js';
        $scripts[] = $this->server . 'dist/js/' . 'view.js';

        echo $this->app->template->render('view', ['server' => $this->server, 'item' => 'view', 'scripts' => $scripts, 'cartCount' => $this->cartCount, 'product' => $product]);
    }
}
