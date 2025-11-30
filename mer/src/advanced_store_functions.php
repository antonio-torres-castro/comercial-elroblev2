<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

/**
 * Obtener todos los productos de una tienda con stock actual y paginación
 */
function getStoreProductsWithStock(int $storeId, int $limit = 100, int $offset = 0): array {
    // Validar parámetros de paginación
    $limit = max(1, min(1000, $limit)); // Entre 1 y 1000
    $offset = max(0, $offset); // No negativo
    
    $stmt = db()->prepare("
        SELECT 
            p.*,
            s.name as store_name,
            COALESCE(SUM(sm.quantity), 0) as current_stock,
            COALESCE(sm.last_updated, p.created_at) as stock_updated
        FROM products p
        JOIN stores s ON s.id = p.store_id
        LEFT JOIN stock_movements sm ON sm.product_id = p.id
        WHERE p.store_id = ?
        GROUP BY p.id
        ORDER BY p.name
        LIMIT ?, ?
    ");
    
    $stmt->bindValue(1, $storeId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Obtener movimientos de stock de un producto con paginación
 */
function getStockMovements(int $productId, int $limit = 50, int $offset = 0): array {
    if ($limit <= 0 || $limit > 1000) $limit = 50;
    if ($offset < 0) $offset = 0;
    
    $stmt = db()->prepare("
        SELECT 
            sm.*,
            p.name as product_name,
            u.username as performed_by
        FROM stock_movements sm
        JOIN products p ON p.id = sm.product_id
        LEFT JOIN users u ON u.id = sm.performed_by_user_id
        WHERE sm.product_id = ?
        ORDER BY sm.created_at DESC
        LIMIT ?, ?
    ");
    
    $stmt->bindValue(1, $productId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Obtener fechas disponibles para un producto específico
 */
function getProductAvailableDates(int $productId, int $daysAhead = 30, int $limit = 30, int $offset = 0): array {
    if ($limit <= 0 || $limit > 100) $limit = 30;
    if ($offset < 0) $offset = 0;
    
    $stmt = db()->prepare("
        SELECT 
            DATE(pdc.capacity_date) as available_date,
            pdc.available_capacity - pdc.booked_capacity as remaining_capacity,
            pdc.available_capacity,
            pdc.booked_capacity
        FROM product_daily_capacity pdc
        WHERE pdc.product_id = ?
        AND pdc.capacity_date >= CURDATE()
        AND pdc.capacity_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
        AND (pdc.available_capacity - pdc.booked_capacity) > 0
        ORDER BY pdc.capacity_date
        LIMIT ?, ?
    ");
    
    $stmt->bindValue(1, $productId, PDO::PARAM_INT);
    $stmt->bindValue(2, $daysAhead, PDO::PARAM_INT);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Obtener ubicaciones de retiro para una tienda
 */
function getStorePickupLocations(int $storeId): array {
    $stmt = db()->prepare("
        SELECT 
            spl.*,
            s.name as store_name
        FROM store_pickup_locations spl
        JOIN stores s ON s.id = spl.store_id
        WHERE spl.store_id = ?
        AND spl.active = 1
        ORDER BY spl.name
    ");
    $stmt->execute([$storeId]);
    return $stmt->fetchAll();
}

/**
 * Obtener estadísticas de productos con bajo stock
 */
function getLowStockProducts(int $storeId = null): array {
    $whereClause = $storeId ? "WHERE p.store_id = ?" : "";
    $params = $storeId ? [$storeId] : [];
    
    $stmt = db()->prepare("
        SELECT 
            p.id,
            p.name,
            p.store_id,
            s.name as store_name,
            p.stock_quantity,
            p.stock_min_threshold,
            p.stock_quantity - p.stock_min_threshold as stock_remaining
        FROM products p
        JOIN stores s ON s.id = p.store_id
        $whereClause
        AND p.stock_quantity <= p.stock_min_threshold
        ORDER BY p.stock_quantity ASC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtener estadísticas de disponibilidad de productos
 */
function getProductAvailabilityStats(int $storeId = null, int $daysAhead = 7): array {
    $whereClause = $storeId ? "WHERE p.store_id = ?" : "";
    $params = $storeId ? [$storeId] : [];
    
    $stmt = db()->prepare("
        SELECT 
            p.id,
            p.name,
            s.name as store_name,
            COUNT(*) as total_slots,
            SUM(CASE WHEN pdc.available_capacity > pdc.booked_capacity THEN 1 ELSE 0 END) as available_days,
            SUM(CASE WHEN pdc.available_capacity <= pdc.booked_capacity THEN 1 ELSE 0 END) as fully_booked_days,
            SUM(pdc.available_capacity - pdc.booked_capacity) as total_available_slots
        FROM products p
        JOIN stores s ON s.id = p.store_id
        JOIN product_daily_capacity pdc ON pdc.product_id = p.id
        $whereClause
        AND pdc.capacity_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        GROUP BY p.id, p.name, s.name
        ORDER BY s.name, p.name
    ");
    $params[] = $daysAhead;
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtener estadísticas de despachos
 */
function getDeliveryStats(int $storeId = null, ?string $startDate = null, ?string $endDate = null): array {
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($storeId) {
        $whereClause .= " AND dg.store_id = ?";
        $params[] = $storeId;
    }
    
    if ($startDate) {
        $whereClause .= " AND DATE(dg.created_at) >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $whereClause .= " AND DATE(dg.created_at) <= ?";
        $params[] = $endDate;
    }
    
    $stmt = db()->prepare("
        SELECT 
            dg.status,
            COUNT(*) as group_count,
            SUM(dgi.subtotal) as total_subtotal,
            AVG(dgi.subtotal) as avg_subtotal
        FROM delivery_groups dg
        LEFT JOIN delivery_group_items dgi ON dgi.delivery_group_id = dg.id
        $whereClause
        GROUP BY dg.status
        ORDER BY dg.status
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtener capacidades diarias de todos los productos de una tienda
 */
function getStoreProductCapacities(int $storeId): array {
    $stmt = db()->prepare("
        SELECT 
            p.id as product_id,
            p.name as product_name,
            p.service_type,
            COUNT(pdc.id) as configured_days,
            SUM(pdc.available_capacity) as total_configured_capacity,
            SUM(CASE WHEN pdc.capacity_date >= CURDATE() THEN pdc.available_capacity ELSE 0 END) as future_capacity,
            SUM(pdc.booked_capacity) as total_booked_capacity
        FROM products p
        LEFT JOIN product_daily_capacity pdc ON p.id = pdc.product_id
        WHERE p.store_id = ?
        GROUP BY p.id, p.name, p.service_type
        ORDER BY p.name
    ");
    $stmt->execute([$storeId]);
    return $stmt->fetchAll();
}

/**
 * Obtener capacidades diarias específicas para un producto en un rango de fechas
 */
function getProductDailyCapacities(int $productId, int $daysAhead = 30): array {
    $stmt = db()->prepare("
        SELECT 
            DATE(capacity_date) as date,
            available_capacity,
            booked_capacity,
            (available_capacity - booked_capacity) as remaining_capacity
        FROM product_daily_capacity
        WHERE product_id = ?
        AND capacity_date >= CURDATE()
        AND capacity_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY capacity_date
    ");
    $stmt->execute([$productId, $daysAhead]);
    return $stmt->fetchAll();
}

/**
 * Actualizar o crear capacidad diaria para un producto
 */
function updateProductDailyCapacity(int $productId, int $storeId, string $date, int $availableCapacity, ?string $notes = null): bool {
    // Verificar que la fecha no sea en el pasado
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        return false;
    }
    
    $stmt = db()->prepare("
        INSERT INTO product_daily_capacity (product_id, store_id, capacity_date, available_capacity, booked_capacity, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            available_capacity = VALUES(available_capacity),
            notes = VALUES(notes),
            updated_at = NOW()
    ");
    
    return $stmt->execute([$productId, $storeId, $date, $availableCapacity, $notes]);
}

/**
 * Eliminar capacidad diaria para un producto
 */
function deleteProductDailyCapacity(int $productId, string $date): bool {
    $stmt = db()->prepare("
        DELETE FROM product_daily_capacity 
        WHERE product_id = ? AND capacity_date = ?
    ");
    return $stmt->execute([$productId, $date]);
}

/**
 * Configurar capacidad masiva para múltiples fechas de un producto
 */
function bulkUpdateProductCapacities(int $productId, int $storeId, array $dateCapacities): array {
    $results = [];
    
    foreach ($dateCapacities as $date => $capacity) {
        if (empty($date) || !is_numeric($capacity) || $capacity < 0) {
            $results[$date] = ['success' => false, 'error' => 'Fecha o capacidad inválida'];
            continue;
        }
        
        $success = updateProductDailyCapacity($productId, $storeId, $date, (int)$capacity);
        $results[$date] = ['success' => $success];
    }
    
    return $results;
}

/**
 * Obtener estadísticas de capacidad por producto
 */
function getProductCapacityStats(int $productId, int $daysAhead = 30): array {
    $stmt = db()->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(available_capacity) as total_configured_capacity,
            SUM(booked_capacity) as total_booked_capacity,
            SUM(available_capacity - booked_capacity) as total_remaining_capacity,
            AVG(available_capacity) as avg_daily_capacity,
            SUM(CASE WHEN (available_capacity - booked_capacity) <= 0 THEN 1 ELSE 0 END) as fully_booked_days,
            SUM(CASE WHEN available_capacity = 0 THEN 1 ELSE 0 END) as unavailable_days
        FROM product_daily_capacity
        WHERE product_id = ?
        AND capacity_date >= CURDATE()
        AND capacity_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ");
    $stmt->execute([$productId, $daysAhead]);
    return $stmt->fetch() ?: [];
}

/**
 * Configurar zonas geográficas de servicio para una tienda
 */
function updateStoreServiceZones(int $storeId, array $zones): bool {
    // Primero eliminar zonas existentes
    $stmt = db()->prepare("DELETE FROM store_service_zones WHERE store_id = ?");
    $stmt->execute([$storeId]);
    
    // Insertar nuevas zonas
    $stmt = db()->prepare("
        INSERT INTO store_service_zones (store_id, zone_name, zone_type, city, region, max_services_per_day, active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    foreach ($zones as $zone) {
        if (!empty($zone['name']) && !empty($zone['type']) && is_numeric($zone['max_services'])) {
            $zoneType = in_array($zone['type'], ['ciudad', 'comuna', 'region']) ? $zone['type'] : 'ciudad';
            $maxServices = max(1, (int)$zone['max_services']);
            
            $stmt->execute([
                $storeId,
                $zone['name'],
                $zoneType,
                $zone['city'] ?? '',
                $zone['region'] ?? '',
                $maxServices
            ]);
        }
    }
    
    return true;
}

/**
 * Obtener zonas de servicio de una tienda
 */
function getStoreServiceZones(int $storeId): array {
    $stmt = db()->prepare("
        SELECT * FROM store_service_zones 
        WHERE store_id = ? AND active = 1 
        ORDER BY zone_name
    ");
    $stmt->execute([$storeId]);
    return $stmt->fetchAll();
}

/**
 * Configurar horarios por defecto para productos/servicios
 */
function setProductDefaultSchedule(int $productId, array $schedule): bool {
    $stmt = db()->prepare("
        INSERT INTO product_default_schedule (product_id, day_of_week, start_time, end_time, active)
        VALUES (?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
            start_time = VALUES(start_time),
            end_time = VALUES(end_time),
            updated_at = NOW()
    ");
    
    foreach ($schedule as $day => $times) {
        if (isset($times['start']) && isset($times['end'])) {
            $dayOfWeek = (int)$day; // 0 = domingo, 1 = lunes, etc.
            $startTime = $times['start'];
            $endTime = $times['end'];
            
            if ($dayOfWeek >= 0 && $dayOfWeek <= 6) {
                $stmt->execute([$productId, $dayOfWeek, $startTime, $endTime]);
            }
        }
    }
    
    return true;
}

/**
 * Obtener horarios por defecto de un producto
 */
function getProductDefaultSchedule(int $productId): array {
    $stmt = db()->prepare("
        SELECT * FROM product_default_schedule 
        WHERE product_id = ? AND active = 1 
        ORDER BY day_of_week
    ");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

/**
 * Generar capacidades automáticas para un producto basado en su configuración
 */
function generateAutomaticCapacities(int $productId, int $storeId, int $daysAhead = 30, ?int $defaultCapacity = null): bool {
    // Obtener configuración del producto
    $product = db()->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
    $product->execute([$productId, $storeId]);
    $product = $product->fetch();
    
    if (!$product) return false;
    
    // Usar capacidad del producto o la pasada como parámetro
    $capacityToUse = $defaultCapacity ?? $product['stock_quantity'] ?? 10;
    
    // Generar capacidades para los próximos días
    $currentDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$daysAhead days"));
    
    $stmt = db()->prepare("
        INSERT IGNORE INTO product_daily_capacity (product_id, store_id, capacity_date, available_capacity, booked_capacity, created_at)
        SELECT ?, ?, date_series.date, ?, 0, NOW()
        FROM (
            SELECT DATE_ADD('$currentDate', INTERVAL row_number OVER () - 1 DAY) as date
            FROM (
                SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION
                SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
            ) numbers
            CROSS JOIN (
                SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION
                SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION
                SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
                SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
                SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION
                SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
            ) more_numbers
            LIMIT $daysAhead
        ) date_series
        WHERE date_series.date <= '$endDate'
    ");
    
    return $stmt->execute([$productId, $storeId, $capacityToUse]);
}

/**
 * =====================================
 * SISTEMA DE CONFIGURACIÓN DE TIENDA
 * =====================================
 */

/**
 * Obtener configuraciones de una tienda
 */
function getStoreConfigurations(int $storeId): array {
    $stmt = db()->prepare("
        SELECT * FROM store_configurations 
        WHERE store_id = ? 
        ORDER BY category, config_key
    ");
    $stmt->execute([$storeId]);
    return $stmt->fetchAll();
}

/**
 * Obtener configuración específica de una tienda
 */
function getStoreConfig(int $storeId, string $category, string $configKey): ?array {
    $stmt = db()->prepare("
        SELECT * FROM store_configurations 
        WHERE store_id = ? AND category = ? AND config_key = ?
    ");
    $stmt->execute([$storeId, $category, $configKey]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Establecer configuración de una tienda
 */
function setStoreConfig(int $storeId, string $category, string $configKey, string $configValue, ?string $description = null): bool {
    $stmt = db()->prepare("
        INSERT INTO store_configurations (store_id, category, config_key, config_value, description, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            config_value = VALUES(config_value),
            description = COALESCE(VALUES(description), description),
            updated_at = NOW()
    ");
    return $stmt->execute([$storeId, $category, $configKey, $configValue, $description]);
}

/**
 * Obtener categorías de configuración disponibles
 */
function getConfigCategories(): array {
    return [
        'payment_methods' => 'Métodos de Pago',
        'language' => 'Idioma y Regionalización',
        'permissions' => 'Usuarios y Permisos',
        'integrations' => 'Integraciones',
        'notifications' => 'Notificaciones',
        'security' => 'Seguridad',
        'general' => 'Configuración General'
    ];
}

/**
 * Obtener métodos de pago disponibles
 */
function getAvailablePaymentMethods(): array {
    return [
        'transbank_webpay' => 'Transbank WebPay Plus',
        'transbank_onepay' => 'Transbank OnePay',
        'cash' => 'Efectivo',
        'transfer' => 'Transferencia Bancaria',
        'check' => 'Cheque',
        'credit_term' => 'Plazo de Pago'
    ];
}

/**
 * Verificar si Transbank está configurado
 */
function isTransbankConfigured(int $storeId): array {
    $config = getStoreConfig($storeId, 'payment_methods', 'transbank_enabled');
    
    if (!$config || $config['config_value'] !== 'true') {
        return ['configured' => false, 'message' => 'Transbank no está habilitado'];
    }
    
    // Verificar credenciales
    $commerceCode = getStoreConfig($storeId, 'payment_methods', 'transbank_commerce_code');
    $apiKey = getStoreConfig($storeId, 'payment_methods', 'transbank_api_key');
    
    if (!$commerceCode || empty($commerceCode['config_value']) || 
        !$apiKey || empty($apiKey['config_value'])) {
        return ['configured' => false, 'message' => 'Credenciales de Transbank incompletas'];
    }
    
    return [
        'configured' => true, 
        'environment' => getStoreConfig($storeId, 'payment_methods', 'transbank_environment')['config_value'] ?? 'Integration',
        'commerce_code' => $commerceCode['config_value'],
        'enabled' => true
    ];
}

/**
 * Configurar Transbank para una tienda
 */
function configureTransbank(int $storeId, array $config): bool {
    $required = ['commerce_code', 'api_key', 'environment'];
    
    foreach ($required as $field) {
        if (!isset($config[$field]) || empty($config[$field])) {
            return false;
        }
    }
    
    // Configurar credenciales
    $success = true;
    $success &= setStoreConfig($storeId, 'payment_methods', 'transbank_enabled', 'true', 'Habilitar procesamiento de pagos Transbank');
    $success &= setStoreConfig($storeId, 'payment_methods', 'transbank_commerce_code', $config['commerce_code'], 'Código de comercio Transbank');
    $success &= setStoreConfig($storeId, 'payment_methods', 'transbank_api_key', $config['api_key'], 'API Key Transbank');
    $success &= setStoreConfig($storeId, 'payment_methods', 'transbank_environment', $config['environment'], 'Ambiente Transbank (Integration/Production)');
    
    if (isset($config['private_key_path'])) {
        $success &= setStoreConfig($storeId, 'payment_methods', 'transbank_private_key_path', $config['private_key_path'], 'Ruta a clave privada');
    }
    
    if (isset($config['public_cert_path'])) {
        $success &= setStoreConfig($storeId, 'payment_methods', 'transbank_public_cert_path', $config['public_cert_path'], 'Ruta a certificado público');
    }
    
    if (isset($config['bank_cert_path'])) {
        $success &= setStoreConfig($storeId, 'payment_methods', 'transbank_bank_cert_path', $config['bank_cert_path'], 'Ruta a certificado del banco');
    }
    
    return $success;
}

/**
 * Obtener configuración de idioma
 */
function getLanguageConfig(int $storeId): array {
    $defaultLang = getStoreConfig($storeId, 'language', 'default_language')['config_value'] ?? 'es';
    $timezone = getStoreConfig($storeId, 'language', 'timezone')['config_value'] ?? 'America/Santiago';
    $dateFormat = getStoreConfig($storeId, 'language', 'date_format')['config_value'] ?? 'd/m/Y';
    $currency = getStoreConfig($storeId, 'language', 'currency')['config_value'] ?? 'CLP';
    $decimalSeparator = getStoreConfig($storeId, 'language', 'decimal_separator')['config_value'] ?? ',';
    
    return [
        'default_language' => $defaultLang,
        'timezone' => $timezone,
        'date_format' => $dateFormat,
        'currency' => $currency,
        'decimal_separator' => $decimalSeparator,
        'thousands_separator' => getStoreConfig($storeId, 'language', 'thousands_separator')['config_value'] ?? '.'
    ];
}

/**
 * Establecer configuración de idioma
 */
function setLanguageConfig(int $storeId, array $config): bool {
    $success = true;
    
    if (isset($config['default_language'])) {
        $success &= setStoreConfig($storeId, 'language', 'default_language', $config['default_language'], 'Idioma predeterminado');
    }
    
    if (isset($config['timezone'])) {
        $success &= setStoreConfig($storeId, 'language', 'timezone', $config['timezone'], 'Zona horaria');
    }
    
    if (isset($config['date_format'])) {
        $success &= setStoreConfig($storeId, 'language', 'date_format', $config['date_format'], 'Formato de fecha');
    }
    
    if (isset($config['currency'])) {
        $success &= setStoreConfig($storeId, 'language', 'currency', $config['currency'], 'Moneda');
    }
    
    if (isset($config['decimal_separator'])) {
        $success &= setStoreConfig($storeId, 'language', 'decimal_separator', $config['decimal_separator'], 'Separador decimal');
    }
    
    if (isset($config['thousands_separator'])) {
        $success &= setStoreConfig($storeId, 'language', 'thousands_separator', $config['thousands_separator'], 'Separador de miles');
    }
    
    return $success;
}

/**
 * Obtener niveles de permisos
 */
function getPermissionLevels(): array {
    return [
        'admin' => [
            'name' => 'Administrador',
            'description' => 'Acceso completo al sistema',
            'permissions' => ['all']
        ],
        'manager' => [
            'name' => 'Gerente de Tienda',
            'description' => 'Administración de tienda específica',
            'permissions' => ['manage_store', 'view_reports', 'manage_inventory', 'process_orders']
        ],
        'employee' => [
            'name' => 'Empleado',
            'description' => 'Operaciones básicas',
            'permissions' => ['view_products', 'process_orders', 'confirm_deliveries']
        ]
    ];
}

/**
 * Obtener permisos configurados para una tienda
 */
function getStorePermissions(int $storeId): array {
    $defaultPermissions = getPermissionLevels();
    $configuredPerms = [];
    
    foreach ($defaultPermissions as $level => $data) {
        $enabled = getStoreConfig($storeId, 'permissions', $level . '_enabled');
        $configuredPerms[$level] = array_merge($data, [
            'enabled' => $enabled ? ($enabled['config_value'] === 'true') : ($level === 'employee') // Employee habilitado por defecto
        ]);
    }
    
    return $configuredPerms;
}

/**
 * Establecer permisos de una tienda
 */
function setStorePermissions(int $storeId, array $permissions): bool {
    $success = true;
    
    foreach ($permissions as $level => $data) {
        if (isset($data['enabled'])) {
            $success &= setStoreConfig($storeId, 'permissions', $level . '_enabled', 
                $data['enabled'] ? 'true' : 'false', 
                "Habilitar permisos de nivel $level"
            );
        }
    }
    
    return $success;
}

/**
 * Obtener integraciones configuradas
 */
function getStoreIntegrations(int $storeId): array {
    $integrations = [];
    
    // Transbank
    $transbankStatus = isTransbankConfigured($storeId);
    $integrations['transbank'] = [
        'name' => 'Transbank',
        'enabled' => $transbankStatus['configured'],
        'status' => $transbankStatus['configured'] ? 'active' : 'disabled',
        'environment' => $transbankStatus['environment'] ?? 'Integration',
        'last_check' => date('Y-m-d H:i:s')
    ];
    
    // SETAP (futuro)
    $setapEnabled = getStoreConfig($storeId, 'integrations', 'setap_enabled');
    $integrations['setap'] = [
        'name' => 'SETAP',
        'enabled' => $setapEnabled ? ($setapEnabled['config_value'] === 'true') : false,
        'status' => 'planned',
        'description' => 'Integración con sistema SETAP (futuro)',
        'last_check' => null
    ];
    
    return $integrations;
}

/**
 * Configurar integración SETAP
 */
function configureSETAP(int $storeId, array $config): bool {
    $success = true;
    
    if (isset($config['enabled'])) {
        $success &= setStoreConfig($storeId, 'integrations', 'setap_enabled', 
            $config['enabled'] ? 'true' : 'false', 
            'Habilitar integración SETAP'
        );
    }
    
    if (isset($config['api_endpoint'])) {
        $success &= setStoreConfig($storeId, 'integrations', 'setap_api_endpoint', 
            $config['api_endpoint'], 
            'URL del endpoint SETAP'
        );
    }
    
    if (isset($config['api_key'])) {
        $success &= setStoreConfig($storeId, 'integrations', 'setap_api_key', 
            $config['api_key'], 
            'API Key SETAP'
        );
    }
    
    return $success;
}

/**
 * Obtener configuración de notificaciones
 */
function getNotificationConfig(int $storeId): array {
    return [
        'email_enabled' => getStoreConfig($storeId, 'notifications', 'email_enabled')['config_value'] ?? 'true',
        'email_admin' => getStoreConfig($storeId, 'notifications', 'email_admin')['config_value'] ?? '',
        'email_sales' => getStoreConfig($storeId, 'notifications', 'email_sales')['config_value'] ?? '',
        'sms_enabled' => getStoreConfig($storeId, 'notifications', 'sms_enabled')['config_value'] ?? 'false',
        'order_confirmations' => getStoreConfig($storeId, 'notifications', 'order_confirmations')['config_value'] ?? 'true',
        'delivery_updates' => getStoreConfig($storeId, 'notifications', 'delivery_updates')['config_value'] ?? 'true'
    ];
}

/**
 * Establecer configuración de notificaciones
 */
function setNotificationConfig(int $storeId, array $config): bool {
    $success = true;
    
    $fields = ['email_enabled', 'email_admin', 'email_sales', 'sms_enabled', 'order_confirmations', 'delivery_updates'];
    
    foreach ($fields as $field) {
        if (isset($config[$field])) {
            $success &= setStoreConfig($storeId, 'notifications', $field, $config[$field], "Configuración de notificación: $field");
        }
    }
    
    return $success;
}

/**
 * Obtener estadísticas de configuración
 */
function getConfigStats(int $storeId): array {
    $configs = getStoreConfigurations($storeId);
    $stats = [
        'total_configs' => count($configs),
        'payment_methods' => 0,
        'language' => 0,
        'permissions' => 0,
        'integrations' => 0,
        'notifications' => 0,
        'security' => 0,
        'general' => 0
    ];
    
    foreach ($configs as $config) {
        if (isset($stats[$config['category']])) {
            $stats[$config['category']]++;
        }
    }
    
    // Verificar estado de integraciones
    $integrations = getStoreIntegrations($storeId);
    $activeIntegrations = array_filter($integrations, function($int) {
        return $int['enabled'] && $int['status'] === 'active';
    });
    
    $stats['active_integrations'] = count($activeIntegrations);
    $stats['total_integrations'] = count($integrations);
    
    return $stats;
}

/**
 * Exportar configuraciones de una tienda
 */
function exportStoreConfig(int $storeId): array {
    return [
        'store_id' => $storeId,
        'exported_at' => date('c'),
        'configurations' => getStoreConfigurations($storeId),
        'integrations' => getStoreIntegrations($storeId),
        'permissions' => getStorePermissions($storeId),
        'language' => getLanguageConfig($storeId),
        'notifications' => getNotificationConfig($storeId)
    ];
}

/**
 * Importar configuraciones a una tienda
 */
function importStoreConfig(int $storeId, array $configData): bool {
    if (!isset($configData['configurations'])) {
        return false;
    }
    
    $success = true;
    
    // Importar configuraciones individuales
    foreach ($configData['configurations'] as $config) {
        $success &= setStoreConfig(
            $storeId, 
            $config['category'], 
            $config['config_key'], 
            $config['config_value'], 
            $config['description'] ?? null
        );
    }
    
    return $success;
}

/**
 * Verificar integridad de configuración
 */
function validateStoreConfig(int $storeId): array {
    $issues = [];
    $warnings = [];
    
    // Verificar Transbank si está habilitado
    $transbankStatus = isTransbankConfigured($storeId);
    if ($transbankStatus['configured'] === false) {
        $transbankEnabled = getStoreConfig($storeId, 'payment_methods', 'transbank_enabled');
        if ($transbankEnabled && $transbankEnabled['config_value'] === 'true') {
            $issues[] = 'Transbank está habilitado pero no está correctamente configurado';
        }
    }
    
    // Verificar configuración de idioma
    $langConfig = getLanguageConfig($storeId);
    if ($langConfig['default_language'] !== 'es') {
        $warnings[] = 'Idioma predeterminado no es español';
    }
    
    // Verificar permisos mínimos
    $permissions = getStorePermissions($storeId);
    $hasActivePermissions = array_filter($permissions, function($perm) {
        return $perm['enabled'];
    });
    
    if (empty($hasActivePermissions)) {
        $issues[] = 'No hay niveles de permisos habilitados';
    }
    
    return [
        'valid' => empty($issues),
        'issues' => $issues,
        'warnings' => $warnings
    ];
}

// =========================================
// SISTEMA DE GESTIÓN DE CITAS Y RESERVAS
// =========================================

/**
 * Crear una nueva cita o reserva
 * @param int $storeId ID de la tienda
 * @param array $appointmentData Datos de la cita
 * @return array Resultado de la operación
 */
function createStoreAppointment(int $storeId, array $appointmentData): array {
    try {
        $pdo = getDBConnection();
        
        // Validar datos requeridos
        $required = ['customer_name', 'customer_phone', 'service_id', 'appointment_date', 'duration_hours'];
        foreach ($required as $field) {
            if (empty($appointmentData[$field])) {
                return ['success' => false, 'error' => "Campo requerido: $field"];
            }
        }
        
        // Validar duración mínima (0.5 días)
        if ($appointmentData['duration_hours'] < 0.5) {
            return ['success' => false, 'error' => 'La duración mínima es de 0.5 horas'];
        }
        
        // Validar fecha no sea pasada
        $appointmentDate = new DateTime($appointmentData['appointment_date']);
        $now = new DateTime();
        if ($appointmentDate < $now) {
            return ['success' => false, 'error' => 'La fecha de la cita no puede ser pasada'];
        }
        
        // Verificar disponibilidad si no se permite múltiples citas simultáneas
        if (!isset($appointmentData['allow_multiple']) || !$appointmentData['allow_multiple']) {
            $conflict = checkAppointmentConflict($storeId, $appointmentData['appointment_date'], $appointmentData['duration_hours']);
            if ($conflict) {
                return ['success' => false, 'error' => 'Conflicto con otra cita en ese horario'];
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO store_appointments (
                store_id, customer_name, customer_phone, customer_email, 
                service_id, appointment_date, duration_hours, status, 
                notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'programada', ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $storeId,
            $appointmentData['customer_name'],
            $appointmentData['customer_phone'],
            $appointmentData['customer_email'] ?? null,
            $appointmentData['service_id'],
            $appointmentData['appointment_date'],
            $appointmentData['duration_hours'],
            $appointmentData['notes'] ?? null,
            $_SESSION['user_id'] ?? null
        ]);
        
        if ($result) {
            $appointmentId = $pdo->lastInsertId();
            
            // Si es un servicio recurrente, crear recordatorios
            if (isset($appointmentData['is_recurring']) && $appointmentData['is_recurring']) {
                createRecurringAppointmentReminders($appointmentId, $appointmentData);
            }
            
            return ['success' => true, 'appointment_id' => $appointmentId];
        }
        
        return ['success' => false, 'error' => 'Error al crear la cita'];
        
    } catch (Exception $e) {
        error_log("Error creating appointment: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del sistema'];
    }
}

/**
 * Obtener citas de una tienda con filtros
 * @param int $storeId ID de la tienda
 * @param array $filters Filtros opcionales
 * @return array Lista de citas
 */
function getStoreAppointments(int $storeId, array $filters = []): array {
    try {
        $pdo = getDBConnection();
        
        $where = ['store_id = ?'];
        $params = [$storeId];
        
        // Filtro por fecha
        if (!empty($filters['date_from'])) {
            $where[] = 'appointment_date >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'appointment_date <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Filtro por estado
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        
        // Filtro por cliente
        if (!empty($filters['customer_phone'])) {
            $where[] = 'customer_phone LIKE ?';
            $params[] = '%' . $filters['customer_phone'] . '%';
        }
        
        $sql = "
            SELECT a.*, s.name as service_name, s.description as service_description,
                   u.name as created_by_name
            FROM store_appointments a
            LEFT JOIN store_services s ON a.service_id = s.id
            LEFT JOIN users u ON a.created_by = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.appointment_date ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting appointments: " . $e->getMessage());
        return [];
    }
}

/**
 * Verificar conflictos de horarios para una cita
 * @param int $storeId ID de la tienda
 * @param string $appointmentDate Fecha y hora de la cita
 * @param float $durationHours Duración en horas
 * @param int|null $excludeId ID de cita a excluir (para actualizaciones)
 * @return bool True si hay conflicto
 */
function checkAppointmentConflict(int $storeId, string $appointmentDate, float $durationHours, ?int $excludeId = null): bool {
    try {
        $pdo = getDBConnection();
        
        // Calcular rango de tiempo de la cita
        $startTime = new DateTime($appointmentDate);
        $endTime = clone $startTime;
        $endTime->add(new DateInterval('PT' . intval($durationHours * 60) . 'M'));
        
        $where = ['store_id = ?', 'status != ?'];
        $params = [$storeId, 'cancelada'];
        
        if ($excludeId) {
            $where[] = 'id != ?';
            $params[] = $excludeId;
        }
        
        // Buscar citas que se superpongan en el tiempo
        $stmt = $pdo->prepare("
            SELECT id, appointment_date, duration_hours
            FROM store_appointments
            WHERE " . implode(' AND ', $where) . "
        ");
        
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($appointments as $apt) {
            $existingStart = new DateTime($apt['appointment_date']);
            $existingEnd = clone $existingStart;
            $existingEnd->add(new DateInterval('PT' . intval($apt['duration_hours'] * 60) . 'M'));
            
            // Verificar superposición
            if ($startTime < $existingEnd && $endTime > $existingStart) {
                return true; // Hay conflicto
            }
        }
        
        return false; // No hay conflicto
        
    } catch (Exception $e) {
        error_log("Error checking appointment conflict: " . $e->getMessage());
        return true; // En caso de error, asumir conflicto por seguridad
    }
}

/**
 * Actualizar estado de una cita
 * @param int $appointmentId ID de la cita
 * @param string $newStatus Nuevo estado
 * @param string|null $reason Razón del cambio
 * @param int|null $storeId ID de la tienda (para validación)
 * @return array Resultado de la operación
 */
function updateAppointmentStatus(int $appointmentId, string $newStatus, ?string $reason = null, ?int $storeId = null): array {
    try {
        $pdo = getDBConnection();
        
        // Validar estado
        $validStatuses = ['programada', 'confirmada', 'en_proceso', 'completada', 'cancelada', 'no_asistio'];
        if (!in_array($newStatus, $validStatuses)) {
            return ['success' => false, 'error' => 'Estado no válido'];
        }
        
        // Validar política de cancelación si se está cancelando
        if ($newStatus === 'cancelada') {
            $validation = validateCancellationPolicy($appointmentId, $reason);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['message']];
            }
        }
        
        // Verificar permisos si se proporciona store_id
        if ($storeId && !checkAppointmentStoreAccess($appointmentId, $storeId)) {
            return ['success' => false, 'error' => 'Sin permisos para modificar esta cita'];
        }
        
        $stmt = $pdo->prepare("
            UPDATE store_appointments 
            SET status = ?, status_reason = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$newStatus, $reason, $appointmentId]);
        
        if ($result) {
            // Registrar cambio en historial
            logAppointmentStatusChange($appointmentId, $newStatus, $reason);
            
            // Notificar cambio de estado si es necesario
            if (in_array($newStatus, ['cancelada', 'confirmada'])) {
                sendAppointmentStatusNotification($appointmentId, $newStatus);
            }
            
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Error al actualizar el estado'];
        
    } catch (Exception $e) {
        error_log("Error updating appointment status: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del sistema'];
    }
}

/**
 * Crear servicio de cita
 * @param int $storeId ID de la tienda
 * @param array $serviceData Datos del servicio
 * @return array Resultado de la operación
 */
function createAppointmentService(int $storeId, array $serviceData): array {
    try {
        $pdo = getDBConnection();
        
        $required = ['name', 'description', 'default_duration_hours'];
        foreach ($required as $field) {
            if (empty($serviceData[$field])) {
                return ['success' => false, 'error' => "Campo requerido: $field"];
            }
        }
        
        // Validar duración mínima
        if ($serviceData['default_duration_hours'] < 0.5) {
            return ['success' => false, 'error' => 'La duración mínima es de 0.5 horas'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO store_services (
                store_id, name, description, default_duration_hours, 
                price, is_recurring, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $storeId,
            $serviceData['name'],
            $serviceData['description'],
            $serviceData['default_duration_hours'],
            $serviceData['price'] ?? null,
            isset($serviceData['is_recurring']) ? 1 : 0
        ]);
        
        if ($result) {
            return ['success' => true, 'service_id' => $pdo->lastInsertId()];
        }
        
        return ['success' => false, 'error' => 'Error al crear el servicio'];
        
    } catch (Exception $e) {
        error_log("Error creating appointment service: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del sistema'];
    }
}

/**
 * Obtener servicios de citas de una tienda
 * @param int $storeId ID de la tienda
 * @return array Lista de servicios
 */
function getAppointmentServices(int $storeId): array {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM store_services 
            WHERE store_id = ? 
            ORDER BY name ASC
        ");
        
        $stmt->execute([$storeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting appointment services: " . $e->getMessage());
        return [];
    }
}

/**
 * Generar calendario automático de citas
 * @param int $storeId ID de la tienda
 * @param string $dateFrom Fecha de inicio
 * @param string $dateTo Fecha de fin
 * @return array Estadísticas de generación
 */
function generateAutomaticCalendar(int $storeId, string $dateFrom, string $dateTo): array {
    try {
        $pdo = getDBConnection();
        
        // Obtener configuración de horarios de la tienda
        $scheduleConfig = getStoreScheduleConfig($storeId);
        $services = getAppointmentServices($storeId);
        
        $generated = 0;
        $errors = [];
        
        $currentDate = new DateTime($dateFrom);
        $endDate = new DateTime($dateTo);
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Verificar si es día laboral
            $dayOfWeek = $currentDate->format('N'); // 1 = lunes, 7 = domingo
            if ($dayOfWeek >= 1 && $dayOfWeek <= 6 && !isHoliday($dateString)) {
                
                // Generar citas para cada servicio recurrente
                foreach ($services as $service) {
                    if ($service['is_recurring']) {
                        $appointmentTimes = calculateAppointmentTimes($dateString, $scheduleConfig);
                        
                        foreach ($appointmentTimes as $timeSlot) {
                            $appointmentData = [
                                'customer_name' => 'Cliente Recurrente',
                                'customer_phone' => '000000000',
                                'service_id' => $service['id'],
                                'appointment_date' => $dateString . ' ' . $timeSlot,
                                'duration_hours' => $service['default_duration_hours'],
                                'allow_multiple' => true,
                                'is_recurring' => true,
                                'notes' => 'Generado automáticamente - Servicio recurrente'
                            ];
                            
                            $result = createStoreAppointment($storeId, $appointmentData);
                            if ($result['success']) {
                                $generated++;
                            } else {
                                $errors[] = "Error en $dateString $timeSlot: " . $result['error'];
                            }
                        }
                    }
                }
            }
            
            $currentDate->add(new DateInterval('P1D'));
        }
        
        return [
            'success' => true,
            'generated' => $generated,
            'errors' => $errors,
            'period' => ['from' => $dateFrom, 'to' => $dateTo]
        ];
        
    } catch (Exception $e) {
        error_log("Error generating automatic calendar: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del sistema'];
    }
}

/**
 * Validar política de cancelación
 * @param int $appointmentId ID de la cita
 * @param string|null $reason Razón de cancelación
 * @return array Resultado de validación
 */
function validateCancellationPolicy(int $appointmentId, ?string $reason = null): array {
    try {
        $pdo = getDBConnection();
        
        // Obtener datos de la cita
        $stmt = $pdo->prepare("
            SELECT a.*, s.cancellation_hours_before 
            FROM store_appointments a
            LEFT JOIN store_services s ON a.service_id = s.id
            WHERE a.id = ?
        ");
        
        $stmt->execute([$appointmentId]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            return ['valid' => false, 'message' => 'Cita no encontrada'];
        }
        
        // Verificar tiempo mínimo de anticipación
        if ($appointment['cancellation_hours_before']) {
            $appointmentDate = new DateTime($appointment['appointment_date']);
            $now = new DateTime();
            $hoursDifference = ($appointmentDate->getTimestamp() - $now->getTimestamp()) / 3600;
            
            if ($hoursDifference < $appointment['cancellation_hours_before']) {
                return [
                    'valid' => false, 
                    'message' => "La cancelación debe hacerse con al menos {$appointment['cancellation_hours_before']} horas de anticipación"
                ];
            }
        }
        
        // Verificar razón requerida
        $settings = getStoreAppointmentSettings($appointment['store_id']);
        if ($settings['require_cancellation_reason'] && empty($reason)) {
            return ['valid' => false, 'message' => 'Se requiere especificar una razón para la cancelación'];
        }
        
        return ['valid' => true, 'message' => 'Cancelación permitida'];
        
    } catch (Exception $e) {
        error_log("Error validating cancellation policy: " . $e->getMessage());
        return ['valid' => false, 'message' => 'Error al validar política de cancelación'];
    }
}

/**
 * Obtener configuración de políticas de cancelación
 * @param int $storeId ID de la tienda
 * @return array Configuración de políticas
 */
function getStoreCancellationPolicies(int $storeId): array {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM store_appointment_policies 
            WHERE store_id = ?
        ");
        
        $stmt->execute([$storeId]);
        $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Configuración por defecto si no existen políticas específicas
        if (empty($policies)) {
            return [
                'default_hours_before' => 24,
                'require_reason' => true,
                'auto_confirm' => true,
                'max_daily_appointments' => 20,
                'allow_double_booking' => false
            ];
        }
        
        return $policies;
        
    } catch (Exception $e) {
        error_log("Error getting cancellation policies: " . $e->getMessage());
        return [];
    }
}

/**
 * Actualizar configuración de políticas de cancelación
 * @param int $storeId ID de la tienda
 * @param array $policies Nueva configuración
 * @return array Resultado de la operación
 */
function updateStoreCancellationPolicies(int $storeId, array $policies): array {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO store_appointment_policies (
                store_id, hours_before_cancellation, require_cancellation_reason,
                auto_confirm_appointments, max_daily_appointments, allow_double_booking,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                hours_before_cancellation = VALUES(hours_before_cancellation),
                require_cancellation_reason = VALUES(require_cancellation_reason),
                auto_confirm_appointments = VALUES(auto_confirm_appointments),
                max_daily_appointments = VALUES(max_daily_appointments),
                allow_double_booking = VALUES(allow_double_booking),
                updated_at = VALUES(updated_at)
        ");
        
        $result = $stmt->execute([
            $storeId,
            $policies['hours_before_cancellation'] ?? 24,
            isset($policies['require_cancellation_reason']) ? 1 : 0,
            isset($policies['auto_confirm_appointments']) ? 1 : 0,
            $policies['max_daily_appointments'] ?? 20,
            isset($policies['allow_double_booking']) ? 1 : 0
        ]);
        
        if ($result) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Error al actualizar políticas'];
        
    } catch (Exception $e) {
        error_log("Error updating cancellation policies: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del sistema'];
    }
}

/**
 * Obtener estadísticas de citas para dashboard
 * @param int $storeId ID de la tienda
 * @param string|null $dateFrom Fecha de inicio
 * @param string|null $dateTo Fecha de fin
 * @return array Estadísticas
 */
function getAppointmentStatistics(int $storeId, ?string $dateFrom = null, ?string $dateTo = null): array {
    try {
        $pdo = getDBConnection();
        
        $where = ['store_id = ?'];
        $params = [$storeId];
        
        if ($dateFrom && $dateTo) {
            $where[] = 'appointment_date BETWEEN ? AND ?';
            $params[] = $dateFrom;
            $params[] = $dateTo . ' 23:59:59';
        } else {
            // Por defecto, último mes
            $where[] = 'appointment_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
        }
        
        // Estadísticas generales
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_appointments,
                COUNT(CASE WHEN status = 'completada' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as cancelled,
                COUNT(CASE WHEN status = 'programada' THEN 1 END) as scheduled,
                AVG(duration_hours) as avg_duration
            FROM store_appointments
            WHERE " . implode(' AND ', $where)
        );
        
        $stmt->execute($params);
        $generalStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Distribución por servicios
        $stmt = $pdo->prepare("
            SELECT s.name, COUNT(a.id) as appointment_count
            FROM store_services s
            LEFT JOIN store_appointments a ON s.id = a.service_id 
                AND (" . implode(' AND ', $where) . ")
            WHERE s.store_id = ?
            GROUP BY s.id, s.name
            ORDER BY appointment_count DESC
        ");
        
        $params[] = $storeId;
        $stmt->execute($params);
        $serviceStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Citas por día de la semana
        $stmt = $pdo->prepare("
            SELECT 
                DAYNAME(appointment_date) as day_name,
                DAYOFWEEK(appointment_date) as day_num,
                COUNT(*) as count
            FROM store_appointments
            WHERE " . implode(' AND ', $where) . "
            GROUP BY DAYOFWEEK(appointment_date), DAYNAME(appointment_date)
            ORDER BY day_num
        ");
        
        $stmt->execute(array_slice($params, 0, -1)); // Quitar store_id duplicado
        $weeklyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'general' => $generalStats,
            'by_service' => $serviceStats,
            'by_weekday' => $weeklyStats,
            'period' => ['from' => $dateFrom, 'to' => $dateTo]
        ];
        
    } catch (Exception $e) {
        error_log("Error getting appointment statistics: " . $e->getMessage());
        return [];
    }
}

// Funciones auxiliares para el sistema de citas

/**
 * Calcular horarios disponibles para un día
 * @param string $date Fecha
 * @param array $scheduleConfig Configuración de horarios
 * @return array Horarios disponibles
 */
function calculateAppointmentTimes(string $date, array $scheduleConfig): array {
    $times = [];
    $dateTime = new DateTime($date);
    
    $startHour = $scheduleConfig['start_time'] ?? '09:00';
    $endHour = $scheduleConfig['end_time'] ?? '18:00';
    $interval = $scheduleConfig['appointment_interval'] ?? 30; // minutos
    
    $current = clone $dateTime;
    $current->setTime(...explode(':', $startHour));
    $end = clone $dateTime;
    $end->setTime(...explode(':', $endHour));
    
    while ($current < $end) {
        $times[] = $current->format('H:i');
        $current->add(new DateInterval('PT' . $interval . 'M'));
    }
    
    return $times;
}

/**
 * Verificar si una fecha es día feriados
 * @param string $date Fecha
 * @return bool True si es feriado
 */
function isHoliday(string $date): bool {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT id FROM store_holidays 
            WHERE date = ?
        ");
        
        $stmt->execute([$date]);
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Error checking holiday: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener configuración de horarios de la tienda
 * @param int $storeId ID de la tienda
 * @return array Configuración de horarios
 */
function getStoreScheduleConfig(int $storeId): array {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM store_schedule_config 
            WHERE store_id = ?
        ");
        
        $stmt->execute([$storeId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            // Configuración por defecto
            return [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'appointment_interval' => 30,
                'working_days' => '1,2,3,4,5,6' // Lunes a Sábado
            ];
        }
        
        return $config;
        
    } catch (Exception $e) {
        error_log("Error getting schedule config: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener configuración de citas de la tienda
 * @param int $storeId ID de la tienda
 * @return array Configuración
 */
function getStoreAppointmentSettings(int $storeId): array {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM store_appointment_settings 
            WHERE store_id = ?
        ");
        
        $stmt->execute([$storeId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            // Configuración por defecto
            return [
                'require_cancellation_reason' => true,
                'send_confirmation_sms' => true,
                'send_reminder_sms' => true,
                'reminder_hours_before' => 24
            ];
        }
        
        return $settings;
        
    } catch (Exception $e) {
        error_log("Error getting appointment settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Crear recordatorios para citas recurrentes
 * @param int $appointmentId ID de la cita
 * @param array $appointmentData Datos de la cita
 */
function createRecurringAppointmentReminders(int $appointmentId, array $appointmentData): void {
    try {
        $pdo = getDBConnection();
        
        $appointmentDate = new DateTime($appointmentData['appointment_date']);
        $reminderDate = clone $appointmentDate;
        $reminderDate->sub(new DateInterval('P1D')); // 1 día antes
        
        $stmt = $pdo->prepare("
            INSERT INTO appointment_reminders (
                appointment_id, reminder_type, reminder_date, 
                message, status, created_at
            ) VALUES (?, 'recurring', ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $appointmentId,
            $reminderDate->format('Y-m-d H:i:s'),
            "Recordatorio: Tiene una cita programada para mañana"
        ]);
        
    } catch (Exception $e) {
        error_log("Error creating recurring reminders: " . $e->getMessage());
    }
}

/**
 * Verificar acceso a cita por tienda
 * @param int $appointmentId ID de la cita
 * @param int $storeId ID de la tienda
 * @return bool True si tiene acceso
 */
function checkAppointmentStoreAccess(int $appointmentId, int $storeId): bool {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT id FROM store_appointments 
            WHERE id = ? AND store_id = ?
        ");
        
        $stmt->execute([$appointmentId, $storeId]);
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Error checking appointment access: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar cambio de estado en historial
 * @param int $appointmentId ID de la cita
 * @param string $newStatus Nuevo estado
 * @param string|null $reason Razón
 */
function logAppointmentStatusChange(int $appointmentId, string $newStatus, ?string $reason): void {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO appointment_status_history (
                appointment_id, old_status, new_status, reason, 
                changed_by, changed_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $appointmentId,
            'programada', // Simplificado, se podría mejorar obteniendo el estado anterior
            $newStatus,
            $reason,
            $_SESSION['user_id'] ?? null
        ]);
        
    } catch (Exception $e) {
        error_log("Error logging status change: " . $e->getMessage());
    }
}

/**
 * Enviar notificación de cambio de estado
 * @param int $appointmentId ID de la cita
 * @param string $status Nuevo estado
 */
function sendAppointmentStatusNotification(int $appointmentId, string $status): void {
    try {
        // Implementación básica - se podría expandir con envío real de SMS/email
        error_log("Appointment $appointmentId status changed to: $status");
        
        // TODO: Integrar con sistema de notificaciones
        // - SMS para cambios de estado importantes
        // - Email de confirmación
        // - Notificaciones push
        
    } catch (Exception $e) {
        error_log("Error sending status notification: " . $e->getMessage());
    }
}

// =========================================
// SISTEMA DE GESTIÓN DE ENTREGAS
// =========================================

/**
 * Crear una nueva entrega
 * @param int $storeId ID de la tienda
 * @param array $deliveryData Datos de la entrega
 * @return array Resultado de la operación
 */
function createStoreDelivery(int $storeId, array $deliveryData): array {
    try {
        $pdo = getDBConnection();
        
        // Validar datos requeridos
        $required = ['customer_name', 'customer_phone', 'delivery_address', 'delivery_city', 'delivery_method_id'];
        foreach ($required as $field) {
            if (empty($deliveryData[$field])) {
                return ['success' => false, 'error' => "Campo requerido: $field"];
            }
        }
        
        // Validar fecha de entrega futura
        if (isset($deliveryData['scheduled_date']) && strtotime($deliveryData['scheduled_date']) < time()) {
            return ['success' => false, 'error' => 'La fecha de entrega debe ser futura'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO deliveries (
                store_id, order_id, customer_name, customer_phone, customer_email,
                delivery_address, delivery_city, delivery_method_id, scheduled_date,
                special_instructions, priority_level, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $storeId,
            $deliveryData['order_id'] ?? null,
            $deliveryData['customer_name'],
            $deliveryData['customer_phone'],
            $deliveryData['customer_email'] ?? null,
            $deliveryData['delivery_address'],
            $deliveryData['delivery_city'],
            $deliveryData['delivery_method_id'],
            $deliveryData['scheduled_date'] ?? null,
            $deliveryData['special_instructions'] ?? null,
            $deliveryData['priority_level'] ?? 'normal'
        ]);
        
        if ($result) {
            $deliveryId = $pdo->lastInsertId();
            
            // Log de auditoría
            logDeliveryActivity($deliveryId, 'creada', 'Entrega creada exitosamente');
            
            return [
                'success' => true,
                'delivery_id' => $deliveryId,
                'message' => 'Entrega creada exitosamente'
            ];
        }
        
        return ['success' => false, 'error' => 'Error al crear la entrega'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Obtener entregas de una tienda
 * @param int $storeId ID de la tienda
 * @param array $filters Filtros opcionales
 * @return array Lista de entregas
 */
function getStoreDeliveries(int $storeId, array $filters = []): array {
    try {
        $pdo = getDBConnection();
        
        $where = ['d.store_id = ?'];
        $params = [$storeId];
        
        // Aplicar filtros
        if (!empty($filters['status'])) {
            $where[] = 'd.status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'd.scheduled_date >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'd.scheduled_date <= ?';
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['city'])) {
            $where[] = 'd.delivery_city = ?';
            $params[] = $filters['city'];
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                d.*,
                dm.name as method_name,
                dm.cost as method_cost,
                o.total as order_total,
                o.order_number
            FROM deliveries d
            LEFT JOIN delivery_methods dm ON dm.id = d.delivery_method_id
            LEFT JOIN orders o ON o.id = d.order_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.scheduled_date DESC, d.created_at DESC
            LIMIT 500
        ");
        
        $stmt->execute($params);
        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular estadísticas para cada entrega
        foreach ($deliveries as &$delivery) {
            $delivery['status_color'] = getDeliveryStatusColor($delivery['status']);
            $delivery['days_until_delivery'] = $delivery['scheduled_date'] ? 
                ceil((strtotime($delivery['scheduled_date']) - time()) / (24*60*60)) : null;
            $delivery['is_overdue'] = $delivery['scheduled_date'] && 
                strtotime($delivery['scheduled_date']) < time() && $delivery['status'] !== 'entregada';
        }
        
        return $deliveries;
        
    } catch (Exception $e) {
        error_log("Error getting deliveries: " . $e->getMessage());
        return [];
    }
}

/**
 * Actualizar estado de una entrega
 * @param int $deliveryId ID de la entrega
 * @param string $newStatus Nuevo estado
 * @param string|null $reason Razón del cambio
 * @param string|null $notes Notas adicionales
 * @return array Resultado de la operación
 */
function updateDeliveryStatus(int $deliveryId, string $newStatus, ?string $reason = null, ?string $notes = null): array {
    try {
        $pdo = getDBConnection();
        
        $validStatuses = ['pendiente', 'programada', 'en_transito', 'entregada', 'fallida', 'cancelada'];
        if (!in_array($newStatus, $validStatuses)) {
            return ['success' => false, 'error' => 'Estado no válido'];
        }
        
        // Obtener estado anterior
        $stmt = $pdo->prepare("SELECT status FROM deliveries WHERE id = ?");
        $stmt->execute([$deliveryId]);
        $oldStatus = $stmt->fetchColumn();
        
        if (!$oldStatus) {
            return ['success' => false, 'error' => 'Entrega no encontrada'];
        }
        
        $stmt = $pdo->prepare("
            UPDATE deliveries 
            SET status = ?, updated_at = NOW(),
                notes = CONCAT(COALESCE(notes, ''), '\n', ?)
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$newStatus, $notes ?? '', $deliveryId]);
        
        if ($result) {
            // Registrar cambio en historial
            logDeliveryStatusChange($deliveryId, $oldStatus, $newStatus, $reason);
            
            // Enviar notificación si es necesario
            sendDeliveryStatusNotification($deliveryId, $newStatus);
            
            return ['success' => true, 'message' => 'Estado actualizado exitosamente'];
        }
        
        return ['success' => false, 'error' => 'Error al actualizar estado'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Crear método de entrega
 * @param int $storeId ID de la tienda
 * @param array $methodData Datos del método
 * @return array Resultado de la operación
 */
function createDeliveryMethod(int $storeId, array $methodData): array {
    try {
        $pdo = getDBConnection();
        
        $required = ['name', 'base_cost', 'delivery_time_days'];
        foreach ($required as $field) {
            if (empty($methodData[$field])) {
                return ['success' => false, 'error' => "Campo requerido: $field"];
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO delivery_methods (
                store_id, name, description, base_cost, delivery_time_days,
                max_weight, max_volume, active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $storeId,
            $methodData['name'],
            $methodData['description'] ?? null,
            $methodData['base_cost'],
            $methodData['delivery_time_days'],
            $methodData['max_weight'] ?? null,
            $methodData['max_volume'] ?? null,
            $methodData['active'] ?? 1
        ]);
        
        if ($result) {
            $methodId = $pdo->lastInsertId();
            logDeliveryActivity($methodId, 'método_creado', 'Método de entrega creado');
            
            return [
                'success' => true,
                'method_id' => $methodId,
                'message' => 'Método de entrega creado exitosamente'
            ];
        }
        
        return ['success' => false, 'error' => 'Error al crear método de entrega'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Obtener métodos de entrega de una tienda
 * @param int $storeId ID de la tienda
 * @param bool $activeOnly Solo métodos activos
 * @return array Lista de métodos
 */
function getStoreDeliveryMethods(int $storeId, bool $activeOnly = true): array {
    try {
        $pdo = getDBConnection();
        
        $where = ['store_id = ?'];
        $params = [$storeId];
        
        if ($activeOnly) {
            $where[] = 'active = 1';
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM delivery_methods
            WHERE " . implode(' AND ', $where) . "
            ORDER BY name ASC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting delivery methods: " . $e->getMessage());
        return [];
    }
}

/**
 * Generar calendario automático de entregas
 * @param int $storeId ID de la tienda
 * @param string $startDate Fecha de inicio
 * @param int $days Días a generar
 * @return array Resultado de la operación
 */
function generateDeliveryCalendar(int $storeId, string $startDate, int $days = 7): array {
    try {
        $pdo = getDBConnection();
        
        // Obtener configuración de horarios de la tienda
        $storeHours = getStoreHours($storeId);
        $workingDays = $storeHours['working_days'] ?? [1,2,3,4,5]; // Lunes a Viernes por defecto
        
        $startTimestamp = strtotime($startDate);
        $generatedDates = [];
        
        for ($i = 0; $i < $days; $i++) {
            $currentDate = date('Y-m-d', $startTimestamp + ($i * 24*60*60));
            $dayOfWeek = date('N', strtotime($currentDate)); // 1 = Lunes, 7 = Domingo
            
            if (in_array($dayOfWeek, $workingDays)) {
                $generatedDates[] = $currentDate;
            }
        }
        
        // Guardar fechas disponibles
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO delivery_schedules (store_id, available_date, slots_morning, slots_afternoon, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $inserted = 0;
        foreach ($generatedDates as $date) {
            $result = $stmt->execute([$storeId, $date, 10, 8]); // Slots por defecto
            if ($result) $inserted++;
        }
        
        return [
            'success' => true,
            'generated_dates' => $inserted,
            'dates' => $generatedDates,
            'message' => "Calendario generado exitosamente: $inserted fechas"
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Verificar conflictos de entrega
 * @param int $storeId ID de la tienda
 * @param string $date Fecha de entrega
 * @param string $timeSlot Turno (morning/afternoon)
 * @param int $driverId ID del repartidor (opcional)
 * @return array Información de conflictos
 */
function checkDeliveryConflicts(int $storeId, string $date, string $timeSlot = 'morning', int $driverId = null): array {
    try {
        $pdo = getDBConnection();
        
        $where = ['d.store_id = ?', 'd.scheduled_date = ?', 'd.status != ?'];
        $params = [$storeId, $date, 'cancelada'];
        
        if ($driverId) {
            $where[] = 'd.assigned_driver_id = ?';
            $params[] = $driverId;
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as delivery_count
            FROM deliveries d
            WHERE " . implode(' AND ', $where)
        );
        
        $stmt->execute($params);
        $deliveryCount = $stmt->fetchColumn();
        
        // Obtener límites de capacidad
        $capacity = $timeSlot === 'morning' ? 10 : 8; // Capacidad por defecto
        $conflicts = $deliveryCount >= $capacity;
        
        return [
            'has_conflicts' => $conflicts,
            'current_deliveries' => (int)$deliveryCount,
            'max_capacity' => $capacity,
            'available_slots' => max(0, $capacity - $deliveryCount),
            'conflicts' => $conflicts ? 'Capacidad alcanzada para este turno' : null
        ];
        
    } catch (Exception $e) {
        return [
            'has_conflicts' => false,
            'error' => 'Error al verificar conflictos: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener estadísticas de entregas
 * @param int $storeId ID de la tienda
 * @param string $startDate Fecha de inicio
 * @param string $endDate Fecha de fin
 * @return array Estadísticas
 */
function getDeliveryStatistics(int $storeId, string $startDate, string $endDate): array {
    try {
        $pdo = getDBConnection();
        
        // Estadísticas generales
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_deliveries,
                SUM(CASE WHEN status = 'entregada' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'en_transito' THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN status = 'fallida' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pendiente' THEN 1 ELSE 0 END) as pending,
                AVG(base_cost) as avg_delivery_cost,
                SUM(base_cost) as total_delivery_revenue
            FROM deliveries
            WHERE store_id = ? AND scheduled_date BETWEEN ? AND ?
        ");
        
        $stmt->execute([$storeId, $startDate, $endDate]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Entregas por ciudad
        $stmt = $pdo->prepare("
            SELECT 
                delivery_city,
                COUNT(*) as delivery_count,
                SUM(base_cost) as total_revenue
            FROM deliveries
            WHERE store_id = ? AND scheduled_date BETWEEN ? AND ?
            GROUP BY delivery_city
            ORDER BY delivery_count DESC
            LIMIT 10
        ");
        
        $stmt->execute([$storeId, $startDate, $endDate]);
        $cityStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Entregas por método
        $stmt = $pdo->prepare("
            SELECT 
                dm.name as method_name,
                COUNT(d.id) as delivery_count,
                SUM(d.base_cost) as total_revenue,
                AVG(d.base_cost) as avg_cost
            FROM delivery_methods dm
            LEFT JOIN deliveries d ON d.delivery_method_id = dm.id
            WHERE dm.store_id = ? AND d.scheduled_date BETWEEN ? AND ?
            GROUP BY dm.id, dm.name
            ORDER BY delivery_count DESC
        ");
        
        $stmt->execute([$storeId, $startDate, $endDate]);
        $methodStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'general' => $stats,
            'by_city' => $cityStats,
            'by_method' => $methodStats,
            'delivery_rate' => $stats['total_deliveries'] > 0 ? 
                round(($stats['delivered'] / $stats['total_deliveries']) * 100, 2) : 0
        ];
        
    } catch (Exception $e) {
        error_log("Error getting delivery statistics: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener horario de entregas de una tienda
 * @param int $storeId ID de la tienda
 * @param string|null $startDate Fecha de inicio (opcional)
 * @return array Horarios disponibles
 */
function getStoreDeliverySchedule(int $storeId, ?string $startDate = null): array {
    try {
        $pdo = getDBConnection();
        
        $where = ['store_id = ?'];
        $params = [$storeId];
        
        if ($startDate) {
            $where[] = 'available_date >= ?';
            $params[] = $startDate;
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM delivery_schedules
            WHERE " . implode(' AND ', $where) . "
            ORDER BY available_date ASC
        ");
        
        $stmt->execute($params);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular entregas programadas para cada fecha
        foreach ($schedules as &$schedule) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM deliveries 
                WHERE store_id = ? AND scheduled_date = ? AND status != 'cancelada'
            ");
            $stmt->execute([$storeId, $schedule['available_date']]);
            $schedule['booked_deliveries'] = (int)$stmt->fetchColumn();
            $schedule['available_slots'] = ($schedule['slots_morning'] + $schedule['slots_afternoon']) - $schedule['booked_deliveries'];
        }
        
        return $schedules;
        
    } catch (Exception $e) {
        error_log("Error getting delivery schedule: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener colores de estado para entregas
 * @param string $status Estado de la entrega
 * @return string Color correspondiente
 */
function getDeliveryStatusColor(string $status): string {
    $colors = [
        'pendiente' => '#f59e0b',     // Amarillo
        'programada' => '#3b82f6',    // Azul
        'en_transito' => '#8b5cf6',   // Púrpura
        'entregada' => '#10b981',     // Verde
        'fallida' => '#ef4444',       // Rojo
        'cancelada' => '#6b7280'      // Gris
    ];
    
    return $colors[$status] ?? '#6b7280';
}

/**
 * Log de actividad de entregas
 * @param int $deliveryId ID de la entrega
 * @param string $action Acción realizada
 * @param string $description Descripción
 * @param string $userId Usuario que realizó la acción
 */
function logDeliveryActivity(int $deliveryId, string $action, string $description, ?string $userId = null): void {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO delivery_activity_log (
                delivery_id, action, description, user_id, created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $deliveryId,
            $action,
            $description,
            $userId ?? ($_SESSION['user_id'] ?? null)
        ]);
        
    } catch (Exception $e) {
        error_log("Error logging delivery activity: " . $e->getMessage());
    }
}

/**
 * Log de cambios de estado de entregas
 * @param int $deliveryId ID de la entrega
 * @param string $oldStatus Estado anterior
 * @param string $newStatus Estado nuevo
 * @param string|null $reason Razón del cambio
 */
function logDeliveryStatusChange(int $deliveryId, string $oldStatus, string $newStatus, ?string $reason): void {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO delivery_status_history (
                delivery_id, old_status, new_status, reason, 
                changed_by, changed_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $deliveryId,
            $oldStatus,
            $newStatus,
            $reason,
            $_SESSION['user_id'] ?? null
        ]);
        
        logDeliveryActivity($deliveryId, 'estado_cambiado', 
            "Estado cambiado de '$oldStatus' a '$newStatus'" . ($reason ? " - $reason" : ''));
        
    } catch (Exception $e) {
        error_log("Error logging delivery status change: " . $e->getMessage());
    }
}

/**
 * Enviar notificación de cambio de estado de entrega
 * @param int $deliveryId ID de la entrega
 * @param string $status Nuevo estado
 */
function sendDeliveryStatusNotification(int $deliveryId, string $status): void {
    try {
        // Implementación básica - se podría expandir con envío real de SMS/email
        error_log("Delivery $deliveryId status changed to: $status");
        
        // TODO: Integrar con sistema de notificaciones
        // - SMS para entregas en tránsito y entregadas
        // - Email de confirmación
        // - Notificaciones push
        
    } catch (Exception $e) {
        error_log("Error sending delivery status notification: " . $e->getMessage());
    }
}

/**
 * Obtener repartidores activos
 * @param int $storeId ID de la tienda
 * @return array Lista de repartidores
 */
function getStoreDrivers(int $storeId): array {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM delivery_drivers
            WHERE store_id = ? AND active = 1
            ORDER BY name ASC
        ");
        
        $stmt->execute([$storeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting store drivers: " . $e->getMessage());
        return [];
    }
}

/**
 * Asignar repartidor a entrega
 * @param int $deliveryId ID de la entrega
 * @param int $driverId ID del repartidor
 * @return array Resultado de la operación
 */
function assignDriverToDelivery(int $deliveryId, int $driverId): array {
    try {
        $pdo = getDBConnection();
        
        // Verificar que el repartidor existe y está activo
        $stmt = $pdo->prepare("
            SELECT id FROM delivery_drivers 
            WHERE id = ? AND store_id = (SELECT store_id FROM deliveries WHERE id = ?) AND active = 1
        ");
        $stmt->execute([$driverId, $deliveryId]);
        
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'Repartidor no encontrado o inactivo'];
        }
        
        $stmt = $pdo->prepare("
            UPDATE deliveries 
            SET assigned_driver_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$driverId, $deliveryId]);
        
        if ($result) {
            logDeliveryActivity($deliveryId, 'repartidor_asignado', "Repartidor ID: $driverId asignado");
            
            return ['success' => true, 'message' => 'Repartidor asignado exitosamente'];
        }
        
        return ['success' => false, 'error' => 'Error al asignar repartidor'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Calcular costo de entrega dinámico
 * @param int $storeId ID de la tienda
 * @param int $methodId ID del método
 * @param string $city Ciudad de destino
 * @param float $weight Peso del paquete (opcional)
 * @return array Cálculo detallado
 */
function calculateDeliveryCost(int $storeId, int $methodId, string $city, float $weight = 0): array {
    try {
        $pdo = getDBConnection();
        
        // Obtener método base
        $stmt = $pdo->prepare("
            SELECT * FROM delivery_methods 
            WHERE id = ? AND store_id = ? AND active = 1
        ");
        $stmt->execute([$methodId, $storeId]);
        $method = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$method) {
            return ['success' => false, 'error' => 'Método de entrega no encontrado'];
        }
        
        $baseCost = (float)$method['base_cost'];
        $totalCost = $baseCost;
        $calculations = [
            'base_cost' => $baseCost,
            'weight_surcharge' => 0,
            'distance_surcharge' => 0,
            'additional_fees' => 0,
            'total_cost' => $totalCost
        ];
        
        // Recargo por peso
        if ($weight > 0 && $method['max_weight'] > 0 && $weight > $method['max_weight']) {
            $overweight = $weight - $method['max_weight'];
            $weightSurcharge = $overweight * 2.0; // $2 por kg extra
            $calculations['weight_surcharge'] = $weightSurcharge;
            $totalCost += $weightSurcharge;
        }
        
        // Recargo por distancia (simplificado)
        $distanceFactors = [
            'local' => 1.0,
            'regional' => 1.5,
            'nacional' => 2.5
        ];
        
        $distanceFactor = 1.0; // Factor base
        if (strpos(strtolower($city), 'capital') !== false || strpos(strtolower($city), 'centro') !== false) {
            $distanceFactor = 1.2; // Entrega local
        } else {
            $distanceFactor = 1.8; // Entrega regional/nacional
        }
        
        if ($distanceFactor > 1.0) {
            $distanceSurcharge = $baseCost * ($distanceFactor - 1.0);
            $calculations['distance_surcharge'] = $distanceSurcharge;
            $totalCost += $distanceSurcharge;
        }
        
        $calculations['total_cost'] = round($totalCost, 2);
        $calculations['delivery_days'] = $method['delivery_time_days'];
        $calculations['method_name'] = $method['name'];
        
        return [
            'success' => true,
            'cost_breakdown' => $calculations,
            'estimated_delivery' => date('Y-m-d', strtotime("+{$method['delivery_time_days']} days"))
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
    }
}
?>