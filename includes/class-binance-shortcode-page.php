<?php
if (!defined('ABSPATH')) exit;

class Binance_Shortcode_Page {

    public function __construct() {
        add_shortcode('binance_payment_page', [$this, 'render_payment_page']);
    }

    public function render_payment_page() {

        $slug = isset($_GET['pay_slug']) ? sanitize_text_field($_GET['pay_slug']) : '';
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $order_key = isset($_GET['key']) ? wc_clean($_GET['key']) : '';
        
        if (empty($slug) || !$order_id || empty($order_key)) {
            return sprintf('<div class="woocommerce-error">%s</div>', __('Referencia de pago no válida o incompleta.', 'c2c-crypto-payments'));
        }

        $order = wc_get_order($order_id);
        if (!$order || !hash_equals($order->get_order_key(), $order_key)) {
             return sprintf('<div class="woocommerce-error">%s</div>', __('Clave de orden no válida. Acceso denegado.', 'c2c-crypto-payments'));
        }

        if ($order->get_meta('_binance_payment_slug') !== $slug) {
            return sprintf('<div class="woocommerce-error">%s</div>', __('Este enlace de pago no es válido o ha expirado.', 'c2c-crypto-payments'));
        }

        $current_status = $order->get_status();
        if (!in_array($current_status, ['pending', 'on-hold'])) {
            $status_name = wc_get_order_status_name($current_status);
            return sprintf(
                '<div class="woocommerce-info">%s <strong>%s</strong>. <a href="%s" class="button">%s</a></div>',
                __('Esta orden ya ha sido procesada. Estado actual:', 'c2c-crypto-payments'),
                $status_name,
                esc_url($order->get_view_order_url()),
                __('Ver Orden', 'c2c-crypto-payments')
            );
        }

        $gateway_options = get_option('woocommerce_binance_pay_c2c_settings');
        $amount_to_pay = $order->get_total();
        $payment_note = $order->get_meta('_binance_payment_note');
        $expiry_time = $order->get_meta('_binance_payment_expiry');
        $currency_symbol = get_woocommerce_currency_symbol($order->get_currency());

        $qr_code_url = esc_url(str_replace('http://', 'https://', $gateway_options['payment_qr_code'] ?? ''));
        $deep_link_url = esc_url(str_replace('http://', 'https://', $gateway_options['deep_link_url'] ?? '#'));
        $gif_url = esc_url(str_replace('http://', 'https://', plugin_dir_url(__FILE__) . '../assets/image/add_note.gif'));

        $order_received_url = $order->get_view_order_url();
        $js_i18n = [
            'paymentVerified'     => __('¡Pago Verificado!', 'c2c-crypto-payments'),
            'orderComplete'       => __('Tu orden está completa.', 'c2c-crypto-payments'),
            'orderProcessing'     => __('Tu orden está siendo procesada.', 'c2c-crypto-payments'),
            'redirectingIn'       => __('Serás redirigido en', 'c2c-crypto-payments'),
            'autoVerificationFailed' => __('Verificación Automática Fallida', 'c2c-crypto-payments'),
            'manualUploadPrompt'  => __('Por favor, sube tu comprobante de pago (JPG, PNG, o PDF, máx 5MB) para revisión manual.', 'c2c-crypto-payments'),
            'receiptUploaded'     => __('Comprobante Subido', 'c2c-crypto-payments'),
            'paymentUnderReview'  => __('Tu pago está ahora bajo revisión y pendiente de aprobación.', 'c2c-crypto-payments'),
            'uploadFailed'        => __('Carga fallida', 'c2c-crypto-payments'),
            'errorOccurred'       => __('Ocurrió un error.', 'c2c-crypto-payments'),
            'sessionExpired'      => __('Sesión Expirada', 'c2c-crypto-payments'),
            'orderCancelled'      => __('Tu orden ha sido cancelada y los artículos devueltos a tu carrito.', 'c2c-crypto-payments'),
            'cancelConfirm'       => __('¿Estás seguro de que quieres cancelar esta orden? Tus artículos serán devueltos al carrito.', 'c2c-crypto-payments'),
            'selectReceipt'       => __('Selecciona tu comprobante de pago', 'c2c-crypto-payments'),
            'orderIdRequired'     => __('El ID de Orden de Binance es obligatorio.', 'c2c-crypto-payments'),
            'currencyRequired'    => __('Por favor, selecciona la moneda que usaste.', 'c2c-crypto-payments'),
            'fileRequired'        => __('Por favor, selecciona un archivo.', 'c2c-crypto-payments'),
            'uploading'           => __('Subiendo...', 'c2c-crypto-payments'),
            'currencyUsed'        => __('Moneda Usada (Obligatorio):', 'c2c-crypto-payments'),
            'orderIdInstructions' => __('<strong>Obligatorio:</strong> Para verificar tu pago, busca el "ID de Orden" en los detalles de tu transacción de Binance y pégalo abajo.', 'c2c-crypto-payments'),
            'pasteOrderId'        => __('Pega el ID de Orden de Binance aquí', 'c2c-crypto-payments'),
            'expired'             => __('Expirado', 'c2c-crypto-payments'),
            'copied'              => __('¡Copiado!', 'c2c-crypto-payments'),
        ];
        $rest_url = get_rest_url(null, 'binancepay/v1/');

        ob_start();
        ?>
        <style>
            :root{--b-yellow:#F0B90B;--b-dark:#1E2329;--b-gray-light:#f5f5f5;--b-text:#474D57;--b-green:#0ECB81;--b-red:#F6465D;--b-blue:#0d6efd;--b-orange:#ffc107;}
            .b-body{font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;animation:b-fade-in .6s ease-out;} @keyframes b-fade-in{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
            .b-container{max-width:980px;margin:40px auto;background:#fff;border-radius:20px;box-shadow:0 15px 50px rgba(0,0,0,0.1);overflow:hidden;display:flex;flex-wrap:wrap;}
            .b-col-left{width:55%;background:var(--b-gray-light);padding:40px;} .b-col-right{width:45%;padding:40px;display:flex;flex-direction:column;justify-content:center;background:#fff;}
            @media(max-width:850px){.b-col-left,.b-col-right{width:100%; padding: 25px;}.b-container{margin: 20px auto; flex-direction: column-reverse;}.b-detail-box .value{font-size: 1.5em !important;}.b-detail-box .value.note{font-size: 2.1em !important; letter-spacing: 1px;}.b-header h2{font-size: 1.4em;}#b-timer{font-size: 1.4em;}.b-alert{margin: 20px auto;}}
            .b-header{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #ddd;padding-bottom:20px;margin-bottom:25px;}
            .b-header h2{font-weight:700;color:var(--b-dark);margin:0;font-size:1.6em;}
            #b-timer{font-size:1.6em;font-weight:700;color:var(--b-red);letter-spacing:1px;background:#ffebee;padding:5px 12px;border-radius:8px;transition:all .3s;}
            #b-timer.low-time{animation:b-pulse 1.2s infinite;} @keyframes b-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
            .b-instructions h5{font-weight:600;color:var(--b-dark);margin-top:25px;margin-bottom:15px;font-size:1em;text-transform:uppercase;letter-spacing:.5px;border-left:3px solid var(--b-yellow);padding-left:10px;}
            .b-instructions ol{list-style-position:outside;padding-left:20px;} .b-instructions li{margin-bottom:12px;color:var(--b-text);line-height:1.7;} .b-instructions strong{color:var(--b-dark);font-weight:600;}
            .b-gif{text-align:center;margin-top:30px;} .b-gif img{max-width:100%;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.1);border:1px solid #eee;}
            .b-payment-details{text-align:center;}
            .b-qr-box{margin-bottom:25px;padding:15px;border:1px solid #eee;border-radius:16px;display:inline-block;background:#fff;} .b-qr-box img{max-width:220px;display:block;border-radius:8px;}
            .b-detail-box{background:var(--b-gray-light);border-radius:12px;padding:15px;margin-bottom:15px;text-align:center;position:relative;}
            .b-detail-box .label{font-size:.9em;color:#777;font-weight:500;margin-bottom:5px;}
            .b-detail-box .value{font-size:2em;font-weight:700;color:var(--b-dark);letter-spacing:.5px;word-wrap:break-word;} .b-detail-box .value.note{color:var(--b-yellow);font-size:2.8em;letter-spacing:2px;}
            .b-detail-box .note-info{font-size:.85em;color:var(--b-red);font-weight:500;margin-top:5px;}
            .b-copy-btn{position:absolute;top:15px;right:15px;background:var(--b-yellow);color:#fff;border:none;padding:8px 14px;border-radius:6px;font-size:.85em;font-weight:600;cursor:pointer;transition:all .3s ease;display:flex;align-items:center;gap:5px;} .b-copy-btn:hover{opacity:.9;transform:translateY(-2px);} .b-copy-btn svg{width:16px;height:16px;fill:currentColor;} .b-copy-btn.copied{background:var(--b-green);} .b-copy-btn.copied svg{display:none;} .b-copy-btn.copied::after{content:'✓';}
            .b-mobile-link{display:block;text-align:center;color:var(--b-blue);text-decoration:none;font-weight:500;margin-top:20px;font-size:1.05em;transition:all .3s ease;} .b-mobile-link:hover{text-decoration:underline;opacity:.8;}
            .b-actions{margin-top:25px;display:flex;flex-direction:column;align-items:center;gap:15px;}
            .b-button{display:inline-block;width:100%;padding:12px;border-radius:8px;font-weight:600;font-size:1.1em;text-align:center;text-decoration:none;cursor:pointer;transition:all .3s ease;border:1px solid transparent;}
            .b-button-primary{background-color:var(--b-green);color:#fff; transition: all .3s ease;}.b-button-primary:hover{opacity:0.9; transform: translateY(-2px);}
            .b-button-secondary{background-color:transparent;color:var(--b-red);border-color:#ffcdd2; transition: all .3s ease;}.b-button-secondary:hover{background-color:#ffebee;color:var(--b-red); transform: translateY(-2px);}
            .b-alert{max-width:980px;margin:30px auto;padding:20px;border-radius:16px;background-color:#fff9e6;border:1px solid var(--b-orange);}
            .b-alert-title{font-weight:700;color:var(--b-dark);font-size:1.2em;margin-bottom:10px;} .b-alert ul{list-style:disc;padding-left:20px;margin:0;} .b-alert li{margin-bottom:8px;color:var(--b-text);}
            .b-modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:none;align-items:center;justify-content:center;z-index:99990;backdrop-filter:blur(5px);animation:b-fade-in .3s;overflow-y:auto;padding:20px;}
            .b-modal-content{background:#fff;border-radius:20px;padding:30px;max-width:500px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);margin:auto;max-height:90vh;overflow-y:auto;}
            .b-modal-icon{margin-bottom:15px;display:flex;justify-content:center;align-items:center;}
            .b-modal-icon svg{width:60px;height:60px;}
            @media(max-width:600px){.b-modal-icon svg{width:50px;height:50px;}}
            .b-modal-title{font-size:1.3em;font-weight:700;color:var(--b-dark);margin-bottom:10px;}
            .b-modal-message{font-size:0.95em;color:var(--b-text);margin-bottom:20px;line-height:1.5;}
            .b-form-group{margin-bottom:15px;text-align:left;}
            .b-form-group label{display:block;font-weight:600;margin-bottom:8px;color:var(--b-dark);font-size:0.95em;}
            .b-form-group input,.b-form-group select{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:1em;box-sizing:border-box;}
            .b-form-group input[type="file"]{padding:8px;background:white;cursor:pointer;}
            .b-upload-status{margin-top:15px;font-weight:600;color:var(--b-text);font-size:0.9em;}
            .b-spinner{border:3px solid #f3f3f3;border-top:3px solid var(--b-yellow);border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;margin:20px auto;}
            @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
            .b-file-preview{margin-top:10px;padding:10px;background:#e8f5e9;border-radius:8px;font-size:0.9em;color:var(--b-green);font-weight:600;word-break:break-all;}
        </style>
        <div class="b-body">
            <div class="b-alert">
                <div class="b-alert-title"><?php _e('⚠️ Importante - Lee Antes de Pagar', 'c2c-crypto-payments'); ?></div>
                <ul>
                    <li><strong><?php _e('Solo USDT o USDC:', 'c2c-crypto-payments'); ?></strong> <?php _e('Envía el pago únicamente en USDT o USDC. Otras criptomonedas no serán aceptadas.', 'c2c-crypto-payments'); ?></li>
                    <li><strong><?php _e('Monto Exacto:', 'c2c-crypto-payments'); ?></strong> <?php printf(__('Transfiere exactamente %s%s (con dos decimales).', 'c2c-crypto-payments'), $currency_symbol, number_format($amount_to_pay, 2, '.', '')); ?></li>
                    <li><strong><?php _e('Incluye la Nota de Pago:', 'c2c-crypto-payments'); ?></strong> <?php _e('Es crítico agregar la nota de pago mostrada abajo para que tu pago se verifique automáticamente.', 'c2c-crypto-payments'); ?></li>
                    <li><strong><?php _e('Temporizador de 10 Minutos:', 'c2c-crypto-payments'); ?></strong> <?php _e('Completa el pago antes de que expire el tiempo, o tu orden será cancelada.', 'c2c-crypto-payments'); ?></li>
                </ul>
            </div>
            <div class="b-container">
                <div class="b-col-left">
                    <div class="b-header">
                        <h2><?php _e('Instrucciones de Pago', 'c2c-crypto-payments'); ?></h2>
                        <div id="b-timer">10:00</div>
                    </div>
                    <div class="b-instructions">
                        <h5><?php _e('Cómo Pagar con Binance', 'c2c-crypto-payments'); ?></h5>
                        <ol>
                            <li><strong><?php _e('Escanea el código QR', 'c2c-crypto-payments'); ?></strong> <?php _e('a la derecha con la app de Binance, o toca el botón "Abrir Binance".', 'c2c-crypto-payments'); ?></li>
                            <li><strong><?php _e('Ingresa el Monto Exacto:', 'c2c-crypto-payments'); ?></strong> <?php printf(__('%s%s', 'c2c-crypto-payments'), $currency_symbol, number_format($amount_to_pay, 2, '.', '')); ?></li>
                            <li><strong><?php _e('Selecciona USDT o USDC', 'c2c-crypto-payments'); ?></strong> <?php _e('como moneda de pago.', 'c2c-crypto-payments'); ?></li>
                            <li><strong><?php _e('CRÍTICO:', 'c2c-crypto-payments'); ?></strong> <?php _e('Agrega la nota de pago numérica mostrada a la derecha en el campo "Nota" de Binance.', 'c2c-crypto-payments'); ?></li>
                            <li><strong><?php _e('Confirma', 'c2c-crypto-payments'); ?></strong> <?php _e('el pago. Una vez completado, haz clic en "Ya Pagué" abajo para verificación automática.', 'c2c-crypto-payments'); ?></li>
                        </ol>
                        <div class="b-gif">
                            <img src="<?php echo $gif_url; ?>" alt="<?php _e('Cómo agregar nota en Binance', 'c2c-crypto-payments'); ?>">
                        </div>
                    </div>
                </div>
                <div class="b-col-right">
                    <div class="b-payment-details">
                        <div class="b-qr-box">
                            <img src="<?php echo $qr_code_url; ?>" alt="<?php _e('Código QR de Pago Binance', 'c2c-crypto-payments'); ?>">
                        </div>
                        <div class="b-detail-box">
                            <div class="label"><?php _e('Monto a Pagar', 'c2c-crypto-payments'); ?></div>
                            <div class="value"><?php echo $currency_symbol . number_format($amount_to_pay, 2, '.', ''); ?></div>
                        </div>
                        <div class="b-detail-box">
                            <button type="button" class="b-copy-btn" id="b-copy-note-btn" title="<?php _e('Copiar Nota', 'c2c-crypto-payments'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                            </button>
                            <div class="label"><?php _e('Nota de Pago Binance', 'c2c-crypto-payments'); ?></div>
                            <div class="value note" id="b-payment-note"><?php echo esc_html($payment_note); ?></div>
                            <div class="note-info"><?php _e('⚠️ Agrega esta nota en Binance', 'c2c-crypto-payments'); ?></div>
                        </div>
                        <?php if (!empty($deep_link_url) && $deep_link_url !== '#') : ?>
                        <a href="<?php echo $deep_link_url; ?>" class="b-mobile-link"><?php _e('📱 Abrir Binance (Móvil)', 'c2c-crypto-payments'); ?></a>
                        <?php endif; ?>
                        <div class="b-actions">
                            <button type="button" class="b-button b-button-primary" id="b-paid-btn"><?php _e('Ya Pagué - Verificar Ahora', 'c2c-crypto-payments'); ?></button>
                            <button type="button" class="b-button b-button-secondary" id="b-cancel-btn"><?php _e('Cancelar Orden', 'c2c-crypto-payments'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="b-modal-overlay" id="b-modal">
                <div class="b-modal-content">
                    <div class="b-modal-icon" id="b-modal-icon"></div>
                    <div class="b-modal-title" id="b-modal-title"></div>
                    <div class="b-modal-message" id="b-modal-message"></div>
                    <div id="b-manual-verification-form" style="display:none;">
                        <div class="b-form-group">
                            <label><?php _e('ID de Orden de Binance (Obligatorio):', 'c2c-crypto-payments'); ?></label>
                            <input type="text" id="b-order-id-input" placeholder="<?php _e('Ejemplo: 123456789012345678', 'c2c-crypto-payments'); ?>">
                            <p style="font-size:.85em;color:var(--b-text);margin-top:5px;" id="b-order-id-instructions"></p>
                        </div>
                        <div class="b-form-group">
                            <label id="b-currency-label"></label>
                            <select id="b-currency-select">
                                <option value=""><?php _e('-- Selecciona --', 'c2c-crypto-payments'); ?></option>
                                <option value="USDT">USDT</option>
                                <option value="USDC">USDC</option>
                            </select>
                        </div>
                        <div class="b-form-group">
                            <label><?php _e('Comprobante de Pago:', 'c2c-crypto-payments'); ?></label>
                            <input type="file" id="b-receipt-file" accept="image/*,application/pdf">
                            <div id="b-file-preview" class="b-file-preview" style="display:none;"></div>
                        </div>
                        <div class="b-upload-status" id="b-upload-status"></div>
                        <div id="b-upload-spinner" class="b-spinner" style="display:none;"></div>
                        <button type="button" class="b-button b-button-primary" id="b-upload-btn" style="margin-top:20px;"><?php _e('Subir Comprobante', 'c2c-crypto-payments'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            'use strict';
            var config = {
                slug: '<?php echo esc_js($slug); ?>',
                expiryTimestamp: <?php echo (int) $expiry_time; ?>,
                apiUrl: '<?php echo esc_url($rest_url); ?>',
                nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
                pollingInterval: 5000,
                orderReceivedUrl: '<?php echo esc_url($order_received_url); ?>',
                paymentNote: '<?php echo esc_js($payment_note); ?>'
            };
            var i18n = <?php echo json_encode($js_i18n); ?>;
            var icons = {
                success: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#0ECB81" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                warning: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#ffc107" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>',
                expired: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#F6465D" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            };
            var ui = {
                timer: document.getElementById('b-timer'),
                paidButton: document.getElementById('b-paid-btn'),
                cancelButton: document.getElementById('b-cancel-btn'),
                modal: document.getElementById('b-modal'),
                modalIcon: document.getElementById('b-modal-icon'),
                modalTitle: document.getElementById('b-modal-title'),
                modalMessage: document.getElementById('b-modal-message'),
                copyBtn: document.getElementById('b-copy-note-btn')
            };
            var timerIntervalId, pollingIntervalId;
            var initPaymentPage = function() {
                var showModal = function(icon, title, message) {
                    ui.modalIcon.innerHTML = icon;
                    ui.modalTitle.textContent = title;
                    ui.modalMessage.textContent = message;
                    ui.modal.style.display = 'flex';
                };
                var redirect = function(url, message, seconds) {
                    seconds = seconds || 3;
                    var countdown = seconds;
                    ui.modalMessage.textContent = message + ' ' + i18n.redirectingIn + ' ' + countdown + '...';
                    var redirectInterval = setInterval(function() {
                        countdown--;
                        if (countdown > 0) {
                            ui.modalMessage.textContent = message + ' ' + i18n.redirectingIn + ' ' + countdown + '...';
                        } else {
                            clearInterval(redirectInterval);
                            window.location.href = url;
                        }
                    }, 1000);
                };
                var handleSuccess = function(orderStatus) {
                    clearInterval(timerIntervalId);
                    clearInterval(pollingIntervalId);
                    var statusMessage = orderStatus === 'completed' ? i18n.orderComplete : i18n.orderProcessing;
                    showModal(icons.success, i18n.paymentVerified, '');
                    redirect(config.orderReceivedUrl, statusMessage);
                };
                var handleManualVerification = function() {
                    var formEl = document.getElementById('b-manual-verification-form');
                    var fileInput = document.getElementById('b-receipt-file');
                    var uploadBtnEl = document.getElementById('b-upload-btn');
                    var statusEl = document.getElementById('b-upload-status');
                    var orderIdInputEl = document.getElementById('b-order-id-input');
                    var currencySelectEl = document.getElementById('b-currency-select');
                    var orderIdInstructionsEl = document.getElementById('b-order-id-instructions');
                    var currencyLabelEl = document.getElementById('b-currency-label');
                    var filePreviewEl = document.getElementById('b-file-preview');
                    var spinnerEl = document.getElementById('b-upload-spinner');
                    
                    showModal(icons.warning, i18n.autoVerificationFailed, i18n.manualUploadPrompt);
                    formEl.style.display = 'block';
                    orderIdInstructionsEl.innerHTML = i18n.orderIdInstructions;
                    currencyLabelEl.textContent = i18n.currencyUsed;
                    
                    fileInput.onchange = function() {
                        if (fileInput.files.length > 0) {
                            var fileName = fileInput.files[0].name;
                            filePreviewEl.textContent = '✓ ' + fileName;
                            filePreviewEl.style.display = 'block';
                            statusEl.textContent = '';
                        }
                    };
                    
                    uploadBtnEl.onclick = function() {
                        var selectedCurrency = currencySelectEl.options[currencySelectEl.selectedIndex];
                        if (!orderIdInputEl.value.trim()) { 
                            statusEl.textContent = i18n.orderIdRequired; 
                            return; 
                        }
                        if (!selectedCurrency || !selectedCurrency.value) { 
                            statusEl.textContent = i18n.currencyRequired; 
                            return; 
                        }
                        if (fileInput.files.length === 0) { 
                            statusEl.textContent = i18n.fileRequired; 
                            return; 
                        }
                        
                        statusEl.textContent = '';
                        spinnerEl.style.display = 'block';
                        uploadBtnEl.disabled = true;
                        uploadBtnEl.style.opacity = '0.6';
                        uploadBtnEl.textContent = i18n.uploading;
                        
                        var formData = new FormData();
                        formData.append('receipt', fileInput.files[0]);
                        formData.append('pay_slug', config.slug);
                        formData.append('binance_order_id', orderIdInputEl.value.trim());
                        formData.append('paid_currency', selectedCurrency.value);
                        
                        fetch(config.apiUrl + 'manual-verify', {
                            method: 'POST', 
                            headers: {'X-WP-Nonce': config.nonce}, 
                            body: formData
                        })
                        .then(function(res) { 
                            return res.json().then(function(data) { 
                                return {ok: res.ok, data: data}; 
                            }); 
                        })
                        .then(function(result) {
                            spinnerEl.style.display = 'none';
                            uploadBtnEl.disabled = false;
                            uploadBtnEl.style.opacity = '1';
                            uploadBtnEl.textContent = '<?php _e('Subir Comprobante', 'c2c-crypto-payments'); ?>';
                            
                            if(result.ok) {
                                showModal(icons.success, i18n.receiptUploaded, '');
                                redirect(config.orderReceivedUrl, i18n.paymentUnderReview);
                            } else { 
                                statusEl.textContent = i18n.uploadFailed + ': ' + result.data.message; 
                            }
                        }).catch(function() { 
                            spinnerEl.style.display = 'none';
                            uploadBtnEl.disabled = false;
                            uploadBtnEl.style.opacity = '1';
                            uploadBtnEl.textContent = '<?php _e('Subir Comprobante', 'c2c-crypto-payments'); ?>';
                            statusEl.textContent = i18n.errorOccurred; 
                        });
                    };
                };
                var verifyPayment = function(isManualTrigger) {
                    isManualTrigger = isManualTrigger || false;
                    console.log('[Binance C2C] Verificando pago...', new Date().toLocaleTimeString());
                    fetch(config.apiUrl + 'verify?pay_slug=' + config.slug)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        console.log('[Binance C2C] Respuesta API:', data);
                        if (data.status === 'success') {
                            handleSuccess(data.order_status);
                        } else if (isManualTrigger) {
                            handleManualVerification();
                        }
                    }).catch(function(err) {
                        console.error('[Binance C2C] Error en verificación:', err);
                        if (isManualTrigger) handleManualVerification();
                    });
                };
                var handleExpiry = function() {
                    fetch(config.apiUrl + 'expire', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce},
                        body: JSON.stringify({pay_slug: config.slug})
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.status === 'success') {
                            showModal(icons.expired, i18n.sessionExpired, '');
                            redirect(data.cart_url, i18n.orderCancelled, 4);
                        }
                    });
                };
                var updateTimer = function() {
                    var timeLeft = config.expiryTimestamp - Math.floor(Date.now() / 1000);
                    if (timeLeft <= 0) {
                        clearInterval(timerIntervalId);
                        clearInterval(pollingIntervalId);
                        ui.timer.textContent = i18n.expired;
                        ui.paidButton.style.display = 'none';
                        ui.cancelButton.style.display = 'none';
                        handleExpiry();
                        return;
                    }
                    if (timeLeft <= 120) ui.timer.classList.add('low-time');
                    ui.timer.textContent = Math.floor(timeLeft / 60) + ':' + (timeLeft % 60).toString().padStart(2, '0');
                };
                ui.copyBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var noteText = config.paymentNote;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(noteText).then(function() {
                            ui.copyBtn.classList.add('copied');
                            ui.copyBtn.textContent = i18n.copied;
                            setTimeout(function() {
                                ui.copyBtn.classList.remove('copied');
                                ui.copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>';
                            }, 2000);
                        }).catch(function() {
                            fallbackCopyTextToClipboard(noteText);
                        });
                    } else {
                        fallbackCopyTextToClipboard(noteText);
                    }
                });
                var fallbackCopyTextToClipboard = function(text) {
                    var textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.top = '0';
                    textArea.style.left = '0';
                    textArea.style.opacity = '0';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        ui.copyBtn.classList.add('copied');
                        ui.copyBtn.textContent = i18n.copied;
                        setTimeout(function() {
                            ui.copyBtn.classList.remove('copied');
                            ui.copyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>';
                        }, 2000);
                    } catch (err) {
                        console.error('Fallback: No se pudo copiar', err);
                    }
                    document.body.removeChild(textArea);
                };
                ui.paidButton.addEventListener('click', function() { verifyPayment(true); });
                ui.cancelButton.addEventListener('click', function() {
                    if (!confirm(i18n.cancelConfirm)) return;
                    fetch(config.apiUrl + 'cancel', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce},
                        body: JSON.stringify({pay_slug: config.slug})
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if(data.status === 'success') {
                            window.location.href = data.cart_url;
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }).catch(function() { alert(i18n.errorOccurred); });
                });
                ui.modal.addEventListener('click', function(event) {
                    if (event.target === ui.modal) {
                        ui.modal.style.display = 'none';
                    }
                });
                console.log('[Binance C2C] Iniciando timer y polling...');
                timerIntervalId = setInterval(updateTimer, 1000);
                pollingIntervalId = setInterval(function() { verifyPayment(false); }, config.pollingInterval);
                console.log('[Binance C2C] Polling configurado cada ' + (config.pollingInterval / 1000) + ' segundos');
                updateTimer();
                verifyPayment(false);
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPaymentPage);
            } else {
                initPaymentPage();
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}