<?php
if (!defined('ABSPATH')) exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

printf(esc_html__('Un cliente ha subido un comprobante de pago manual para la orden #%s. El pago ahora requiere tu aprobación.', 'c2c-crypto-payments'), esc_html($order->get_order_number()));
echo "\n\n";

echo esc_html__('El comprobante de pago debe estar adjunto a este correo. También puedes verlo en la página de detalles de la orden.', 'c2c-crypto-payments');
echo "\n\n";

echo esc_html__('Gestionar Orden:', 'c2c-crypto-payments') . ' ' . esc_url($order->get_edit_order_url());
echo "\n\n";

do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

echo "\n----------------------------------------\n\n";

do_action('woocommerce_email_footer', $email);
