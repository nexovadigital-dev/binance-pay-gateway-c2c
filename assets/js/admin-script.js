jQuery(function($){
    if ($('#binance-verification-box').length) {

        $(document).on('click', '#binance-accept-btn, #binance-reject-btn', function() {
            var button = $(this);
            var action = button.is('#binance-accept-btn') ? 'binance_accept_payment' : 'binance_reject_payment';
            var orderId = button.data('order-id');

            var nonce = binance_admin_ajax.nonce;
            var i18n = binance_admin_ajax.i18n;

            $('#binance-verification-box button').prop('disabled', true);
            $('#binance-verification-spinner').show();
            $('#binance-verification-status').text(i18n.processing);

            $.post(binance_admin_ajax.ajax_url, {
                action: action,
                order_id: orderId,
                _ajax_nonce: nonce
            }).done(function(response) {
                if (response.success) {
                    $('#binance-verification-status').css('color', 'green').text(response.data.message);
                    window.location.reload();
                } else {
                    var errorMessage = i18n.error.replace('%s', response.data.message);
                    $('#binance-verification-status').css('color', 'red').text(errorMessage);
                    $('#binance-verification-box button').prop('disabled', false);
                }
            }).fail(function() {
                $('#binance-verification-status').css('color', 'red').text(i18n.unknownError);
                $('#binance-verification-box button').prop('disabled', false);
            }).always(function() {
                $('#binance-verification-spinner').hide();
            });
        });
    }
});
