<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use Carbon\Carbon;


class ActivityLog extends Model
{
    use HasFactory;

   
  protected static function insertActivity($hotel,$bookingId,$type,$idRoomOrIdBed,$user,$description,$order){

     $now = Carbon::now()->format('Y-m-d H:i:s');

     DB::insert("INSERT INTO tblactivitylog(HotelId,BookingId,RoomIdOrBedId,Type,Description,CreatedAt,CreatedBy,Hierarchy) VALUES(?,?,?,?,?,?,?,?);", array($hotel,$bookingId,$idRoomOrIdBed,$type,$description,$now,$user,$order));
   

  }


  protected static function insertLogEmail($rpta,$hotel,$asunto,$mensaje,$list,$user,$destinatarios,$bccEmails){

  		$success = ( $rpta > 0 )?'ok':'error';


         $values = array(

                "HotelId" => $hotel ,
                "Subject" => $asunto ,
                "SendDate" => Carbon::now()->format('Y-m-d H:i:s') ,
                "Sender" => $list['CORREO'] ,
                "Recipients" => json_encode($destinatarios) ,  
                "Cc" => $bccEmails ,
                "Message" => json_encode($mensaje) ,
              	"File" => null ,
                "Success" => $success,
                "CreatedAt" => Carbon::now()->format('Y-m-d H:i:s') ,
                "CreatedBy" => $user
               
            );


        DB::table('tbllogsmails')->insert($values);



    }
  
    
}
