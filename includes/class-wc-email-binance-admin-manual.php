<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WC_Email_Binance_Admin_Manual')) :

class WC_Email_Binance_Admin_Manual extends WC_Email {

    /**
     */
    public function __construct() {
        $this->id             = 'binance_admin_manual_notification';
        $this->title          = __('Verificación Manual (Binance C2C)', 'c2c-crypto-payments');
        $this->description    = __('Este email se envía al admin cuando un cliente sube un comprobante de pago manual.', 'c2c-crypto-payments');
        $this->placeholders   = ['{order_number}' => ''];

        $this->template_base  = plugin_dir_path(C2C_CRYPTO_PLUGIN_FILE) . 'templates/';

        $this->template_html  = 'emails/admin-manual-notification.php';
        $this->template_plain = 'emails/plain/admin-manual-notification.php';

        $this->recipient = $this->get_option('recipient', get_option('admin_email'));

        parent::__construct();

        // Hooks
        add_action('binance_c2c_manual_verification_required_notification', [$this, 'trigger'], 10, 1);
        add_filter('woocommerce_email_attachments', [$this, 'attach_payment_receipt'], 10, 3);
    }

    /**
     */
    public function get_email_type() {
        return 'html';
    }

    /**
     */
    public function trigger($order_id) {
        $this->setup_locale();
        $order = wc_get_order($order_id);

        if ($order) {
            $this->object                         = $order;
            $this->placeholders['{order_number}'] = $order->get_order_number();
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }

    /**
     */
    public function attach_payment_receipt($attachments, $email_id, $order) {
        if ($this->id === $email_id && $order instanceof WC_Order) {
            $attachment_id = $order->get_meta('_binance_receipt_attachment_id');

            if ($attachment_id) {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path)) {
                    $attachments[] = $file_path;
                }
            }
        }
        return $attachments;
    }

    /**
     */
    public function get_content_html() {
        return wc_get_template_html($this->template_html, [
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => true,
            'plain_text'    => false,
            'email'         => $this,
        ], '', $this->template_base);
    }

    /**
     */
    public function get_content_plain() {
        return wc_get_template_html($this->template_plain, [
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => true,
            'plain_text'    => true,
            'email'         => $this,
        ], '', $this->template_base);
    }

    /**
     */
    public function get_default_subject() {
        return __('Verificación de pago (Binance C2C) requerida para la Orden #{order_number}', 'c2c-crypto-payments');
    }

    /**
     */
    public function get_default_heading() {
        return __('Verificación Manual (Binance C2C)', 'c2c-crypto-payments');
    }

    /**
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Activar/Desactivar', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Activar esta notificación por email', 'woocommerce'),
                'default' => 'yes',
            ],
            'recipient' => [
                'title'       => __('Destinatario(s)', 'woocommerce'),
                'type'        => 'text',
                'description' => sprintf(__('Introduce los destinatarios (separados por comas) para este email. Por defecto es %s.', 'woocommerce'), '<code>' . esc_attr(get_option('admin_email')) . '</code>'),
                'placeholder' => '',
                'default'     => '',
                'desc_tip'    => true,
            ],
            'subject' => [
                'title'       => __('Asunto', 'woocommerce'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => sprintf(__('Placeholders disponibles: %s', 'woocommerce'), '<code>{order_number}</code>'),
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading' => [
                'title'       => __('Encabezado del Email', 'woocommerce'),
                'type'        => 'text',
                'desc_tip'    => true,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ],
        ];
    }
}

endif;

return new WC_Email_Binance_Admin_Manual();
