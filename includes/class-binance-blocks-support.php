<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) exit;

/**
 */
final class Binance_Gateway_Blocks_Support extends AbstractPaymentMethodType {

    /**
     */
    protected $name = 'binance_pay_c2c';

    /**
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_binance_pay_c2c_settings', []);
    }

    /**
     */
    public function is_active() {
        $is_enabled_by_admin = $this->get_setting('enabled', 'no') === 'yes';

        $is_license_active = function_exists('c2c_is_license_active') && c2c_is_license_active();

        return $is_enabled_by_admin && $is_license_active;
    }
    /**
     */

    /**
     */
    public function get_payment_method_script_handles() {
        return ['wc-binance-blocks-integration'];
    }


    public function get_payment_method_data() {

        $plugin_folder_name = plugin_basename(dirname(C2C_CRYPTO_PLUGIN_FILE));
        $logo_url = WP_CONTENT_URL . '/plugins/' . $plugin_folder_name . '/assets/image/logo.png';

        return [
            'name'        => $this->name,
            'title'       => $this->get_setting('title', __('Binance P2P (Automático / Manual)', 'c2c-crypto-payments')),
            'description' => $this->get_setting('description', __('Paga con tu cuenta de Binance. Se requerirá una nota de pago.', 'c2c-crypto-payments')),
            'icon'        => esc_url($logo_url),
            'supports'    => [
                'features' => $this->get_supported_features(),
            ],
        ];
    }
}
