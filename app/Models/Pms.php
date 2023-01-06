<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use Carbon\Carbon;


class Pms extends Model
{
    use HasFactory;

   
   protected static function dashboardToday($request){


   		
   		$hotel  = $request->hotel;
   		
   		$type  = $request->type;

    	$list  = DB::select('CALL sp_pms_get_list_bookings_today (?,?)', array($hotel,$type));

    	return $list;
    
    }

    protected static function dashboardIndicators($request){


   		
   		$hotel  = $request->hotel;
   		
   		

    	$list  = DB::select('CALL sp_pms_get_indicators_dashboard (?)', array($hotel));

    	return $list;
    
    }

    protected static function getListRatesHistoryOld($hotel,$type,$idRoomOrIdBed,$bookingId){


      
      

      $list  = DB::select('CALL sp_pms_get_list_rates_booking (?,?,?,?)', array($hotel,$type,$idRoomOrIdBed,$bookingId));

      return $list;
    
    }

    protected static function getListRatesHistoryNew($request,$fromDate,$toDate){


      
      $hotel  = $request->hotel;
      
      $type   = trim($request->type);

      $idRoomOrIdBed  = $request->idRoomOrIdBed;

      $checkIn  = Carbon::parse($fromDate)->format('Y-m-d');

      $checkOut  = Carbon::parse($toDate)->format('Y-m-d');

      $text  = DB::select('SELECT fn_pms_temp_hist_book_dates (?,?,?,?,?) AS result', array($hotel,$type,$idRoomOrIdBed,$checkIn,$checkOut));

      return (isset($text[0]->result))?$text[0]->result:null;
    
    }
    
    
    protected static function savePayments($request){


      
      $hotel  = $request->hotel;
      
      $user = $request->user;

      $booking = $request->bookingId;

      $type   = trim($request->type);

      $idRoomOrIdBed  = $request->idRoomOrIdBed;

      $paymentType   = $request->paymentType;

      $amount   = $request->amount;

      $description   = $request->description;
      
      $positive   = $request->positive;

      DB::insert('CALL sp_pms_insert_payment (?,?,?,?,?,?,?,?,?)', array($hotel,$user,$booking,$type,$idRoomOrIdBed,$paymentType,$amount,$description,$positive));

      
    
    }

    protected static function saveProducts($hotel,$user,$bookingId,$type,$idRoomOrIdBed,$idProduct,$quantity,$unitPrice,$subTotal,$discount,$igv,$total){


      
     
      
      DB::insert('CALL sp_pms_insert_products (?,?,?,?,?,?,?,?,?,?,?,?)', array($hotel,$user,$bookingId,$type,$idRoomOrIdBed,$idProduct,$quantity,$unitPrice,$subTotal,$discount,$igv,$total));

      
    
    }

    protected static function saveNotes($hotel,$bookingId,$idRoomOrIdBed,$type,$description,$user){


      
     
      
      $rpta = DB::insert('CALL sp_pms_insert_notes (?,?,?,?,?,?)', array($hotel,$bookingId,$idRoomOrIdBed,$type,$description,$user));

      return $rpta;
      
    
    }


     protected static function confirmDiscount($hotel,$user,$bookingId,$type,$idRoomOrIdBed,$description,$discount){


      
     
      
      DB::update('CALL sp_pms_update_discount (?,?,?,?,?,?,?)', array($hotel,$user,$bookingId,$type,$idRoomOrIdBed,$description,$discount));

     
      
    
    }

    protected static function alltotals($bookingId){


    	$list  = DB::select('CALL sp_pms_get_alltotals_booking (?)', array($bookingId));

    	return $list;

    }



    protected static function getHistoryProducts($hotel,$type,$idRoomOrIdBed,$bookingId){


      
      

      $list  = DB::select('CALL sp_pms_get_products_booking (?,?,?,?)', array($hotel,$type,$idRoomOrIdBed,$bookingId));

      return $list;
    
    }

    protected static function getHistoryPayments($hotel,$type,$idRoomOrIdBed,$bookingId){


      
      

      $list  = DB::select('CALL sp_pms_get_payments_booking (?,?,?,?)', array($hotel,$type,$idRoomOrIdBed,$bookingId));

      return $list;
    
    }



     protected static function changeToIgv($hotel,$type,$idRoomOrIdBed,$bookingId,$active){


      
     
      
      DB::update('CALL sp_pms_change_igv_booking (?,?,?,?,?)', array($hotel,$type,$idRoomOrIdBed,$bookingId,$active));

      
      
    
    }


    protected static function getHistoryNotes($hotel,$type,$idRoomOrIdBed,$bookingId){


      
      

      $list  = DB::select('CALL sp_pms_get_notes_booking (?,?,?,?)', array($hotel,$type,$idRoomOrIdBed,$bookingId));

      return $list;
    
    }


    

    protected static function assignGuest($hotel,$type,$idRoomOrIdBed,$bookingId,$idGuest,$user){


     
      
      DB::update('CALL sp_pms_assign_guest (?,?,?,?,?,?)', array($hotel,$type,$idRoomOrIdBed,$bookingId,$idGuest,$user));

      
      
    
    }
    

    protected static function inactiveItemProduct($hotel,$user,$id){


     
      
       DB::update('CALL sp_pms_delete_item_booking (?,?,?)', array($hotel,$user,$id));

      
      
    
    }

     protected static function getInfoDetail($bookingId){

      $list  = DB::select('CALL sp_pms_get_details_booking (?)', array($bookingId));

      return $list;
    
    }



    protected static function updateState($hotel,$type,$idRoomOrIdBed,$bookingId,$user,$state){

      
       DB::update('CALL sp_pms_update_state_booking (?,?,?,?,?,?)', array($hotel,$type,$idRoomOrIdBed,$bookingId,$user,$state));

    
    
    }

    protected static function reassignRoom($request){


    }

    
    
}
