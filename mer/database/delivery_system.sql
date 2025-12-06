-- ===============================================================
-- ESTRUCTURAS DE BASE DE DATOS PARA EL MÓDULO DE ENTREGAS
-- Creado por: MiniMax Agent
-- Fecha: 2025-12-06
-- ===============================================================

-- Métodos de entrega disponibles
CREATE TABLE delivery_methods (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL COMMENT 'ID de la tienda',
  name varchar(100) NOT NULL COMMENT 'Nombre del método',
  description text COMMENT 'Descripción detallada del método',
  type enum('standard','express','same_day','scheduled') DEFAULT 'standard' COMMENT 'Tipo de entrega',
  
  -- Costos y tiempos
  base_cost decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Costo base de entrega',
  cost_per_kg decimal(10,2) DEFAULT '0.00' COMMENT 'Costo adicional por kilogramo',
  cost_per_km decimal(10,2) DEFAULT '0.00' COMMENT 'Costo adicional por kilómetro',
  delivery_time_days int NOT NULL DEFAULT '1' COMMENT 'Días de entrega estimados',
  min_delivery_time_hours int DEFAULT NULL COMMENT 'Tiempo mínimo de entrega en horas',
  max_delivery_time_hours int DEFAULT NULL COMMENT 'Tiempo máximo de entrega en horas',
  
  -- Restricciones
  max_weight decimal(8,2) DEFAULT NULL COMMENT 'Peso máximo en kg',
  max_volume decimal(8,2) DEFAULT NULL COMMENT 'Volumen máximo en litros',
  max_distance_km decimal(8,2) DEFAULT NULL COMMENT 'Distancia máxima en km',
  min_order_amount decimal(10,2) DEFAULT '0.00' COMMENT 'Monto mínimo de orden',
  coverage_areas json DEFAULT NULL COMMENT 'Áreas de cobertura (JSON con ciudades/regiones)',
  
  -- Configuración operativa
  working_hours_start time DEFAULT NULL COMMENT 'Hora de inicio de operaciones',
  working_hours_end time DEFAULT NULL COMMENT 'Hora de fin de operaciones',
  working_days varchar(50) DEFAULT '1,2,3,4,5' COMMENT 'Días laborales (1=lunes, 7=domingo)',
  booking_advance_hours int DEFAULT '24' COMMENT 'Horas mínimas de anticipación para reservar',
  max_daily_orders int DEFAULT NULL COMMENT 'Máximo de órdenes por día',
  
  -- Estado y configuración
  active tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado activo/inactivo',
  auto_assign_drivers tinyint(1) DEFAULT '1' COMMENT 'Asignar repartidores automáticamente',
  requires_driver_signature tinyint(1) DEFAULT '0' COMMENT 'Requiere firma del repartidor',
  allows_cod tinyint(1) DEFAULT '0' COMMENT 'Permite pago contra entrega',
  max_cod_amount decimal(10,2) DEFAULT NULL COMMENT 'Monto máximo para pago contra entrega',
  
  -- Configuración de notificaciones
  send_sms_confirmation tinyint(1) DEFAULT '1' COMMENT 'Enviar SMS de confirmación',
  send_email_confirmation tinyint(1) DEFAULT '1' COMMENT 'Enviar email de confirmación',
  send_sms_updates tinyint(1) DEFAULT '1' COMMENT 'Enviar SMS de actualizaciones',
  send_email_updates tinyint(1) DEFAULT '1' COMMENT 'Enviar email de actualizaciones',
  
  -- Sistema
  sort_order int DEFAULT '0' COMMENT 'Orden de visualización',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  KEY idx_store_active (store_id,active),
  KEY idx_type_active (type,active),
  KEY idx_name (name),
  KEY idx_sort_order (sort_order),
  
  CONSTRAINT delivery_methods_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Métodos de entrega disponibles por tienda';

-- Repartidores asociados a las tiendas
CREATE TABLE delivery_drivers (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL COMMENT 'ID de la tienda',
  
  -- Información personal
  name varchar(200) NOT NULL COMMENT 'Nombre completo del repartidor',
  phone varchar(50) NOT NULL COMMENT 'Teléfono de contacto',
  email varchar(200) DEFAULT NULL COMMENT 'Email del repartidor',
  license_number varchar(100) DEFAULT NULL COMMENT 'Número de licencia de conducir',
  license_expiry date DEFAULT NULL COMMENT 'Fecha de vencimiento de licencia',
  
  -- Vehículo
  vehicle_type enum('motorcycle','car','bicycle','walking','other') NOT NULL COMMENT 'Tipo de vehículo',
  vehicle_make varchar(100) DEFAULT NULL COMMENT 'Marca del vehículo',
  vehicle_model varchar(100) DEFAULT NULL COMMENT 'Modelo del vehículo',
  vehicle_year int DEFAULT NULL COMMENT 'Año del vehículo',
  vehicle_plate varchar(20) DEFAULT NULL COMMENT 'Patente del vehículo',
  vehicle_color varchar(50) DEFAULT NULL COMMENT 'Color del vehículo',
  
  -- Capacidad
  max_weight_capacity decimal(8,2) DEFAULT NULL COMMENT 'Capacidad máxima de peso en kg',
  max_volume_capacity decimal(8,2) DEFAULT NULL COMMENT 'Capacidad máxima de volumen en litros',
  max_distance_per_day decimal(8,2) DEFAULT NULL COMMENT 'Distancia máxima diaria en km',
  
  -- Estado y configuración
  active tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado activo/inactivo',
  status enum('available','busy','offline','break','maintenance') DEFAULT 'available' COMMENT 'Estado actual',
  current_latitude decimal(10, 8) DEFAULT NULL COMMENT 'Latitud actual',
  current_longitude decimal(11, 8) DEFAULT NULL COMMENT 'Longitud actual',
  last_location_update timestamp NULL DEFAULT NULL COMMENT 'Última actualización de ubicación',
  
  -- Configuración de trabajo
  working_hours_start time DEFAULT NULL COMMENT 'Hora de inicio de trabajo',
  working_hours_end time DEFAULT NULL COMMENT 'Hora de fin de trabajo',
  working_days varchar(50) DEFAULT '1,2,3,4,5' COMMENT 'Días de trabajo',
  max_deliveries_per_day int DEFAULT NULL COMMENT 'Máximo de entregas por día',
  delivery_radius_km decimal(8,2) DEFAULT NULL COMMENT 'Radio de entrega máximo en km',
  
  -- Estadísticas
  total_deliveries int DEFAULT '0' COMMENT 'Total de entregas realizadas',
  successful_deliveries int DEFAULT '0' COMMENT 'Entregas exitosas',
  failed_deliveries int DEFAULT '0' COMMENT 'Entregas fallidas',
  average_delivery_time int DEFAULT NULL COMMENT 'Tiempo promedio de entrega en minutos',
  customer_rating decimal(3,2) DEFAULT NULL COMMENT 'Calificación promedio (1.0-5.0)',
  total_earnings decimal(10,2) DEFAULT '0.00' COMMENT 'Ganancias totales',
  
  -- Configuración adicional
  can_handle_fragile tinyint(1) DEFAULT '0' COMMENT 'Puede manejar paquetes frágiles',
  can_handle_cod tinyint(1) DEFAULT '0' COMMENT 'Puede manejar pagos contra entrega',
  preferred_zones json DEFAULT NULL COMMENT 'Zonas preferidas de entrega',
  excluded_zones json DEFAULT NULL COMMENT 'Zonas excluidas',
  
  -- Notas y información adicional
  notes text COMMENT 'Notas sobre el repartidor',
  emergency_contact varchar(200) DEFAULT NULL COMMENT 'Contacto de emergencia',
  emergency_phone varchar(50) DEFAULT NULL COMMENT 'Teléfono de emergencia',
  
  -- Sistema
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  KEY idx_store_active (store_id,active),
  KEY idx_status (status),
  KEY idx_phone (phone),
  KEY idx_vehicle_type (vehicle_type),
  KEY idx_location (current_latitude,current_longitude),
  KEY idx_working_hours (working_hours_start,working_hours_end),
  
  CONSTRAINT delivery_drivers_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Repartidores asociados a las tiendas';

-- Tabla principal de entregas
CREATE TABLE deliveries (
  id int NOT NULL AUTO_INCREMENT,
  store_id int NOT NULL COMMENT 'ID de la tienda',
  order_id int DEFAULT NULL COMMENT 'ID de la orden asociada',
  order_number varchar(50) DEFAULT NULL COMMENT 'Número de orden',
  delivery_method_id int DEFAULT NULL COMMENT 'ID del método de entrega',
  assigned_driver_id int DEFAULT NULL COMMENT 'ID del repartidor asignado',
  
  -- Información del cliente y dirección
  customer_name varchar(200) NOT NULL COMMENT 'Nombre completo del cliente',
  customer_phone varchar(50) NOT NULL COMMENT 'Teléfono del cliente',
  customer_email varchar(200) DEFAULT NULL COMMENT 'Email del cliente',
  delivery_address text NOT NULL COMMENT 'Dirección de entrega completa',
  delivery_city varchar(100) NOT NULL COMMENT 'Ciudad de entrega',
  delivery_zip_code varchar(20) DEFAULT NULL COMMENT 'Código postal',
  delivery_instructions text DEFAULT NULL COMMENT 'Instrucciones especiales de entrega',
  
  -- Información del pedido
  order_total decimal(10,2) DEFAULT NULL COMMENT 'Total de la orden',
  delivery_cost decimal(10,2) DEFAULT '0.00' COMMENT 'Costo de entrega',
  items_count int DEFAULT NULL COMMENT 'Cantidad de productos',
  total_weight decimal(10,2) DEFAULT NULL COMMENT 'Peso total en kg',
  
  -- Programación y tiempo
  scheduled_date date DEFAULT NULL COMMENT 'Fecha programada de entrega',
  scheduled_time_slot varchar(50) DEFAULT NULL COMMENT 'Franja horaria programada (ej: 09:00-12:00)',
  estimated_delivery_time timestamp NULL DEFAULT NULL COMMENT 'Tiempo estimado de entrega',
  actual_delivery_time timestamp NULL DEFAULT NULL COMMENT 'Tiempo real de entrega',
  delivery_duration_minutes int DEFAULT NULL COMMENT 'Duración de la entrega en minutos',
  
  -- Estado y seguimiento
  status enum('pendiente','programada','en_transito','entregada','fallida','cancelada') NOT NULL DEFAULT 'pendiente' COMMENT 'Estado de la entrega',
  priority enum('baja','normal','alta','urgente') DEFAULT 'normal' COMMENT 'Prioridad de la entrega',
  is_fragile tinyint(1) DEFAULT '0' COMMENT 'Indica si el paquete es frágil',
  requires_signature tinyint(1) DEFAULT '0' COMMENT 'Requiere firma de recepción',
  
  -- Ubicación GPS (opcional)
  delivery_latitude decimal(10, 8) DEFAULT NULL COMMENT 'Latitud del destino',
  delivery_longitude decimal(11, 8) DEFAULT NULL COMMENT 'Longitud del destino',
  driver_current_latitude decimal(10, 8) DEFAULT NULL COMMENT 'Latitud actual del repartidor',
  driver_current_longitude decimal(11, 8) DEFAULT NULL COMMENT 'Longitud actual del repartidor',
  last_location_update timestamp NULL DEFAULT NULL COMMENT 'Última actualización de ubicación',
  
  -- Información adicional
  tracking_number varchar(100) DEFAULT NULL COMMENT 'Número de seguimiento',
  notes text COMMENT 'Notas internas de la entrega',
  delivery_proof_url varchar(500) DEFAULT NULL COMMENT 'URL de la foto de entrega',
  recipient_signature_url varchar(500) DEFAULT NULL COMMENT 'URL de la firma del receptor',
  failure_reason text COMMENT 'Razón del fallo de entrega',
  return_address text COMMENT 'Dirección de devolución en caso de fallo',
  
  -- Sistema
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  KEY idx_store_status (store_id,status),
  KEY idx_order_id (order_id),
  KEY idx_scheduled_date (scheduled_date),
  KEY idx_delivery_city (delivery_city),
  KEY idx_assigned_driver (assigned_driver_id),
  KEY idx_delivery_method (delivery_method_id),
  KEY idx_tracking_number (tracking_number),
  KEY idx_customer_phone (customer_phone),
  KEY idx_status_date (status,scheduled_date),
  
  CONSTRAINT deliveries_ibfk_1 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
  CONSTRAINT deliveries_ibfk_2 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL,
  CONSTRAINT deliveries_ibfk_3 FOREIGN KEY (delivery_method_id) REFERENCES delivery_methods (id) ON DELETE SET NULL,
  CONSTRAINT deliveries_ibfk_4 FOREIGN KEY (assigned_driver_id) REFERENCES delivery_drivers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tabla principal de entregas';

-- Historial de actividades de entregas
CREATE TABLE delivery_activity_log (
  id int NOT NULL AUTO_INCREMENT,
  delivery_id int NOT NULL COMMENT 'ID de la entrega',
  action varchar(100) NOT NULL COMMENT 'Acción realizada (creado, actualizado, asignado, etc.)',
  description text NOT NULL COMMENT 'Descripción detallada de la actividad',
  user_id int DEFAULT NULL COMMENT 'ID del usuario que realizó la acción',
  user_type enum('admin','store_admin','customer','driver','system') DEFAULT NULL COMMENT 'Tipo de usuario',
  
  -- Información del cambio
  old_values json DEFAULT NULL COMMENT 'Valores anteriores (JSON)',
  new_values json DEFAULT NULL COMMENT 'Valores nuevos (JSON)',
  changed_fields json DEFAULT NULL COMMENT 'Campos que cambiaron (JSON)',
  
  -- Contexto adicional
  ip_address varchar(45) DEFAULT NULL COMMENT 'Dirección IP del usuario',
  user_agent text DEFAULT NULL COMMENT 'User agent del navegador',
  session_id varchar(100) DEFAULT NULL COMMENT 'ID de sesión',
  request_id varchar(100) DEFAULT NULL COMMENT 'ID de la petición',
  
  -- Geolocalización (opcional)
  latitude decimal(10, 8) DEFAULT NULL COMMENT 'Latitud cuando ocurrió la actividad',
  longitude decimal(11, 8) DEFAULT NULL COMMENT 'Longitud cuando ocurrió la actividad',
  
  -- Metadata
  duration_seconds int DEFAULT NULL COMMENT 'Duración de la acción en segundos',
  priority enum('low','normal','high') DEFAULT 'normal' COMMENT 'Prioridad del log',
  
  -- Sistema
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (id),
  KEY idx_delivery_id (delivery_id),
  KEY idx_action (action),
  KEY idx_user_id (user_id),
  KEY idx_created_at (created_at),
  KEY idx_delivery_action (delivery_id,action),
  KEY idx_user_action (user_id,action),
  KEY idx_date_action (created_at,action),
  
  CONSTRAINT delivery_activity_log_ibfk_1 FOREIGN KEY (delivery_id) REFERENCES deliveries (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Historial detallado de actividades de entregas';

-- ===============================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ===============================================================

-- Índice compuesto para consultas frecuentes de entregas por estado y fecha
CREATE INDEX idx_deliveries_status_date_store ON deliveries (status, scheduled_date, store_id);

-- Índice para búsquedas de entregas por cliente
CREATE INDEX idx_deliveries_customer_search ON deliveries (customer_name, customer_phone, delivery_city);

-- Índice para consultas de repartidores disponibles
CREATE INDEX idx_drivers_availability ON delivery_drivers (store_id, active, status, current_latitude, current_longitude);

-- Índice para logs de actividad por fecha
CREATE INDEX idx_activity_logs_date_range ON delivery_activity_log (delivery_id, created_at, action);

-- ===============================================================
-- TRIGGERS PARA MANTENIMIENTO AUTOMÁTICO
-- ===============================================================

-- Trigger para actualizar estadísticas de repartidores
DELIMITER //
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
END//
DELIMITER ;

-- Trigger para log automático al crear entrega
DELIMITER //
CREATE TRIGGER log_delivery_creation
AFTER INSERT ON deliveries
FOR EACH ROW
BEGIN
    INSERT INTO delivery_activity_log (delivery_id, action, description, user_type)
    VALUES (NEW.id, 'entrega_creada', CONCAT('Entrega creada - Cliente: ', NEW.customer_name, ', Dirección: ', NEW.delivery_address), 'system');
END//
DELIMITER ;

-- ===============================================================
-- VISTAS ÚTILES PARA CONSULTAS FRECUENTES
-- ===============================================================

-- Vista de entregas con información completa
CREATE VIEW view_deliveries_complete AS
SELECT 
    d.*,
    dm.name as method_name,
    dm.type as method_type,
    dm.base_cost as method_base_cost,
    dd.name as driver_name,
    dd.phone as driver_phone,
    dd.vehicle_type as driver_vehicle_type,
    dd.vehicle_plate as driver_vehicle_plate,
    o.id as original_order_number,
    o.total as original_order_total,
    
    -- Calculados
    CASE 
        WHEN d.status = 'pendiente' THEN DATEDIFF(d.scheduled_date, CURDATE())
        ELSE NULL
    END as days_until_delivery,
    
    CASE 
        WHEN d.scheduled_date < CURDATE() AND d.status NOT IN ('entregada', 'cancelada') THEN 1
        ELSE 0
    END as is_overdue,
    
    CASE 
        WHEN d.actual_delivery_time IS NOT NULL AND d.estimated_delivery_time IS NOT NULL 
        THEN TIMESTAMPDIFF(MINUTE, d.estimated_delivery_time, d.actual_delivery_time)
        ELSE NULL
    END as delivery_delay_minutes
    
FROM deliveries d
LEFT JOIN delivery_methods dm ON d.delivery_method_id = dm.id
LEFT JOIN delivery_drivers dd ON d.assigned_driver_id = dd.id
LEFT JOIN orders o ON d.order_id = o.id;

-- Vista de estadísticas de repartidores
CREATE VIEW view_driver_performance AS
SELECT 
    dd.*,
    
    -- Cálculos de rendimiento
    CASE 
        WHEN dd.total_deliveries > 0 
        THEN ROUND((dd.successful_deliveries / dd.total_deliveries) * 100, 2)
        ELSE 0
    END as success_rate_percent,
    
    CASE 
        WHEN dd.total_deliveries > 0 
        THEN ROUND(dd.failed_deliveries / dd.total_deliveries, 2)
        ELSE 0
    END as failure_rate_percent,
    
    CASE 
        WHEN dd.successful_deliveries > 0 AND dd.average_delivery_time IS NOT NULL
        THEN ROUND(dd.average_delivery_time, 2)
        ELSE NULL
    END as avg_delivery_time_minutes,
    
    -- Estados de disponibilidad
    CASE 
        WHEN dd.status = 'available' AND dd.working_hours_start <= CURTIME() 
             AND dd.working_hours_end >= CURTIME() 
             AND FIND_IN_SET(DAYOFWEEK(CURDATE()), dd.working_days) > 0
        THEN 'currently_available'
        ELSE 'not_available'
    END as real_time_availability
    
FROM delivery_drivers dd;

-- ===============================================================
-- PROCEDIMIENTOS ALMACENADOS ÚTILES
-- ===============================================================

DELIMITER //

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
END//

-- Procedimiento para asignar el mejor repartidor disponible
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
END//

DELIMITER ;

-- ===============================================================
-- CONFIGURACIÓN INICIAL DE DATOS
-- ===============================================================

-- Insertar métodos de entrega por defecto para nuevas tiendas
INSERT INTO delivery_methods (
    store_id, name, description, type, base_cost, delivery_time_days, active
) VALUES 
(1, 'Entrega Estándar', 'Entrega regular en 1-3 días hábiles', 'standard', 2500.00, 2, 1),
(1, 'Entrega Express', 'Entrega en 24 horas', 'express', 4500.00, 1, 1),
(1, 'Mismo Día', 'Entrega el mismo día (sujeto a horarios)', 'same_day', 6500.00, 0, 1),
(1, 'Programada', 'Entrega en fecha específica seleccionada', 'scheduled', 3500.00, 1, 1);

-- Insertar repartidor de ejemplo
INSERT INTO delivery_drivers (
    store_id, name, phone, email, vehicle_type, 
    max_weight_capacity, active, status
) VALUES 
(1, 'Juan Pérez', '+56912345678', 'juan.perez@email.com', 'motorcycle', 50.00, 1, 'available');

-- ===============================================================
-- FIN DEL SCRIPT
-- ===============================================================

-- Verificación de integridad
SELECT 'Tablas de entregas creadas exitosamente' as status;

SHOW TABLES LIKE 'delivery%';

SELECT 
    'deliveries' as tabla, 
    COUNT(*) as registros 
FROM deliveries
UNION ALL
SELECT 
    'delivery_methods' as tabla, 
    COUNT(*) as registros 
FROM delivery_methods
UNION ALL
SELECT 
    'delivery_drivers' as tabla, 
    COUNT(*) as registros 
FROM delivery_drivers
UNION ALL
SELECT 
    'delivery_activity_log' as tabla, 
    COUNT(*) as registros 
FROM delivery_activity_log;