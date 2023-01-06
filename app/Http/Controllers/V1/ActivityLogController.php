<?php
namespace App\Http\Controllers\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\ActivityLog;


class ActivityLogController extends Controller
{
   


    
    public function insertActivity($request,$order,$description){

        $hotel = $request->hotel;

        $bookingId = $request->bookingId;

        $type = $request->type;

        $idRoomOrIdBed = $request->idRoomOrIdBed;
        
        $user = $request->user;
       
        ActivityLog::insertActivity($hotel,$bookingId,$type,$idRoomOrIdBed,$user,$description,$order);

        

    }
   
   
    
}