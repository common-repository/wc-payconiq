jQuery( function ( $ ) {
    "use strict";

    if ( typeof wc_add_to_cart_params === 'undefined' ) {
        return false;
    }

    /**
     * Order Id
     * @type {string}
    */
    var order_id = $( '#order_id' ).val();
    /**
     * Ajax url
     * @type {string}
    */
    var url = document.location.protocol + "//" + document.location.hostname + wc_add_to_cart_params.ajax_url;

    checkPayment(url, order_id, 'start');

    /**
     * Every 5 seconds check for order status
     */
    setInterval( function () {
        checkPayment(url, order_id, 'check');
    }, 5000 );

    function checkPayment(url, order_id, type) {
        $.ajax( {
            url: url,
            type: 'POST',
            data: {
                action: 'payconiq_check_order_status',
                order_id: order_id,
                type: type
            },
            success: function ( data ) {
                /**
                 * if order status is completed or processing then redirect page to thank you page
                 */
                if ( data.message !== '' ) {
                    $('.payconiq-container').html('Payment successfull.')
                    window.location.href = data.message;
                }
            }
        } );
    }

} );