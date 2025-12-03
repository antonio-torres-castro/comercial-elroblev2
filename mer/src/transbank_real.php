<?php
/**
 * Configuración CORRECTA de Transbank SDK - Versión Actualizada
 * 
 * CORREGIDO: Usa las clases reales que están disponibles en tu instalación
 */

// Configuración básica (desde tu config.php)
$TRANSBANK_ENV = 'sandbox'; // 'sandbox' o 'production'
$TRANSBANK_COMMERCE_CODE = '597012345678'; // Tu código de comercio
$TRANSBANK_API_KEY = 'test_api_key'; // Tu API key
$TRANSBANK_PRIVATE_KEY_PATH = '/path/to/private.key'; // Ruta al certificado

/**
 * Configuración de Transbank - Versión CORREGIDA
 * 
 * @return object|null Options object o null si falla
 */
function transbank_config() {
    try {
        // Verificar que tenemos el SDK instalado
        if (!class_exists('\Transbank\Webpay\Options')) {
            throw new Exception("SDK de Transbank no encontrado. Ejecuta: composer install");
        }
        
        // Configuración usando la clase Options real
        return new \Transbank\Webpay\Options(
            $GLOBALS['TRANSBANK_COMMERCE_CODE'],
            $GLOBALS['TRANSBANK_API_KEY'],
            $GLOBALS['TRANSBANK_PRIVATE_KEY_PATH']
        );
        
    } catch (Exception $e) {
        error_log("Error configurando Transbank: " . $e->getMessage());
        return null;
    }
}

/**
 * Crear transacción Webpay Plus - Versión CORREGIDA
 * 
 * @param object $options Options object
 * @return object Transaction object
 */
function transbank_transaction($options) {
    try {
        if (!class_exists('\Transbank\Webpay\WebpayPlus\Transaction')) {
            throw new Exception("Clase WebpayPlus\\Transaction no encontrada");
        }
        
        return new \Transbank\Webpay\WebpayPlus\Transaction($options);
        
    } catch (Exception $e) {
        error_log("Error creando transacción: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Crear transacción Webpay Plus Mall - Versión CORREGIDA
 * Para transacciones con múltiples comercios
 * 
 * @param object $options Options object
 * @return object MallTransaction object
 */
function transbank_mall_transaction($options) {
    try {
        if (!class_exists('\Transbank\Webpay\WebpayPlus\MallTransaction')) {
            throw new Exception("Clase WebpayPlus\\MallTransaction no encontrada");
        }
        
        return new \Transbank\Webpay\WebpayPlus\MallTransaction($options);
        
    } catch (Exception $e) {
        error_log("Error creando transacción mall: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Verificar si Transbank SDK está disponible
 * 
 * @return bool true si está disponible
 */
function transbank_available() {
    return class_exists('\Transbank\Webpay\Options') && 
           class_exists('\Transbank\Webpay\WebpayPlus\Transaction');
}

/**
 * Obtener clase de configuración disponible
 * 
 * @return string Nombre de la clase de configuración
 */
function transbank_config_class() {
    if (class_exists('\Transbank\Webpay\Options')) {
        return '\Transbank\Webpay\Options';
    }
    return 'Options (no disponible)';
}

/**
 * Obtener clases de transacción disponibles
 * 
 * @return array Lista de clases de transacción disponibles
 */
function transbank_transaction_classes() {
    $classes = [];
    
    if (class_exists('\Transbank\Webpay\WebpayPlus\Transaction')) {
        $classes[] = 'Transbank\Webpay\WebpayPlus\Transaction';
    }
    
    if (class_exists('\Transbank\Webpay\WebpayPlus\MallTransaction')) {
        $classes[] = 'Transbank\Webpay\WebpayPlus\MallTransaction';
    }
    
    if (class_exists('\Transbank\Webpay\Oneclick')) {
        $classes[] = 'Transbank\Webpay\Oneclick';
    }
    
    if (class_exists('\Transbank\Webpay\Modal')) {
        $classes[] = 'Transbank\Webpay\Modal';
    }
    
    return $classes;
}
?>