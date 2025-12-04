<?php
if (!defined('ABSPATH')) exit;

class WC_Gateway_Binance extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'binance_pay_c2c';
        $this->has_fields = false;
        $this->method_title = __('Binance C2C Autopayments', 'c2c-crypto-payments');
        $this->method_description = __('Permite a los clientes pagar usando pagos de criptomonedas estilo C2C (automático/manual).', 'c2c-crypto-payments');

        $this->icon = plugin_dir_url( __FILE__ ) . '../assets/image/logo.png';

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $is_enabled_by_admin = $this->get_option('enabled');

        if ($is_enabled_by_admin === 'yes' && !c2c_is_license_active()) {
            $this->enabled = 'no';
            add_action('admin_notices', [$this, 'admin_notice_license_inactive']);
        } else {
            $this->enabled = $is_enabled_by_admin;
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    public function admin_notice_license_inactive() {
        if (!c2c_is_license_active() && $this->get_option('enabled') === 'yes') {

            $settings_url = admin_url('admin.php?page=binance-c2c-hub');

            echo '<div class="notice notice-error"><p>';
            printf(
                __('La pasarela de pago Binance C2C está habilitada, pero la licencia no está activa. El método de pago se ocultará en el checkout. Por favor, %sactiva tu licencia%s.', 'c2c-crypto-payments'),
                '<a href="' . esc_url($settings_url) . '">',
                '</a>'
            );
            echo '</p></div>';
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'woocommerce_page_wc-settings' || !isset($_GET['section']) || $_GET['section'] !== 'binance_pay_c2c') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'c2c-crypto-admin-settings',
            plugin_dir_url(__FILE__) . '../assets/js/admin-settings-script.js',
            ['jquery'],
            '2.9.8',
            true
        );
        wp_localize_script('c2c-crypto-admin-settings', 'c2c_admin_i18n', [
            'upload_qr' => __('Seleccionar QR de Pago', 'c2c-crypto-payments'),
            'use_qr' => __('Usar esta imagen', 'c2c-crypto-payments'),
        ]);
    }

    public function init_form_fields() {
        $pages = get_pages();
        $page_options = ['' => '— ' . __('Seleccionar una Página', 'c2c-crypto-payments') . ' —'];
        if ($pages) {
            foreach ($pages as $page) {
                $page_options[$page->ID] = $page->post_title;
            }
        }

        $license_status = c2c_is_license_active();

        $license_url = admin_url('admin.php?page=binance-c2c-hub');

        $license_desc = $license_status ?
            '<span style="color:green; font-weight:bold;">' . __('Licencia activa.', 'c2c-crypto-payments') . '</span>' :
            sprintf(
                __('La licencia no está activa. %sActívala aquí%s para habilitar la pasarela.', 'c2c-crypto-payments'),
                '<a href="' . esc_url($license_url) . '" style="font-weight:bold;">', '</a>'
            );

        $this->form_fields = [
            'license_status' => [
                'title' => __('Estado de la Licencia', 'c2c-crypto-payments'),
                'type'  => 'title',
                'description' => $license_desc
            ],
            'enabled' => [
                'title'   => __('Activar/Desactivar', 'c2c-crypto-payments'),
                'type'    => 'checkbox',
                'label'   => __('Activar Binance C2C Autopayments', 'c2c-crypto-payments'),
                'default' => 'no',
                'disabled' => !$license_status
            ],
            'title' => [
                'title'   => __('Título', 'c2c-crypto-payments'),
                'type'    => 'text',
                'default' => __('Binance Pay', 'c2c-crypto-payments'),
                'desc_tip' => true
            ],
            'description' => [
                'title'   => __('Descripción', 'c2c-crypto-payments'),
                'type'    => 'textarea',
                'default' => __('Completa tu pedido con Binance Pay de manera fácil y con acreditación automática.', 'c2c-crypto-payments')
            ],
            'page_settings' => [
                'title' => __('Configuración de Página de Pago', 'c2c-crypto-payments'),
                'type'  => 'title'
            ],
            'payment_page_id' => [
                'title'   => __('Página de Pago', 'c2c-crypto-payments'),
                'type'    => 'select',
                'options' => $page_options,
                'description' => __('Crea una página de WordPress, añádele el shortcode <b>[binance_payment_page]</b>, y luego selecciona esa página aquí. (El plugin intenta crearla automáticamente al activarse).', 'c2c-crypto-payments'),
                'desc_tip' => true,
                'required' => true,
            ],
            'on_success_status' => [
                'title'   => __('Al confirmar un pago, cambiar estado a:', 'c2c-crypto-payments'),
                'type'    => 'select',
                'options' => [
                    'processing' => __('Procesando', 'c2c-crypto-payments'),
                    'completed'  => __('Completado', 'c2c-crypto-payments')
                ],
                'default' => 'processing',
                'desc_tip' => true
            ],
            'api_settings' => [
                'title' => __('Credenciales API (Obligatorio)', 'c2c-crypto-payments'),
                'type' => 'title',
                'description' => sprintf(
                    __('Para obtener tus credenciales API: %1$s 1. %2$sCrea una cuenta de Binance%3$s y completa la verificación de identidad. %1$s 2. Ve a %2$s%4$s%3$s. %1$s 3. Crea una nueva API Key (preferiblemente "Generada por el sistema"). %1$s 4. **IMPORTANTE**: Otorga únicamente permiso de %2$s"Lectura"%3$s por seguridad.', 'c2c-crypto-payments'),
                    '<br>', '<strong>', '</strong>', '<a href="https://www.binance.com/es/my/settings/api-management" target="_blank">https://www.binance.com/es/my/settings/api-management</a>'
                )
            ],
            'binance_api_key' => [
                'title' => __('Binance API Key', 'c2c-crypto-payments'),
                'type' => 'text',
                'required' => true,
            ],
            'binance_secret_key' => [
                'title' => __('Binance Secret Key', 'c2c-crypto-payments'),
                'type' => 'password',
                'required' => true,
            ],
            'payment_details' => [
                'title' => __('Detalles de Pago (Para el cliente)', 'c2c-crypto-payments'),
                'type' => 'title'
            ],
            'binance_id' => [
                'title'   => __('Binance ID (Pay ID)', 'c2c-crypto-payments'),
                'type'    => 'text',
                'desc_tip'    => true
            ],
            'payment_qr_code' => [
                'title'   => __('URL del Código QR de Pago', 'c2c-crypto-payments'),
                'type'    => 'text',
                'desc_tip' => true,
                'required' => true,
                'description' => __('Haz clic en el campo y luego en el botón "Subir QR" para seleccionar desde la biblioteca de medios.', 'c2c-crypto-payments'),
            ],
            'deep_link_url' => [
                'title'   => __('URL de App Binance (Opcional)', 'c2c-crypto-payments'),
                'type'    => 'text',
                'desc_tip' => true,
                'required' => false,
            ],
            'limits' => [
                'title' => __('Límites de Pago', 'c2c-crypto-payments'),
                'type' => 'title'
            ],
            'enable_limits' => [
                'title'   => __('Activar Límites', 'c2c-crypto-payments'),
                'type'    => 'checkbox',
                'label'   => __('Activar validación de monto mínimo y máximo', 'c2c-crypto-payments'),
                'default' => 'no',
                'desc_tip' => true,
                'description' => __('Si está activado, el checkout mostrará un error si el carrito no cumple con los límites.', 'c2c-crypto-payments'),
            ],
            'min_limit' => [
                'title'   => __('Monto Mínimo de Pago', 'c2c-crypto-payments'),
                'type'    => 'number',
                'default' => '15',
                'desc_tip' => true
            ],
            'max_limit' => [
                'title'   => __('Monto Máximo de Pago', 'c2c-crypto-payments'),
                'type'    => 'number',
                'default' => '1000',
                'desc_tip' => true
            ],
        ];
    }

    public function validate_binance_api_key_field($key, $value) {
        if (empty($value)) {
            WC_Admin_Settings::add_error(esc_html__('El campo "Binance API Key" es obligatorio.', 'c2c-crypto-payments'));
        }
        return $value;
    }

    public function validate_binance_secret_key_field($key, $value) {
        if (empty($value)) {
            WC_Admin_Settings::add_error(esc_html__('El campo "Binance Secret Key" es obligatorio.', 'c2c-crypto-payments'));
        }
        return $value;
    }

    public function validate_payment_qr_code_field($key, $value) {
        if (empty($value)) {
            WC_Admin_Settings::add_error(esc_html__('El campo "URL del Código QR de Pago" es obligatorio.', 'c2c-crypto-payments'));
        }
        return $value;
    }

    public function validate_payment_page_id_field($key, $value) {
        if (empty($value)) {
            WC_Admin_Settings::add_error(esc_html__('El campo "Página de Pago" es obligatorio. Asegúrate de crear una página con el shortcode [binance_payment_page].', 'c2c-crypto-payments'));
        }
        return $value;
    }

    public function process_payment($order_id) {

        $enable_limits = $this->get_option('enable_limits');

        if ($enable_limits === 'yes') {
            $min_limit = (float) $this->get_option('min_limit');
            $max_limit = (float) $this->get_option('max_limit');

            $cart_total = 0;
            if ( ! is_null( WC()->cart ) && WC()->cart->get_total('edit') > 0 ) {
                $cart_total = (float) WC()->cart->get_total( 'edit' );
            } else {
                $order_for_total = wc_get_order($order_id);
                if($order_for_total) {
                    $cart_total = (float) $order_for_total->get_total();
                }
            }

            $is_blocks_environment = class_exists('\Automattic\WooCommerce\StoreApi\Exceptions\RouteException');

            if ($cart_total > 0 && $min_limit > 0 && $cart_total < $min_limit) {
                $error_message = sprintf(
                    __('El monto mínimo para pagar con %s es de %s. Tu monto actual es %s.', 'c2c-crypto-payments'),
                    $this->title,
                    wc_price($min_limit),
                    wc_price($cart_total)
                );

                if ($is_blocks_environment && (defined('REST_REQUEST') && REST_REQUEST)) {
                    throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
                        'binance_min_limit_error',
                        $error_message
                    );
                } else {
                    wc_add_notice($error_message, 'error');
                    return null;
                }
            }

            if ($cart_total > 0 && $max_limit > 0 && $cart_total > $max_limit) {
                $error_message = sprintf(
                    __('El monto máximo para pagar con %s es de %s. Tu monto actual es %s.', 'c2c-crypto-payments'),
                    $this->title,
                    wc_price($max_limit),
                    wc_price($cart_total)
                );

                if ($is_blocks_environment && (defined('REST_REQUEST') && REST_REQUEST)) {
                    throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
                        'binance_max_limit_error',
                        $error_message
                    );
                } else {
                    wc_add_notice($error_message, 'error');
                    return null;
                }
            }
        }

        $order = wc_get_order($order_id);

        $api_key = $this->get_option('binance_api_key');
        $secret_key = $this->get_option('binance_secret_key');
        $qr_code = $this->get_option('payment_qr_code');
        $page_id = $this->get_option('payment_page_id');

        if (empty($api_key) || empty($secret_key) || empty($qr_code) || empty($page_id)) {
            wc_add_notice(__('La pasarela de pago Cripto C2C no está configurada correctamente. Por favor, contacta al administrador de la tienda.', 'c2c-crypto-payments'), 'error');
            return null;
        }

        $order->update_status('pending', __('Cliente redirigido a la página de pago C2C.', 'c2c-crypto-payments'));

        $order->update_meta_data('_binance_payment_note', rand(100000, 999999));
        $order->update_meta_data('_binance_payment_slug', wp_generate_password(32, false));
        $order->update_meta_data('_binance_payment_expiry', time() + (10 * 60));


        $order->save();

        $payment_page_url = get_permalink($page_id);

        return [
            'result'   => 'success',
            'redirect' => add_query_arg(
                [
                    'pay_slug' => $order->get_meta('_binance_payment_slug'),
                    'order_id' => $order_id,
                    'key'      => $order->get_order_key()
                ],
                $payment_page_url
            )
        ];
    }
}