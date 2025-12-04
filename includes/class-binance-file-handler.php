<?php
if (!defined('ABSPATH')) exit;

class Binance_File_Handler {

    public static function handle_upload($file, $order_id) {
        $max_size = 5 * 1024 * 1024;
        $allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf'];

        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('El archivo es demasiado grande. El tamaño máximo es 5 MB.', 'c2c-crypto-payments'));
        }

        $file_info = wp_check_filetype(basename($file['name']));
        if (empty($file_info['type']) || !in_array($file_info['type'], $allowed_mime_types)) {
            return new WP_Error('invalid_file_type', __('Tipo de archivo no válido. Solo se permiten archivos JPG, PNG y PDF.', 'c2c-crypto-payments'));
        }

        $upload_dir = wp_upload_dir();
        $custom_dir = $upload_dir['basedir'] . '/binance-c2c-private';

        if (!file_exists($custom_dir)) {
            wp_mkdir_p($custom_dir);

            $htaccess_content = "Options -Indexes\n<Files *>\n    Order Allow,Deny\n    Deny from all\n</Files>";
            file_put_contents($custom_dir . '/.htaccess', $htaccess_content);

            file_put_contents($custom_dir . '/index.php', '<?php // Silence is golden');
        }

        $file_extension = $file_info['ext'];
        $filename = 'receipt-' . $order_id . '-' . time() . '.' . $file_extension;
        $file_path = $custom_dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return new WP_Error('upload_error', __('Hubo un error al subir el archivo.', 'c2c-crypto-payments'));
        }

        $file_url = $upload_dir['baseurl'] . '/binance-c2c-private/' . $filename;

        return array(
            'file_path' => $file_path,
            'file_url' => $file_url,
            'filename' => $filename
        );
    }
}
