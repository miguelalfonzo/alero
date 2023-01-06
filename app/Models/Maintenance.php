<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use Carbon\Carbon;


class Maintenance extends Model
{
    use HasFactory;

   
   protected static function options($request){


   		
   		$type  = $request->type;
   		
    	$list  = DB::select('CALL sp_pms_get_select_maintenance (?)', array($type));

    	return $list;
    
    }

    protected static function getIgv($hotel){

      $query = DB::select("SELECT Igv FROM tblparameters WHERE HotelId=?" ,array($hotel));

      return $query[0]->Igv;

    }


    protected static function getMailUserLogin($user){

      $query = DB::select("SELECT email FROM users WHERE id=?" ,array($user));

      return $query[0]->email;

    }

    protected static function getNameStatusBooking($id){

      $query = DB::select("SELECT Name FROM tblbookingstatus WHERE Id=?" ,array($id));

      return $query[0]->Name;
    }

    protected static function getNameProductBooking($id){

      $query = DB::select("SELECT Name FROM tblproducts WHERE Id=?" ,array($id));

      
      return $query[0]->Name;
    }

    protected static function getNumberRoomBooking($id,$IdTypeRoom,$TypeSelect){

      if($TypeSelect == 'bed'){

         $query = DB::select("SELECT Number FROM tblrooms WHERE RoomType=?" ,array($IdTypeRoom));

         return $query[0]->Number.' - '.$id;
      }

      $query = DB::select("SELECT Number FROM tblrooms WHERE Id=?" ,array($id));

      return $query[0]->Number;
      
    }
    
    protected static function seekerProducts($request){



   		$hotel     = $request->hotel;
   		$category  = $request->category;
   		$term      = $request->term;


    	$list  = DB::select('CALL sp_pms_seeker_products (?,?,?)', array($hotel,$category,$term));

    	return $list;
    
    }



    protected static function getPriceBaseTypeRoom($hotel,$id){



      $list  = DB::select('CALL sp_web_get_pbase_room (?,?)', array($hotel,$id));

      return (isset($list[0]->Rate)?$list[0]->Rate:'');
    
    }

    protected static function getNameTypeRoom($hotel,$id){



      $list  = DB::select('CALL sp_web_get_type_room (?,?)', array($hotel,$id));

      return (isset($list[0]->Name)?$list[0]->Name:'');
    
    }
    
    
    
}
