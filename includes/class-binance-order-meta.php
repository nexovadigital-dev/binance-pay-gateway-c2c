<?php
if (!defined('ABSPATH')) exit;

class Binance_Order_Meta {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_verification_meta_box'], 10, 2);

        add_action('wp_ajax_binance_accept_payment', [$this, 'handle_accept_payment']);
        add_action('wp_ajax_binance_reject_payment', [$this, 'handle_reject_payment']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

        add_filter('get_user_option_meta-box-order_shop_order', [$this, 'prioritize_meta_box']);
        add_filter('get_user_option_meta-box-order_woocommerce_page_wc-orders', [$this, 'prioritize_meta_box']);
    }

    public function prioritize_meta_box($order_of_boxes) {
        $our_box = 'binance_payment_verification';
        $context = 'side';

        if (empty($order_of_boxes[$context]) || !is_array($order_of_boxes[$context])) {
            return $order_of_boxes;
        }

        $other_boxes = array_filter($order_of_boxes[$context], function($box) use ($our_box) {
            return $box !== $our_box;
        });

        $order_of_boxes[$context] = array_merge([$our_box], $other_boxes);

        return $order_of_boxes;
    }


    public function add_verification_meta_box($post_type, $post_or_order_object) {
        $screen_id = '';
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            $screen_id = $screen->id;
        }

        $allowed_screens = ['shop_order', 'woocommerce_page_wc-orders'];

        if (in_array($screen_id, $allowed_screens)) {
            add_meta_box(
                'binance_payment_verification',
                __('Verificación de Pago Binance', 'c2c-crypto-payments'),
                [$this, 'render_meta_box_content'],
                $screen_id,
                'side',
                'high'
            );
        }
    }

    public function render_meta_box_content($post_or_order_object) {
        $order = null;
        if ($post_or_order_object instanceof WC_Order) {
            $order = $post_or_order_object;
        } elseif ($post_or_order_object instanceof WP_Post) {
            $order = wc_get_order($post_or_order_object->ID);
        }

        if (!$order) {
            echo '<p>' . __('No se pudo cargar la información de la orden.', 'c2c-crypto-payments') . '</p>';
            return;
        }

        if ($order->get_payment_method() !== 'binance_pay_c2c') {
            echo '<p>' . __('No es un pago Cripto C2C.', 'c2c-crypto-payments') . '</p>';
            return;
        }

        $receipt_url = $order->get_meta('_binance_receipt_file_url');
        $transaction_id = $order->get_transaction_id();
        $manual_order_id = $order->get_meta('_binance_manual_order_id');

        echo '<div id="binance-verification-box">';

        if ($receipt_url) {
            $manual_currency = $order->get_meta('_binance_manual_currency');
            ?>
            <p><strong><?php _e('Nota de Pago Binance Esperada:', 'c2c-crypto-payments'); ?></strong><br><?php echo esc_html($order->get_meta('_binance_payment_note')); ?></p>
            <?php if ($manual_order_id) : ?>
                <p><strong><?php _e('ID de Orden (enviado por cliente):', 'c2c-crypto-payments'); ?></strong><br><?php echo esc_html($manual_order_id); ?></p>
            <?php endif; ?>
            <?php if ($manual_currency) : ?>
                <p><strong><?php _e('Moneda (declarada por cliente):', 'c2c-crypto-payments'); ?></strong><br><?php echo esc_html($manual_currency); ?></p>
            <?php endif; ?>
            <p><strong><?php _e('Monto Esperado:', 'c2c-crypto-payments'); ?></strong><br><?php echo wp_kses_post($order->get_formatted_order_total()); ?></p>
            <p><strong><?php _e('Adjunto:', 'c2c-crypto-payments'); ?></strong><br><a href="<?php echo esc_url($receipt_url); ?>" target="_blank" class="button"><?php _e('Ver Comprobante', 'c2c-crypto-payments'); ?></a></p>
            <hr>
            <?php if ($order->get_status() === 'on-hold') : ?>
            <p><strong><?php _e('Validación:', 'c2c-crypto-payments'); ?></strong></p>
            <p>
                <button type="button" class="button button-primary" id="binance-accept-btn" data-order-id="<?php echo $order->get_id(); ?>"><?php _e('Aceptar', 'c2c-crypto-payments'); ?></button>
                <button type="button" class="button" id="binance-reject-btn" data-order-id="<?php echo $order->get_id(); ?>"><?php _e('Rechazar', 'c2c-crypto-payments'); ?></button>
            </p>
            <div id="binance-verification-spinner" class="spinner" style="float:none; width:auto; height:auto; padding: 10px 0; background-position: center; display: none;"></div>
            <p id="binance-verification-status" style="font-weight:bold;"></p>
            <?php else : ?>
            <p style="font-weight:bold; color: #2271b1;"><?php _e('Este pago fue revisado manualmente.', 'c2c-crypto-payments'); ?></p>
            <?php endif; ?>
            <?php
        } elseif ($transaction_id) {
            $paid_amount = $order->get_meta('_binance_paid_amount');
            $paid_currency = $order->get_meta('_binance_paid_currency');
            $payer_info = $order->get_meta('_binance_payer_info');
            
            echo '<p style="color: #2271b1; font-weight:bold;">' . __('✓ Pago Verificado Automáticamente', 'c2c-crypto-payments') . '</p>';
            echo '<hr style="margin: 10px 0;">';
            
            if ($paid_amount && $paid_currency && $paid_currency !== 'N/A') {
                echo '<p><strong>' . __('Monto Recibido:', 'c2c-crypto-payments') . '</strong><br>';
                echo '<span style="font-size: 1.1em; color: #0ECB81; font-weight:600;">' . esc_html($paid_amount) . ' ' . esc_html($paid_currency) . '</span></p>';
            }
            
            if ($payer_info && $payer_info !== 'N/A') {
                echo '<p><strong>' . __('Usuario Binance:', 'c2c-crypto-payments') . '</strong><br>' . esc_html($payer_info) . '</p>';
            }
            
            echo '<p><strong>' . __('ID de Orden Binance:', 'c2c-crypto-payments') . '</strong><br>' . esc_html($transaction_id) . '</p>';
            echo '<p><strong>' . __('Nota de Pago Usada:', 'c2c-crypto-payments') . '</strong><br>' . esc_html($order->get_meta('_binance_payment_note')) . '</p>';
        } else {
            echo '<p>' . __('Esperando pago del cliente o subida de comprobante manual.', 'c2c-crypto-payments') . '</p>';
        }
        echo '</div>';
    }

    public function enqueue_scripts($hook) {
        $screen_id = '';
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            $screen_id = $screen->id;
        }

        if (!in_array($screen_id, ['shop_order', 'woocommerce_page_wc-orders'])) {
            return;
        }

        wp_enqueue_script('binance-admin-script', plugin_dir_url(__FILE__) . '../assets/js/admin-script.js', ['jquery'], '2.9.8', true);

        wp_localize_script('binance-admin-script', 'binance_admin_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('binance_payment_nonce'),
            'i18n'     => [
                'processing'   => __('Procesando...', 'c2c-crypto-payments'),
                'error'        => __('Error: %s', 'c2c-crypto-payments'),
                'unknownError' => __('Ocurrió un error desconocido.', 'c2c-crypto-payments')
            ]
        ]);
    }

    public function handle_accept_payment() {
        check_ajax_referer('binance_payment_nonce');
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => __('Permiso denegado.', 'c2c-crypto-payments')]);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $order = wc_get_order($order_id);

        if ($order) {
            $gateway_options = get_option('woocommerce_binance_pay_c2c_settings');
            $on_success_status = $gateway_options['on_success_status'] ?? 'processing';
            $order->update_status($on_success_status, __('Pago manual aceptado por el administrador.', 'c2c-crypto-payments'));
            wp_send_json_success(['message' => __('Pago aceptado.', 'c2c-crypto-payments')]);
        } else {
            wp_send_json_error(['message' => __('Orden no encontrada.', 'c2c-crypto-payments')]);
        }
    }

    public function handle_reject_payment() {
        check_ajax_referer('binance_payment_nonce');
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => __('Permiso denegado.', 'c2c-crypto-payments')]);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $order = wc_get_order($order_id);

        if ($order) {
            $order->update_status('cancelled', __('Pago manual rechazado por el administrador.', 'c2c-crypto-payments'));
            wp_send_json_success(['message' => __('Pago rechazado.', 'c2c-crypto-payments')]);
        } else {
            wp_send_json_error(['message' => __('Orden no encontrada.', 'c2c-crypto-payments')]);
        }
    }
}