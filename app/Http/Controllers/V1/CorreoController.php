<?php
namespace App\Http\Controllers\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Maintenance;
use Swift_Mailer;
use Swift_SmtpTransport;
use Swift_Message;

use App\Models\ActivityLog;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;


class CorreoController extends Controller
{
   

  public function sendEmailBookingSuccess($request){
        

        
        $mail = $this->mailOhotels();

        $host         = $mail['HOST'];
        $puerto       = $mail['PUERTO'];
        $encriptacion = $mail['ENCRIPTACION'];
        $from         = $mail['CORREO'];
        $password     = $mail['PASSWORD'];

        $destinatarios = $request->guestEmail;
        $hotel = $request->hotel;
        $user  = $request->agent;
        
        $bccEmails = Maintenance::getMailUserLogin($user);

        $header = 'Estimado cliente : '.$request->guestFirstName.' '.$request->guestLastName;
       

        $transport = (new Swift_SmtpTransport($host, $puerto, $encriptacion))
              ->setUsername($from)
              ->setPassword($password);

        $mailer = new Swift_Mailer($transport);


        $contenidoImagen = file_get_contents('http://localhost/ohotels/public/typeRoomFiles/LOGO.png');
        $contenidoImagen = base64_encode($contenidoImagen);
        $contenidoImagen = "data:image/png;base64,".$contenidoImagen;

    
        $mensaje=array( 

            'header' => $header ,
            'body' => 'Se creó exitosamente su reserva para las fechas : ' .$request->checkIn .' a '.$request->checkOut,
            'footer' => 'Sistema automatizado de envio de correos ',
            'logo'=>$contenidoImagen,
            'sign'=>''

          );

          $body = array('information'=> $mensaje);

          

          if( !config("global.production") ){

             $destinatarios = config("global.soporte");

        }

       
          $asunto = 'successful create reservation';

          $message   = (new Swift_Message($asunto))
              ->setFrom($from)
              ->setTo($destinatarios)
              ->addBcc($bccEmails)
              ->setBody(view('emails.SuccessBooking', $body)->render(),'text/html');
             
        

        if($mailer->send($message)>0){

             
           ActivityLog::insertLogEmail(1,$hotel,$asunto,$mensaje,$mail,$user,$destinatarios,$bccEmails);

           return $this->setRpta("ok","Se envió el correo de manera satisfactoria",[]);

        }else{

            ActivityLog::insertLogEmail(0,$hotel,$asunto,$mensaje,$mail,$user,$destinatarios,$bccEmails);

          
          return $this->setRpta("error","No se pudo enviar el correo",[]);

        }



   }



   
   
    
}