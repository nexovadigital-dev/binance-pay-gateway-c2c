# Binance C2C Autopayments for WooCommerce

**Plugin de Pagos Automatizados en Criptomonedas para WooCommerce**

![Version](https://img.shields.io/badge/version-3.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0+-green.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-6.0+-purple.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![License](https://img.shields.io/badge/license-GPL--2.0+-red.svg)

---

## 📋 Descripción

Plugin profesional de WooCommerce que permite recibir pagos automatizados y manuales en **USDT** y **USDC** utilizando Binance Pay C2C. Diseñado para e-commerce que desean ofrecer pagos en criptomonedas con verificación automática y manual.

### ✨ Características Principales

- ✅ **Pagos Automatizados**: Verificación automática de pagos en Binance
- ✅ **Pagos Manuales**: Sistema de reporte manual con subida de comprobantes
- ✅ **Múltiples Monedas**: Soporte para USDT y USDC
- ✅ **Seguridad Avanzada**: Sistema de licencias y protección de archivos
- ✅ **Gestión Privada de Archivos**: Los comprobantes se guardan en carpeta privada (no aparecen en gestor multimedia)
- ✅ **Compatible con Blocks**: Soporte para WooCommerce Blocks Checkout
- ✅ **Notificaciones Email**: Alertas automáticas al administrador
- ✅ **Interfaz Amigable**: Panel de administración intuitivo

---

## 🔧 Requisitos

### Servidor
- PHP 7.4 o superior
- WordPress 6.0 o superior
- WooCommerce 6.0 o superior
- Extensión PHP: cURL
- Extensión PHP: JSON

### Binance
- Cuenta de Binance verificada
- API Key de Binance con permisos de lectura
- Cuenta C2C activa

---

## 📦 Instalación

### Método 1: Instalación Manual

1. Descarga el archivo ZIP del plugin
2. Ve a **WordPress Admin** → **Plugins** → **Añadir nuevo**
3. Haz clic en **Subir plugin**
4. Selecciona el archivo ZIP y haz clic en **Instalar ahora**
5. Activa el plugin

### Método 2: Instalación por FTP

1. Descomprime el archivo ZIP del plugin
2. Sube la carpeta `binance-pay-gateway-c2c` a `/wp-content/plugins/`
3. Ve a **WordPress Admin** → **Plugins**
4. Activa el plugin **Binance C2C Autopayments for WooCommerce**

---

## ⚙️ Configuración

### 1. Configuración Básica

1. Ve a **WordPress Admin** → **Binance C2C Hub**
2. Introduce tu **License Key** (proporcionada por Nexova)
3. Activa la licencia

### 2. Configuración de API Binance

1. Obtén tu API Key desde [Binance API Management](https://www.binance.com/es/my/settings/api-management)
2. En el Hub del plugin, introduce:
   - **API Key**: Tu clave API de Binance
   - **Secret Key**: Tu clave secreta de Binance
3. Configura el **Payment Note** (nota que aparecerá en Binance)

### 3. Configuración de Pagos

1. Ve a **WooCommerce** → **Ajustes** → **Pagos**
2. Habilita **Binance C2C Crypto Payments**
3. Configura:
   - Título del método de pago
   - Descripción para clientes
   - Timeout de verificación
   - Opciones de moneda (USDT/USDC)

---

## 🎯 Uso

### Para Clientes

1. El cliente selecciona productos y procede al checkout
2. Selecciona **Pago con Criptomonedas C2C** como método de pago
3. Completa la orden y es redirigido a la página de pago
4. Realiza el pago en Binance siguiendo las instrucciones
5. El sistema verifica automáticamente el pago

### Verificación Manual (si el pago no se detecta automáticamente)

1. El cliente hace clic en **"Reportar Pago Manualmente"**
2. Sube el comprobante de pago (JPG, PNG o PDF - máx. 5MB)
3. Introduce el **Order ID de Binance**
4. Selecciona la moneda utilizada (USDT o USDC)
5. El administrador recibe una notificación por email
6. El administrador verifica y aprueba el pago desde el panel de WordPress

---

## 📁 Estructura de Archivos

```
binance-pay-gateway-c2c/
├── assets/
│   ├── css/
│   │   └── frontend.css
│   └── js/
│       ├── block-checkout.js
│       └── block-checkout.asset.php
├── includes/
│   ├── class-binance-admin-hub-page.php
│   ├── class-binance-api-handler.php
│   ├── class-binance-blocks-support.php
│   ├── class-binance-file-handler.php
│   ├── class-binance-license-handler.php
│   ├── class-binance-order-meta.php
│   ├── class-binance-shortcode-page.php
│   ├── class-wc-email-binance-admin-manual.php
│   └── class-wc-gateway-binance.php
├── templates/
│   └── emails/
│       ├── admin-manual-notification.php
│       └── plain/
│           └── admin-manual-notification.php
├── binance-pay-gateway.php
├── README.md
├── GUIA_IMPLEMENTACION.md
└── GUIA_RAPIDA_CLIENTES.md
```

---

## 🔐 Seguridad

### Gestión Privada de Comprobantes

Los comprobantes de pago se almacenan en una carpeta privada:
- **Ubicación**: `/wp-content/uploads/binance-c2c-private/`
- **Protección**: Archivos `.htaccess` y `index.php` para evitar acceso directo
- **Visibilidad**: NO aparecen en el gestor multimedia de WordPress
- **Acceso**: Solo administradores mediante enlaces internos

### Validación de Archivos

- Tipos permitidos: JPG, PNG, PDF
- Tamaño máximo: 5 MB
- Validación MIME type
- Nombres únicos con timestamp

---

## 🆕 Changelog

### Version 3.0.0 (2025-12-04)
- ✨ **NUEVO**: Gestión privada de comprobantes de pago
- ✨ **NUEVO**: Los comprobantes ya no aparecen en el gestor multimedia
- ✨ **NUEVO**: Carpeta de almacenamiento protegida con .htaccess
- 🔧 **MEJORA**: Sistema de archivos más seguro y organizado
- 📝 **NUEVO**: README.md completo con documentación

### Version 2.9.8
- 🔧 Correcciones de estabilidad
- 🔐 Mejoras en el sistema de licencias
- ⚡ Optimizaciones de rendimiento

---

## 🚀 Roadmap - Próximas Actualizaciones

### Planificado para Version 3.1.0

#### 🔍 Verificación Automática Mejorada
- **Búsqueda por Order ID**: Verificación automática de pagos usando el Order ID de Binance cuando el cliente olvide colocar la nota de pago
- **Sistema de fallback inteligente**: Si no se encuentra el pago por nota, el sistema buscará automáticamente por Order ID
- **Reducción de verificaciones manuales**: Menos intervención del administrador

#### 🎨 Personalización del Checkout
- **Editor de paleta de colores**: Personaliza los colores del checkout de pago desde el panel de administración
- **Colores personalizables**:
  - Color primario (botones, enlaces)
  - Color secundario (fondos, bordes)
  - Color de acentos (alertas, notificaciones)
  - Color de texto
- **Vista previa en tiempo real**: Ve los cambios antes de aplicarlos
- **Temas predefinidos**: Plantillas de colores listas para usar
- **Compatibilidad con tu marca**: Adapta el checkout a la identidad visual de tu tienda

#### 🔔 Otras Mejoras en Consideración
- Notificaciones push para administradores
- Reportes y estadísticas de pagos
- Soporte para más criptomonedas
- Integración con más wallets

### ¿Tienes una sugerencia?

Si tienes ideas para mejorar el plugin, ¡contáctanos! Tu feedback es muy valioso:
📱 [WhatsApp - Nexova Digital Solutions](https://wa.me/message/GXMDON7MEALCG1)

---

## 📞 Soporte

### Contacto Nexova Digital Solutions

- **WhatsApp**: [https://wa.me/message/GXMDON7MEALCG1](https://wa.me/message/GXMDON7MEALCG1)
- **Licencias**: Contacta para obtener tu license key

### Documentación Adicional

- **Guía de Implementación**: Ver `GUIA_IMPLEMENTACION.md`
- **Guía para Clientes**: Ver `GUIA_RAPIDA_CLIENTES.md`

---

## 📄 Licencia

Este plugin está protegido por derechos de autor y propiedad intelectual.

**Nexova Digital Solutions © 2025-2026. Todos los derechos reservados.**

- ❌ **Prohibida** la redistribución sin autorización
- ❌ **Prohibida** la modificación del sistema de licencias
- ❌ **Prohibido** el uso comercial sin licencia válida
- ✅ **Permitido** el uso con licencia válida y activa

Para más información sobre licencias comerciales, contacta con Nexova Digital Solutions.

---

## 🤝 Créditos

**Desarrollado por**: [Nexova Digital Solutions](https://wa.me/message/GXMDON7MEALCG1)

**Tecnologías Utilizadas**:
- WordPress API
- WooCommerce API
- Binance API
- React (WooCommerce Blocks)

---

## ⚠️ Descargo de Responsabilidad

Este plugin interactúa con servicios de terceros (Binance). El usuario es responsable de:
- Cumplir con los términos de servicio de Binance
- Mantener seguras sus API Keys
- Verificar la legalidad de las criptomonedas en su jurisdicción
- Realizar las configuraciones fiscales necesarias

Nexova Digital Solutions no se hace responsable del uso indebido del plugin.

---

## 🌟 ¿Te gusta este plugin?

Si este plugin te ha sido útil:
- ⭐ Contáctanos para más soluciones personalizadas
- 📢 Recomiéndanos a otros desarrolladores
- 💬 Envíanos tus sugerencias de mejora

**¡Gracias por confiar en Nexova Digital Solutions!**
