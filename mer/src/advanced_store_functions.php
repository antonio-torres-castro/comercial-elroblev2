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
?>