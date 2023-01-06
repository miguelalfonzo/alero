<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <!-- Replace "test" with your own sandbox Business account app client ID -->
  <script src="https://www.paypal.com/sdk/js?client-id=AQf25Qh1urWeMTncQFT0d9f3yYNCpAULd7KJ7oPOlXzMEBMgbWTd_cJbGEdTx_7wSRZZGzUYbCqrdV2g&currency=USD"></script>
  <!-- Set up a container element for the button -->




   <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>


  
  <div id="paypal-button-container"></div>
  <script>
    var amount = '77.44';
    var token='37473473434';
    var email='json@gmail.com';


    paypal.Buttons({
      style:{

        color:'blue',
        shape:'pill',
        label:'pay'
      },
      // Sets up the transaction when a payment button is clicked
      createOrder: (data, actions) => {
        return actions.order.create({
          purchase_units: [{
            amount: {
              value: amount // Can also reference a variable or function
            }
          }]
        });
      },onCancel(){


        alert('pago cancelado')
      },
      // Finalize the transaction after payer approval
      onApprove: (data, actions) => {
        return actions.order.capture().then(function(orderData) {
          // Successful capture! For dev/demo purposes:
          console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));
          const transaction = orderData.purchase_units[0].payments.captures[0];


          alert(`Transaction ${transaction.status}: ${transaction.id}\n\nSee console for all available details`);

          if(transaction.status =='COMPLETED'){

            updateStatus(transaction.id);

          }
          // When ready to go live, remove the alert and show a success message within this page. For example:
          // const element = document.getElementById('paypal-button-container');
          // element.innerHTML = '<h3>Thank you for your payment!</h3>';
          // Or go to another URL:  actions.redirect('thank_you.html');
        });
      }
    }).render('#paypal-button-container');


    function updateStatus(reference){



        var data = { 
              hotel:1,
              bookingId:60,
              user:1,
              typePayment:2,
              email:email,
              applyIgv:0,
              coupon:"",
              amount:amount,
              token:token,
              reference:reference
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

    }
  </script>
</body>
</html>