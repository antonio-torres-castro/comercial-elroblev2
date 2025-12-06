DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE add_orders_columns()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN END;

    SET @sql = 'ALTER TABLE orders 
                ADD COLUMN delivery_address TEXT 
                AFTER total';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE orders 
                ADD COLUMN delivery_city VARCHAR(100) 
                AFTER delivery_address';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE orders 
                ADD COLUMN delivery_contact_name VARCHAR(200) 
                AFTER delivery_city';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE orders 
                ADD COLUMN delivery_contact_phone VARCHAR(50) 
                AFTER delivery_contact_name';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE orders 
                ADD COLUMN delivery_contact_email VARCHAR(200) 
                AFTER delivery_contact_phone';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE orders 
                ADD COLUMN pickup_location_id INT 
                AFTER delivery_contact_email';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE orders 
                ADD COLUMN delivery_date DATE 
                AFTER pickup_location_id';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE orders 
                ADD COLUMN delivery_time_slot TIME 
                AFTER delivery_date';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE add_products_columns()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
    -- Puedes dejar esto vacío o agregar algún manejo de error
    END;

    -- Agregar columnas una por una verificando existencia
    SET @sql = 'ALTER TABLE products 
                ADD COLUMN stock_quantity INT DEFAULT 0 
                AFTER price';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE products 
                ADD COLUMN stock_min_threshold INT DEFAULT 5 
                AFTER stock_quantity';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE products 
                ADD COLUMN delivery_days_min INT DEFAULT 1 
                AFTER stock_min_threshold';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE products 
                ADD COLUMN delivery_days_max INT DEFAULT 3 
                AFTER delivery_days_min';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE products 
                ADD COLUMN service_type ENUM(\'producto\', \'servicio\', \'ambos\') DEFAULT \'producto\' 
                AFTER delivery_days_max';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE products 
                ADD COLUMN requires_appointment BOOLEAN DEFAULT FALSE 
                AFTER service_type';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE products 
                ADD COLUMN image_url VARCHAR(500) DEFAULT NULL 
                AFTER requires_appointment';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'ALTER TABLE products 
                ADD COLUMN active BOOLEAN DEFAULT TRUE 
                AFTER image_url';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE add_remaining_indexes()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN END;

    -- Índices específicos después de que las tablas estén creadas
    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_products_active_store ON products(active, store_id)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_orders_delivery_date ON orders(delivery_date)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_appointments_store_date ON product_appointments(store_id, appointment_date)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_delivery_groups_status ON delivery_groups(status, delivery_date)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE AssignBestDriver(IN p_delivery_id INT)
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
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE check_appointment_availability(
    IN p_store_id INT,
    IN p_appointment_date DATETIME,
    IN p_duration_hours DECIMAL(4,2),
    IN p_exclude_appointment_id INT
)
BEGIN
    DECLARE v_conflict_count INT DEFAULT 0;
    
    -- Verificar si está permitido doble reserva
    SELECT allow_double_booking INTO @double_booking
    FROM store_appointment_policies 
    WHERE store_id = p_store_id;
    
    -- Si no está permitido doble reserva, verificar conflictos
    IF COALESCE(@double_booking, 0) = 0 THEN
        SELECT COUNT(*) INTO v_conflict_count
        FROM store_appointments
        WHERE store_id = p_store_id
          AND id != COALESCE(p_exclude_appointment_id, 0)
          AND status != 'cancelada'
          AND (
              (appointment_date <= p_appointment_date AND 
               DATE_ADD(appointment_date, INTERVAL duration_hours HOUR) > p_appointment_date)
              OR
              (appointment_date < DATE_ADD(p_appointment_date, INTERVAL p_duration_hours HOUR) AND 
               DATE_ADD(appointment_date, INTERVAL duration_hours HOUR) >= DATE_ADD(p_appointment_date, INTERVAL p_duration_hours HOUR))
          );
    END IF;
    
    -- Retornar resultado
    SELECT 
        CASE 
            WHEN COALESCE(@double_booking, 0) = 1 THEN TRUE
            WHEN v_conflict_count = 0 THEN TRUE
            ELSE FALSE
        END AS is_available,
        v_conflict_count as conflict_count;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE check_product_availability(
    IN p_product_id INT,
    IN p_quantity INT,
    IN p_date DATE
)
BEGIN
    DECLARE available_cap INT DEFAULT 0;
    DECLARE current_stock INT DEFAULT 0;
    DECLARE total_required INT DEFAULT 0;
    
    -- Verificar stock
    SELECT COALESCE(stock_quantity, 0) INTO current_stock
    FROM products
    WHERE id = p_product_id;
    
    -- Verificar capacidad disponible para la fecha
    SELECT COALESCE(available_capacity - booked_capacity, 0) INTO available_cap
    FROM product_daily_capacity
    WHERE product_id = p_product_id AND capacity_date = p_date;
    
    -- Calcular total requerido (stock + capacidad)
    SET total_required = LEAST(current_stock, available_cap);
    
    -- Retornar resultado
    SELECT 
        p_product_id as product_id,
        current_stock as current_stock,
        available_cap as available_capacity,
        total_required as total_available,
        CASE WHEN total_required >= p_quantity THEN 'available' ELSE 'unavailable' END as availability_status;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE create_safe_indexes()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN END;

    -- Índices para products
    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_products_active_store ON products(active, store_id)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_products_stock_low ON products(stock_quantity, stock_min_threshold)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Índices para orders
    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_orders_delivery_date ON orders(delivery_date)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Índices para product_appointments (se crea después)
    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_appointments_store_date ON product_appointments(store_id, appointment_date)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Índices para delivery_groups (se crea después)
    SET @sql = 'CREATE INDEX IF NOT EXISTS idx_delivery_groups_status ON delivery_groups(status, delivery_date)';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE generate_daily_capacities()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE p_id INT;
    DECLARE p_store_id INT;
    DECLARE cur CURSOR FOR 
        SELECT p.id, p.store_id 
        FROM products p 
        WHERE p.active = 1;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO p_id, p_store_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Insertar capacidades para los próximos 30 días si no existen
        INSERT IGNORE INTO product_daily_capacity (product_id, store_id, capacity_date, available_capacity)
        SELECT p_id, p_store_id, DATE_ADD(CURDATE(), INTERVAL n DAY), 20
        FROM (
            SELECT 0 as n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
            SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL
            SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL
            SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL
            SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL
            SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
        ) days
        WHERE NOT EXISTS (
            SELECT 1 FROM product_daily_capacity pdc 
            WHERE pdc.product_id = p_id AND pdc.capacity_date = DATE_ADD(CURDATE(), INTERVAL n DAY)
        );
    END LOOP;
    
    CLOSE cur;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE get_appointment_statistics(
    IN p_store_id INT,
    IN p_date_from DATE,
    IN p_date_to DATE
)
BEGIN
    SELECT 
        'general' as stat_type,
        COUNT(*) as total_appointments,
        COUNT(CASE WHEN status = 'completada' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as cancelled,
        COUNT(CASE WHEN status = 'no_asistio' THEN 1 END) as no_shows,
        COUNT(CASE WHEN status IN ('programada', 'confirmada', 'en_proceso') THEN 1 END) as pending,
        AVG(duration_hours) as avg_duration
    FROM store_appointments
    WHERE store_id = p_store_id
      AND DATE(appointment_date) BETWEEN p_date_from AND p_date_to
    
    UNION ALL
    
    SELECT 
        'by_service' as stat_type,
        s.id,
        COUNT(a.id) as total_appointments,
        COUNT(CASE WHEN a.status = 'completada' THEN 1 END) as completed,
        COUNT(CASE WHEN a.status = 'cancelada' THEN 1 END) as cancelled,
        0 as no_shows,
        0 as pending,
        AVG(a.duration_hours) as avg_duration
    FROM store_services s
    LEFT JOIN store_appointments a ON s.id = a.service_id
        AND DATE(a.appointment_date) BETWEEN p_date_from AND p_date_to
    WHERE s.store_id = p_store_id
    GROUP BY s.id, s.name;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost PROCEDURE GetPendingDeliveries(IN p_store_id INT)
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
DELIMITER ;
