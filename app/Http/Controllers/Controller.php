<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function setRpta($status,$description,$data){


    	return array('status' => $status,'description' => $description,'data' => $data);

    	
    }

   

    public function mailOhotels(){


    	 return array("HOST"     =>'criocord.com.pe',
                     "PUERTO"   => 587,
                     "CORREO"   => 'reportes@criocord.com.pe',
                     "PASSWORD"  => 'L(W)LmJ7vdOs0zIa',
                     "ENCRIPTACION"=> 'tls');


    	
    }

   


    
}
