-- Consulta de Verificación de Estructura de Base de Datos
-- Este archivo contiene las consultas SQL para verificar que el script advanced_store_system.sql se ejecutó correctamente

-- =============================================================================
-- VERIFICACIÓN DE TABLAS CREADAS
-- =============================================================================

-- 1. Verificar que todas las tablas del sistema avanzado existen
SHOW TABLES LIKE 'product_daily_capacity';
SHOW TABLES LIKE 'product_appointments';
SHOW TABLES LIKE 'delivery_groups';
SHOW TABLES LIKE 'delivery_group_items';
SHOW TABLES LIKE 'pickup_locations';
SHOW TABLES LIKE 'stock_movements';
SHOW TABLES LIKE 'delivery_coupons';
SHOW TABLES LIKE 'store_settings';
SHOW TABLES LIKE 'store_holidays';

-- =============================================================================
-- VERIFICACIÓN DE COLUMNAS AGREGADAS
-- =============================================================================

-- 2. Verificar columnas agregadas a la tabla products
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'comerci3_bdmer' 
AND TABLE_NAME = 'products' 
AND COLUMN_NAME IN ('stock_quantity', 'stock_min_threshold', 'delivery_days_min', 'delivery_days_max', 'service_type', 'requires_appointment', 'image_url', 'active')
ORDER BY COLUMN_NAME;

-- 3. Verificar columnas agregadas a la tabla orders
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'comerci3_bdmer' 
AND TABLE_NAME = 'orders' 
AND COLUMN_NAME IN ('delivery_address', 'delivery_city', 'delivery_contact_name', 'delivery_contact_phone', 'delivery_contact_email', 'pickup_location_id', 'delivery_date', 'delivery_time_slot')
ORDER BY COLUMN_NAME;

-- =============================================================================
-- VERIFICACIÓN DE DATOS DE EJEMPLO
-- =============================================================================

-- 4. Contar registros en cada tabla nueva
SELECT 'product_daily_capacity' as tabla, COUNT(*) as registros FROM product_daily_capacity
UNION ALL
SELECT 'product_appointments' as tabla, COUNT(*) as registros FROM product_appointments
UNION ALL
SELECT 'delivery_groups' as tabla, COUNT(*) as registros FROM delivery_groups
UNION ALL
SELECT 'delivery_group_items' as tabla, COUNT(*) as registros FROM delivery_group_items
UNION ALL
SELECT 'pickup_locations' as tabla, COUNT(*) as registros FROM pickup_locations
UNION ALL
SELECT 'stock_movements' as tabla, COUNT(*) as registros FROM stock_movements
UNION ALL
SELECT 'delivery_coupons' as tabla, COUNT(*) as registros FROM delivery_coupons
UNION ALL
SELECT 'store_settings' as tabla, COUNT(*) as registros FROM store_settings
UNION ALL
SELECT 'store_holidays' as tabla, COUNT(*) as registros FROM store_holidays;

-- =============================================================================
-- VERIFICACIÓN DE DATOS DE TIENDA-A (CAFÉ BREW)
-- =============================================================================

-- 5. Configuraciones de Tienda-A
SELECT setting_key, setting_value, description 
FROM store_settings 
WHERE store_id = 1 
ORDER BY setting_key;

-- 6. Ubicaciones de recojo para Tienda-A
SELECT name, address, city, phone, hours_start, hours_end 
FROM pickup_locations 
WHERE store_id = 1;

-- 7. Cupones de descuento activos
SELECT code, discount_type, discount_value, min_order_amount, usage_limit, used_count, valid_until 
FROM delivery_coupons 
WHERE is_active = 1;

-- 8. Productos con información de stock (Tienda-A)
SELECT id, name, stock_quantity, stock_min_threshold, active,
       CASE WHEN stock_quantity <= stock_min_threshold THEN 'Stock Bajo' ELSE 'OK' END as estado_stock
FROM products 
WHERE store_id = 1 
ORDER BY id;

-- =============================================================================
-- VERIFICACIÓN DE FUNCIONALIDADES AUTOMÁTICAS
-- =============================================================================

-- 9. Verificar que las vistas existen
SHOW TABLES LIKE 'products_low_stock';
SHOW TABLES LIKE 'product_availability';
SHOW TABLES LIKE 'orders_with_delivery';

-- 10. Verificar triggers
SHOW TRIGGERS LIKE 'update_stock_on_order';
SHOW TRIGGERS LIKE 'restore_stock_on_cancellation';

-- 11. Verificar procedimientos almacenados
SHOW PROCEDURE STATUS WHERE Db = 'comerci3_bdmer' 
AND Name IN ('check_product_availability', 'generate_daily_capacities');

-- =============================================================================
-- CONSULTAS DE PRUEBA ESPECÍFICAS
-- =============================================================================

-- 12. Probar procedimiento de disponibilidad
CALL check_product_availability(1, 5, CURDATE());

-- 13. Vista de productos con stock bajo
SELECT * FROM products_low_stock WHERE store_id = 1;

-- 14. Vista de disponibilidad de productos
SELECT * FROM product_availability 
WHERE store_id = 1 AND capacity_date = CURDATE() 
LIMIT 5;

-- =============================================================================
-- VERIFICACIÓN DE ÍNDICES
-- =============================================================================

-- 15. Verificar índices específicos creados
SHOW INDEX FROM product_daily_capacity;
SHOW INDEX FROM product_appointments;
SHOW INDEX FROM delivery_groups;
SHOW INDEX FROM stock_movements;

-- =============================================================================
-- CONSULTAS DE INTEGRIDAD
-- =============================================================================

-- 16. Verificar claves foráneas
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_SCHEMA = 'comerci3_bdmer' 
AND TABLE_NAME IN ('product_daily_capacity', 'product_appointments', 'delivery_groups', 'delivery_group_items', 'pickup_locations', 'stock_movements', 'store_settings', 'store_holidays')
ORDER BY TABLE_NAME, CONSTRAINT_NAME;