<?php
if (!defined('ABSPATH')) exit;

/**
 */
class Binance_Admin_Hub_Page {

    private $settings_option_name = 'woocommerce_c2c_crypto_payments_settings';
    private $settings_group = 'binance_c2c_settings_group';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    /**
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Binance C2C', 'c2c-crypto-payments'),
            __('Binance C2C', 'c2c-crypto-payments'),
            'manage_woocommerce',
            'binance-c2c-hub',
            [$this, 'render_page']
        );
    }

    /**
     */
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'woocommerce_page_binance-c2c-hub') {
            return;
        }

        $plugin_folder_name = plugin_basename(dirname(C2C_CRYPTO_PLUGIN_FILE));
        $js_url = WP_CONTENT_URL . '/plugins/' . $plugin_folder_name . '/assets/js/binance-admin-hub.js';
        $css_url = WP_CONTENT_URL . '/plugins/' . $plugin_folder_name . '/assets/css/binance-admin-hub.css';

        wp_enqueue_script('binance-admin-hub-js', $js_url, ['jquery'], '2.9.8', true);
        wp_enqueue_style('binance-admin-hub-css', $css_url, [], '2.9.8');
    }

    /**
     */
    public function register_settings() {
        register_setting(
            $this->settings_group,
            $this->settings_option_name,
            [$this, 'sanitize_settings_callback']
        );

        // Sección de Licencia
        add_settings_section(
            'binance_license_section',
            __('Gestión de Licencia', 'c2c-crypto-payments'),
            null,
            $this->settings_group
        );

        add_settings_field(
            'license_key',
            __('Clave de Licencia', 'c2c-crypto-payments'),
            [$this, 'render_field_license_key'],
            $this->settings_group,
            'binance_license_section'
        );

        add_settings_field(
            'license_status',
            __('Estado de la Licencia', 'c2c-crypto-payments'),
            [$this, 'render_field_license_status'],
            $this->settings_group,
            'binance_license_section'
        );

        add_settings_section(
            'binance_global_section',
            __('Ajustes Globales', 'c2c-crypto-payments'),
            null,
            $this->settings_group
        );

        add_settings_field(
            'gateway_config',
            __('Pasarela de Pago', 'c2c-crypto-payments'),
            [$this, 'render_field_gateway_config'],
            $this->settings_group,
            'binance_global_section'
        );

        add_settings_field(
            'enable_debug_log',
            __('Activar Log de Depuración', 'c2c-crypto-payments'),
            [$this, 'render_field_enable_debug_log'],
            $this->settings_group,
            'binance_global_section'
        );
    }

    /**
     */
    public function sanitize_settings_callback($input) {
        $options = get_option($this->settings_option_name, []);

        $input['license_key']      = sanitize_text_field($input['license_key'] ?? '');
        $input['enable_debug_log'] = ($input['enable_debug_log'] === 'yes') ? 'yes' : 'no';

        $old_key = $options['license_key'] ?? '';
        $new_key = $input['license_key'];

        $final_input = $input;

        if ($new_key !== $old_key) {
            delete_transient('c2c_license_status_cache');

            if (empty($new_key)) {
                $response = ['status' => 'inactive', 'message' => 'Clave eliminada.', 'licensee' => 'N/A', 'domain' => 'N/A', 'expires' => 'N/A'];
                add_settings_error('binance_messages', 'license_cleared', __('Clave de licencia eliminada.', 'c2c-crypto-payments'), 'warning');
            } else {
                $response = Binance_License_Handler::check_license($new_key);

                if ($response['status'] === 'active') {
                    add_settings_error('binance_messages', 'license_active', __('Licencia activada con éxito.', 'c2c-crypto-payments'), 'updated');
                } else {
                    add_settings_error('binance_messages', 'license_inactive', sprintf(__('La licencia es inválida o no se pudo activar. Error: %s', 'c2c-crypto-payments'), $response['message']), 'error');
                }
            }
            $final_input['license_status'] = $response['status'];
            $final_input['licensee_name'] = $response['licensee'];
            $final_input['license_domain'] = $response['domain'];
            $final_input['license_expires'] = $response['expires'];

        } else {
            $final_input['license_status']  = $options['license_status'] ?? 'inactive';
            $final_input['licensee_name'] = $options['licensee_name'] ?? 'N/A';
            $final_input['license_domain']  = $options['license_domain'] ?? 'N/A';
            $final_input['license_expires'] = $options['license_expires'] ?? 'N/A';
        }

        return $final_input;
    }

    /*
     */

    private function get_options() {
        return get_option($this->settings_option_name, []);
    }

    public function render_field_license_key() {
        $options = $this->get_options();
        $key = $options['license_key'] ?? '';
        echo '<input type="text" name="' . $this->settings_option_name . '[license_key]" value="' . esc_attr($key) . '" class="regular-text" placeholder="Ingresa tu clave de licencia">';
    }

    public function render_field_license_status() {
        $options = $this->get_options();
        $status_label = ($options['license_status'] ?? 'inactive') === 'active' ?
            '<span style="color:green; font-weight:bold;">' . __('ACTIVA', 'c2c-crypto-payments') . '</span>' :
            '<span style="color:red; font-weight:bold;">' . __('INACTIVA', 'c2c-crypto-payments') . '</span>';

        $status_text = sprintf(
            '<fieldset><p style="margin-top: 0; line-height: 1.6;"><strong>%1$s</strong> %2$s <br> <strong>%3$s</strong> %4$s <br> <strong>%5$s</strong> %6$s <br> <strong>%7$s</strong> %8$s <br> <em style="font-size: 12px; color: #555;">%9$s</em></p></fieldset>',
            __('Estado:', 'c2c-crypto-payments'), $status_label,
            __('Registrada a:', 'c2c-crypto-payments'), esc_html($options['licensee_name'] ?? 'N/A'),
            __('Dominio:', 'c2c-crypto-payments'), esc_html($options['license_domain'] ?? 'N/A'),
            __('Expira:', 'c2c-crypto-payments'), esc_html($options['license_expires'] ?? 'N/A'),
            __('El estado se actualiza al guardar la clave.', 'c2c-crypto-payments')
        );
        echo $status_text;
    }

    public function render_field_gateway_config() {
        $gateway_settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=binance_pay_c2c');
        echo '<a href="' . esc_url($gateway_settings_url) . '" class="button button-secondary">' . __('Configurar Pasarela (API Keys, Monedas, etc.)', 'c2c-crypto-payments') . '</a>';
        echo '<p class="description">' . __('Aquí configuras los detalles de la pasarela de pago, como las API keys de Binance, las monedas aceptadas y los límites.', 'c2c-crypto-payments') . '</p>';
    }

    public function render_field_enable_debug_log() {
        $options = $this->get_options();
        $checked = $options['enable_debug_log'] ?? 'no';
        ?>
        <input type="checkbox" name="<?php echo $this->settings_option_name; ?>[enable_debug_log]" value="yes" <?php checked($checked, 'yes'); ?>> <label><?php _e('Activar registro (log) para solución de problemas.', 'c2c-crypto-payments'); ?></label>
        <p class="description"><?php printf(__('Los logs se guardarán en %s', 'c2c-crypto-payments'), '<code>wp-content/uploads/wc-logs/c2c-crypto-payments-*.log</code>'); ?></p>
        <?php
    }

    /**
     */
    public function render_page() {
        ?>
        <div class="wrap" id="binance-admin-hub">
            <h2><?php _e('Binance C2C Autopayments', 'c2c-crypto-payments'); ?></h2>
            <?php settings_errors('binance_messages'); ?>

            <h2 class="nav-tab-wrapper">
                <a href="#tab-settings" class="nav-tab nav-tab-active"><?php _e('Ajustes y Licencia', 'c2c-crypto-payments'); ?></a>
                <a href="#tab-info" class="nav-tab"><?php _e('Información y Soporte', 'c2c-crypto-payments'); ?></a>
            </h2>

            <div id="tab-settings" class="binance-tab-content active">
                <form action="options.php" method="post">
                    <?php
                    settings_fields($this->settings_group);
                    do_settings_sections($this->settings_group);
                    submit_button();
                    ?>
                </form>
            </div>

            <div id="tab-info" class="binance-tab-content" style="display:none;">
                <?php $this->render_info_section(); ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var $tabs = $('.nav-tab-wrapper a');
            var $content = $('.binance-tab-content');

            function change_tab(targetId) {
                $tabs.removeClass('nav-tab-active');
                $tabs.filter('[href="' + targetId + '"]').addClass('nav-tab-active');
                $content.hide();
                $(targetId).show();
            }

            $tabs.on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                var targetId = $this.attr('href');
                change_tab(targetId);
                if (history.pushState) {
                    history.pushState(null, null, targetId);
                } else {
                    window.location.hash = targetId;
                }
            });

            if(window.location.hash) {
                var valid_hash = $tabs.filter('[href="' + window.location.hash + '"]');
                if (valid_hash.length > 0) {
                    change_tab(window.location.hash);
                } else {
                    change_tab($tabs.first().attr('href'));
                }
            }
        });
        </script>
        <?php
    }

    /**
     */
    public function render_info_section() {
        ?>
        <h3><?php _e('Información y Soporte', 'c2c-crypto-payments'); ?></h3>
        <p><strong><?php _e('Versión:', 'c2c-crypto-payments'); ?></strong> <?php echo defined('C2C_CRYPTO_PLUGIN_VERSION') ? C2C_CRYPTO_PLUGIN_VERSION : 'N/A'; ?></p>

        <div style="max-width: 700px; font-size: 13px; line-height: 1.5; color: #444; border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
            <p><strong><?php esc_html_e('Descargo de responsabilidad:', 'c2c-crypto-payments'); ?></strong> <?php esc_html_e('Este complemento no es un producto oficial de Binance Holdings Ltd. y Nexova Digital Solutions SAS no está afiliada, asociada ni respaldada por Binance. Es una herramienta desarrollada por Nexova Digital Solutions SAS para facilitar operaciones de pago automatizadas.', 'c2c-crypto-payments'); ?></p>
            <p><strong><?php esc_html_e('Licenciamiento:', 'c2c-crypto-payments'); ?></strong> <?php esc_html_e('Este complemento de software es propiedad intelectual de Nexova Digital Solutions SAS y se licencia bajo un acuerdo comercial. Su uso está sujeto a la posesión de una licencia válida y activa. La modificación, ingeniería inversa, redistribución no autorizada o cualquier intento de eludir las medidas de licenciamiento del código fuente está estrictamente prohibido. Tales acciones constituyen una infracción directa de los derechos de propiedad intelectual y una violación de los términos del acuerdo de licencia, resultando en la revocación inmediata de la misma y reservándonos el derecho de emprender las acciones legales correspondientes.', 'c2c-crypto-payments'); ?></p>
            <p><strong><?php esc_html_e('Soporte:', 'c2c-crypto-payments'); ?></strong> <?php esc_html_e('Para soporte técnico, visite:', 'c2c-crypto-payments'); ?> <a href="https://wa.me/message/GXMDON7MEALCG1" target="_blank">https://wa.me/message/GXMDON7MEALCG1</a></p>
        </div>
        <?php
    }
}