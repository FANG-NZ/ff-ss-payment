/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function(){
    //To define the paypal execute url
    var EXECUTE_URL = "/ff-payment/complete/PayPal",
        temp_execute_url;
    
    // TODO
    // init paypal
    paypal.Button.render({

        env: 'sandbox', // sandbox | production

        // Show the buyer a 'Pay Now' button in the checkout flow
        commit: true,
    
        // payment() is called when the button is clicked
        payment: function() {

            // Set up a url on your server to create the payment
            var CREATE_URL = '/ff-payment/capture/PayPal';

            ///
            /// TODO
            /// To setup data we will send back to server
            ///
            var data = {
                'amount'   : 16.99,
                'currency' : "NZD",
                'reference': "This is my test for PayPal" 
            };
            
            // Make a call to your server to set up the payment
            return paypal.request.post(CREATE_URL, data)
                .then(function(res) {
                    //To setup temp execute url
                    temp_execute_url = EXECUTE_URL + "/" + res.ffPaymentID;
                    
                    return res.paymentID;
                });
        },

        // onAuthorize() is called when the buyer approves the payment
        onAuthorize: function(data, actions) {

            // Set up the data you need to pass to your server
            // data.returnUrl
            var data = {
                paymentID: data.paymentID,
                payerID:   data.payerID
            };

            // Make a call to your server to execute the payment
            return paypal.request.post(temp_execute_url, data)
                .then(function (res) {
                    
                    if(res.State){
                        //To setup callback url
                        window.location.href = "/home/callback/" + res.FFPaymentID;
                    }
                    else{
                        alert('ERROR! Please try again.');
                    }
                    
                });
        }

    }, '#paypal-button');
    
})();




