<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\V1\ProductsController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\BookingController;
use App\Http\Controllers\V1\MaintenanceController;
use App\Http\Controllers\V1\PaymentsController;
use App\Http\Controllers\V1\PmsController;
use Carbon\Carbon;


Route::prefix('v1')->group(function () {

	

	  Route::group(['prefix' => 'payments'], function () {

        Route::get('time', function(){ 

        	return Carbon::now()->format('Y-m-d H:i:s');

        });

	 	Route::get('culqui', [PaymentsController::class, 'culqui']);

		Route::post('culquiPayment', [PaymentsController::class, 'culquiPayment']);

		Route::get('paypal', [PaymentsController::class, 'paypal']);

		Route::get('paypalPayment', [PaymentsController::class, 'paypalPayment']);
    });
	

	 Route::group(['prefix' => 'booking'], function () {

	 	Route::get('search', [BookingController::class, 'search']);

	 	Route::get('viewAll', [BookingController::class, 'viewAll']);

	 	Route::get('coupon', [BookingController::class, 'coupon']);

	 	Route::post('create', [BookingController::class, 'create']);

	 	Route::put('confirm', [BookingController::class, 'confirm']);

	 	Route::delete('delete', [BookingController::class, 'delete']);
		
    });


	  Route::group(['prefix' => 'maintenance'], function () {

        
	 	Route::get('options', [MaintenanceController::class, 'options']);

	 	Route::get('getIgv', [MaintenanceController::class, 'getIgv']);

	 	Route::prefix('seeker')->group(function () {
	
	   			Route::get('products', [MaintenanceController::class, 'seekerProducts']);

	
			});

		
    });



	   Route::group(['prefix' => 'pms'], function () {

	   		Route::prefix('dashboard')->group(function () {
	
	   			Route::get('today', [PmsController::class, 'dashboardToday']);

	 			Route::get('indicators', [PmsController::class, 'dashboardIndicators']);

	
			});



	   		

			Route::prefix('booking')->group(function () {
	
	   			

	 			
	 			Route::prefix('edit')->group(function () {
	
	   				Route::get('suggestNewHistory', [PmsController::class, 'suggestNewHistory']);

	   				Route::post('saveEditDates', [PmsController::class, 'saveEditDates']);
	 			
	   				Route::post('savePayments', [PmsController::class, 'savePayments']);

	   				Route::post('saveProducts', [PmsController::class, 'saveProducts']);

	   				Route::post('saveNotes', [PmsController::class, 'saveNotes']);

	   				Route::put('confirmDiscount', [PmsController::class, 'confirmDiscount']);

	   				Route::get('getHistoryPrices', [PmsController::class, 'getHistoryPrices']);

	   				Route::get('getHistoryProducts', [PmsController::class, 'getHistoryProducts']);

	   				Route::get('getHistoryPayments', [PmsController::class, 'getHistoryPayments']);

	   				Route::get('getHistoryNotes', [PmsController::class, 'getHistoryNotes']);

	   				Route::get('getInfoDetail', [PmsController::class, 'getInfoDetail']);

	   				Route::put('changeToIgv', [PmsController::class, 'changeToIgv']);

	   				Route::put('assignGuest', [PmsController::class, 'assignGuest']);

	   				Route::put('inactiveItemProduct', [PmsController::class, 'inactiveItemProduct']);

	   				Route::put('updateState', [PmsController::class, 'updateState']);

	   				//para el mismo tipo privada o publica
	   				
	   				Route::post('reassignRoom', [PmsController::class, 'reassignRoom']);

	   				
	
				});


				Route::prefix('create')->group(function () {
	

					Route::get('viewAvailability', [PmsController::class, 'viewAvailability']);

					Route::post('booking', [PmsController::class, 'createBooking']);

					Route::post('sendEmail', [PmsController::class, 'sendEmail']);

					
				});

	
			});
       
	 	
		
    	});

	 

	



    Route::post('login', [AuthController::class, 'authenticate']);
    
    Route::post('register', [AuthController::class, 'register']);
    

    Route::group(['middleware' => ['jwt.verify']], function() {
      
      //verificación 
        
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('get-user', [AuthController::class, 'getUser']);
      
    });
});