<?php
/**
 * Helpers para vistas - FASE 3 Mejoras de Seguridad
 * Funciones para acceso seguro a datos y escape HTML
 */

/**
 * Acceso seguro a arrays anidados en vistas
 * @param array $data Array de datos
 * @param string $path Ruta usando notación de punto: 'menu.id'
 * @param mixed $default Valor por defecto
 * @return mixed Valor encontrado o valor por defecto
 */
function safe($data, $path, $default = '') {
    $keys = explode('.', $path);
    $current = $data;
    
    foreach ($keys as $key) {
        if (!is_array($current) || !array_key_exists($key, $current)) {
            return $default;
        }
        $current = $current[$key];
    }
    
    return $current;
}

/**
 * Escape HTML seguro con acceso a arrays anidados
 * @param array $data Array de datos
 * @param string $path Ruta usando notación de punto
 * @param mixed $default Valor por defecto
 * @return string Valor escapado para HTML
 */
function safeHtml($data, $path, $default = '') {
    $value = safe($data, $path, $default);
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Acceso seguro con verificación de existencia para atributos HTML
 * @param array $data Array de datos
 * @param string $path Ruta del valor
 * @param mixed $checkValue Valor a comparar
 * @param string $output Salida si coincide
 * @return string Salida si coincide o cadena vacía
 */
function safeSelected($data, $path, $checkValue, $output = 'selected') {
    $value = safe($data, $path);
    return ($value == $checkValue) ? $output : '';
}

/**
 * Acceso seguro para checkboxes
 * @param array $data Array de datos
 * @param string $path Ruta del valor
 * @param mixed $checkValue Valor a comparar (default: true, 1, 'on')
 * @return string 'checked' si coincide o cadena vacía
 */
function safeChecked($data, $path, $checkValue = true) {
    $value = safe($data, $path);
    $truthy = [true, 1, '1', 'on', 'yes', 'true'];
    return in_array($value, $truthy, true) || $value == $checkValue ? 'checked' : '';
}

/**
 * Formatear fecha de forma segura
 * @param array $data Array de datos
 * @param string $path Ruta de la fecha
 * @param string $format Formato de salida
 * @param string $default Valor por defecto
 * @return string Fecha formateada o valor por defecto
 */
function safeDate($data, $path, $format = 'Y-m-d', $default = '') {
    $value = safe($data, $path);
    if (empty($value)) {
        return $default;
    }
    
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $value) ?: DateTime::createFromFormat('Y-m-d', $value);
    return $date ? $date->format($format) : $default;
}

/**
 * Mostrar valor numérico de forma segura
 * @param array $data Array de datos
 * @param string $path Ruta del número
 * @param int $decimals Número de decimales
 * @param mixed $default Valor por defecto
 * @return string Número formateado o valor por defecto
 */
function safeNumber($data, $path, $decimals = 0, $default = '0') {
    $value = safe($data, $path);
    return is_numeric($value) ? number_format((float)$value, $decimals) : $default;
}