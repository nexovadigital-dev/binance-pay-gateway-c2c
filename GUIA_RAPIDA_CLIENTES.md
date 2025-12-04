# 📘 GUÍA RÁPIDA - Binance C2C Autopayments

## ✅ REQUISITOS MÍNIMOS

### Servidor
- ✅ WordPress 6.0+
- ✅ WooCommerce 6.0+
- ✅ PHP 7.4+ (Recomendado: 8.0+)
- ✅ SSL/HTTPS activo
- ✅ Memoria PHP: 256MB mínimo

### Binance
- ✅ Cuenta verificada (KYC completo)
- ✅ API Key con permiso de SOLO LECTURA
- ✅ Binance Pay activado
- ✅ Código QR de pago generado

---

## ⛔ PLUGINS QUE DEBES DESACTIVAR

Estos plugins bloquean el funcionamiento:

1. **Disable REST API** ❌
2. **Disable WP REST API** ❌  
3. **REST API Toolbox** (si está bloqueando todo) ❌
4. **WP REST API Controller** (configuración estricta) ❌

**Cómo verificar:** Ve a `tudominio.com/wp-json/` - Debe mostrar JSON, no un error.

---

## 🚀 INSTALACIÓN PASO A PASO

### PASO 1: Preparación (5 minutos)
```
□ Hacer backup completo
□ Verificar que WooCommerce está activo
□ Verificar que tienes SSL (https://)
□ Desactivar plugins incompatibles
□ Ir a tudominio.com/wp-json/ y verificar que funciona
```

### PASO 2: Instalar Plugin (2 minutos)
```
1. Subir ZIP del plugin en WordPress
2. Activar plugin
3. Verificar que no aparecen errores
```

### PASO 3: Configurar Binance (10 minutos)

#### 3.1 Crear API Key en Binance
```
1. Ir a binance.com
2. Perfil > API Management
3. Crear nueva API Key
4. ⚠️ IMPORTANTE: Solo activar "Enable Reading"
5. NO activar withdrawals, trading, ni futures
6. Copiar API Key y Secret Key
```

#### 3.2 Obtener Código QR
```
1. Abrir app de Binance
2. Ir a Binance Pay
3. Generar tu código QR de pago
4. Guardar imagen del QR
```

### PASO 4: Configurar Plugin (5 minutos)
```
1. Ir a WooCommerce > Ajustes > Pagos
2. Clic en "Binance C2C Autopayments"
3. Activar licencia (en Hub de Binance C2C)
4. Ingresar API Key
5. Ingresar Secret Key
6. Subir imagen del código QR
7. Seleccionar página de pago
8. Guardar cambios
```

### PASO 5: Probar (10 minutos)
```
1. Crear orden de prueba
2. Seleccionar método de pago Binance
3. Verificar redirección a página de pago
4. Verificar que QR se muestra
5. Hacer pago real de $1 en Binance
6. Verificar que se detecta automáticamente
```

---

## 🔍 DIAGNÓSTICO RÁPIDO

### Si el pago NO se detecta automáticamente:

**1. Verificar REST API:**
```
Ve a: tudominio.com/wp-json/

✅ Debe mostrar: {"name":"Mi Sitio","description":"..."}
❌ No debe mostrar: Error 404, 403, o página en blanco
```

**2. Verificar plugins activos:**
```
□ Desactivar "Disable REST API" si está activo
□ Desactivar plugins de seguridad temporalmente
□ Probar de nuevo
```

**3. Verificar que ingresaste la NOTA DE PAGO:**
```
En Binance Pay, al enviar dinero, DEBES poner la nota de 6 dígitos
que aparece en la página de pago. Sin esto NO funciona automático.
```

**4. Revisar logs:**
```
WooCommerce > Estado > Logs
Buscar archivo: c2c-crypto-payments-...
Ver qué errores aparecen
```

---

## ❓ PROBLEMAS COMUNES

### Problema: "El plugin se desactiva solo"
**Causa:** WooCommerce no está activo  
**Solución:** Activar WooCommerce primero, luego activar Binance C2C

### Problema: "Error 403 al verificar pago"
**Causa:** Firewall bloqueando REST API  
**Solución:**
- Si usas iThemes Security: Configuración > REST API > Permitir
- Si usas Wordfence: Whitelist tu IP
- Si usas Cloudflare: Agregar regla para permitir /wp-json/

### Problema: "La página de pago sale en blanco"
**Solución:**
1. Ajustes > Enlaces permanentes > Guardar cambios
2. Limpiar caché del navegador
3. Limpiar caché del servidor
4. Verificar que la página existe y tiene el shortcode [binance_payment_page]

### Problema: "API Key inválida"
**Solución:**
1. Verificar que copiaste la key completa (sin espacios)
2. Verificar que tiene permiso de "Reading" activado
3. Verificar que no ha expirado
4. Crear nueva API Key si es necesario

---

## 📞 SOPORTE

### Antes de contactar soporte, ten esta información:

```
1. Versión de WordPress: _______
2. Versión de WooCommerce: _______
3. Versión de PHP: _______
4. ¿Funciona tu REST API? (ir a tudominio.com/wp-json/) SI / NO
5. ¿Qué plugins de seguridad tienes activos? _______
6. Mensaje de error exacto: _______
7. Captura de pantalla del error
```

### Información del Sistema
Para obtener info del sistema:
```
WooCommerce > Estado > Obtener informe del sistema
Copiar y enviar
```

---

## ✅ CHECKLIST DE VERIFICACIÓN

Antes de declarar que "no funciona", verificar:

```
□ WooCommerce está activo
□ Plugin está activado
□ Licencia está activa
□ API Key ingresada correctamente
□ Secret Key ingresada correctamente
□ Código QR subido
□ Página de pago seleccionada
□ REST API funciona (tudominio.com/wp-json/)
□ SSL activo (https://)
□ No hay plugins que bloqueen REST API
□ Ingresé la NOTA DE PAGO en Binance al enviar dinero
□ Esperé al menos 15 segundos después de enviar el pago
```

---

## 🎯 CONFIGURACIÓN RECOMENDADA DE BINANCE PAY

### Límites Sugeridos:
```
Monto Mínimo: $15 USD
Monto Máximo: $1,000 USD

(Puedes ajustar según tu negocio)
```

### Monedas Soportadas:
```
✅ USDT (Tether)
✅ USDC (USD Coin)
```

### Tiempos:
```
Tiempo para pagar: 10 minutos
Verificación automática: cada 5 segundos
Máximo 120 verificaciones (10 minutos)
```

---

## 🔐 SEGURIDAD

### ⚠️ NUNCA des estos permisos a la API Key:
```
❌ Enable Withdrawals
❌ Enable Trading  
❌ Enable Futures
❌ Enable Margin

✅ SOLO: Enable Reading
```

### Protege tus claves:
```
□ No compartas tu API Key con nadie
□ No subas tu Secret Key a repositorios públicos
□ Cambia tus claves cada 3-6 meses
□ Si sospechas que fueron comprometidas, cámbialas inmediatamente
```

---

## 📊 ESTADÍSTICAS

Para ver pagos recibidos:
```
WooCommerce > Pedidos
Filtrar por método de pago: Binance Pay
```

Para ver detalles de verificación:
```
WooCommerce > Estado > Logs
Archivo: c2c-crypto-payments-[fecha]
```

---

**¿Necesitas ayuda?**  
Contacta: https://wa.me/message/GXMDON7MEALCG1

**Documentación completa:**  
[Incluir enlace a documentación extendida]

---

*Última actualización: Noviembre 2025*  
*Plugin: Binance C2C Autopayments*  
*Desarrollado por: Nexova Digital Solutions*
