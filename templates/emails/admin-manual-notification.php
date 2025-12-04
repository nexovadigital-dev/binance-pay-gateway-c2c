<?php
if (!defined('ABSPATH')) exit;

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p><?php
    printf(
        esc_html__('La verificación automática de Binance C2C falló para la orden #%s. Como resultado, el cliente ha adjuntado el recibo de pago manualmente.', 'c2c-crypto-payments'),
        esc_html($order->get_order_number())
    );
?></p>

<p><?php
    esc_html_e('Por favor, revisa cuidadosamente el monto recibido, la fecha y el ID de Orden de Binance (si el cliente lo proporcionó) antes de aprobar este pago.', 'c2c-crypto-payments');
?></p>

<p><?php
    esc_html_e('El comprobante de pago está adjunto a este correo. También puedes verlo en la página de detalles de la orden.', 'c2c-crypto-payments');
?></p>

<p style="margin: 25px 0 35px; text-align: center;">
    <a href="<?php echo esc_url($order->get_edit_order_url()); ?>"
       style="background-color: #0073aa; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
        <?php esc_html_e('Gestionar Orden', 'c2c-crypto-payments'); ?>
    </a>
</p>

<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

do_action('woocommerce_email_footer', $email);
