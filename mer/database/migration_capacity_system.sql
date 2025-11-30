-- Migración para sistema de gestión de capacidades
-- Crear tablas necesarias para la gestión de capacidades diarias y zonas de servicio

-- Tabla para capacidades diarias de productos
CREATE TABLE IF NOT EXISTS product_daily_capacity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    store_id INT NOT NULL,
    capacity_date DATE NOT NULL,
    available_capacity INT NOT NULL DEFAULT 0,
    booked_capacity INT NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_date (product_id, capacity_date),
    INDEX idx_product_date (product_id, capacity_date),
    INDEX idx_store_date (store_id, capacity_date),
    INDEX idx_capacity_date (capacity_date),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para zonas de servicio de tiendas
CREATE TABLE IF NOT EXISTS store_service_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    zone_name VARCHAR(255) NOT NULL,
    zone_type ENUM('ciudad', 'comuna', 'region') NOT NULL,
    city VARCHAR(255),
    region VARCHAR(255),
    max_services_per_day INT NOT NULL DEFAULT 1,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_store_active (store_id, active),
    INDEX idx_zone_type (zone_type),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para horarios por defecto de productos
CREATE TABLE IF NOT EXISTS product_default_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    day_of_week INT NOT NULL COMMENT '0=domingo, 1=lunes, 2=martes, 3=miércoles, 4=jueves, 5=viernes, 6=sábado',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_day (product_id, day_of_week),
    INDEX idx_product_active (product_id, active),
    INDEX idx_day_active (day_of_week, active),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunos datos de ejemplo para pruebas
INSERT INTO store_service_zones (store_id, zone_name, zone_type, city, region, max_services_per_day) VALUES
(1, 'Centro de Viña del Mar', 'comuna', 'Viña del Mar', 'Valparaíso', 20),
(1, 'Valparaíso', 'ciudad', 'Valparaíso', 'Valparaíso', 1),
(1, 'Región de Valparaíso', 'region', 'Valparaíso', 'Valparaíso', 3);

-- Configuración por defecto para productos de café (ejemplo)
INSERT INTO product_default_schedule (product_id, day_of_week, start_time, end_time) VALUES
-- Horario de lunes a viernes: 09:30 a 19:30
(1, 1, '09:30:00', '19:30:00'), -- lunes
(1, 2, '09:30:00', '19:30:00'), -- martes
(1, 3, '09:30:00', '19:30:00'), -- miércoles
(1, 4, '09:30:00', '19:30:00'), -- jueves
(1, 5, '09:30:00', '19:30:00'), -- viernes
-- Horario de sábado a domingo: 10:00 a 18:30
(1, 6, '10:00:00', '18:30:00'), -- sábado
(1, 0, '10:00:00', '18:30:00'); -- domingo

-- Configuración por defecto para servicios de aseo (ejemplo)
INSERT INTO product_default_schedule (product_id, day_of_week, start_time, end_time) VALUES
-- Horario de lunes a viernes: 07:50 a 17:50
(2, 1, '07:50:00', '17:50:00'), -- lunes
(2, 2, '07:50:00', '17:50:00'), -- martes
(2, 3, '07:50:00', '17:50:00'), -- miércoles
(2, 4, '07:50:00', '17:50:00'), -- jueves
(2, 5, '07:50:00', '17:50:00'); -- viernes

-- Comentarios para documentación:
/*
Uso de las tablas:

1. product_daily_capacity:
   - Almacena la capacidad diaria configurada para cada producto
   - 'available_capacity': capacidad máxima disponible para ese día
   - 'booked_capacity': capacidad ya comprometida (reservas)
   - 'remaining_capacity': capacidad disponible (se calcula automáticamente)

2. store_service_zones:
   - Define las zonas geográficas donde una tienda puede entregar servicios
   - 'zone_type': tipo de zona (ciudad, comuna, región)
   - 'max_services_per_day': máximo de servicios que se pueden realizar en esa zona por día

3. product_default_schedule:
   - Define los horarios por defecto para cada día de la semana
   - Se aplica automáticamente cuando se genera capacidad para nuevos productos
   - Permite personalización por tipo de producto/servicio

Ejemplos de consultas útiles:

-- Ver todas las capacidades futuras de un producto
SELECT DATE(capacity_date) as fecha, available_capacity, booked_capacity, 
       (available_capacity - booked_capacity) as disponible
FROM product_daily_capacity 
WHERE product_id = 1 
AND capacity_date >= CURDATE()
ORDER BY capacity_date;

-- Ver estadísticas de capacidad por tienda
SELECT s.name as tienda, 
       COUNT(pdc.id) as dias_configurados,
       SUM(pdc.available_capacity) as capacidad_total,
       SUM(pdc.booked_capacity) as reservado_total
FROM stores s
LEFT JOIN products p ON p.store_id = s.id
LEFT JOIN product_daily_capacity pdc ON p.id = pdc.product_id
WHERE s.id = 1
GROUP BY s.id, s.name;

-- Ver zonas de servicio de una tienda
SELECT zone_name, zone_type, city, region, max_services_per_day
FROM store_service_zones 
WHERE store_id = 1 AND active = 1
ORDER BY zone_type, zone_name;
*/