# 🚀 Guía de Instalación y Configuración - Transbank SDK
## Mall Virtual - Viña del Mar

---

## 📦 INSTALACIÓN DEL SDK

### 1. Instalar Composer (si no está instalado)
```bash
# Verificar si Composer está instalado
composer --version

# Si no está instalado, instalar:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Instalar SDK de Transbank
```bash
# Navegar al directorio del proyecto
cd /ruta/a/comercial-elroblev2/mer

# Instalar el SDK oficial de Transbank
composer require transbank/transbank-sdk

# O instalar versión específica recomendada
composer require "transbank/transbank-sdk:^3.0"
```

### 3. Verificar instalación
```bash
composer show transbank/transbank-sdk
```

---

## ⚙️ CONFIGURACIÓN PASO A PASO

### Paso 1: Configuración de Desarrollo
Edita el archivo `src/config.php`:

```php
// Configuración básica para desarrollo
const TRANSBANK_MOCK = true;  // Mantener true hasta tener credenciales reales
const TRANSBANK_COMMERCE_CODE = '';
const TRANSBANK_API_KEY = '';
const TRANSBANK_ENV = 'Integration';
```

### Paso 2: Obtener Credenciales de Transbank

1. **Registrarse en Transbank:** 
   - Visitar: https://www.transbankdevelopers.cl/
   - Crear cuenta de desarrollador
   - Solicitar credenciales de prueba primero

2. **Configurar credenciales de prueba:**
```php
// Para ambiente de integración/pruebas
const TRANSBANK_MOCK = false;
const TRANSBANK_COMMERCE_CODE = '597012345678';  // Código de comercio de prueba
const TRANSBANK_API_KEY = '579B532A7440BB69C69EF3E687B7714A'; // API key de prueba
const TRANSBANK_ENV = 'Integration';
```

3. **Para producción:**
```php
// Obtener credenciales reales de Transbank
const TRANSBANK_MOCK = false;
const TRANSBANK_COMMERCE_CODE = 'TU_CODIGO_REAL';
const TRANSBANK_API_KEY = 'TU_API_KEY_REAL';
const TRANSBANK_ENV = 'Production';

// Rutas de certificados (obligatorio en producción)
const TRANSBANK_PRIVATE_KEY_PATH = '/ruta/absoluta/a/clave_privada.key';
const TRANSBANK_PUBLIC_CERT_PATH = '/ruta/absoluta/a/certificado_publico.crt';
```

### Paso 3: Configurar URLs
```php
// En config.php, agregar estas líneas:
const SITE_URL = 'https://tudominio.com';  // Tu dominio real
const TRANSBANK_RETURN_URL = SITE_URL . '/pay_transbank.php';
const TRANSBANK_FINAL_URL = SITE_URL . '/pay_transbank.php';
```

---

## 🔒 CONFIGURACIÓN DE SEGURIDAD

### 1. Crear archivo `.htaccess` para proteger config
Crear archivo `.htaccess` en directorio `src/`:

```apache
# Proteger archivos de configuración
<FilesMatch "config.*\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

<FilesMatch "config_transbank.*\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Forzar HTTPS en producción
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

### 2. Crear directorio de logs
```bash
# Crear directorio de logs con permisos correctos
sudo mkdir -p /var/log/transbank
sudo chown www-data:www-data /var/log/transbank
sudo chmod 755 /var/log/transbank

# Crear archivos de log
sudo touch /var/log/transbank/transbank.log
sudo touch /var/log/transbank/errors.log
sudo touch /var/log/transbank/audit.log

sudo chown www-data:www-data /var/log/transbank/*.log
sudo chmod 644 /var/log/transbank/*.log
```

### 3. Configurar SSL/TLS
```bash
# Instalar certificado SSL (ejemplo con Let's Encrypt)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d tudominio.com

# Verificar configuración SSL
curl -I https://tudominio.com
```

---

## 🧪 PRUEBAS

### 1. Probar en ambiente de integración
```php
// Usar credenciales de prueba
const TRANSBANK_ENV = 'Integration';
const TRANSBANK_COMMERCE_CODE = '597012345678';
const TRANSBANK_API_KEY = '579B532A7440BB69C69EF3E687B7714A';
```

**Tarjetas de prueba válidas:**
- **Visa:** 4051885600446623
- **Mastercard:** 5186496545400267  
- **American Express:** 375123456789012
- **CVV:** Cualquier número de 3 dígitos
- **Fecha:** Cualquier fecha futura (12/25)
- **Nombre:** Cualquier nombre

### 2. Probar flujo completo
1. Agregar productos al carrito
2. Completar checkout
3. Pagar con tarjeta de prueba
4. Verificar que se marca como pago exitoso
5. Revisar logs: `tail -f /var/log/transbank/transbank.log`

### 3. Probar casos de error
- Tarjeta rechazada
- Fondos insuficientes
- CVV incorrecto
- Fecha de vencimiento expirada

---

## 📊 MONITOREO Y LOGS

### 1. Ver logs en tiempo real
```bash
# Logs generales
tail -f /var/log/transbank/transbank.log

# Solo errores
tail -f /var/log/transbank/errors.log

# Auditoría
tail -f /var/log/transbank/audit.log
```

### 2. Rotación de logs automática
Crear archivo `/etc/logrotate.d/transbank`:

```
/var/log/transbank/*.log {
    daily
    missingok
    rotate 90
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### 3. Alertas por email (opcional)
Configurar `logwatch` o script personalizado para enviar errores críticos.

---

## 🚨 TROUBLESHOOTING

### Error: "The API key is not valid"
- Verificar que `TRANSBANK_API_KEY` sea correcta
- Confirmar que el ambiente (`TRANSBANK_ENV`) coincida con la clave

### Error: "The commerce code is not valid"
- Verificar `TRANSBANK_COMMERCE_CODE`
- Confirmar que no haya espacios o caracteres extra

### Error: "SSL Certificate verification failed"
- Verificar que el sitio tenga SSL válido
- Instalar certificados CA: `sudo apt-get install ca-certificates`

### Error: "Timeout connecting to Webpay"
- Verificar conectividad a internet
- Revisar firewall y puertos
- Aumentar `TRANSBANK_TIMEOUT` si es necesario

### Transacción aparece como "pending"
- Verificar webhook configuration
- Revisar logs de respuesta de Transbank
- Confirmar que la URL de retorno sea accesible

---

## 💡 CONSEJOS ADICIONALES

### 1. Backup de configuración
```bash
# Hacer backup de credenciales
cp src/config.php src/config.php.backup.$(date +%Y%m%d)

# Crear script de restauración
echo '#!/bin/bash
cp src/config.php.backup.* src/config.php' > restore_config.sh
```

### 2. Testing automatizado
Crear tests unitarios para:
- Validación de configuración
- Simulación de pagos
- Manejo de errores
- Flujo completo de checkout

### 3. Documentación
- Mantener esta guía actualizada
- Documentar credenciales en gestor seguro (no en código)
- Crear runbook de incidentes

### 4. Mantenimiento
- Revisar logs semanalmente
- Actualizar SDK mensualmente: `composer update transbank/transbank-sdk`
- Monitorear estado de certificados SSL

---

## ✅ CHECKLIST FINAL

- [ ] SDK instalado via Composer
- [ ] Credenciales configuradas correctamente
- [ ] SSL/TLS configurado y funcionando
- [ ] URLs de retorno configuradas
- [ ] Logs configurados y funcionando
- [ ] Pruebas exitosas en ambiente de integración
- [ ] Certificados SSL válidos
- [ ] Archivos de configuración protegidos
- [ ] Backup de configuración creado
- [ ] Documentación actualizada

**¡Listo para procesar pagos reales en producción!** 🎉