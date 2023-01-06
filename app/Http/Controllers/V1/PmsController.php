<?php
namespace App\Http\Controllers\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Pms;
use App\Models\Maintenance;


use App\Models\Booking;
use App\Models\ActivityLog;
use Carbon\Carbon;
use DB;
use App\Http\Controllers\V1\ActivityLogController;
use App\Http\Controllers\V1\BookingController;
use App\Http\Controllers\V1\CorreoController;

class PmsController extends Controller
{
   

  

    

    protected function dashboardToday(Request $request)
    {
        


        $data = $request->only('hotel','type');

        $validator = Validator::make($data, [
            'hotel' => 'required|numeric',
            'type' => 'required|numeric',
           
            
        ]);
        
        if ($validator->fails()) {

            $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

            return response()->json($middleRpta, 400);
        }
      
    
        $list = Pms::dashboardToday($request);

        $middleRpta = $this->setRpta('ok','success list',$list);

        return response()->json($middleRpta,Response::HTTP_OK);

    }
    
    
   protected function dashboardIndicators(Request $request)
    {
        


        $data = $request->only('hotel');

        $validator = Validator::make($data, [
            'hotel' => 'required|numeric',
           
           
            
        ]);
        
        if ($validator->fails()) {

            $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

            return response()->json($middleRpta, 400);
        }
      
    
        $list = Pms::dashboardIndicators($request);

        $middleRpta = $this->setRpta('ok','success list',$list);

        return response()->json($middleRpta,Response::HTTP_OK);

    }

    protected function validateNewDatesBooking($request){

        $hotel          = $request->hotel;
        $type           = trim($request->type);
        $idRoomOrIdBed  = $request->idRoomOrIdBed;
        $booking        = $request->bookingId;

        $checkIn        = Carbon::parse($request->checkIn)->format('Y-m-d');
        $checkOut       = Carbon::parse($request->checkOut)->format('Y-m-d');

        //$now = Carbon::now()->format('Y-m-d');


        if($type=='room'){

            $sql = DB::select("SELECT BookingId,ReserveFromDate,ReserveToDate FROM tblbookingroom  
              WHERE HotelId = ? AND RoomId = ? AND ( (ReserveFromDate>=CURDATE() )OR (CURDATE() BETWEEN ReserveFromDate AND ReserveToDate));" ,array($hotel,$idRoomOrIdBed));

        }else{

            $sql = DB::select("SELECT BookingId,ReserveFromDate,ReserveToDate FROM tblbookingbeds  
              WHERE HotelId = ? AND BedId = ? AND ( (ReserveFromDate>=CURDATE() )OR (CURDATE() BETWEEN ReserveFromDate AND ReserveToDate));" ,array($hotel,$idRoomOrIdBed));
        }
        
        $interference = [];


        

        foreach($sql as $values){

            if($values->ReserveToDate > $checkIn AND $values->ReserveFromDate<$checkOut){

                $interference[] = $values->BookingId;
            }
                

        }


       


        if(count($interference)==0){

            return  $this->setRpta('ok','success validate',[]);

        }elseif(count($interference)==1){

            if(intval($interference[0]) == $booking ){

                return  $this->setRpta('ok','success validate',[]);

            }else{

                return  $this->setRpta('error','there is no availability for the room',[]);
            }

        }else{

            return  $this->setRpta('error','there is no availability for the room',[]);
        }
        
    }


    protected function suggestNewHistory(Request $request){

        $data = $request->only('checkIn','checkOut','bookingId');

        $validator = Validator::make($data, [
          
            'checkIn'=> 'required|date',
            'checkOut'=> 'required|date|after:checkIn',
            'bookingId'=>'required|numeric',
          
           
            
        ]);

        if ($validator->fails()) {

            
            return response()->json($this->setRpta('warning','validator fails',$validator->messages()), 400);
        }

       

        $middleRpta = $this->validateNewDatesBooking($request);
        
       

        if($middleRpta["status"]=="ok"){

            $list = $this->setTemporalListRatesDates($request);

            $rpta = $this->setRpta('ok','success response',$list);

            return response()->json($rpta,Response::HTTP_OK); 
        }
        
         return response()->json($middleRpta, 400);


    }

    protected function setRangoDates($request){

        $from = Carbon::parse($request->checkIn);

        $to   = Carbon::parse($request->checkOut);

        $diff = $from->diffInDays($to);

        $dates = [];

        $ini  = $from ;

        $ini = $ini->subDays(1);

        for($i = 0; $i < $diff+1; ++$i) {
       
            $ini = $ini->addDays(1);

            $dates[] = $ini->format('Y-m-d');
        }

        return $dates;

    }


    protected function setTemporalListRatesDates($request){

        
         
         $oldList = [];

         $temporal =[];

         $dataSet = [] ;

         $listOld = Pms::getListRatesHistoryOld($request->hotel,trim($request->type),$request->idRoomOrIdBed,$request->bookingId);

         foreach($listOld as $list){

            $oldList[$list->Date]=$list->Price;

         }

         //partimos de 10 en 10 todo el rango de fechas , el mysql no puede retonar una cadena muy grande para la funcion fn_pms_temp_hist_book_dates, se enviara por lotes

         $datesAll = $this->setRangoDates($request);

         $datesAll = array_chunk($datesAll,10);

         
         foreach($datesAll as $values){

            $fromDate = current($values); //primer valor a enviar

            $toDate = end($values); //ultimo valor a enviar


            $listNew = Pms::getListRatesHistoryNew($request,$fromDate,$toDate);

         


            if(!empty($listNew)){

                $split = explode("&", $listNew);

                foreach($split as $list){

                    $subString = explode("|", $list);

                    $date = $subString[0];

                    $price_new = $subString[1];

                    //si existe un antiguo precio 

                    $price = (isset($oldList[$date]))?$oldList[$date]:$price_new;

                    $temporal[$date] = $price;

                }

            }
            

         }

        
         

         foreach($temporal as $key=>$val){

            $dataSet[] = array("date"=>$key,"price"=>$val);

         }
         return $dataSet;


    }
   
    protected function saveEditDates(Request $request){

        try {

            DB::beginTransaction();
            
            $data = $request->only('hotel','user','bookingId','type','idRoomOrIdBed','list');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'user'=> 'required|numeric',
                'bookingId'=>'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'list'=>'required'
               
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

           


            $hotel         = $request->hotel;
            $user          = $request->user;
            $bookingId     = $request->bookingId;
            $type          = trim($request->type);
            $idRoomOrIdBed = $request->idRoomOrIdBed;

            $now = Carbon::now()->format('Y-m-d H:i:s');

           
            DB::delete("DELETE FROM tblbookinghistoryprices WHERE HotelId=? AND BookingId=? AND RoomIdOrBedId=? AND Type=?",array($hotel ,$bookingId ,$idRoomOrIdBed ,$type ));

            $newPrice = 0;

            $minMaxDates = [];

            foreach($request->list  as $values ){

                $minMaxDates[] = $values["date"];

                $date = $values["date"];
                
                $price = $values["price"];

                DB::insert("INSERT INTO tblbookinghistoryprices(HotelId,BookingId,RoomIdOrBedId,Type,Date,Price,CreatedAt,CreatedBy) VALUES(?,?,?,?,?,?,?,?)",array($hotel,$bookingId,$idRoomOrIdBed,$type,$date,$price,$now,$user));

                $newPrice = $newPrice + $price;
            }

            //actualizamos el precio total en la cabecera y fechas de reservas

            $fromDate = current($minMaxDates); //primer valor a enviar

            $toDate = end($minMaxDates); //ultimo valor a enviar

            

            if($type == 'room'){

                $query = DB::select("SELECT Igv,Discount FROM tblbookingroom WHERE HotelId=? AND BookingId=? AND RoomId=?",array($hotel,$bookingId,$idRoomOrIdBed));

                $igv = (!empty($query[0]->Igv))?(float)$query[0]->Igv:0;

                $discount = (!empty($query[0]->Discount))?(float)$query[0]->Discount:0;

                $igvVal = $igv*$newPrice / 100 ;

                $total = $newPrice  - $discount + $igvVal;

                DB::update("UPDATE tblbookingroom SET PriceFinal =? , Total =? ,ReserveFromDate =? ,ReserveToDate=?,CheckIn=?,CheckOut=?,UpdatedAt = ? ,UpdatedBy=?
                    WHERE HotelId=? AND BookingId=? AND RoomId=?",array($newPrice,$total,$fromDate,$toDate,$fromDate,$toDate,$now,$user,$hotel,$bookingId,$idRoomOrIdBed));

            }else{


                $query = DB::select("SELECT Igv,Discount FROM tblbookingbeds WHERE HotelId=? AND BookingId=? AND BedId=?",array($hotel,$bookingId,$idRoomOrIdBed));

                $igv = (!empty($query[0]->Igv))?(float)$query[0]->Igv:0;

                $discount = (!empty($query[0]->Discount))?(float)$query[0]->Discount:0;

                $igvVal = $igv*$newPrice / 100 ;

                $total = $newPrice  - $discount + $igvVal;

                DB::update("UPDATE tblbookingbeds SET PriceFinal =? , Total =? ,ReserveFromDate =? ,ReserveToDate=?,CheckIn=?,CheckOut=?,UpdatedAt = ? ,UpdatedBy=?
                    WHERE HotelId=? AND BookingId=? AND BedId=?",array($newPrice,$total,$fromDate,$toDate,$fromDate,$toDate,$now,$user,$hotel,$bookingId,$idRoomOrIdBed));

            }
            
            
             $action = 'se hizo la modificación de check-in y check-out para la reserva : '.$fromDate.' a '.$toDate;

             $logActivity = new ActivityLogController();

             $logActivity->insertActivity($request,1,$action);

             DB::commit();

             $totals =  Pms::alltotals($bookingId);

             

             return response()->json($this->setRpta('ok','success created history ',$totals),201);

        } catch (\Exception $e) {
            
            DB::rollBack();

           
            return response()->json($this->setRpta('error','transact : '.$e->getMessage(),[]), 400);
        }



        

        

    }

    protected  function savePayments(Request $request){


        try {

            DB::beginTransaction();
            
            $data = $request->only('hotel','user','bookingId','type','idRoomOrIdBed','paymentType','amount','description','positive');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'user'=> 'required|numeric',
                'bookingId'=>'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'paymentType'=>'required|numeric',
                'amount'=>'required|numeric',
                'description'=>'nullable|string|max:250',
                'positive'=>'required|numeric|in:0,1' //devolucion , pago
               
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

           
             Pms::savePayments($request);
             
             $desPayment = ($request->positive==0)?'devolución':'pago';

             $action = 'se aplicó '.$desPayment.' para la reserva , de : '.$request->amount;

             $logActivity = new ActivityLogController();

             $logActivity->insertActivity($request,1,$action);


             DB::commit();

             $hotel       = $request->hotel;
             $bookingId   = $request->bookingId;
             $type        = $request->type;
             $idRoomOrIdBed = $request->idRoomOrIdBed;
             

             $totals =  Pms::alltotals($bookingId);

             return response()->json($this->setRpta('ok','success created payment ',$totals),201);

        } catch (\Exception $e) {
            
            DB::rollBack();

           
            return response()->json($this->setRpta('error','transact : '.$e->getMessage(),[]), 400);
        }

    }


    protected  function saveProducts(Request $request){


        try {

            DB::beginTransaction();
            
            $data = $request->only('hotel','user','bookingId','type','idRoomOrIdBed','list');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'user'=> 'required|numeric',
                'bookingId'=>'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'list'=>'required'
                
               
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

              $hotel     = $request->hotel;
              $user      = $request->user;
              $bookingId = $request->bookingId;
              $type      = trim($request->type);
              $idRoomOrIdBed = $request->idRoomOrIdBed;

             foreach ($request->list as $value) {
                
                $idProduct = $value["idProduct"];
                $quantity  = $value["quantity"];
                $unitPrice = $value["unitPrice"];
                $subTotal = $value["subTotal"];
                $discount = $value["discount"];
                $igv = $value["igv"];
                $total    = $value["total"];
                


                Pms::saveProducts($hotel,$user,$bookingId,$type,$idRoomOrIdBed,$idProduct,$quantity,$unitPrice,$subTotal,$discount,$igv,$total);

             }
             
             

             $action = 'se agregó la siguiente lista de productos : '.json_encode($request->list);

             $logActivity = new ActivityLogController();

             $logActivity->insertActivity($request,1,$action);


             DB::commit();

             $totals =  Pms::alltotals($bookingId);

             return response()->json($this->setRpta('ok','success created product ',$totals),201);

        } catch (\Exception $e) {
            
            DB::rollBack();

           
            return response()->json($this->setRpta('error','transact : '.$e->getMessage(),[]), 400);
        }

    }


    protected function saveNotes(Request $request){

       

        $data = $request->only('hotel','user','bookingId','type','idRoomOrIdBed','description');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'user'=> 'required|numeric',
                'bookingId'=>'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'description'=>'required|string|max:250'
                
               
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }


            $hotel     = $request->hotel; 
            $bookingId = $request->bookingId;
            $idRoomOrIdBed = $request->idRoomOrIdBed;
            $type      = trim($request->type);
            $description = $request->description;
            $user   = $request->user;

            $rpta = Pms::saveNotes($hotel,$bookingId,$idRoomOrIdBed,$type,$description,$user);


           


            if($rpta > 0){

                $action = 'se agregó una nota para la reserva : '.$description;

                $logActivity = new ActivityLogController();

                $logActivity->insertActivity($request,1,$action);

              
              return response()->json($this->setRpta('ok','success created note ',[]),201);

            }

            return response()->json($this->setRpta('error','could not insert note',[]), 400);
            


    }


    protected function confirmDiscount(Request $request){

        try {
            
             DB::beginTransaction();


            $data = $request->only('hotel','user','bookingId','type','idRoomOrIdBed','description','discount');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'user'=> 'required|numeric',
                'bookingId'=>'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'description'=>'required|string|max:250',
                'discount'=>'required|numeric|min:1'
                
               
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }


            $hotel     = $request->hotel; 
            $user      = $request->user;
            $bookingId = $request->bookingId;
            $type      = trim($request->type);
            $idRoomOrIdBed = $request->idRoomOrIdBed;
            $description = $request->description;
            $discount    = $request->discount;

            Pms::confirmDiscount($hotel,$user,$bookingId,$type,$idRoomOrIdBed,$description,$discount);

             $action = 'se agregó un descuento de : '.$discount .' por tal motivo : '.$description;

             $logActivity = new ActivityLogController();

             $logActivity->insertActivity($request,1,$action);

             
              DB::commit();

              $totals =  Pms::alltotals($bookingId);

              return response()->json($this->setRpta('ok','added discount',$totals),200);
            

            



        } catch (\Exception $e) {
            
            DB::rollBack();

            return response()->json($this->setRpta('error','transact :'.$e->getMessage(),[]), 400);
        }


        

    }

    protected function getHistoryPrices(Request $request){


        $data = $request->only('hotel','type','idRoomOrIdBed','bookingId');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'bookingId'=>'required|numeric'
  
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

            $hotel          = $request->hotel;
            $type           = trim($request->type);
            $idRoomOrIdBed  = $request->idRoomOrIdBed;
            $bookingId        = $request->bookingId;


            $list = Pms::getListRatesHistoryOld($hotel,$type,$idRoomOrIdBed,$bookingId);

            $rpta = $this->setRpta('ok','success response',$list);

            return response()->json($rpta,Response::HTTP_OK); 


    }

    protected function getHistoryProducts(Request $request){


        $data = $request->only('hotel','type','idRoomOrIdBed','bookingId');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'bookingId'=>'required|numeric'
  
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

            $hotel          = $request->hotel;
            $type           = trim($request->type);
            $idRoomOrIdBed  = $request->idRoomOrIdBed;
            $bookingId        = $request->bookingId;


            $list = Pms::getHistoryProducts($hotel,$type,$idRoomOrIdBed,$bookingId);

            $rpta = $this->setRpta('ok','success response',$list);

            return response()->json($rpta,Response::HTTP_OK); 


    }

    protected function getHistoryPayments(Request $request){


            $data = $request->only('hotel','type','idRoomOrIdBed','bookingId');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'bookingId'=>'required|numeric'
  
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

            $hotel          = $request->hotel;
            $type           = trim($request->type);
            $idRoomOrIdBed  = $request->idRoomOrIdBed;
            $bookingId        = $request->bookingId;


            $list = Pms::getHistoryPayments($hotel,$type,$idRoomOrIdBed,$bookingId);

            $rpta = $this->setRpta('ok','success response',$list);

            return response()->json($rpta,Response::HTTP_OK); 


    }

    

    protected function getHistoryNotes(Request $request){


            $data = $request->only('hotel','type','idRoomOrIdBed','bookingId');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'bookingId'=>'required|numeric'
  
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

            $hotel          = $request->hotel;
            $type           = trim($request->type);
            $idRoomOrIdBed  = $request->idRoomOrIdBed;
            $bookingId        = $request->bookingId;


            $list = Pms::getHistoryNotes($hotel,$type,$idRoomOrIdBed,$bookingId);

            $rpta = $this->setRpta('ok','success response',$list);

            return response()->json($rpta,Response::HTTP_OK); 


    }
    
    protected function changeToIgv(Request $request){

            try {
                
                DB::beginTransaction();


                $data = $request->only('hotel','type','user','idRoomOrIdBed','bookingId','active');

                $validator = Validator::make($data, [
                    'hotel' => 'required|numeric',
                    'user' => 'required|numeric',
                    'type'=> 'required|string',
                    'idRoomOrIdBed'=> 'required|numeric',
                    'bookingId'=>'required|numeric',
                    'active'=>'required|numeric|in:0,1' //aplica igv o no
      
                    
                ]);

                if ($validator->fails()) {

                    $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                    return response()->json($middleRpta, 400);
                }

                
                $hotel         = $request->hotel;
                $type          = trim($request->type);
                $idRoomOrIdBed = $request->idRoomOrIdBed;
                $bookingId     = $request->bookingId;
                $active        = $request->active;

                
                Pms::changeToIgv($hotel,$type,$idRoomOrIdBed,$bookingId,$active);

                $igvType = ($active == 1)?'activó':'inactivó';

                $action = 'se '.$igvType.' igv a la reserva' ;

                $logActivity = new ActivityLogController();

                $logActivity->insertActivity($request,1,$action);


                DB::commit();

                $totals =  Pms::alltotals($bookingId);

                return response()->json($this->setRpta('ok','change to igv success',$totals),200);

            } catch (\Exception $e) {
            
                DB::rollBack();

                return response()->json($this->setRpta('error','transact :'.$e->getMessage(),[]), 400);
            }


            



    }



    protected function assignGuest(Request $request){

        try {
             DB::beginTransaction();

             $data = $request->only('hotel','user','bookingId','type','idRoomOrIdBed','country','name','lastName','email','phone');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'user'=> 'required|numeric',
                'bookingId'=>'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'country'=>'required|string|max:3',
                'name'=>'required|string|max:50',
                'lastName'=>'required|string|max:50',
                'email'=>'required|string|email|max:50',
                'phone'=>'nullable|string|max:20',
  
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

            
            $hotel         = $request->hotel;
            $type          = trim($request->type);
            $idRoomOrIdBed = $request->idRoomOrIdBed;
            $bookingId     = $request->bookingId;


            $country        = $request->country;
            $guestFirstName = $request->name;
            $guestLastName  = $request->lastName;
            $guestEmail     = $request->email;
            $guestPhone     = $request->phone;
            $user           = $request->user;


            
             $rpta = Booking::createUserBooking($hotel,$country,$guestFirstName,$guestLastName,$guestEmail,$guestPhone,$user);

            if(isset($rpta[0]->ID) && is_int($rpta[0]->ID)){

                    
                    $idGuest = $rpta[0]->ID;


                    Pms::assignGuest($hotel,$type,$idRoomOrIdBed,$bookingId,$idGuest,$user);

                    $action = 'se asignó a la reserva el cliente con correo : '.$guestEmail ;

                    $logActivity = new ActivityLogController();

                    $logActivity->insertActivity($request,1,$action);

                    DB::commit();

                    return response()->json($this->setRpta('ok','assign guest',[]),200);


            }

            DB::rollBack();

            return $this->setRpta('error','could not create user ',[]);

        } catch (\Exception $e) {
            
            DB::rollBack();

            return response()->json($this->setRpta('error','transact :'.$e->getMessage(),[]), 400);

        }
            



    }

    protected function inactiveItemProduct(Request $request){

        try {
            
            DB::beginTransaction();

            $data = $request->only('hotel','bookingId','type','idRoomOrIdBed','user','id');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'bookingId'=>'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'user'=> 'required|numeric',
                'id'=>'required|numeric', //id del item a inactivar
               
  
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }


            $hotel     = $request->hotel;
            $bookingId = $request->bookingId;
            $type      = trim($request->type);
            $idRoomOrIdBed = $request->idRoomOrIdBed;
            $user      = $request->user;
            $id        = $request->id;

             Pms::inactiveItemProduct($hotel,$user,$id);

            
             $action = 'se inactivó el registro de un producto : '.Maintenance::getNameProductBooking($id);

             $logActivity = new ActivityLogController();

             $logActivity->insertActivity($request,1,$action);

             DB::commit();

             $totals =  Pms::alltotals($bookingId);

              return response()->json($this->setRpta('ok','inactive item product success',$totals),200);

        } catch (\Exception $e) {
            
            DB::rollBack();

            return response()->json($this->setRpta('error','transact :'.$e->getMessage(),[]), 400);

        }


         

    }




     protected function getInfoDetail(Request $request){


            $data = $request->only('bookingId');

            $validator = Validator::make($data, [
               
                'bookingId'=>'required|numeric'
  
                
            ]);

            if ($validator->fails()) {

                $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

                return response()->json($middleRpta, 400);
            }

            $hotel          = $request->hotel;
            $bookingId      = $request->bookingId;


            $list = Pms::getInfoDetail($bookingId);

            $totals =  Pms::alltotals($bookingId);

         

            $dataSet = array_merge((array)$list[0],(array)$totals[0]);
            
            $rpta = $this->setRpta('ok','success response',$dataSet);

            return response()->json($rpta,Response::HTTP_OK); 


    }

    protected function validateBalanceDue($hotel,$type,$idRoomOrIdBed,$bookingId,$state){

        //para estado check out o cancelacion o no show valida

        $statesDue = [2,3,4];

       if (in_array($state, $statesDue)) {
            
             $totals =  Pms::alltotals($bookingId);

           
             $due = (isset($totals[0]->BalanceDue))?$totals[0]->BalanceDue:0;

             if(floatval($due)>0){

                return $this->setRpta('error','cannot be modified due to debt : '.$due ,[]);
             }

             return $this->setRpta('ok','success validation',[]);
        }

        return $this->setRpta('ok','success validation',[]);

    }




    protected function updateState(Request $request){

        try {
            
            DB::beginTransaction();

            $data = $request->only('hotel','type','idRoomOrIdBed','bookingId','user','state');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'type'=> 'required|string',
                'idRoomOrIdBed'=> 'required|numeric',
                'bookingId'=>'required|numeric',
                'user'=>'required|numeric',
                'state'=>'required|numeric'
  
                
            ]);

            if ($validator->fails()) {

                return response()->json($this->setRpta('warning','validator fails',$validator->messages()), 400);
            }

            
            $hotel          = $request->hotel;
            $type           = trim($request->type);
            $idRoomOrIdBed  = $request->idRoomOrIdBed;
            $bookingId      = $request->bookingId;
            $user           = $request->user;
            $state          = $request->state;

            $middleRpta = $this->validateBalanceDue($hotel,$type,$idRoomOrIdBed,$bookingId,$state);
            
            if($middleRpta["status"] == "ok"){

                Pms::updateState($hotel,$type,$idRoomOrIdBed,$bookingId,$user,$state);

  
                $action = 'se cambió el estado de la reserva a : '. Maintenance::getNameStatusBooking($state);

                $logActivity = new ActivityLogController();

                $logActivity->insertActivity($request,1,$action);
             
                DB::commit();
              
              return response()->json($this->setRpta('ok','updated status booking success',[]),200);

            }


            return $middleRpta;
            

        } catch (\Exception $e) {
            
            DB::rollBack();

            return response()->json($this->setRpta('error','transact :'.$e->getMessage(),[]), 400);

        }
        

    }


     protected function viewAvailability(Request $request){


            $data = $request->only('hotel','checkIn','checkOut','lang');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'checkIn'=> 'required|date|after_or_equal:today',
                'checkOut'=> 'required|date|after:checkIn',
                'lang'=>'required|string|max:2'
                
  
                
            ]);

            if ($validator->fails()) {

                return response()->json($this->setRpta('warning','validator fails',$validator->messages()), 400);
            }

           
            $parseToFloat = Booking::searchRooms($request);

            $dataSet = [];

            foreach($parseToFloat as $values){


                    $strToArray = explode(",",$values["ItemsIdsAvailable"]);

                    foreach($strToArray as $list){

                          $dataSet[] = array(
                                "TypeSelect"      => $values["TypeSelect"],
                                "IdTypeRoom"      => $values["IdTypeRoom"],
                                "Name"            => $values["Name"],
                                "Description"     => $values["Description"],
                                "DescriptionBeds" => $values["DescriptionBeds"],
                                "MaxPersonRoom"   => $values["MaxPersonRoom"],
                                "Rate"            => $values["Rate"],
                                "Images"          => $values["Images"],
                                "Area"            => $values["Area"],
                                "RoomIdOrBedId"   => $list,
                                "Number"          => Maintenance::getNumberRoomBooking($list,$values["IdTypeRoom"],$values["TypeSelect"]),
                                "Private"         => $values["Private"],
                                "FlagExternalBath" => $values["FlagExternalBath"],
                                "FlagSharedBath"  => $values["FlagSharedBath"],
                                "FlagBreakfast"   => $values["FlagBreakfast"],
                                "FlagTv"          => $values["FlagTv"],
                                "FlagWifi"        => $values["FlagWifi"],
                                "FlagFullDay"     => $values["FlagFullDay"],
                                "FlagBalcony"     => $values["FlagBalcony"],
                                "FlagFridge"      => $values["FlagFridge"],
                                "FlagHotTub"      => $values["FlagHotTub"],
                                "FlagRoomService" => $values["FlagRoomService"]
                            );
                    }

            }

            $middleRpta = $this->setRpta('ok','success response',$dataSet);

            return response()->json($middleRpta,Response::HTTP_OK);
    }



    public function validateInterferenceDatesNewBooking($hotel,$checkIn,$checkOut,$idRoomOrIdBed){

        

        $checkIn        = Carbon::parse($checkIn)->format('Y-m-d');

        $checkOut       = Carbon::parse($checkOut)->format('Y-m-d');

        $sql = DB::select("SELECT ReserveFromDate,ReserveToDate FROM tblbookings  
              WHERE HotelId = ? AND RoomBedId = ? AND ( (ReserveFromDate>=CURDATE() )OR (CURDATE() BETWEEN ReserveFromDate AND ReserveToDate));" ,array($hotel,$idRoomOrIdBed));
        
        $interference = 0;

        foreach($sql as $values){

            if($values->ReserveToDate > $checkIn AND $values->ReserveFromDate<$checkOut){

                $interference = $interference + 1;
            }
                

        }


        if($interference == 0){

              $activeQ = DB::select("SELECT Active FROM tblrooms WHERE HotelId=? AND Id=?",array($hotel,$idRoomOrIdBed));

              $active = (isset($activeQ[0]->Active))?$activeQ[0]->Active:0;

              if(intval($active) == 0){

                return  $this->setRpta('error','the room or bed is not enabled : '. $idRoomOrIdBed,[]);

              }

            return  $this->setRpta('ok','success validate',[]);

        }else{

            return  $this->setRpta('error','there is no availability for the room Id: '. $idRoomOrIdBed,[]);
        }
        
    }


    protected function validateBeforeInsertBookingPms($request){


        $list = $request->roomsTypeCount;

        $hotel = $request->hotel;

        $checkIn  = Carbon::parse($request->checkIn)->format('Y-m-d');
        
        $checkOut = Carbon::parse($request->checkOut)->format('Y-m-d');

        $groupTypes = [];

        $str = '';

        foreach($list as $values){

            //re validacion unitaria

            //precio base

            $typeRoom = $values["idTypeRoom"];

            $idRoomOrBed = $values["idRoomOrBed"];


            $nameType = Maintenance::getNameTypeRoom($hotel,$typeRoom);

            if(empty($nameType)){

                return $this->setRpta('error','the type of room does not exist for: '.$typeRoom ,[]);
            }


            $priceBase = Maintenance::getPriceBaseTypeRoom($hotel,$typeRoom);

            if(empty($priceBase)){

                return $this->setRpta('error','  this type of room does not have a base price :  '.$nameType ,[]);
            }


            //disponiblidad unitaria x id de cuarto o cama

            

            $middleRpta = $this->validateInterferenceDatesNewBooking($hotel,$checkIn,$checkOut,$idRoomOrIdBed,$typeRoom);

            if($middleRpta["status"]=="error"){

                return $middleRpta;
            }


            $groupTypes[$typeRoom][] = $idRoomOrBed;


        }

        

        foreach($groupTypes as $key=>$list){

            $ids = implode(",",$list);
            
            $strAdultsKids = "0&0";

            $str .= $key.'|'.$ids.'|'.$strAdultsKids.'-' ;
        }

        $insertIds = rtrim($str,"-");

        return $this->setRpta('ok','validate success',$insertIds);
    }


    protected function createBooking(Request $request){


        try {
              
             DB::beginTransaction();

              $data = $request->only('hotel','agent', 'country', 'guestFirstName','guestLastName','guestEmail', 'guestPhone','checkIn', 'checkOut', 'dateArrival','arrivalTime', 'specialRequest','origen', 'roomsTypeCount','temporary','statusBooking');

             $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'agent' => 'required|numeric',
                'country' => 'required|string|max:2',
                'guestFirstName' => 'required|string|max:100',
                'guestLastName' => 'required|string|max:100',
                'guestEmail' => 'required|string|email|max:100',
                'guestPhone' => 'nullable|string|max:20',
                'checkIn' => 'required|date|after_or_equal:today',
                'checkOut' => 'required|date|after:checkIn',
                'dateArrival' => 'required|date|after_or_equal:dateFrom|before_or_equal:checkOut',
                'arrivalTime' => 'nullable|string|max:10',
                'specialRequest' => 'nullable|string|max:250',
                'origen' => 'required|numeric',
                'roomsTypeCount' =>'required',
                'temporary'=>'required|numeric|in:0,1',
                'statusBooking'=>'required|numeric',
                
                
                
            ]);

            if ($validator->fails()) {

                return response()->json($this->setRpta('warning','validator fails',$validator->messages()), 400);
            }


             
                $booking = new BookingController();


                 $holderRpta = $booking->createUserBooking($request);

                 if($holderRpta["status"]=="ok"){

                     $holderId = $holderRpta["data"];

                 }else{

                      return response()->json($holderRpta,400);
                 }


                $middleRpta = $this->validateBeforeInsertBookingPms($request);

               

                if($middleRpta["status"] == "ok"){

                    $idsInsert = $middleRpta["data"] ;

                    $resultId = Booking::create($request,$idsInsert,$holderId);

                    if(isset($resultId[0]->ID) && is_int($resultId[0]->ID)){


                

                        DB::commit();

                        return response()->json($this->setRpta('ok','reservation created successfully ',$resultId[0]->ID),201);

                    }

             
                    DB::rollBack();
           
                    return response()->json($this->setRpta('error','could not generate reservation ',[]),400);

                }


                DB::rollBack();

                return response()->json($middleRpta,400);



           

        } catch (\Exception $e) {
            
            DB::rollBack();

            return response()->json($this->setRpta('error','transact :'.$e->getMessage(),[]), 400);

        }


    

    }

    protected function sendEmail(Request $request){


        $data = $request->only('hotel','agent','guestFirstName','guestLastName','guestEmail');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'agent' => 'required|numeric',
                'guestFirstName' => 'required|string|max:100',
                'guestLastName' => 'required|string|max:100',
                'guestEmail' => 'required|string|email|max:100'
  
                
            ]);

            if ($validator->fails()) {

                return response()->json($this->setRpta('warning','validator fails',$validator->messages()), 400);
            }


            $correo = new CorreoController();

            $middleRpta = $correo->sendEmailBookingSuccess($request);


            return response()->json($middleRpta,400);

    }

    protected function reassignRoom(Request $request){



        try {


            DB::beginTransaction();

            $data = $request->only('hotel','user','type','idRoomOrIdBed','bookingId','newIdRoomOrBed','newIdTypeRoom');

            $validator = Validator::make($data, [
                'hotel' => 'required|numeric',
                'user' => 'required|numeric',
                'type' => 'required|string|max:10',
                'idRoomOrIdBed' =>  'required|numeric',
                'bookingId' =>  'required|numeric', 
                'newIdRoomOrBed' => 'required|numeric',
                'newIdTypeRoom' => 'required|numeric'
                
  
                
            ]);

            if ($validator->fails()) {

                return response()->json($this->setRpta('warning','validator fails',$validator->messages()), 400);
            }


            $hotel =  $request->hotel;

            $checkIn  = Carbon::parse($request->checkIn)->format('Y-m-d');
        
            $checkOut = Carbon::parse($request->checkOut)->format('Y-m-d');

            $newIdRoomOrBed = $request->newIdRoomOrBed;

            $newIdTypeRoom =  $request->newIdTypeRoom;

            $middleRpta = $this->validateInterferenceDatesNewBooking($hotel,$checkIn,$checkOut,$newIdRoomOrBed,$newIdTypeRoom);

            if($middleRpta["status"]=="error"){

                return $middleRpta;
            }

                //para el mismo tipo de room 
            
               Pms::reassignRoom($request);

             
                $action = 'se cambió de cuarto / cama de : '.$idRoomOrIdBed.' a ' .$newIdRoomOrBed;

                $logActivity = new ActivityLogController();

                $logActivity->insertActivity($request,1,$action);
             
                DB::commit();
              
              return response()->json($this->setRpta('ok','updated room or bed booking success',[]),200);


        } catch (\Exception $e) {
            
            DB::rollBack();

            return response()->json($this->setRpta('error','transact :'.$e->getMessage(),[]), 400);

        }


        


    }
    
}