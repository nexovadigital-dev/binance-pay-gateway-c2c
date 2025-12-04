<?php
/**
* Plugin Name:       Binance C2C Autopayments for WooCommerce
* Plugin URI:        https://wa.me/message/GXMDON7MEALCG1
* Description:       Recibe pagos automatizados y manuales en USDT y USDC con Binance Pay C2C.
* Version:           2.9.8
* Author:            Nexova Digital Solutions
* Author URI:        https://wa.me/message/GXMDON7MEALCG1
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Requires at least: 6.0
* Requires PHP:      7.4
* WC requires at least: 6.0
* WC tested up to: 8.5
* Text Domain:       c2c-crypto-payments
* Domain Path:       /languages
 */
 /*
 * ═══════════════════════════════════════════════════════════════════════════
 * ADVERTENCIA LEGAL - PROPIEDAD INTELECTUAL PROTEGIDA
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Este software es propiedad exclusiva de Nexova Digital Solutions.
 * Está protegido por leyes internacionales de derechos de autor y propiedad intelectual.
 * 
 * PROHIBICIONES ESTRICTAS:
 * - Modificar, alterar o eliminar el sistema de licencias
 * - Redistribuir con o sin modificaciones
 * - Ingeniería inversa del código
 * - Uso comercial sin licencia válida
 * - Eliminación de atribuciones de autoría
 * 
 * CONSECUENCIAS LEGALES:
 * La modificación no autorizada de este código puede resultar en:
 * - Acciones legales civiles por violación de derechos de autor
 * - Demandas por daños y perjuicios
 * - Sanciones penales según las leyes aplicables
 * - Responsabilidad por lucro cesante
 * 
 * AL UTILIZAR ESTE SOFTWARE, USTED ACEPTA:
 * - Respetar todos los derechos de propiedad intelectual
 * - Mantener una licencia válida y activa
 * - No manipular ni eludir los sistemas de protección
 * 
 * Para licencias comerciales o consultas legales, contacte:
 * Nexova Digital Solutions - https://wa.me/message/GXMDON7MEALCG1
 * 
 * © 2025-2026 Nexova Digital Solutions. Todos los derechos reservados.
 * ═══════════════════════════════════════════════════════════════════════════
 */

if (!defined('ABSPATH')) exit;

define('C2C_CRYPTO_PLUGIN_FILE', __FILE__);
define('C2C_CRYPTO_PLUGIN_VERSION', '2.9.8');

function c2c_check_woocommerce_dependency() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . esc_html__('Binance C2C Autopayments', 'c2c-crypto-payments') . ':</strong> ';
            echo esc_html__('Este plugin requiere WooCommerce activo. Por favor, active WooCommerce.', 'c2c-crypto-payments');
            echo ' <a href="' . esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=woocommerce')) . '">' . esc_html__('Instalar WooCommerce', 'c2c-crypto-payments') . '</a></p>';
            echo '</div>';
        });
        
        deactivate_plugins(plugin_basename(C2C_CRYPTO_PLUGIN_FILE));
        return false;
    }
    return true;
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', C2C_CRYPTO_PLUGIN_FILE, true );
    }
} );

if ( ! function_exists( 'init_c2c_crypto_gateway_plugin' ) ) {

    function init_c2c_crypto_gateway_plugin() {

        $plugin_path = plugin_dir_path(C2C_CRYPTO_PLUGIN_FILE);

        include_once $plugin_path . 'includes/class-binance-license-handler.php';
        include_once $plugin_path . 'includes/class-wc-gateway-binance.php';
        include_once $plugin_path . 'includes/class-binance-api-handler.php';
        include_once $plugin_path . 'includes/class-binance-admin-hub-page.php';
        include_once $plugin_path . 'includes/class-binance-order-meta.php';
        include_once $plugin_path . 'includes/class-binance-file-handler.php';
        include_once $plugin_path . 'includes/class-binance-blocks-support.php';
        include_once $plugin_path . 'includes/class-binance-shortcode-page.php';

        new Binance_API_Handler();
        new Binance_Shortcode_Page();

        if (is_admin()) {
            new Binance_Order_Meta();
            new Binance_Admin_Hub_Page();
        }

        add_filter('woocommerce_payment_gateways', 'add_c2c_crypto_gateway_class');
        add_action('plugins_loaded', 'c2c_crypto_load_textdomain', 20);

        add_action('woocommerce_blocks_payment_method_type_registration', function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register( new Binance_Gateway_Blocks_Support() );
        });
    }

    add_action('woocommerce_loaded', 'init_c2c_crypto_gateway_plugin');
}

function c2c_crypto_register_block_scripts() {
    $script_asset_path = plugin_dir_path(C2C_CRYPTO_PLUGIN_FILE) . 'assets/js/block-checkout.asset.php';
    $script_url = plugin_dir_url(C2C_CRYPTO_PLUGIN_FILE) . 'assets/js/block-checkout.js';
    $version = '2.9.8';

    if ( file_exists( $script_asset_path ) ) {
        $script_asset = require $script_asset_path;
        $dependencies = $script_asset['dependencies'];
    } else {
        $dependencies = ['wp-blocks', 'wp-i18n', 'wp-element', 'wc-blocks-registry', 'wp-html-entities'];
    }

    wp_register_script('wc-binance-blocks-integration', $script_url, $dependencies, $version, true);
}
add_action('init', 'c2c_crypto_register_block_scripts');

function add_c2c_crypto_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_Binance';
    return $gateways;
}

function c2c_crypto_load_textdomain() {
    load_plugin_textdomain('c2c-crypto-payments', false, dirname(plugin_basename(C2C_CRYPTO_PLUGIN_FILE)) . '/languages/');
}

add_filter('plugin_action_links_' . plugin_basename(C2C_CRYPTO_PLUGIN_FILE), 'c2c_crypto_add_settings_link');
function c2c_crypto_add_settings_link($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=binance-c2c-hub')) . '" style="font-weight: bold; color: #0073aa;">' . esc_html__('Configurar', 'c2c-crypto-payments') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_action('wp_enqueue_scripts', 'c2c_crypto_enqueue_frontend_styles');
function c2c_crypto_enqueue_frontend_styles() {
    $gateway_settings = get_option('woocommerce_binance_pay_c2c_settings', []);
    $page_id = isset($gateway_settings['payment_page_id']) ? absint($gateway_settings['payment_page_id']) : 0;

    if ( (is_checkout() || ($page_id > 0 && is_page($page_id))) && c2c_is_license_active() ) {
        $css_url = plugin_dir_url(C2C_CRYPTO_PLUGIN_FILE) . 'assets/css/frontend.css';
        wp_enqueue_style('c2c-crypto-frontend-styles', $css_url, [], C2C_CRYPTO_PLUGIN_VERSION);
    }
}

function c2c_crypto_activate_plugin() {
    $lock_key = 'c2c_activation_lock';
    
    if (get_transient($lock_key)) {
        return;
    }
    
    set_transient($lock_key, true, 30);
    
    try {
        $page_title = __('Pago Cripto C2C', 'c2c-crypto-payments');
        $page_content = '[binance_payment_page]';
        $page_slug = 'c2c-crypto-payment';
        
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT ID FROM $wpdb->posts 
             WHERE post_type = 'page' 
             AND post_status IN ('publish', 'draft', 'pending') 
             AND (post_content LIKE %s OR post_name = %s OR post_title = %s)
             ORDER BY post_status DESC, ID ASC
             LIMIT 1",
            '%[binance_payment_page]%',
            $page_slug,
            $page_title
        );
        
        $page_id = $wpdb->get_var($sql);
        
        if (!$page_id) {
            $page = [
                'post_type'      => 'page',
                'post_title'     => $page_title,
                'post_content'   => $page_content,
                'post_status'    => 'publish',
                'post_author'    => get_current_user_id() ?: 1,
                'post_name'      => $page_slug,
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ];
            
            $page_id = wp_insert_post($page, true);
            
            if (is_wp_error($page_id)) {
                error_log('[Binance C2C] Error creando página: ' . $page_id->get_error_message());
                delete_transient($lock_key);
                return;
            }
        } else {
            $page = get_post($page_id);
            if ($page && strpos($page->post_content, '[binance_payment_page]') === false) {
                wp_update_post([
                    'ID' => $page_id,
                    'post_content' => $page->post_content . "\n\n" . $page_content,
                    'post_status' => 'publish',
                ]);
            }
        }
        
        if ($page_id) {
            $gateway_options = get_option('woocommerce_binance_pay_c2c_settings', []);
            if (empty($gateway_options['payment_page_id'])) {
                $gateway_options['payment_page_id'] = $page_id;
                update_option('woocommerce_binance_pay_c2c_settings', $gateway_options);
            }
        }
        
        flush_rewrite_rules();
        
    } finally {
        delete_transient($lock_key);
    }
}

register_activation_hook(C2C_CRYPTO_PLUGIN_FILE, function() {
    if (!c2c_check_woocommerce_dependency()) {
        wp_die(
            '<h1>' . esc_html__('Error de Activación', 'c2c-crypto-payments') . '</h1>' .
            '<p>' . esc_html__('Este plugin requiere <strong>WooCommerce</strong>. Por favor, instale y active WooCommerce primero.', 'c2c-crypto-payments') . '</p>',
            esc_html__('Error de Activación', 'c2c-crypto-payments'),
            ['back_link' => true]
        );
    }
    c2c_crypto_activate_plugin();
});

function c2c_crypto_deactivate_plugin() {
    flush_rewrite_rules();
    delete_transient('c2c_license_status_cache');
    wp_clear_scheduled_hook('c2c_license_check_cron');
}
register_deactivation_hook(C2C_CRYPTO_PLUGIN_FILE, 'c2c_crypto_deactivate_plugin');

add_filter('woocommerce_email_classes', 'add_c2c_admin_manual_email');
function add_c2c_admin_manual_email($email_classes) {
    if (!class_exists('WC_Email_Binance_Admin_Manual')) {
        include_once(plugin_dir_path(C2C_CRYPTO_PLUGIN_FILE) . 'includes/class-wc-email-binance-admin-manual.php');
    }
    if (class_exists('WC_Email_Binance_Admin_Manual') && !isset($email_classes['WC_Email_Binance_Admin_Manual'])) {
        $email_classes['WC_Email_Binance_Admin_Manual'] = new WC_Email_Binance_Admin_Manual();
    }
    return $email_classes;
}

function c2c_is_license_active() {
    $license_data = get_transient('c2c_license_status_cache');
    
    if (false === $license_data) {
        if (!class_exists('Binance_License_Handler')) {
            error_log('[Binance C2C] Error crítico: Clase Binance_License_Handler no encontrada');
            
            if (is_admin()) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p><strong>' . esc_html__('Binance C2C', 'c2c-crypto-payments') . ':</strong> ';
                    echo esc_html__('Error crítico. Por favor, reinstale el plugin.', 'c2c-crypto-payments') . '</p></div>';
                });
            }
            return false;
        }
        
        Binance_License_Handler::check_license();
        $license_data = get_transient('c2c_license_status_cache');
        
        if (false === $license_data) {
            error_log('[Binance C2C] Error: No se pudo obtener estado de licencia después de verificación');
            return false;
        }
    }
    
    if (is_array($license_data) && isset($license_data['status'])) {
        return $license_data['status'] === 'active';
    } elseif (is_string($license_data)) {
        return $license_data === 'active';
    }
    
    if ($license_data !== false) {
        error_log('[Binance C2C] Estructura de licencia inválida: ' . print_r($license_data, true));
    }
    
    return false;
}

add_action('admin_init', function() {
    if (is_plugin_active(plugin_basename(C2C_CRYPTO_PLUGIN_FILE))) {
        c2c_check_woocommerce_dependency();
    }
});

add_action('c2c_license_check_cron', function() {
    if (class_exists('Binance_License_Handler')) {
        Binance_License_Handler::check_license();
    }
});

function binc2c_add_cfasync_attribute_to_scripts( $tag, $handle ) {
    $plugin_dir_name = basename( plugin_dir_path( __FILE__ ) );

    if ( strpos( $tag, $plugin_dir_name ) !== false || strpos( $handle, 'binance-' ) === 0 || strpos( $handle, 'wc-binance' ) === 0 ) {
        if ( strpos( $tag, 'data-cfasync="false"' ) === false ) {
            $tag = str_replace( ' src=', ' data-cfasync="false" src=', $tag );
        }
    }

    return $tag;
}
add_filter( 'script_loader_tag', 'binc2c_add_cfasync_attribute_to_scripts', 10, 2 );

function binc2c_exclude_from_autoptimize( $exclude_js ) {
    $plugin_dir_name = basename( plugin_dir_path( __FILE__ ) );
    $exclude_js .= ", {$plugin_dir_name}/, block-checkout.js";
    return $exclude_js;
}
add_filter( 'autoptimize_filter_js_exclude', 'binc2c_exclude_from_autoptimize', 10, 1 );

function binc2c_exclude_from_wp_rocket( $excluded_js_files ) {
    $plugin_dir_url = plugin_dir_url( __FILE__ );
    $excluded_js_files[] = $plugin_dir_url . '(.*).js';
    return $excluded_js_files;
}
add_filter( 'rocket_exclude_js', 'binc2c_exclude_from_wp_rocket', 10, 1 );