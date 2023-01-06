
<!DOCTYPE html>
<html>
<head>
	<title>Integración de Culqui</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

	<!-- Incluyendo Culqi Checkout -->
	<script type="text/javascript" src="https://checkout.culqi.com/js/v3"></script>

	<!-- Google Fonts -->
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,400italic,600,600italic,700,700italic,800' rel='stylesheet' type='text/css'>
	<link href='https://fonts.googleapis.com/css?family=Poppins:400,300,500,600,700' rel='stylesheet' type='text/css'>
	<link href="https://fonts.googleapis.com/css?family=Kaushan+Script&amp;subset=latin-ext" rel="stylesheet">
	
</head>
<body>


		<button type="submit" class="button btn-proceed-checkout" id="buyButton" name="prePago" title="Procesar con el Pago"><span>Procesar con el Pago</span></button>




   <!--  <form>
  <div>
    <label>
      <span>Correo Electrónico</span>
      <input type="text" size="50" data-culqi="card[email]" id="card[email]">
    </label>
  </div>
  <div>
    <label>
      <span>Número de tarjeta</span>
      <input type="text" size="20" data-culqi="card[number]" id="card[number]">
    </label>
  </div>
  <div>
    <label>
      <span>CVV</span>
      <input type="text" size="4" data-culqi="card[cvv]" id="card[cvv]">
    </label>
  </div>
  <div>
    <label>
      <span>Fecha expiración (MM/YYYY)</span>
      <input size="2" data-culqi="card[exp_month]" id="card[exp_month]">
      <span>/</span>
      <input size="4" data-culqi="card[exp_year]" id="card[exp_year]">
    </label>
  </div>
</form>
<button id="btn_pagar">Pagar</button> -->



  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

  <script>
    Culqi.publicKey = 'pk_test_lRVmqhtxqd0Agn0v';

   	

    let amount = 32.5;

    let amountCulqui = amount*100 //formato culqui 

    

    let email = 'malfonzo@yahoo.com';

    Culqi.settings({
      title: 'Tienda en Linea - ohotels',
      currency: 'USD',
      description: 'Pago reserva ohotels',
      amount: amountCulqui,
     
    });

    //invocando modal de culqui
    $('#buyButton').on('click', function(e) {
       
        Culqi.open();
        e.preventDefault();
    });


    //personalizado
  //   const btn_pagar = document.getElementById('btn_pagar');

  //     btn_pagar.addEventListener('click', function (e) {
     
  //     Culqi.createToken();
  //     e.preventDefault();
  // })
   

    function culqi() {
      if (Culqi.token) { // ¡Objeto Token creado exitosamente!
        var token = Culqi.token.id;
       

        var data = { 
              hotel:1,
              bookingId:60,
              user:1,
              typePayment:7,
              email:email,
              applyIgv:0,
              coupon:"",
              amount:amount,
              token:token
        };

        //var url = "http://localhost/ohotels/public/api/v1/payments/culquiPayment";
        var url = "http://localhost/ohotels/public/api/v1/booking/confirm";

        $.ajax({
            type:"PUT", 
            url:url,
             dataType: 'json', 
            data:data,
              success:function(datos){ 

                console.log(datos)

                alert(datos.description)
                  

                 
              },
    
          })
        
      } else { 

        console.log(Culqi.error);
        alert(Culqi.error.user_message);
      }
    };
  </script>
</body>
</html>