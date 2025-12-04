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

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_sideload($file, $order_id);

        if (is_wp_error($attachment_id)) {
            $error_message = sprintf(
                __('Hubo un error al subir el archivo: %s', 'c2c-crypto-payments'),
                $attachment_id->get_error_message()
            );
            return new WP_Error('upload_error', $error_message);
        }

        return $attachment_id;
    }
}
