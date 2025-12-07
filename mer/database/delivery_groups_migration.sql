-- Migración para Delivery Groups System
-- Crear las tablas necesarias para el sistema de grupos de entrega
DROP TABLE IF EXISTS delivery_activity_log;
DROP TABLE IF EXISTS delivery_groups_items;
DROP TABLE IF EXISTS delivery_groups;

-- Tabla de grupos de entrega
CREATE TABLE IF NOT EXISTS delivery_groups (
    id INT AUTO_INCREMENT PRIMARY KEY, --
    order_id INT NOT NULL, --
    
    delivery_method_id INT NOT NULL, 
    delivery_address_id INT NOT NULL,
    
    delivery_date DATE NOT NULL, --
    delivery_time_slot ENUM('morning', 'afternoon', 'evening') DEFAULT 'morning', --
    status ENUM('pendiente', 'confirmado', 'en_preparacion', 'en_transito', 'entregado', 'fallido', 'cancelado') DEFAULT 'pendiente', --
    shipping_cost DECIMAL(10,2) DEFAULT 0.00, --
    delivery_notes TEXT, --
    assigned_driver_id INT NULL,
    estimated_delivery_time DATETIME NULL, --
    actual_delivery_time DATETIME NULL, --
    signature_data TEXT NULL,
    photo_proof TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_delivery_date (delivery_date),
    INDEX idx_assigned_driver (assigned_driver_id),
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_method_id) REFERENCES delivery_methods(id),
    FOREIGN KEY (delivery_address_id) REFERENCES delivery_addresses(id),
    FOREIGN KEY (assigned_driver_id) REFERENCES delivery_drivers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de items en grupos de entrega
CREATE TABLE IF NOT EXISTS delivery_group_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_group_id INT NOT NULL,
    order_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    weight_grams DECIMAL(8,2) DEFAULT 0.00,
    dimensions JSON NULL,
    special_handling ENUM('normal', 'fragile', 'refrigerated', 'hazardous') DEFAULT 'normal',
    notes TEXT,
    picked_up BOOLEAN DEFAULT FALSE,
    picked_up_at TIMESTAMP NULL,
    delivered BOOLEAN DEFAULT FALSE,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_group_item (delivery_group_id, order_item_id),
    INDEX idx_delivery_group (delivery_group_id),
    INDEX idx_order_item (order_item_id),
    INDEX idx_picked_up (picked_up),
    INDEX idx_delivered (delivered),
    
    FOREIGN KEY (delivery_group_id) REFERENCES delivery_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de actividad/auditoría para grupos de entrega
CREATE TABLE IF NOT EXISTS delivery_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_group_id INT NOT NULL,
    activity_type ENUM('created', 'item_added', 'item_removed', 'status_changed', 'driver_assigned', 'pickup_completed', 'delivery_completed', 'delivery_failed', 'notes_added') NOT NULL,
    description TEXT NOT NULL,
    user_id INT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_delivery_group (delivery_group_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (delivery_group_id) REFERENCES delivery_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vista para grupos de entrega activos con información completa
CREATE OR REPLACE VIEW v_active_delivery_groups AS
SELECT 
    dg.id,
    dg.order_id,
    dg.delivery_method_id,
    dm.name as delivery_method_name,
    dg.delivery_address_id,
    da.full_address,
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
JOIN delivery_methods dm ON dg.delivery_method_id = dm.id
JOIN delivery_addresses da ON dg.delivery_address_id = da.id
LEFT JOIN delivery_drivers dd ON dg.assigned_driver_id = dd.id
LEFT JOIN delivery_group_items dgi ON dg.id = dgi.delivery_group_id
WHERE dg.status IN ('pendiente', 'confirmado', 'en_preparacion', 'en_transito')
GROUP BY dg.id;

-- Trigger para registrar cambios de estado automáticamente
DELIMITER //
CREATE TRIGGER delivery_groups_status_change_log
AFTER UPDATE ON delivery_groups
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO delivery_activity_log (delivery_group_id, activity_type, description)
        VALUES (
            NEW.id, 
            'status_changed', 
            CONCAT('Estado cambiado de "', OLD.status, '" a "', NEW.status, '"')
        );
    END IF;
END//

DELIMITER ;

-- Insertar métodos de entrega por defecto si no existen
INSERT IGNORE INTO delivery_methods (name, description, base_cost, delivery_time_days, active) VALUES
('Entrega Estándar', 'Entrega en 2-3 días hábiles', 2500.00, 3, 1),
('Entrega Express', 'Entrega en 24 horas', 4500.00, 1, 1),
('Entrega Programada', 'Entrega en fecha y hora específica', 3500.00, 2, 1),
('Recojo en Tienda', 'Retiro en punto de venta', 0.00, 0, 1);

-- Insertar direcciones de entrega por defecto si no existen  
INSERT IGNORE INTO delivery_addresses (store_id, type, full_address, city, region, country, postal_code) VALUES
(1, 'warehouse', 'Bodega Principal - Comercial El Roble V2', 'Viña del Mar', 'Valparaíso', 'Chile', '2520000');

-- Insertar drivers por defecto si no existen
INSERT IGNORE INTO delivery_drivers (store_id, name, phone, vehicle_type, capacity_kg, license_number, rating, active) VALUES
(1, 'Sistema Automático', '+56900000000', 'motorcycle', 50, 'DRV001', 5.0, 1);

COMMIT;