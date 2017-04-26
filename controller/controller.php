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

    protected function microtime_float()
    {
        list($usec, $sec)  = explode(" ", microtime());
        list($str1, $str2) = explode(".", $usec);
        $string            = $sec . $str2;
        return $string;
    }

    protected function GeraHash($qtd, $number = false)
    {
        if ($number) {
            $Caracteres = '0123456789'; 
        } else {
            $Caracteres = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuioplkjhgfdsazxcvbnm0123456789';
        }
         
        $QuantidadeCaracteres = strlen($Caracteres);

        $QuantidadeCaracteres--; 

        $Hash  = NULL; 

        for($x = 1; $x <= $qtd; $x++)
        { 
            $Posicao = rand(0, $QuantidadeCaracteres); 

            $Hash   .= substr($Caracteres, $Posicao, 1); 
        }
        
        return $Hash; 
    }
}

