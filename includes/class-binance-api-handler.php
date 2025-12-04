<?php
if (!defined('ABSPATH')) exit;

class Binance_API_Handler {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
    }

    private function log($message) {
        $options = get_option('woocommerce_c2c_crypto_payments_settings', []);
        if (isset($options['enable_debug_log']) && $options['enable_debug_log'] === 'yes') {
            if (class_exists('WC_Logger')) {
                $logger = wc_get_logger();
                $logger->debug($message, ['source' => 'c2c-crypto-payments']);
            }
        }
    }

    public function register_rest_endpoints() {
        register_rest_route('binancepay/v1', '/verify', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_payment_verification'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('binancepay/v1', '/cancel', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_order_cancellation'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('binancepay/v1', '/expire', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_order_expiration'],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('binancepay/v1', '/manual-verify', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_manual_verification'],
            'permission_callback' => '__return_true'
        ]);
    }

    private function get_order_from_slug($slug) {
        if (empty($slug)) return null;
        $orders = wc_get_orders([
            'limit' => 1,
            'meta_key' => '_binance_payment_slug',
            'meta_value' => $slug
        ]);
        return empty($orders) ? null : $orders[0];
    }

    private function verify_order_permission($order, $slug = '') {
        if (!$order) return false;
        
        // Si el usuario está logueado, verificar que sea su orden o sea admin
        if (is_user_logged_in()) {
            return ($order->get_customer_id() === get_current_user_id() || current_user_can('manage_woocommerce'));
        }
        
        // Si no está logueado, verificar que tenga el slug correcto (invitado con link válido)
        if (!empty($slug)) {
            $order_slug = $order->get_meta('_binance_payment_slug');
            return $order_slug === $slug;
        }
        
        return false;
    }

    public function run_automatic_verification($order) {
        if (!$order) return false;

        $this->log('--- Iniciando Verificación Automática ---');
        $this->log('Orden ID: ' . $order->get_id());

        $payment_note = $order->get_meta('_binance_payment_note');
        $amount_to_pay = (float) $order->get_total();

        $this->log('Buscando Monto: ' . $amount_to_pay . ' | Nota de Pago: ' . $payment_note);

        $gateway_options = get_option('woocommerce_binance_pay_c2c_settings');
        $api_key = $gateway_options['binance_api_key'] ?? '';
        $secret_key = $gateway_options['binance_secret_key'] ?? '';
        $on_success_status = $gateway_options['on_success_status'] ?? 'processing';

        $payment_found = false;
        $binance_api_order_id = null;
        $paid_amount = null;
        $paid_currency = null;
        $payer_info = null;

        try {
            $response = $this->binance_api_signed_request($api_key, $secret_key, 'pay/transactions', ['limit' => 100]);

            if ($response && isset($response['data']) && is_array($response['data'])) {
                $this->log('Respuesta API OK. Encontradas ' . count($response['data']) . ' transacciones para revisar.');

                foreach ($response['data'] as $tx) {
                    
                    $tx_amount = isset($tx['amount']) ? (float) $tx['amount'] : 0;
                    $tx_note = isset($tx['note']) ? trim($tx['note']) : '';
                    $tx_currency = isset($tx['currency']) ? strtoupper(trim($tx['currency'])) : '';
                    
                    if ($tx_amount <= 0) {
                        continue;
                    }
                    
                    if (!in_array($tx_currency, ['USDT', 'USDC'])) {
                        continue;
                    }
                    
                    $formatted_order_amount = number_format($amount_to_pay, 2, '.', '');
                    $formatted_tx_amount = number_format($tx_amount, 2, '.', '');
                    
                    if ($formatted_tx_amount !== $formatted_order_amount) {
                        continue;
                    }
                    
                    if ($tx_note != $payment_note) {
                        continue;
                    }
                    
                    $payment_found = true;
                    $binance_api_order_id = $tx['orderId'] ?? 'N/A';
                    $paid_amount = $formatted_tx_amount;
                    $paid_currency = $tx_currency;
                    
                    if (isset($tx['payerInfo'])) {
                        if (isset($tx['payerInfo']['name'])) {
                            $payer_info = $tx['payerInfo']['name'];
                        } elseif (isset($tx['payerInfo']['binanceId'])) {
                            $payer_info = 'ID: ' . $tx['payerInfo']['binanceId'];
                        } else {
                            $payer_info = 'N/A';
                        }
                    } else {
                        $payer_info = 'N/A';
                    }
                    
                    $this->log('¡PAGO VÁLIDO ENCONTRADO!');
                    $this->log('Tx ID: ' . $binance_api_order_id);
                    $this->log('Monto: ' . $paid_amount . ' ' . $paid_currency);
                    $this->log('Pagador: ' . $payer_info);
                    break;
                }
                
            } else {
                 $this->log('Respuesta de la API vacía o inválida.');
            }
        } catch (Exception $e) {
            $this->log('Excepción durante la llamada API: ' . $e->getMessage());
            return false;
        }

        if ($payment_found) {
            $this->log('PAGO ENCONTRADO Y VALIDADO. Completando orden.');
            $order->payment_complete($binance_api_order_id);
            $order->update_status($on_success_status, sprintf(
                __('Pago de Binance verificado automáticamente. Nota: %s', 'c2c-crypto-payments'),
                $payment_note
            ));

            if ($binance_api_order_id !== 'N/A') {
                $order->add_order_note(sprintf(
                    __('ID de Orden de Binance: %s', 'c2c-crypto-payments'),
                    $binance_api_order_id
                ), false);
            }

            $order->update_meta_data('_binance_paid_amount', $paid_amount);
            $order->update_meta_data('_binance_paid_currency', $paid_currency);
            $order->update_meta_data('_binance_payer_info', $payer_info);

            $order->save();
            return true;
        }

        $this->log('PAGO NO ENCONTRADO o no cumple con validaciones estrictas.');
        $this->log('--- Fin Verificación Automática ---');
        return false;
    }

    public function handle_payment_verification(WP_REST_Request $request) {
        $slug = $request->get_param('pay_slug');
        $order = $this->get_order_from_slug($slug);

        if (!$order || !in_array($order->get_status(), ['pending', 'on-hold'])) {
            return new WP_REST_Response(['status' => 'pending'], 200);
        }

        $payment_verified = $this->run_automatic_verification($order);

        if ($payment_verified) {
            $on_success_status = get_option('woocommerce_binance_pay_c2c_settings')['on_success_status'] ?? 'processing';
            return new WP_REST_Response(['status' => 'success', 'order_status' => $on_success_status], 200);
        }

        return new WP_REST_Response(['status' => 'pending'], 200);
    }

    public function handle_manual_verification(WP_REST_Request $request) {
        $slug = $request->get_param('pay_slug');
        $order = $this->get_order_from_slug($slug);
        
        // Verificar permisos usando el slug para invitados
        if (!$this->verify_order_permission($order, $slug)) {
            return new WP_REST_Response(['message' => __('Permiso denegado.', 'c2c-crypto-payments')], 403);
        }

        $files = $request->get_file_params();
        if (empty($files['receipt'])) {
            return new WP_REST_Response(['message' => __('No se proporcionó archivo de comprobante.', 'c2c-crypto-payments')], 400);
        }

        $binance_order_id = sanitize_text_field($request->get_param('binance_order_id'));
        if (empty($binance_order_id)) {
            return new WP_REST_Response(['message' => __('El ID de Orden de Binance es obligatorio.', 'c2c-crypto-payments')], 400);
        }

        $paid_currency = sanitize_text_field($request->get_param('paid_currency'));
        if (empty($paid_currency) || !in_array($paid_currency, ['USDT', 'USDC'])) {
            return new WP_REST_Response(['message' => __('Por favor, selecciona la moneda usada (USDT o USDC).', 'c2c-crypto-payments')], 400);
        }

        $attachment = Binance_File_Handler::handle_upload($files['receipt'], $order->get_id());
        if (is_wp_error($attachment)) {
            return new WP_REST_Response(['message' => $attachment->get_error_message()], 500);
        }

        $order->update_meta_data('_binance_manual_order_id', $binance_order_id);
        $order->update_meta_data('_binance_manual_currency', $paid_currency);
        $order->update_meta_data('_binance_receipt_attachment_id', $attachment);
        $order->update_status('on-hold', __('Cliente subió un comprobante de pago para verificación manual.', 'c2c-crypto-payments'));

        $order_note = sprintf(
            __('Comprobante subido por el cliente. <a href="%s" target="_blank">Ver Comprobante</a>', 'c2c-crypto-payments'),
            wp_get_attachment_url($attachment)
        );
        $order->add_order_note($order_note);
        $order->save();

        do_action('binance_c2c_manual_verification_required_notification', $order->get_id());

        return new WP_REST_Response(['status' => 'success'], 200);
    }

    public function handle_order_expiration(WP_REST_Request $request) {
        $slug = $request->get_param('pay_slug');
        $order = $this->get_order_from_slug($slug);

        if ($order && in_array($order->get_status(), ['pending', 'on-hold'])) {

            $payment_verified = $this->run_automatic_verification($order);

            if ($payment_verified) {
                return new WP_REST_Response(['status' => 'success_on_expire'], 200);
            }

            if (WC()->cart) {
                WC()->cart->empty_cart(true);
                foreach ($order->get_items() as $item) {
                    WC()->cart->add_to_cart($item->get_product_id(), $item->get_quantity());
                }
            }
            $order->update_status('cancelled', __('Orden cancelada debido a expiración del temporizador de pago.', 'c2c-crypto-payments'));
            return new WP_REST_Response(['status' => 'success', 'cart_url' => wc_get_cart_url()], 200);
        }
        return new WP_REST_Response(['status' => 'error', 'message' => __('La orden no puede ser expirada.', 'c2c-crypto-payments')], 400);
    }

    public function handle_order_cancellation(WP_REST_Request $request) {
        $slug = $request->get_param('pay_slug');
        $order = $this->get_order_from_slug($slug);

        if (!$this->verify_order_permission($order, $slug) || !in_array($order->get_status(), ['pending', 'on-hold'])) {
            return new WP_REST_Response(['status' => 'error', 'message' => __('No se puede cancelar la orden.', 'c2c-crypto-payments')], 400);
        }

        if (WC()->cart) {
            WC()->cart->empty_cart(true);
            foreach ($order->get_items() as $item) {
                WC()->cart->add_to_cart($item->get_product_id(), $item->get_quantity());
            }
        }
        $order->update_status('cancelled', __('Orden cancelada por el cliente desde la página de pago.', 'c2c-crypto-payments'));
        return new WP_REST_Response(['status' => 'success', 'cart_url' => wc_get_cart_url()], 200);
    }

    private function binance_api_signed_request($api_key, $secret_key, $endpoint, $params = []) {
        if (empty($api_key) || empty($secret_key)) {
            $this->log('Llamada API fallida: API Key o Secret Key están vacías.');
            return null;
        }

        $base_url = 'https://api.binance.com';
        $url_path = '/sapi/v1/' . $endpoint;
        $params['timestamp'] = sprintf('%.0f', microtime(true) * 1000);
        $params['recvWindow'] = 10000;

        $query_string = http_build_query($params);
        $signature = hash_hmac('sha256', $query_string, $secret_key);
        $url = $base_url . $url_path . '?' . $query_string . '&signature=' . $signature;

        $this->log('Realizando llamada API a: ' . $url_path);

        $response = wp_remote_get($url, [
            'headers' => ['X-MBX-APIKEY' => $api_key],
            'timeout' => 20
        ]);

        if (is_wp_error($response)) {
            $this->log('Llamada API fallida (WP_Error): ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['code']) && $data['code'] != 0 && $data['code'] != '000000') {
            $this->log('La API de Binance devolvió un error. Código: ' . $data['code'] . ' - Mensaje: ' . $data['msg']);
            return null;
        }

        $this->log('Llamada API exitosa.');
        return $data;
    }
}