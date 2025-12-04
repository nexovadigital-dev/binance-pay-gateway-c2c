<?php
if (!defined('ABSPATH')) exit;

class Binance_License_Handler {

    private static $api_url = 'https://script.google.com/macros/s/AKfycbxygZUReie2dlzqdaEav9gjPkm928NdmYdb3l27MOtQxkXStCC9suzfRVrNqkAcdf7UDg/exec';

    /**
     */
    private static function log($message) {
        $options = get_option('woocommerce_c2c_crypto_payments_settings');
        if (isset($options['enable_debug_log']) && 'yes' === $options['enable_debug_log']) {
            if (class_exists('WC_Logger')) {
                $logger = wc_get_logger();
                $context = ['source' => 'c2c_crypto_payments'];
                $logger->debug($message, $context);
            }
        }
    }

    /**
     *
     * @param string|null $key_to_check La clave para validar (opcional).
     * @return array ['status' => 'active'|'inactive', 'message' => '...']
     */
    public static function check_license($key_to_check = null) {

        self::log('--- Inicio de Verificación de Licencia ---');
        $key = $key_to_check;

        if (null === $key) {
            $options = get_option('woocommerce_c2c_crypto_payments_settings', []);
            $key = $options['license_key'] ?? '';
            self::log('Clave leída de Opciones: ' . substr($key, 0, 5) . '...');
        } else {
            self::log('Clave recibida como parámetro: ' . substr($key, 0, 5) . '...');
        }

        $default_response = [
            'status'   => 'inactive',
            'licensee' => 'N/A',
            'domain'   => 'N/A',
            'expires'  => 'N/A',
            'message'  => __('Clave de licencia no proporcionada.', 'c2c-crypto-payments')
        ];

        if (empty($key)) {
            self::log('Error: Clave de licencia no proporcionada.');
            self::update_license_data($default_response);
            return $default_response;
        }

        $params_array = [
            'license_key' => $key,
            'domain'      => site_url(),
            'product_id'  => 'binance-c2c-autopayments'
        ];

        $url_with_params = add_query_arg($params_array, self::$api_url);

        self::log('Enviando petición GET a Google API. URL: ' . $url_with_params);

        $response = wp_remote_get($url_with_params, ['timeout' => 15]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            self::log('Error de WP_Error: ' . $error_message);
            $default_response['message'] = $error_message;
            self::update_license_data($default_response);
            return $default_response;
        }

        $body = wp_remote_retrieve_body($response);
        self::log('Respuesta recibida (cuerpo en crudo): ' . $body);

        $data = json_decode($body, true);

        if (!$data || !isset($data['success'])) {
            self::log('Error: Falla al decodificar JSON o "success" no está presente. Respuesta: ' . $body);
            $default_response['message'] = __('Respuesta desconocida del servidor de licencias.', 'c2c-crypto-payments');
            self::update_license_data($default_response);
            return $default_response;
        }

        $log_success = $data['success'] ? 'true' : 'false';
        $log_msg = $data['message'] ?? 'N/A';
        self::log('JSON decodificado con éxito. Success: ' . $log_success . ', Message: ' . $log_msg);

        if ($data['success'] === true && $data['status'] === 'active') {
            $final_response = [
                'status'   => 'active',
                'licensee' => $data['licensee_name'] ?? 'N/A',
                'domain'   => $data['domain'] ?? 'N/A',
                'expires'  => $data['ExpirationDate'] ?? 'N/A',
                'message'  => __('Licencia activada.', 'c2c-crypto-payments')
            ];
            self::log('Resultado: Licencia ACTIVA.');
            self::update_license_data($final_response);
            return $final_response;
        } else {
            $default_response['message'] = $data['message'] ?? __('La clave no es válida o está expirada.', 'c2c-crypto-payments');
            self::log('Resultado: Licencia INACTIVA. Razón: ' . $default_response['message']);
            self::update_license_data($default_response);
            return $default_response;
        }
    }

    /**
     */
    private static function update_license_data($response_data) {
        $options = get_option('woocommerce_c2c_crypto_payments_settings', []);

        $options['license_status'] = $response_data['status'];
        $options['licensee_name'] = $response_data['licensee'];
        $options['license_domain'] = $response_data['domain'];
        $options['license_expires'] = $response_data['expires'];

        update_option('woocommerce_c2c_crypto_payments_settings', $options);

        $transient_data = [
            'status'   => $response_data['status'],
            'licensee' => $response_data['licensee'],
            'domain'   => $response_data['domain'],
            'expires'  => $response_data['expires']
        ];
        set_transient('c2c_license_status_cache', $transient_data, 12 * HOUR_IN_SECONDS);
    }
}
