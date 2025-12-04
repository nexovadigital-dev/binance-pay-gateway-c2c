jQuery(function($) {
    'use strict';

    if (typeof c2c_admin_i18n === 'undefined') {
        return;
    }

    var field_id = 'woocommerce_binance_pay_c2c_payment_qr_code';

    var $button = $('<button type="button" class="button button-secondary" id="c2c-upload-qr-btn" style="margin-left: 10px;">' + c2c_admin_i18n.upload_qr + '</button>');

    $('#' + field_id).after($button);

    $button.on('click', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: c2c_admin_i18n.upload_qr,
            button: {
                text: c2c_admin_i18n.use_qr
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();

            var url = attachment.url;
            if (url.startsWith('http://')) {
                url = url.replace(/^http:\/\//i, 'https://');
            }

            $('#' + field_id).val(url);
        });

        frame.open();
    });
});
