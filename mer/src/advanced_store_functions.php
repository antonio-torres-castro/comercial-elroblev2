<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

/**
 * FUNCIONES PARA SISTEMA DE TIENDA AVANZADO
 * Mall Virtual - Viña del Mar
 */

// =============================================================================
// GESTIÓN DE PRODUCTOS CON STOCK Y AGENDAMIENTO
// =============================================================================

/**
 * Obtener productos de una tienda con información de stock
 */
function getStoreProductsWithStock(int $storeId): array {
    $stmt = db()->prepare("
        SELECT 
            p.*,
            pdc.available_capacity - pdc.booked_capacity as available_slots,
            pdc.capacity_date,
            CASE WHEN p.stock_quantity <= p.stock_min_threshold THEN 'LOW' ELSE 'OK' END as stock_status,
            CASE 
                WHEN p.service_type = 'servicio' AND p.requires_appointment = 1 THEN 'requires_appointment'
                WHEN p.service_type = 'producto' THEN 'standard'
                ELSE 'hybrid'
            END as service_mode
        FROM products p
        LEFT JOIN product_daily_capacity pdc ON pdc.product_id = p.id AND pdc.capacity_date = CURDATE()
        WHERE p.store_id = ? AND p.active = 1
        ORDER BY p.name
    ");
    $stmt->execute([$storeId]);
    return $stmt->fetchAll();
}

/**
 * Verificar disponibilidad de producto para fecha específica
 */
function checkProductAvailability(int $productId, int $quantity, ?string $date = null): array {
    $checkDate = $date ?? date('Y-m-d');
    
    $stmt = db()->prepare("CALL check_product_availability(?, ?, ?)");
    $stmt->execute([$productId, $quantity, $checkDate]);
    $result = $stmt->fetch();
    
    if (!$result) {
        return [
            'available' => false,
            'message' => 'No se pudo verificar disponibilidad'
        ];
    }
    
    return [
        'available' => $result['availability_status'] === 'available',
        'current_stock' => (int)$result['current_stock'],
        'available_capacity' => (int)$result['available_capacity'],
        'total_available' => (int)$result['total_available'],
        'quantity_requested' => $quantity,
        'check_date' => $checkDate,
        'message' => $result['availability_status'] === 'available' 
            ? 'Producto disponible' 
            : 'Producto no disponible en la cantidad solicitada'
    ];
}

/**
 * Obtener fechas disponibles para un producto
 */
function getProductAvailableDates(int $productId, int $daysAhead = 30): array {
    $stmt = db()->prepare("
        SELECT 
            pdc.capacity_date,
            pdc.available_capacity - pdc.booked_capacity as available_slots,
            p.stock_quantity
        FROM product_daily_capacity pdc
        JOIN products p ON p.id = pdc.product_id
        WHERE pdc.product_id = ? 
          AND pdc.capacity_date >= CURDATE()
          AND pdc.capacity_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
          AND pdc.available_capacity > pdc.booked_capacity
          AND p.stock_quantity > 0
        ORDER BY pdc.capacity_date
        LIMIT ?
    ");
    $stmt->execute([$productId, $daysAhead, $daysAhead]);
    return $stmt->fetchAll();
}

/**
 * Crear o actualizar un producto
 */
function upsertProduct(array $productData): array {
    $required = ['store_id', 'name', 'price', 'service_type'];
    
    foreach ($required as $field) {
        if (empty($productData[$field])) {
            return ['success' => false, 'error' => "Campo requerido: $field"];
        }
    }
    
    $isUpdate = !empty($productData['id']);
    
    // Campos permitidos para inserción/actualización
    $allowedFields = [
        'id', 'store_id', 'name', 'description', 'price', 'group_id',
        'stock_quantity', 'stock_min_threshold', 'delivery_days_min', 
        'delivery_days_max', 'service_type', 'requires_appointment',
        'image_url', 'active'
    ];
    
    $data = [];
    foreach ($allowedFields as $field) {
        if (isset($productData[$field])) {
            $data[$field] = $productData[$field];
        }
    }
    
    if ($isUpdate) {
        $id = (int)$data['id'];
        unset($data['id']);
        
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;
        
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = db()->prepare($sql);
        $success = $stmt->execute($params);
        
        if ($success && !empty($data['stock_quantity'])) {
            logStockMovement($id, $data['store_id'], 'adjustment', $data['stock_quantity'], 'purchase', null, 'Actualización de stock por administrador');
        }
        
        return ['success' => $success, 'id' => $id];
    } else {
        $sql = "INSERT INTO products (" . implode(', ', array_keys($data)) . ") VALUES (" . 
               str_repeat('?,', count($data) - 1) . "?)";
        
        $stmt = db()->prepare($sql);
        $success = $stmt->execute(array_values($data));
        
        $id = (int)db()->lastInsertId();
        
        if ($success) {
            // Generar capacidades para los próximos 30 días
            generateProductDailyCapacities($id, $data['store_id']);
            
            // Registrar movimiento inicial de stock si es > 0
            if (!empty($data['stock_quantity']) && $data['stock_quantity'] > 0) {
                logStockMovement($id, $data['store_id'], 'in', $data['stock_quantity'], 'purchase', null, 'Stock inicial');
            }
        }
        
        return ['success' => $success, 'id' => $id];
    }
}

/**
 * Actualizar stock de un producto
 */
function updateProductStock(int $productId, int $newStock, ?string $reason = null): array {
    $product = productById($productId);
    if (!$product) {
        return ['success' => false, 'error' => 'Producto no encontrado'];
    }
    
    $oldStock = (int)$product['stock_quantity'];
    $difference = $newStock - $oldStock;
    
    $stmt = db()->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
    $success = $stmt->execute([$newStock, $productId]);
    
    if ($success) {
        $movementType = $difference > 0 ? 'in' : ($difference < 0 ? 'out' : 'adjustment');
        logStockMovement($productId, $product['store_id'], $movementType, abs($difference), 'adjustment', null, $reason ?? 'Ajuste de stock manual');
    }
    
    return ['success' => $success, 'old_stock' => $oldStock, 'new_stock' => $newStock];
}

// =============================================================================
// SISTEMA DE AGENDAMIENTO
// =============================================================================

/**
 * Crear una cita/agendamiento
 */
function createAppointment(int $productId, string $date, string $time, int $quantity, ?int $orderId = null, ?string $notes = null): array {
    $product = productById($productId);
    if (!$product) {
        return ['success' => false, 'error' => 'Producto no encontrado'];
    }
    
    // Verificar disponibilidad
    $availability = checkProductAvailability($productId, $quantity, $date);
    if (!$availability['available']) {
        return ['success' => false, 'error' => 'No hay disponibilidad para la fecha y cantidad solicitada'];
    }
    
    // Verificar capacidad para la fecha
    $capacity = getProductCapacity($productId, $date);
    if ($capacity['available_slots'] < $quantity) {
        return ['success' => false, 'error' => 'No hay suficiente capacidad para la fecha solicitada'];
    }
    
    // Crear la cita
    $stmt = db()->prepare("
        INSERT INTO product_appointments 
        (product_id, store_id, appointment_date, appointment_time, quantity_ordered, capacity_consumed, order_id, customer_notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $capacityConsumed = $quantity;
    $success = $stmt->execute([
        $productId,
        $product['store_id'],
        $date,
        $time,
        $quantity,
        $capacityConsumed,
        $orderId,
        $notes
    ]);
    
    if ($success) {
        $appointmentId = (int)db()->lastInsertId();
        
        // Actualizar capacidad consumed
        updateProductCapacity($productId, $date, $capacityConsumed);
        
        return ['success' => true, 'appointment_id' => $appointmentId];
    }
    
    return ['success' => false, 'error' => 'Error al crear la cita'];
}

/**
 * Obtener capacidad disponible para un producto en una fecha
 */
function getProductCapacity(int $productId, string $date): array {
    $stmt = db()->prepare("
        SELECT available_capacity, booked_capacity, available_capacity - booked_capacity as available_slots
        FROM product_daily_capacity 
        WHERE product_id = ? AND capacity_date = ?
    ");
    $stmt->execute([$productId, $date]);
    $result = $stmt->fetch();
    
    if (!$result) {
        return [
            'available_capacity' => 0,
            'booked_capacity' => 0,
            'available_slots' => 0
        ];
    }
    
    return [
        'available_capacity' => (int)$result['available_capacity'],
        'booked_capacity' => (int)$result['booked_capacity'],
        'available_slots' => (int)$result['available_slots']
    ];
}

/**
 * Actualizar capacidad consumed para un producto
 */
function updateProductCapacity(int $productId, string $date, int $quantity): bool {
    $stmt = db()->prepare("
        UPDATE product_daily_capacity 
        SET booked_capacity = booked_capacity + ?, updated_at = CURRENT_TIMESTAMP
        WHERE product_id = ? AND capacity_date = ?
    ");
    return $stmt->execute([$quantity, $productId, $date]);
}

/**
 * Generar capacidades diarias para un producto
 */
function generateProductDailyCapacities(int $productId, int $storeId, int $days = 30): bool {
    $stmt = db()->prepare("CALL generate_daily_capacities()");
    return $stmt->execute();
}

// =============================================================================
// SISTEMA DE DESPACHOS AGRUPADOS
// =============================================================================

/**
 * Crear grupo de despacho
 */
function createDeliveryGroup(array $groupData): array {
    $required = ['order_id', 'delivery_address', 'delivery_city', 'delivery_contact_name', 'delivery_contact_phone'];
    
    foreach ($required as $field) {
        if (empty($groupData[$field])) {
            return ['success' => false, 'error' => "Campo requerido: $field"];
        }
    }
    
    $stmt = db()->prepare("
        INSERT INTO delivery_groups 
        (order_id, group_name, delivery_address, delivery_city, delivery_contact_name, 
         delivery_contact_phone, delivery_contact_email, pickup_location_id, 
         delivery_date, delivery_time_slot, shipping_cost, delivery_notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $groupData['order_id'],
        $groupData['group_name'] ?? 'Grupo 1',
        $groupData['delivery_address'],
        $groupData['delivery_city'],
        $groupData['delivery_contact_name'],
        $groupData['delivery_contact_phone'],
        $groupData['delivery_contact_email'] ?? null,
        $groupData['pickup_location_id'] ?? null,
        $groupData['delivery_date'] ?? null,
        $groupData['delivery_time_slot'] ?? null,
        $groupData['shipping_cost'] ?? 0.00,
        $groupData['delivery_notes'] ?? null
    ]);
    
    if ($success) {
        return ['success' => true, 'group_id' => (int)db()->lastInsertId()];
    }
    
    return ['success' => false, 'error' => 'Error al crear grupo de despacho'];
}

/**
 * Agregar item a grupo de despacho
 */
function addItemToDeliveryGroup(int $groupId, int $orderItemId, int $quantity): array {
    // Verificar que el item no esté ya en un grupo
    $stmt = db()->prepare("SELECT 1 FROM delivery_group_items WHERE order_item_id = ?");
    $stmt->execute([$orderItemId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'El item ya está asignado a un grupo'];
    }
    
    // Obtener información del item
    $stmt = db()->prepare("SELECT quantity, unit_price FROM order_items WHERE id = ?");
    $stmt->execute([$orderItemId]);
    $item = $stmt->fetch();
    
    if (!$item) {
        return ['success' => false, 'error' => 'Item no encontrado'];
    }
    
    $subtotal = (float)$item['unit_price'] * $quantity;
    
    $stmt = db()->prepare("
        INSERT INTO delivery_group_items (delivery_group_id, order_item_id, quantity, unit_price, subtotal) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([$groupId, $orderItemId, $quantity, $item['unit_price'], $subtotal]);
    
    return ['success' => $success, 'subtotal' => $subtotal];
}

/**
 * Calcular costo de despacho para un grupo
 */
function calculateDeliveryCost(int $groupId, ?string $couponCode = null): array {
    // Obtener items del grupo
    $stmt = db()->prepare("
        SELECT 
            dgi.*,
            p.name as product_name,
            dgi.quantity * dgi.unit_price as item_total
        FROM delivery_group_items dgi
        JOIN order_items oi ON oi.id = dgi.order_item_id
        JOIN products p ON p.id = oi.product_id
        WHERE dgi.delivery_group_id = ?
    ");
    $stmt->execute([$groupId]);
    $items = $stmt->fetchAll();
    
    if (empty($items)) {
        return ['success' => false, 'error' => 'El grupo no tiene items'];
    }
    
    $subtotal = array_sum(array_column($items, 'item_total'));
    $shippingCost = 0;
    $discount = 0;
    
    // Calcular costo de envío base (esto se puede personalizar por tienda)
    $groupInfo = getDeliveryGroupById($groupId);
    if ($groupInfo && $groupInfo['shipping_cost'] > 0) {
        $shippingCost = (float)$groupInfo['shipping_cost'];
    }
    
    // Aplicar cupón si existe
    if ($couponCode) {
        $coupon = getDeliveryCoupon($couponCode);
        if ($coupon && isValidCoupon($coupon, $subtotal + $shippingCost)) {
            $discount = calculateCouponDiscount($coupon, $subtotal + $shippingCost);
        }
    }
    
    $total = $subtotal + $shippingCost - $discount;
    
    return [
        'success' => true,
        'subtotal' => $subtotal,
        'shipping_cost' => $shippingCost,
        'discount' => $discount,
        'total' => $total,
        'item_count' => count($items)
    ];
}

/**
 * Obtener grupo de despacho por ID
 */
function getDeliveryGroupById(int $groupId): ?array {
    $stmt = db()->prepare("
        SELECT dg.*, 
               pl.name as pickup_location_name
        FROM delivery_groups dg
        LEFT JOIN pickup_locations pl ON pl.id = dg.pickup_location_id
        WHERE dg.id = ?
    ");
    $stmt->execute([$groupId]);
    return $stmt->fetch() ?: null;
}

// =============================================================================
// GESTIÓN DE CUPONES DE DESPACHO
// =============================================================================

/**
 * Obtener cupón de descuento por código
 */
function getDeliveryCoupon(string $code): ?array {
    $stmt = db()->prepare("SELECT * FROM delivery_coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    return $stmt->fetch() ?: null;
}

/**
 * Verificar si un cupón es válido
 */
function isValidCoupon(array $coupon, float $orderAmount): bool {
    // Verificar si está activo
    if (!$coupon['is_active']) {
        return false;
    }
    
    // Verificar fecha de validez
    if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < time()) {
        return false;
    }
    
    // Verificar monto mínimo
    if ($orderAmount < (float)$coupon['min_order_amount']) {
        return false;
    }
    
    // Verificar límite de uso
    if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
        return false;
    }
    
    return true;
}

/**
 * Calcular descuento de cupón
 */
function calculateCouponDiscount(array $coupon, float $orderAmount): float {
    if ($coupon['discount_type'] === 'fixed') {
        return min((float)$coupon['discount_value'], $orderAmount);
    } else {
        $discount = $orderAmount * ((float)$coupon['discount_value'] / 100);
        if ($coupon['max_discount_amount']) {
            $discount = min($discount, (float)$coupon['max_discount_amount']);
        }
        return $discount;
    }
}

/**
 * Marcar cupón como usado
 */
function useCoupon(string $code): bool {
    $stmt = db()->prepare("UPDATE delivery_coupons SET used_count = used_count + 1 WHERE code = ?");
    return $stmt->execute([$code]);
}

// =============================================================================
// GESTIÓN DE UBICACIONES DE RECOJO
// =============================================================================

/**
 * Obtener ubicaciones de recojo de una tienda
 */
function getStorePickupLocations(int $storeId): array {
    $stmt = db()->prepare("
        SELECT *, 
               CASE 
                   WHEN CURDATE() BETWEEN valid_from AND valid_until THEN 'valid'
                   ELSE 'expired'
               END as validity_status
        FROM pickup_locations 
        WHERE store_id = ? AND is_active = 1 
        ORDER BY name
    ");
    $stmt->execute([$storeId]);
    return $stmt->fetchAll();
}

/**
 * Crear ubicación de recojo
 */
function createPickupLocation(array $locationData): array {
    $required = ['store_id', 'name', 'address', 'city'];
    
    foreach ($required as $field) {
        if (empty($locationData[$field])) {
            return ['success' => false, 'error' => "Campo requerido: $field"];
        }
    }
    
    $stmt = db()->prepare("
        INSERT INTO pickup_locations 
        (store_id, name, address, city, phone, hours_start, hours_end, days_of_week) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $locationData['store_id'],
        $locationData['name'],
        $locationData['address'],
        $locationData['city'],
        $locationData['phone'] ?? null,
        $locationData['hours_start'] ?? null,
        $locationData['hours_end'] ?? null,
        $locationData['days_of_week'] ?? null
    ]);
    
    return ['success' => $success, 'id' => $success ? (int)db()->lastInsertId() : null];
}

// =============================================================================
// MOVIMIENTOS DE STOCK
// =============================================================================

/**
 * Registrar movimiento de stock
 */
function logStockMovement(int $productId, int $storeId, string $movementType, int $quantity, string $referenceType, ?int $referenceId = null, ?string $notes = null): bool {
    $stmt = db()->prepare("
        INSERT INTO stock_movements 
        (product_id, store_id, movement_type, quantity, reference_type, reference_id, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $productId,
        $storeId,
        $movementType,
        $quantity,
        $referenceType,
        $referenceId,
        $notes
    ]);
}

/**
 * Obtener historial de movimientos de stock
 */
function getStockMovements(int $productId, int $limit = 50): array {
    $stmt = db()->prepare("
        SELECT 
            sm.*,
            p.name as product_name,
            s.name as store_name
        FROM stock_movements sm
        JOIN products p ON p.id = sm.product_id
        JOIN stores s ON s.id = sm.store_id
        WHERE sm.product_id = ?
        ORDER BY sm.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$productId, $limit]);
    return $stmt->fetchAll();
}

// =============================================================================
// CONFIGURACIÓN DE TIENDAS
// =============================================================================

/**
 * Obtener configuración de tienda
 */
function getStoreSettings(int $storeId): array {
    $stmt = db()->prepare("
        SELECT setting_key, setting_value, setting_type, description
        FROM store_settings 
        WHERE store_id = ?
        ORDER BY setting_key
    ");
    $stmt->execute([$storeId]);
    $settings = $stmt->fetchAll();
    
    $result = [];
    foreach ($settings as $setting) {
        $value = $setting['setting_value'];
        
        // Parsear según el tipo
        switch ($setting['setting_type']) {
            case 'number':
                $value = (float)$value;
                break;
            case 'boolean':
                $value = (bool)$value;
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
        }
        
        $result[$setting['setting_key']] = [
            'value' => $value,
            'type' => $setting['setting_type'],
            'description' => $setting['description']
        ];
    }
    
    return $result;
}

/**
 * Actualizar configuración de tienda
 */
function updateStoreSetting(int $storeId, string $key, $value, string $type = 'text', ?string $description = null): bool {
    $stmt = db()->prepare("
        INSERT INTO store_settings (store_id, setting_key, setting_value, setting_type, description) 
        VALUES (?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value), 
        setting_type = VALUES(setting_type),
        description = VALUES(description),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stringValue = is_string($value) ? $value : (is_array($value) ? json_encode($value) : (string)$value);
    
    return $stmt->execute([$storeId, $key, $stringValue, $type, $description]);
}

// =============================================================================
// REPORTES Y ESTADÍSTICAS
// =============================================================================

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
?>