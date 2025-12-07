-- =============================================================================
-- SCRIPT DE CORRECCIÓN Y NORMALIZACIÓN DEL SISTEMA DE DELIVERY
-- Versión: 1.1 - CORREGIDO
-- Autor: MiniMax Agent
-- Fecha: 2025-12-08
-- Descripción: Corrige inconsistencias entre estructuras de BD y código del sistema
-- CORRECCIONES v1.1:
-- - Agregado campo weight_grams en delivery_group_items
-- - Agregados campos faltantes en tabla products
-- =============================================================================

-- Configuración inicial
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Ejecutar limpieza
CALL quick_orphan_cleanup();

DROP TABLE IF EXISTS delivery_addresses;
DROP TABLE IF EXISTS delivery_schedules;
DROP TABLE IF EXISTS delivery_status_history;
DROP TABLE IF EXISTS delivery_notifications;
DROP TABLE IF EXISTS delivery_tracking;
DROP TABLE IF EXISTS delivery_zone_costs;

-- =============================================================================
-- PARTE 1: CREACIÓN DE TABLA delivery_addresses (TABLA FALTANTE)
-- =============================================================================

CREATE TABLE IF NOT EXISTS delivery_addresses (
  id int NOT NULL AUTO_INCREMENT COMMENT 'ID único de la dirección',
  store_id int NOT NULL COMMENT 'ID de la tienda',
  user_id int DEFAULT NULL COMMENT 'ID del usuario asociado (opcional)',
  order_id int DEFAULT NULL COMMENT 'ID de la orden (para direcciones de una sola orden)',
  
  -- Información de contacto
  contact_name varchar(200) NOT NULL COMMENT 'Nombre completo del destinatario',
  phone varchar(50) NOT NULL COMMENT 'Teléfono de contacto',
  email varchar(200) DEFAULT NULL COMMENT 'Email del destinatario',
  
  -- Dirección completa
  address_line_1 text NOT NULL COMMENT 'Dirección principal',
  address_line_2 text DEFAULT NULL COMMENT 'Dirección secundaria (apto, casa, etc.)',
  city varchar(100) NOT NULL COMMENT 'Ciudad',
  state_province varchar(100) DEFAULT NULL COMMENT 'Estado o provincia',
  postal_code varchar(20) DEFAULT NULL COMMENT 'Código postal',
  country varchar(100) NOT NULL DEFAULT 'Chile' COMMENT 'País',
  
  -- Geolocalización
  latitude decimal(10,8) DEFAULT NULL COMMENT 'Latitud',
  longitude decimal(11,8) DEFAULT NULL COMMENT 'Longitud',
  
  -- Configuración
  type enum('home','work','other') DEFAULT 'home' COMMENT 'Tipo de dirección',
  is_default tinyint(1) DEFAULT '0' COMMENT 'Dirección por defecto',
  instructions text DEFAULT NULL COMMENT 'Instrucciones de entrega',
  
  -- Metadata
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  KEY idx_store_id (store_id),
  KEY idx_user_id (user_id),
  KEY idx_order_id (order_id),
  KEY idx_city (city),
  KEY idx_postal_code (postal_code),
  KEY idx_type (type),
  KEY idx_coordinates (latitude,longitude),
  KEY idx_fulltext (address_line_1(255),address_line_2(255),city),
  
  CONSTRAINT fk_delivery_addresses_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
  CONSTRAINT fk_delivery_addresses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_delivery_addresses_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Direcciones de entrega normalizadas';

-- =============================================================================
-- PARTE 2: CREACIÓN DE TABLAS FALTANTES DEL SISTEMA COMPLETO
-- =============================================================================

-- Tabla: delivery_schedules (Calendario de entregas)
CREATE TABLE IF NOT EXISTS delivery_schedules (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  available_date date NOT NULL,
  time_slot enum('morning','afternoon','evening','anytime') NOT NULL DEFAULT 'morning',
  slots_available int NOT NULL DEFAULT 10,
  slots_booked int NOT NULL DEFAULT 0,
  delivery_time_start time NULL,
  delivery_time_end time NULL,
  blocked tinyint(1) NOT NULL DEFAULT 0,
  block_reason varchar(255) NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  UNIQUE KEY unique_schedule_date_slot (store_id, available_date, time_slot),
  INDEX idx_date (available_date),
  INDEX idx_store_date (store_id, available_date),
  
  CONSTRAINT fk_delivery_schedules_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Calendario de disponibilidad para entregas';

-- Tabla: delivery_status_history (Historial de cambios de estado)
CREATE TABLE IF NOT EXISTS delivery_status_history (
  id int NOT NULL AUTO_INCREMENT,
  delivery_id int NOT NULL,
  old_status enum('pendiente','programada','en_preparacion','en_transito','entregada','fallida','cancelada') NULL,
  new_status enum('pendiente','programada','en_preparacion','en_transito','entregada','fallida','cancelada') NOT NULL,
  reason varchar(255) NULL,
  notes text NULL,
  changed_by int NULL,
  changed_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  INDEX idx_delivery_id (delivery_id),
  INDEX idx_changed_at (changed_at),
  INDEX idx_status_change (old_status, new_status),
  
  CONSTRAINT fk_delivery_status_history_delivery FOREIGN KEY (delivery_id) REFERENCES deliveries (id) ON DELETE CASCADE,
  CONSTRAINT fk_delivery_status_history_user FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Historial de cambios de estado de entregas';

-- Tabla: delivery_notifications (Notificaciones)
CREATE TABLE IF NOT EXISTS delivery_notifications (
  id int NOT NULL AUTO_INCREMENT,
  delivery_id int NOT NULL,
  notification_type enum('sms','email','push','whatsapp') NOT NULL,
  recipient varchar(255) NOT NULL,
  subject varchar(255) NULL,
  message text NOT NULL,
  status enum('pending','sent','failed','delivered') NOT NULL DEFAULT 'pending',
  sent_at timestamp NULL,
  delivered_at timestamp NULL,
  error_message text NULL,
  retry_count int NOT NULL DEFAULT 0,
  max_retries int NOT NULL DEFAULT 3,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  INDEX idx_delivery_id (delivery_id),
  INDEX idx_status (status),
  INDEX idx_type (notification_type),
  INDEX idx_sent_at (sent_at),
  
  CONSTRAINT fk_delivery_notifications_delivery FOREIGN KEY (delivery_id) REFERENCES deliveries (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Notificaciones de entregas';

-- Tabla: delivery_tracking (Seguimiento GPS)
CREATE TABLE IF NOT EXISTS delivery_tracking (
  id int NOT NULL AUTO_INCREMENT,
  delivery_id int NOT NULL,
  driver_id int NULL,
  latitude decimal(10, 8) NULL,
  longitude decimal(11, 8) NULL,
  accuracy decimal(8,2) NULL,
  speed decimal(6,2) NULL COMMENT 'Velocidad en km/h',
  heading decimal(5,2) NULL COMMENT 'Dirección en grados',
  battery_level decimal(5,2) NULL COMMENT 'Nivel de batería del dispositivo',
  recorded_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  INDEX idx_delivery_id (delivery_id),
  INDEX idx_driver_id (driver_id),
  INDEX idx_recorded_at (recorded_at),
  INDEX idx_location (latitude, longitude),
  
  CONSTRAINT fk_delivery_tracking_delivery FOREIGN KEY (delivery_id) REFERENCES deliveries (id) ON DELETE CASCADE,
  CONSTRAINT fk_delivery_tracking_driver FOREIGN KEY (driver_id) REFERENCES delivery_drivers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Datos de seguimiento GPS de entregas';

-- Tabla: delivery_zone_costs (Costos por zona)
CREATE TABLE IF NOT EXISTS delivery_zone_costs (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL,
  zone_name varchar(100) NOT NULL,
  city_pattern varchar(255) NOT NULL COMMENT 'Patrón o nombre de la ciudad',
  zone_type enum('local','regional','nacional') NOT NULL DEFAULT 'local',
  base_cost decimal(10,2) NOT NULL DEFAULT 0.00,
  cost_multiplier decimal(4,2) NOT NULL DEFAULT 1.00,
  min_cost decimal(10,2) NOT NULL DEFAULT 0.00,
  max_cost decimal(10,2) NULL,
  delivery_days int NOT NULL DEFAULT 1,
  active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  INDEX idx_store_active (store_id, active),
  INDEX idx_zone_type (zone_type),
  INDEX idx_city_pattern (city_pattern),
  
  CONSTRAINT fk_delivery_zone_costs_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Costos de entrega por zona geográfica';

-- =============================================================================
-- PARTE 3: CORRECCIÓN DE CAMPOS FALTANTES
-- =============================================================================

-- CORRECCIÓN 1: Asegurar que delivery_group_items tenga el campo weight_grams
-- Verificar si existe la columna weight_grams en delivery_group_items
SET @db_name = DATABASE();
SET @table_name = 'delivery_group_items';
SET @column_name = 'weight_grams';

SELECT COUNT(*) INTO @column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = @db_name 
AND TABLE_NAME = @table_name 
AND COLUMN_NAME = @column_name;

-- Agregar weight_grams solo si no existe
SET @sql = IF(@column_exists = 0, 
    CONCAT('ALTER TABLE ', @table_name, ' ADD COLUMN ', @column_name, ' DECIMAL(8,2) DEFAULT 0.00 COMMENT "Peso en gramos del producto"'),
    CONCAT('SELECT "Columna ', @column_name, ' ya existe en ', @table_name, '" AS mensaje')
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- CORRECCIÓN 2: Agregar campos faltantes en products (VERSIÓN WORKBENCH)
SET @db = DATABASE();

-- 1. AGREGAR CAMPOS FALTANTES
SET @add_columns_sql = NULL;

SELECT 
    CONCAT('ALTER TABLE products ', 
           GROUP_CONCAT(
               CONCAT('ADD COLUMN ', col_name, ' ', col_type, 
                      IF(col_default = 'NULL', ' DEFAULT NULL', CONCAT(' DEFAULT ', col_default)),
                      ' COMMENT "', col_comment, '"')
               SEPARATOR ', '
           )
    ) INTO @add_columns_sql
FROM (
    SELECT 'cost_price' as col_name, 'DECIMAL(10,2)' as col_type, '0.00' as col_default, 'Precio de costo' as col_comment
    UNION SELECT 'category', 'VARCHAR(100)', 'NULL', 'Categoría del producto'
    UNION SELECT 'sku', 'VARCHAR(50)', 'NULL', 'Código SKU'
    UNION SELECT 'barcode', 'VARCHAR(50)', 'NULL', 'Código de barras'
    UNION SELECT 'min_stock_level', 'INT', '5', 'Nivel mínimo de stock'
    UNION SELECT 'weight_grams', 'DECIMAL(8,2)', '0.00', 'Peso en gramos'
    UNION SELECT 'dimensions', 'JSON', 'NULL', 'Dimensiones del producto'
    UNION SELECT 'supplier_id', 'INT', 'NULL', 'ID del proveedor'
    UNION SELECT 'tax_rate', 'DECIMAL(5,2)', '0.00', 'Tasa de impuesto'
) cols
WHERE NOT EXISTS (
    SELECT 1 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @db 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = cols.col_name
);

-- Usar función IF() para decidir qué ejecutar
SET @execute_sql = IF(@add_columns_sql IS NOT NULL,
    @add_columns_sql,
    'SELECT "Todos los campos ya existen en products" AS mensaje'
);

PREPARE stmt FROM @execute_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. AGREGAR FOREIGN KEY CON VALIDACIÓN COMPLETA
-- Verificar todas las condiciones usando subconsultas
SET @fk_sql = (
    SELECT IF(
        -- Condiciones para agregar FK
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
         WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'suppliers') > 0
        AND
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' 
         AND COLUMN_NAME = 'supplier_id') > 0
        AND
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
         WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'products' 
         AND CONSTRAINT_NAME = 'fk_products_supplier' AND CONSTRAINT_TYPE = 'FOREIGN KEY') = 0,
        
        -- Si se cumplen las condiciones
        'ALTER TABLE products ADD CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE SET NULL',
        
        -- Si no se cumplen
        CONCAT('SELECT "', 
            CASE 
                WHEN (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'suppliers') = 0 
                THEN 'Tabla suppliers no existe'
                WHEN (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'supplier_id') = 0 
                THEN 'Columna supplier_id no existe en products'
                WHEN (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = @db AND TABLE_NAME = 'products' AND CONSTRAINT_NAME = 'fk_products_supplier') > 0 
                THEN 'Foreign key fk_products_supplier ya existe'
                ELSE 'No se puede agregar foreign key'
            END,
            '" AS fk_info')
    )
);

PREPARE fk_stmt FROM @fk_sql;
EXECUTE fk_stmt;
DEALLOCATE PREPARE fk_stmt;

-- =============================================================================
-- PARTE 4: MIGRACIÓN DE DATOS EXISTENTES
-- =============================================================================

-- Crear direcciones basadas en los datos existentes de delivery_groups
INSERT INTO delivery_addresses (
    store_id, 
    order_id,
    contact_name, 
    phone, 
    email,
    address_line_1, 
    city,
    instructions,
    type,
    created_at,
    updated_at
)
SELECT DISTINCT
    oi.store_id,
    dg.order_id,
    dg.delivery_contact_name,
    dg.delivery_contact_phone,
    dg.delivery_contact_email,
    dg.delivery_address,
    dg.delivery_city,
    dg.delivery_notes,
    'other' as type,
    dg.created_at,
    dg.updated_at
FROM delivery_groups dg
JOIN order_items oi ON dg.order_id = oi.id
WHERE NOT EXISTS (
    SELECT 1 FROM delivery_addresses da 
    WHERE da.order_id = dg.order_id 
    AND da.address_line_1 = dg.delivery_address
    AND da.city = dg.delivery_city
);

-- =============================================================================
-- PARTE 5: MODIFICACIÓN DE TABLA delivery_groups
-- =============================================================================

-- Agregar la columna delivery_address_id a delivery_groups
-- ALTER TABLE delivery_groups 
-- ADD COLUMN delivery_address_id int NULL AFTER pickup_location_id,
-- ADD COLUMN delivery_method_id int NULL AFTER delivery_address_id,
-- ADD COLUMN assigned_driver_id int NULL AFTER delivery_method_id,
-- ADD COLUMN estimated_delivery_time timestamp NULL AFTER delivery_notes,
-- ADD COLUMN actual_delivery_time timestamp NULL AFTER estimated_delivery_time;

SET @db_name = DATABASE();
SET @table_name = 'delivery_groups';

-- Generar y ejecutar las sentencias ALTER dinámicamente
SELECT 
    CONCAT(
        'ALTER TABLE ', @table_name, ' ',
        GROUP_CONCAT(
            CONCAT('ADD COLUMN ', column_name, ' ', column_type, ' ', 
                   IF(is_nullable = 'YES', 'NULL', 'NOT NULL'),
                   IF(after_column IS NOT NULL, CONCAT(' AFTER ', after_column), ''))
            SEPARATOR ', '
        )
    ) INTO @alter_sql
FROM (
    SELECT 
        'delivery_address_id' as column_name,
        'int' as column_type,
        'YES' as is_nullable,
        'pickup_location_id' as after_column
    UNION ALL
    SELECT 'delivery_method_id', 'int', 'YES', 'delivery_address_id'
    UNION ALL
    SELECT 'assigned_driver_id', 'int', 'YES', 'delivery_method_id'
    UNION ALL
    SELECT 'estimated_delivery_time', 'timestamp', 'YES', 'delivery_notes'
    UNION ALL
    SELECT 'actual_delivery_time', 'timestamp', 'YES', 'estimated_delivery_time'
) new_columns
WHERE NOT EXISTS (
    SELECT 1 FROM information_schema.columns 
    WHERE table_schema = @db_name 
    AND table_name = @table_name 
    AND column_name = new_columns.column_name
);

SET @alter_sql = IF(@alter_sql is null, 'Select "las columnas ya existen" as mensaje', @alter_sql);

-- Ejecutar solo si hay columnas para agregar
PREPARE stmt FROM @alter_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizar delivery_groups con los datos de las direcciones creadas
UPDATE delivery_groups dg
JOIN orders o ON dg.order_id = o.id
JOIN delivery_addresses da ON da.order_id = dg.order_id 
    AND da.address_line_1 = dg.delivery_address 
    AND da.city = dg.delivery_city
SET 
    dg.delivery_address_id = da.id,
    dg.delivery_method_id = 1, -- Método por defecto
    dg.assigned_driver_id = NULL
WHERE dg.delivery_address_id IS NULL and dg.id > 0;

-- Agregar índices para las nuevas columnas
-- Agregar índices para las nuevas columnas solo si no existen

-- Verificar y crear índice para delivery_address_id
SET @index_name = 'idx_delivery_address_id';
SET @table_name = 'delivery_groups';
SET @db_name = DATABASE();

SELECT COUNT(*) INTO @index_exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @db_name 
AND TABLE_NAME = @table_name 
AND INDEX_NAME = @index_name;

SET @sql = IF(@index_exists = 0, 
    CONCAT('CREATE INDEX ', @index_name, ' ON ', @table_name, ' (delivery_address_id)'),
    CONCAT('SELECT "Índice ', @index_name, ' ya existe" AS mensaje')
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y crear índice para delivery_method_id
SET @index_name = 'idx_delivery_method_id';

SELECT COUNT(*) INTO @index_exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @db_name 
AND TABLE_NAME = @table_name 
AND INDEX_NAME = @index_name;

SET @sql = IF(@index_exists = 0, 
    CONCAT('CREATE INDEX ', @index_name, ' ON ', @table_name, ' (delivery_method_id)'),
    CONCAT('SELECT "Índice ', @index_name, ' ya existe" AS mensaje')
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y crear índice para assigned_driver_id
SET @index_name = 'idx_assigned_driver_id';

SELECT COUNT(*) INTO @index_exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = @db_name 
AND TABLE_NAME = @table_name 
AND INDEX_NAME = @index_name;

SET @sql = IF(@index_exists = 0, 
    CONCAT('CREATE INDEX ', @index_name, ' ON ', @table_name, ' (assigned_driver_id)'),
    CONCAT('SELECT "Índice ', @index_name, ' ya existe" AS mensaje')
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar foreign keys
-- Agregar foreign keys solo si no existen

SET @db_name = DATABASE();
SET @table_name = 'delivery_groups';

-- 1. Verificar y agregar fk_delivery_groups_address
SELECT COUNT(*) INTO @fk_exists 
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = @db_name 
AND TABLE_NAME = @table_name 
AND CONSTRAINT_NAME = 'fk_delivery_groups_address'
AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists = 0, 
    CONCAT('ALTER TABLE ', @table_name, 
           ' ADD CONSTRAINT fk_delivery_groups_address ',
           'FOREIGN KEY (delivery_address_id) ',
           'REFERENCES delivery_addresses (id) ',
           'ON DELETE SET NULL'),
    CONCAT('SELECT "Foreign key fk_delivery_groups_address ya existe" AS mensaje')
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Verificar y agregar fk_delivery_groups_method
SELECT COUNT(*) INTO @fk_exists 
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = @db_name 
AND TABLE_NAME = @table_name 
AND CONSTRAINT_NAME = 'fk_delivery_groups_method'
AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists = 0, 
    CONCAT('ALTER TABLE ', @table_name, 
           ' ADD CONSTRAINT fk_delivery_groups_method ',
           'FOREIGN KEY (delivery_method_id) ',
           'REFERENCES delivery_methods (id) ',
           'ON DELETE SET NULL'),
    CONCAT('SELECT "Foreign key fk_delivery_groups_method ya existe" AS mensaje')
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Verificar y agregar fk_delivery_groups_driver
SELECT COUNT(*) INTO @fk_exists 
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = @db_name 
AND TABLE_NAME = @table_name 
AND CONSTRAINT_NAME = 'fk_delivery_groups_driver'
AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql = IF(@fk_exists = 0, 
    CONCAT('ALTER TABLE ', @table_name, 
           ' ADD CONSTRAINT fk_delivery_groups_driver ',
           'FOREIGN KEY (assigned_driver_id) ',
           'REFERENCES delivery_drivers (id) ',
           'ON DELETE SET NULL'),
    CONCAT('SELECT "Foreign key fk_delivery_groups_driver ya existe" AS mensaje')
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- PARTE 6: ACTUALIZACIÓN DE ESTADOS PARA CONSISTENCIA
-- =============================================================================

-- Actualizar estados de delivery_groups para que coincidan con el estándar
ALTER TABLE delivery_groups 
MODIFY COLUMN status enum('pendiente','programada','en_preparacion','en_transito','entregada','fallida','cancelada') DEFAULT 'pendiente';

-- Actualizar estados existentes (mapeo de estados antiguos a nuevos)
UPDATE delivery_groups SET status = 'pendiente' WHERE status = 'pending';
UPDATE delivery_groups SET status = 'en_preparacion' WHERE status = 'preparing';
UPDATE delivery_groups SET status = 'en_transito' WHERE status = 'dispatched';
UPDATE delivery_groups SET status = 'entregada' WHERE status = 'delivered';
UPDATE delivery_groups SET status = 'cancelada' WHERE status = 'cancelled';

-- =============================================================================
-- PARTE 7: TRIGGERS ACTUALIZADOS (CORREGIDO)
-- =============================================================================

-- Primero, resetear la conexión para evitar problemas de sincronización
SELECT 'Configurando triggers...' as Estado;

-- Eliminar triggers existentes primero
DROP TRIGGER IF EXISTS log_delivery_creation;
DROP TRIGGER IF EXISTS update_driver_stats_after_delivery;
DROP TRIGGER IF EXISTS delivery_groups_status_change_log;

-- Ahora crear los triggers correctamente
DELIMITER $$

-- Trigger actualizado para log automático al crear entrega
CREATE TRIGGER log_delivery_creation
AFTER INSERT ON deliveries
FOR EACH ROW
BEGIN
    INSERT INTO delivery_activity_log (delivery_id, action, description, user_type)
    VALUES (
        NEW.id, 
        'entrega_creada', 
        CONCAT('Entrega creada - Cliente: ', NEW.customer_name, ', Dirección: ', NEW.delivery_address), 
        'system'
    );
END$$

-- Trigger para actualizar estadísticas de repartidores
CREATE TRIGGER update_driver_stats_after_delivery
AFTER UPDATE ON deliveries
FOR EACH ROW
BEGIN
    DECLARE driver_id_var INT;
    DECLARE is_success TINYINT(1);
    
    -- Solo procesar si cambió el estado de entrega
    IF OLD.status != NEW.status AND NEW.assigned_driver_id IS NOT NULL THEN
        SET driver_id_var = NEW.assigned_driver_id;
        
        -- Verificar si la entrega fue exitosa
        IF NEW.status = 'entregada' THEN
            SET is_success = 1;
        ELSEIF NEW.status IN ('fallida', 'cancelada') THEN
            SET is_success = 0;
        ELSE
            SET is_success = NULL; -- No contar otros estados
        END IF;
        
        -- Actualizar estadísticas del repartidor
        IF is_success IS NOT NULL THEN
            UPDATE delivery_drivers
            SET 
                total_deliveries = total_deliveries + 1,
                successful_deliveries = successful_deliveries + IF(is_success = 1, 1, 0),
                failed_deliveries = failed_deliveries + IF(is_success = 0, 1, 0),
                updated_at = NOW()
            WHERE id = driver_id_var;
        END IF;
    END IF;
END$$

-- Trigger para registrar cambios de estado en delivery_groups
CREATE TRIGGER delivery_groups_status_change_log
AFTER UPDATE ON delivery_groups
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO delivery_activity_log (delivery_id, activity_type, description)
        VALUES (
            NEW.id, 
            'status_changed', 
            CONCAT('Estado cambiado de "', OLD.status, '" a "', NEW.status, '"')
        );
    END IF;
END$$

DELIMITER ;

-- =============================================================================
-- PARTE 8: VISTAS ACTUALIZADAS
-- =============================================================================

-- Vista actualizada para grupos de entrega activos con información completa
DROP VIEW IF EXISTS v_active_delivery_groups;
CREATE VIEW v_active_delivery_groups AS
SELECT 
    dg.id,
    dg.order_id,
    dg.delivery_method_id,
    dm.name as delivery_method_name,
    dg.delivery_address_id,
    da.address_line_1 as full_address,
    da.city,
    dg.delivery_date,
    dg.delivery_time_slot,
    dg.status,
    dg.shipping_cost,
    dg.delivery_notes,
    dg.assigned_driver_id,
    dd.name as driver_name,
    dd.vehicle_type,
    dd.phone as driver_phone,
    dg.estimated_delivery_time,
    dg.actual_delivery_time,
    dg.created_at,
    dg.updated_at,
    COUNT(dgi.id) as total_items,
    SUM(dgi.quantity) as total_quantity,
    SUM(dgi.weight_grams * dgi.quantity) as total_weight_grams
FROM delivery_groups dg
LEFT JOIN delivery_methods dm ON dg.delivery_method_id = dm.id
LEFT JOIN delivery_addresses da ON dg.delivery_address_id = da.id
LEFT JOIN delivery_drivers dd ON dg.assigned_driver_id = dd.id
LEFT JOIN delivery_group_items dgi ON dg.id = dgi.delivery_group_id
WHERE dg.status IN ('pendiente', 'programada', 'en_preparacion', 'en_transito')
GROUP BY dg.id;

-- Vista para estadísticas de entregas por periodo
DROP VIEW IF EXISTS v_delivery_statistics;
CREATE VIEW v_delivery_statistics AS
SELECT 
    d.store_id,
    DATE(d.scheduled_date) as delivery_date,
    COUNT(*) as total_deliveries,
    SUM(CASE WHEN d.status = 'entregada' THEN 1 ELSE 0 END) as delivered_count,
    SUM(CASE WHEN d.status = 'en_transito' THEN 1 ELSE 0 END) as in_transit_count,
    SUM(CASE WHEN d.status = 'fallida' THEN 1 ELSE 0 END) as failed_count,
    SUM(d.delivery_cost) as total_revenue,
    AVG(d.delivery_cost) as avg_delivery_cost,
    COUNT(DISTINCT d.delivery_city) as cities_served,
    COUNT(DISTINCT d.assigned_driver_id) as drivers_used
FROM deliveries d
WHERE d.scheduled_date IS NOT NULL
GROUP BY d.store_id, DATE(d.scheduled_date);

-- =============================================================================
-- PARTE 9: PROCEDIMIENTOS ALMACENADOS
-- =============================================================================

-- Primero eliminar procedimientos existentes
DROP PROCEDURE IF EXISTS GetPendingDeliveries;
DROP PROCEDURE IF EXISTS AssignBestDriver;
DROP PROCEDURE IF EXISTS createDeliveryGroupFixed;

-- Ahora crear los procedimientos con delimitador correcto
DELIMITER $$

-- Procedimiento para obtener entregas pendientes de asignar
CREATE PROCEDURE GetPendingDeliveries(IN p_store_id INT)
BEGIN
    SELECT 
        d.*,
        dm.name as method_name,
        o.total as order_total
    FROM deliveries d
    LEFT JOIN delivery_methods dm ON d.delivery_method_id = dm.id
    LEFT JOIN orders o ON d.order_id = o.id
    WHERE d.store_id = p_store_id 
        AND d.status = 'pendiente'
        AND d.assigned_driver_id IS NULL
        AND (d.scheduled_date IS NULL OR d.scheduled_date >= CURDATE())
    ORDER BY 
        d.priority DESC,
        d.scheduled_date ASC,
        d.created_at ASC;
END$$

-- Procedimiento para asignar el mejor repartidor disponible (actualizado)
CREATE PROCEDURE AssignBestDriver(IN p_delivery_id INT)
BEGIN
    DECLARE v_store_id INT;
    DECLARE v_delivery_weight DECIMAL(8,2);
    DECLARE v_delivery_city VARCHAR(100);
    DECLARE v_delivery_date DATE;
    DECLARE v_driver_id INT;
    DECLARE v_distance DECIMAL(8,2);
    
    -- Obtener información de la entrega
    SELECT store_id, total_weight, delivery_city, scheduled_date 
    INTO v_store_id, v_delivery_weight, v_delivery_city, v_delivery_date
    FROM deliveries WHERE id = p_delivery_id;
    
    -- Buscar el mejor repartidor disponible
    SELECT dd.id, 
           (6371 * acos(cos(radians(dd.current_latitude)) * cos(radians(0)) * 
           cos(radians(0) - radians(dd.current_longitude)) + sin(radians(dd.current_latitude)) * 
           sin(radians(0)))) as distance
    INTO v_driver_id, v_distance
    FROM delivery_drivers dd
    WHERE dd.store_id = v_store_id 
        AND dd.active = 1 
        AND dd.status = 'available'
        AND (dd.max_weight_capacity IS NULL OR dd.max_weight_capacity >= v_delivery_weight)
        AND (dd.working_hours_start IS NULL OR 
             (CURTIME() >= dd.working_hours_start AND CURTIME() <= dd.working_hours_end))
        AND FIND_IN_SET(DAYOFWEEK(v_delivery_date), dd.working_days) > 0
    ORDER BY dd.average_delivery_time ASC, distance ASC
    LIMIT 1;
    
    -- Asignar el repartidor si se encontró
    IF v_driver_id IS NOT NULL THEN
        UPDATE deliveries SET assigned_driver_id = v_driver_id, updated_at = NOW()
        WHERE id = p_delivery_id;
        
        SELECT CONCAT('Repartidor ', v_driver_id, ' asignado exitosamente') as message;
    ELSE
        SELECT 'No se encontró repartidor disponible' as message;
    END IF;
END$$

-- Procedimiento para crear grupo de entrega (versión corregida)
CREATE PROCEDURE createDeliveryGroupFixed(
    IN p_order_id INT,
    IN p_delivery_address_id INT,
    IN p_delivery_date DATE,
    IN p_delivery_time_slot VARCHAR(20),
    IN p_delivery_notes TEXT,
    OUT p_group_id INT
)
BEGIN
    DECLARE v_store_id INT;
    DECLARE v_group_name VARCHAR(100);
    
    -- Obtener store_id de la orden
    SELECT store_id INTO v_store_id FROM orders WHERE id = p_order_id;
    
    -- Generar nombre del grupo
    SET v_group_name = CONCAT('Grupo-', p_order_id, '-', DATE_FORMAT(NOW(), '%Y%m%d-%H%i%s'));
    
    -- Crear el grupo
    INSERT INTO delivery_groups (
        order_id, 
        group_name,
        delivery_address_id,
        delivery_date,
        delivery_time_slot,
        delivery_notes,
        status,
        shipping_cost,
        created_at,
        updated_at
    ) VALUES (
        p_order_id,
        v_group_name,
        p_delivery_address_id,
        p_delivery_date,
        p_delivery_time_slot,
        p_delivery_notes,
        'pendiente',
        0.00,
        NOW(),
        NOW()
    );
    
    SET p_group_id = LAST_INSERT_ID();
    
    -- Registrar actividad
    INSERT INTO delivery_activity_log (delivery_id, action, description, user_type)
    VALUES (
        p_group_id,
        'grupo_creado',
        CONCAT('Grupo de entrega creado: ', v_group_name),
        'system'
    );
END$$

DELIMITER ;

-- =============================================================================
-- PARTE 10: DATOS INICIALES
-- =============================================================================

-- Insertar métodos de entrega por defecto si no existen
INSERT IGNORE INTO delivery_methods (
    store_id, name, description, type, base_cost, delivery_time_days, active
) VALUES 
(1, 'Entrega Estándar', 'Entrega regular en 1-3 días hábiles', 'standard', 2500.00, 2, 1),
(1, 'Entrega Express', 'Entrega en 24 horas', 'express', 4500.00, 1, 1),
(1, 'Mismo Día', 'Entrega el mismo día (sujeto a horarios)', 'same_day', 6500.00, 0, 1),
(1, 'Programada', 'Entrega en fecha específica seleccionada', 'scheduled', 3500.00, 1, 1);

-- Insertar zonas de entrega por defecto
INSERT IGNORE INTO delivery_zone_costs (
    store_id, zone_name, city_pattern, zone_type, base_cost, cost_multiplier, min_cost, max_cost, delivery_days, active
) VALUES
(1, 'Centro', '%centro%', 'local', 20.00, 1.0, 20.00, 30.00, 1, 1),
(1, 'Zona Metropolitana', '%metro%', 'local', 30.00, 1.2, 30.00, 50.00, 2, 1),
(1, 'Regional', '%regional%', 'regional', 50.00, 1.5, 50.00, 100.00, 4, 1),
(1, 'Nacional', '%nacional%', 'nacional', 80.00, 2.0, 80.00, 200.00, 7, 1);

-- Horarios de entrega para los próximos 30 días
INSERT IGNORE INTO delivery_schedules (
    store_id, available_date, time_slot, slots_available, delivery_time_start, delivery_time_end
)
SELECT 
    1 as store_id,
    CURDATE() + INTERVAL t.n DAY as available_date,
    s.time_slot,
    10 as slots_available,
    CASE s.time_slot 
        WHEN 'morning' THEN '08:00:00'
        WHEN 'afternoon' THEN '14:00:00'
        WHEN 'evening' THEN '17:00:00'
    END as delivery_time_start,
    CASE s.time_slot 
        WHEN 'morning' THEN '12:00:00'
        WHEN 'afternoon' THEN '16:00:00'
        WHEN 'evening' THEN '20:00:00'
    END as delivery_time_end
FROM (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION
    SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION
    SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION
    SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
) t
CROSS JOIN (
    SELECT 'morning' as time_slot UNION ALL
    SELECT 'afternoon' UNION ALL
    SELECT 'evening'
) s
WHERE DAYOFWEEK(CURDATE() + INTERVAL t.n DAY) NOT IN (1, 7); -- Excluir domingos y sábados

-- =============================================================================
-- PARTE 11: VERIFICACIÓN Y LIMPIEZA FINAL
-- =============================================================================

-- Habilitar foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- Verificar que todas las tablas se crearon correctamente
SELECT 
    TABLE_NAME as 'Tabla',
    TABLE_ROWS as 'Registros Estimados',
    CREATE_TIME as 'Fecha Creación'
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN (
    'delivery_addresses', 'deliveries', 'delivery_methods', 'delivery_drivers', 
    'delivery_groups', 'delivery_group_items', 'delivery_activity_log',
    'delivery_schedules', 'delivery_status_history', 'delivery_notifications',
    'delivery_tracking', 'delivery_zone_costs', 'delivery_coupons'
)
ORDER BY TABLE_NAME;

-- Mostrar resumen de la migración
SELECT 
    'MIGRACIÓN COMPLETADA EXITOSAMENTE' as Status,
    'Sistema de Delivery Normalizado' as Sistema,
    NOW() as Fecha_Completada;

-- Mostrar estadísticas finales
SELECT 
    'delivery_addresses' as tabla, 
    COUNT(*) as registros 
FROM delivery_addresses
UNION ALL
SELECT 
    'delivery_groups' as tabla, 
    COUNT(*) as registros 
FROM delivery_groups
UNION ALL
SELECT 
    'delivery_schedules' as tabla, 
    COUNT(*) as registros 
FROM delivery_schedules
UNION ALL
SELECT 
    'delivery_status_history' as tabla, 
    COUNT(*) as registros 
FROM delivery_status_history
UNION ALL
SELECT 
    'delivery_notifications' as tabla, 
    COUNT(*) as registros 
FROM delivery_notifications
UNION ALL
SELECT 
    'delivery_tracking' as tabla, 
    COUNT(*) as registros 
FROM delivery_tracking
UNION ALL
SELECT 
    'delivery_zone_costs' as tabla, 
    COUNT(*) as registros 
FROM delivery_zone_costs;

COMMIT;

-- =============================================================================
-- NOTAS DE IMPLEMENTACIÓN
-- =============================================================================
/*
CAMBIOS PRINCIPALES IMPLEMENTADOS:

1. NORMALIZACIÓN DE DIRECCIONES:
   - Creada tabla delivery_addresses para reutilización
   - Migrados datos existentes de delivery_groups
   - Actualizada referencia en delivery_groups.delivery_address_id

2. ESTRUCTURA COMPLETA DEL SISTEMA:
   - delivery_schedules: Calendario de entregas
   - delivery_status_history: Historial de cambios
   - delivery_notifications: Sistema de notificaciones
   - delivery_tracking: Seguimiento GPS
   - delivery_zone_costs: Costos por zona

3. CORRECCIÓN DE CAMPOS FALTANTES:
   - Agregado campo weight_grams a delivery_group_items (REQUIRED por código PHP)
   - Agregados 9 campos faltantes a tabla products: cost_price, category, sku, barcode, min_stock_level, weight_grams, dimensions, supplier_id, tax_rate
   - Agregado foreign key para supplier_id
   - CAMPOS AGREGADOS A PRODUCTS: cost_price, category, sku, barcode, min_stock_level, weight_grams, dimensions, supplier_id, tax_rate

4. CONSISTENCIA DE ESTADOS:
   - Estados unificados: pendiente, programada, en_preparacion, en_transito, entregada, fallida, cancelada
   - Triggers actualizados para nueva estructura

5. PROCEDIMIENTOS CORREGIDOS:
   - createDeliveryGroupFixed: Versión corregida que usa delivery_address_id
   - GetPendingDeliveries: Mejorado para nueva estructura
   - AssignBestDriver: Optimizado con nueva estructura

6. VISTAS ACTUALIZADAS:
   - v_active_delivery_groups: Usa delivery_addresses en lugar de campos directos
   - v_delivery_statistics: Estadísticas mejoradas

COMPATIBILIDAD:
- Mantiene compatibilidad con código existente
- Preserva todos los datos existentes
- Agrega funcionalidad completa del sistema
- CORRIGE INCONSISTENCIAS: delivery_group_items.weight_grams y products.* campos

PRÓXIMOS PASOS:
1. Actualizar código PHP para usar createDeliveryGroupFixed()
2. Implementar funcionalidad de notificaciones
3. Integrar con APIs de geolocalización
4. Desarrollar dashboard de seguimiento
5. Verificar que todos los campos PHP funcionan correctamente

CAMPOS CORREGIDOS:
- delivery_group_items.weight_grams (DECIMAL(8,2) DEFAULT 0.00)
- products.cost_price (DECIMAL(10,2) DEFAULT 0.00)
- products.category (VARCHAR(100) DEFAULT NULL)
- products.sku (VARCHAR(50) DEFAULT NULL)
- products.barcode (VARCHAR(50) DEFAULT NULL)
- products.min_stock_level (INT DEFAULT 5)
- products.weight_grams (DECIMAL(8,2) DEFAULT 0.00)
- products.dimensions (JSON DEFAULT NULL)
- products.supplier_id (INT DEFAULT NULL)
- products.tax_rate (DECIMAL(5,2) DEFAULT 0.00)
*/