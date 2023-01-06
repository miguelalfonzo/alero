<?php
namespace App\Http\Controllers\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Maintenance;


class MaintenanceController extends Controller
{
   

  


    protected function options(Request $request)
    {
        


        $data = $request->only('type');

        $validator = Validator::make($data, [
            'type' => 'required|numeric',
           
            
        ]);
        
        if ($validator->fails()) {

            $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

            return response()->json($middleRpta, 400);
        }
      
    
        $list = Maintenance::options($request);

        $middleRpta = $this->setRpta('ok','success list',$list);

        return response()->json($middleRpta,Response::HTTP_OK);

    }
    
    protected function seekerProducts(Request $request){

        $data = $request->only('hotel','category','term');

        $validator = Validator::make($data, [
            'hotel' => 'required|numeric',
            'category' => 'required|numeric',
            'term'=>'nullable|max:250'
           
            
        ]);
        
        if ($validator->fails()) {

            $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

            return response()->json($middleRpta, 400);
        }
      
        
       
        $list = Maintenance::seekerProducts($request);

        $middleRpta = $this->setRpta('ok','success list',$list);

        return response()->json($middleRpta,Response::HTTP_OK);

    }
   

   protected function getIgv(Request $request)
    {
        


        $data = $request->only('hotel');

        $validator = Validator::make($data, [
            'hotel' => 'required|numeric',
           
            
        ]);
        
        if ($validator->fails()) {

            $middleRpta = $this->setRpta('warning','validator fails',$validator->messages());

            return response()->json($middleRpta, 400);
        }
      
    
        $list = Maintenance::getIgv($request->hotel);

        $middleRpta = $this->setRpta('ok','success list',$list);

        return response()->json($middleRpta,Response::HTTP_OK);

    }
   
    
}