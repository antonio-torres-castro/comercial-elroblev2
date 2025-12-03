<?php

/**
 * Configuración de Transbank para Producción
 * Mall Virtual - Viña del Mar
 * 
 * INSTRUCCIONES DE CONFIGURACIÓN:
 * 1. Copia este archivo a config_transbank.php
 * 2. Modifica las credenciales con tus datos reales
 * 3. Descomenta las líneas de producción
 * 4. Comenta las líneas de desarrollo
 * 5. Asegúrate de que el archivo .htaccess proteja este archivo
 */

// =============================================================================
// DESARROLLO / STAGING
// =============================================================================
/*
// Para desarrollo y testing (usar siempre en desarrollo)
const TRANSBANK_MOCK = true;
const TRANSBANK_COMMERCE_CODE = '';  // Sin código en desarrollo
const TRANSBANK_API_KEY = '';        // Sin API key en desarrollo  
const TRANSBANK_ENV = 'Integration'; // Ambiente de integración
*/

// =============================================================================
// PRODUCCIÓN (DESCOMENTA CUANDO ESTÉS LISTO PARA VENDER)
// =============================================================================

// IMPORTANTE: Descomenta estas líneas cuando tengas las credenciales reales
/*
const TRANSBANK_MOCK = false;
const TRANSBANK_COMMERCE_CODE = 'TU_CODIGO_COMERCIO_AQUI';     // Reemplazar con tu código de comercio
const TRANSBANK_API_KEY = 'TU_API_KEY_AQUI';                   // Reemplazar con tu API key
const TRANSBANK_ENV = 'Production';                            // Ambiente de producción
*/

// =============================================================================
// CONFIGURACIÓN DE CERTIFICADOS (OBLIGATORIO EN PRODUCCIÓN)
// =============================================================================

// Rutas de certificados (obligatorios en producción)
const TRANSBANK_PRIVATE_KEY_PATH = '/ruta/absoluta/a/tu/clave_privada.key';
const TRANSBANK_PUBLIC_CERT_PATH = '/ruta/absoluta/a/tu/certificado_publico.crt';
const TRANSBANK_BANK_CERT_PATH = '/ruta/absoluta/a/certificado_banco.crt';

// Verificación de SSL/TLS
const TRANSBANK_SSL_VERIFY = true;
const TRANSBANK_SSL_CA_PATH = '/ruta/a/ca-certificates.crt';

// Validación de certificados
define('TRANSBANK_VALIDATE_CERTS', true);

// =============================================================================
// CONFIGURACIÓN AVANZADA DE SEGURIDAD
// =============================================================================

// Timeouts y límites
const TRANSBANK_TIMEOUT = 30;                    // Segundos
const TRANSBANK_MAX_RETRIES = 3;                 // Máximo reintentos
const TRANSBANK_LOG_LEVEL = 'ERROR';             // ERROR, WARNING, INFO, DEBUG

// Validación de IP (opcional, para mayor seguridad)
const TRANSBANK_ALLOWED_IPS = [
    '190.82.0.0/16',   // IPs de Transbank Chile
    '200.12.24.0/24',  // Red adicional
    // Agregar más rangos según documentación de Transbank
];

// =============================================================================
// CONFIGURACIÓN DE WEBPAY PLUS
// =============================================================================

// URLs de retorno (ajustar según tu dominio)
const TRANSBANK_RETURN_URL = 'https://tudominio.com/pay_transbank.php';
const TRANSBANK_FINAL_URL = 'https://tudominio.com/pay_transbank.php';

// Configuración de sesión
const TRANSBANK_SESSION_TIMEOUT = 1800;  // 30 minutos

// Configuración de intentos por transacción
const TRANSBANK_MAX_TRANSACTION_ATTEMPTS = 5;

// =============================================================================
// CONFIGURACIÓN DE PARCELADO (OPCIONAL)
// =============================================================================

const TRANSBANK_ENABLE_INSTALLMENTS = true;
const TRANSBANK_MIN_INSTALLMENTS_AMOUNT = 3000;  // Mínimo para cuotas en CLP

const TRANSBANK_AVAILABLE_INSTALLMENTS = [
    '3' => ['label' => '3 cuotas sin interés'],
    '6' => ['label' => '6 cuotas sin interés'],
    '12' => ['label' => '12 cuotas sin interés'],
    '24' => ['label' => '24 cuotas sin interés']
];

// =============================================================================
// CONFIGURACIÓN DE WALLETS DIGITALES (OPCIONAL)
// =============================================================================

const TRANSBANK_ENABLE_ONE_TAP = true;          // Webpay OneTap
const TRANSBANK_ENABLE_KHIPU = true;           // Billetera khipu

// =============================================================================
// CONFIGURACIÓN DE LOGGING Y MONITOREO
// =============================================================================

// Archivos de log (asegurar permisos de escritura)
const TRANSBANK_LOG_FILE = '/var/log/transbank/transbank.log';
const TRANSBANK_ERROR_LOG_FILE = '/var/log/transbank/errors.log';
const TRANSBANK_AUDIT_LOG_FILE = '/var/log/transbank/audit.log';

// Rotación de logs (días)
const TRANSBANK_LOG_RETENTION_DAYS = 90;

// =============================================================================
// CONFIGURACIÓN DE EMAIL Y NOTIFICACIONES
// =============================================================================

// Notificaciones de errores críticas
const TRANSBANK_ERROR_NOTIFICATION_EMAIL = 'admin@tudominio.com';
const TRANSBANK_ERROR_NOTIFICATION_SLACK = 'https://hooks.slack.com/services/TU/SLACK/WEBHOOK';

// Notificaciones de transacciones exitosas
const TRANSBANK_SUCCESS_NOTIFICATIONS = true;
const TRANSBANK_ADMIN_EMAIL = 'ventas@tudominio.com';

// =============================================================================
// VALIDACIONES Y CHECKS DE SEGURIDAD
// =============================================================================

/**
 * Verificar si la configuración es válida para producción
 */
function validateTransbankConfig()
{
    $errors = [];

    if (!defined('TRANSBANK_MOCK') || TRANSBANK_MOCK) {
        return ['warning' => 'Sistema en modo desarrollo - No procesar pagos reales'];
    }

    // Validar credenciales
    if (empty(TRANSBANK_COMMERCE_CODE) || TRANSBANK_COMMERCE_CODE === 'TU_CODIGO_COMERCIO_AQUI') {
        $errors[] = 'Código de comercio no configurado';
    }

    if (empty(TRANSBANK_API_KEY) || TRANSBANK_API_KEY === 'TU_API_KEY_AQUI') {
        $errors[] = 'API Key no configurada';
    }

    if (TRANSBANK_ENV !== 'Production') {
        $errors[] = 'Ambiente no configurado para producción';
    }

    // Validar archivos de certificado (si están definidos)
    if (defined('TRANSBANK_PRIVATE_KEY_PATH') && !file_exists(TRANSBANK_PRIVATE_KEY_PATH)) {
        $errors[] = 'Archivo de clave privada no encontrado';
    }

    if (defined('TRANSBANK_PUBLIC_CERT_PATH') && !file_exists(TRANSBANK_PUBLIC_CERT_PATH)) {
        $errors[] = 'Archivo de certificado público no encontrado';
    }

    return $errors;
}

/**
 * Verificar que el servidor cumple con los requisitos mínimos
 */
function checkServerRequirements()
{
    $requirements = [
        'openssl' => extension_loaded('openssl'),
        'curl' => extension_loaded('curl'),
        'json' => extension_loaded('json'),
        'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'ssl_enabled' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'https_forced' => true // Forzar HTTPS en producción
    ];

    $missing = [];
    foreach ($requirements as $req => $status) {
        if (!$status && $req !== 'https_forced') {
            $missing[] = $req;
        }
    }

    return [
        'status' => empty($missing),
        'missing' => $missing,
        'requirements' => $requirements
    ];
}

/**
 * Log de auditoría de transacciones
 */
function logTransactionAudit($action, $data = [])
{
    $auditEntry = [
        'timestamp' => date('c'),
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'session_id' => session_id(),
        'data' => $data
    ];

    if (defined('TRANSBANK_AUDIT_LOG_FILE')) {
        error_log(json_encode($auditEntry) . PHP_EOL, 3, TRANSBANK_AUDIT_LOG_FILE);
    }
}

/**
 * Validación de IP permitida
 */
function isAllowedTransbankIP()
{
    if (!defined('TRANSBANK_ALLOWED_IPS') || empty(TRANSBANK_ALLOWED_IPS)) {
        return true; // Permitir todas si no hay restricciones
    }

    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

    foreach (TRANSBANK_ALLOWED_IPS as $allowedRange) {
        if (ip_in_range($clientIP, $allowedRange)) {
            return true;
        }
    }

    return false;
}

/**
 * Utilidad para verificar si un IP está en un rango
 */
function ip_in_range($ip, $range)
{
    if (strpos($range, '/') === false) {
        return $ip === $range;
    }

    list($subnet, $mask) = explode('/', $range);

    if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
        return true;
    }

    return false;
}

// =============================================================================
// CHECKS AL INCLUIR EL ARCHIVO
// =============================================================================

// Validar configuración al cargar
$configErrors = validateTransbankConfig();
if (!empty($configErrors['warning'])) {
    error_log('Transbank Config Warning: ' . $configErrors['warning']);
} elseif (!empty($configErrors)) {
    error_log('Transbank Config Errors: ' . implode(', ', $configErrors));
}

// Verificar requisitos del servidor
$serverCheck = checkServerRequirements();
if (!$serverCheck['status']) {
    error_log('Transbank Server Requirements Missing: ' . implode(', ', $serverCheck['missing']));
}
