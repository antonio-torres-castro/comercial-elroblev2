<?php
// Sistema de Rutas Centralizado para Tienda Virtual
// Esto soluciona el problema de rutas en diferentes entornos

class Router
{
    private static $base_path = '';
    private static $current_domain = '';

    public static function init()
    {
        // Detectar el entorno actual
        if (isset($_SERVER['HTTP_HOST'])) {
            self::$current_domain = $_SERVER['HTTP_HOST'];
        } else {
            self::$current_domain = 'localhost';
        }

        // Configurar base path según el entorno
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/mer/') !== false) {
            self::$base_path = '/mer/';
        } else {
            self::$base_path = '/';
        }
    }

    /**
     * Generar URL absoluta correcta para cualquier entorno
     */
    public static function url($path = '')
    {
        if (empty($path)) {
            return self::$base_path;
        }

        // Remover slash inicial si existe para evitar doble slash
        $path = ltrim($path, '/');
        return self::$base_path . $path;
    }

    /**
     * URL para tienda específica
     */
    public static function storeUrl($store_id, $page = 'index')
    {
        return self::url("stores/{$store_id}/{$page}");
    }

    /**
     * URL para admin de tienda
     */
    public static function adminStoreUrl($store_id, $module = 'dashboard')
    {
        return self::url("admin_store_{$module}.php?store_id={$store_id}");
    }

    /**
     * URL para checkout
     */
    public static function checkoutUrl($type = 'advanced')
    {
        return self::url("checkout_{$type}.php");
    }

    /**
     * URL para assets (CSS, JS, images)
     */
    public static function asset($path)
    {
        return self::url("assets/{$path}");
    }

    /**
     * URL para API endpoints
     */
    public static function api($endpoint)
    {
        return self::url("api/{$endpoint}");
    }

    /**
     * Detectar si estamos en entorno local o producción
     */
    public static function isLocal()
    {
        return in_array(self::$current_domain, ['localhost', '127.0.0.1']);
    }

    /**
     * Obtener base URL completa
     */
    public static function baseUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        return $protocol . self::$current_domain . self::$base_path;
    }
}

// Inicializar el router
Router::init();

/**
 * Función helper global para generar URLs
 */
function url($path = '')
{
    return Router::url($path);
}

/**
 * Función helper para URLs de tiendas
 */
function store_url($store_id, $page = 'index')
{
    return Router::storeUrl($store_id, $page);
}

/**
 * Función helper para URLs de admin
 */
function admin_store_url($store_id, $module = 'dashboard')
{
    return Router::adminStoreUrl($store_id, $module);
}
