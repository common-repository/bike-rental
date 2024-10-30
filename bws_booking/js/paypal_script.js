(function($) {
    var ajaxurl = bws_bkng_paypal.ajaxurl;
    paypal.Buttons({
        // Set up the transaction
        createOrder: function(data, actions) {
            var order_total = $('.bws_bkng_order_total').text();
            var user_email = $('input[name="bkng_billing_data[user_email]"]').val();
            var user_age = $('input[name="bkng_billing_data[user_age]"]').val();
            var user_phone = $('input[name="bkng_billing_data[user_phone]"]').val();
            var user_firstname = $('input[name="bkng_billing_data[user_firstname]"]').val();
            var user_lastname = $('input[name="bkng_billing_data[user_lastname]"]').val();
            var reg_phone = /^[\+]{0,1}\d{0,1}[\s\-]{0,1}\d{0,1}[\s\-]{0,1}[[\(\s-]{0,2}\d{1,4}[\)\s-]{0,2}]{0,1}\d{1,3}[\s-]{0,1}\d{1,3}[\s-]{0,1}\d{1,3}$/i;
            var found = user_phone.match(reg_phone);
            var reg_email = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;

            if( user_email != '' &&
                user_phone != '' &&
                user_firstname != '' &&
                user_age  >= '18' &&
                user_lastname != '' &&
                order_total != '' &&
                found != null &&
                reg_email.test( user_email ) != false
            ) {
                $('.message_paypal').html( ' ' );
                return actions.order.create({
                    payer: {
                        email_address: user_email
                    },
                    purchase_units: [{
                        amount: {
                            currency_code: bws_bkng_paypal.currency_code,
                            value: order_total
                        }
                    }]
                });
            } else {
                $('.message_paypal').html( bws_bkng_paypal.message_input );
                $('.message_paypal').css('color', 'red');
            }
            
        },
        // Finalize the transaction
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                var order_data = {
                    action: "bws_pay_pal_orderid",
                    orderid: data.orderID,
                    payerid: data.payerID
                };
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: order_data,
                    dataType: "text",
                    success: function( success_data ) {
                        var data = JSON.parse(success_data);
                        if (  data.error ){
                            $('.message_paypal').html( data.error );
                        } else {
                            if ( data.status == 'COMPLETED' ){
                                $('.message_paypal').html( bws_bkng_paypal.message );
                                setTimeout(function(){
                                    $(".bws_bkng_checkout_form").find(".button-primary").click(); // if you want
                                }, 5000);
                            }
                            $('#bkng_payment_id').val(data.id);
                            $('#bkng_payment_status').val(data.status);
                            $('#bkng_payment_data').val(data.time);
                        }
                    }
                });
            });
        }
    }).render('#paypal-button-container');
})(jQuery);