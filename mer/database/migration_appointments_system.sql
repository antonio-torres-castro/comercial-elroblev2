-- =====================================================
-- MIGRACIÓN DEL SISTEMA DE GESTIÓN DE CITAS Y RESERVAS
-- Fecha: 2025-11-30
-- Descripción: Base de datos completa para gestión de citas
-- Funcionalidades:
-- - Duración en días (mínimo 0.5 días)
-- - Servicios recurrentes 
-- - Múltiples citas simultáneas permitidas
-- - Generación de calendario automático
-- - Políticas de cancelación configurables
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS store_services;
DROP TABLE IF EXISTS store_appointments;
DROP TABLE IF EXISTS store_schedule_config;
DROP TABLE IF EXISTS store_appointment_policies;
DROP TABLE IF EXISTS store_appointment_settings;
DROP TABLE IF EXISTS store_holidays;
DROP TABLE IF EXISTS appointment_reminders;
DROP TABLE IF EXISTS appointment_time_slots;
DROP TABLE IF EXISTS appointment_status_history;

-- Tabla principal de servicios de citas
CREATE TABLE IF NOT EXISTS `store_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Nombre del servicio',
  `description` text NOT NULL COMMENT 'Descripción detallada del servicio',
  `default_duration_hours` decimal(4,2) NOT NULL DEFAULT 1.00 COMMENT 'Duración por defecto en horas',
  `price` decimal(10,2) DEFAULT NULL COMMENT 'Precio del servicio',
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si es un servicio recurrente',
  `cancellation_hours_before` int(11) DEFAULT 24 COMMENT 'Horas mínimas para cancelar sin penalización',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Estado del servicio',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_name` (`name`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Servicios disponibles para citas';

-- Tabla principal de citas y reservas
CREATE TABLE IF NOT EXISTS `store_appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL COMMENT 'Nombre completo del cliente',
  `customer_phone` varchar(20) NOT NULL COMMENT 'Teléfono del cliente',
  `customer_email` varchar(255) DEFAULT NULL COMMENT 'Email del cliente',
  `service_id` int(11) NOT NULL COMMENT 'ID del servicio',
  `appointment_date` datetime NOT NULL COMMENT 'Fecha y hora de la cita',
  `duration_hours` decimal(4,2) NOT NULL DEFAULT 1.00 COMMENT 'Duración en horas (mínimo 0.5)',
  `status` enum('programada','confirmada','en_proceso','completada','cancelada','no_asistio') NOT NULL DEFAULT 'programada',
  `status_reason` text DEFAULT NULL COMMENT 'Razón del cambio de estado',
  `notes` text DEFAULT NULL COMMENT 'Notas adicionales',
  `created_by` int(11) DEFAULT NULL COMMENT 'ID del usuario que creó la cita',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_appointment` (`store_id`, `appointment_date`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_status` (`status`),
  KEY `idx_appointment_date` (`appointment_date`),
  KEY `idx_customer_phone` (`customer_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Citas y reservas programadas';

-- Tabla de configuración de horarios de la tienda
CREATE TABLE IF NOT EXISTS `store_schedule_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `start_time` time NOT NULL DEFAULT '09:00:00' COMMENT 'Hora de inicio de atención',
  `end_time` time NOT NULL DEFAULT '18:00:00' COMMENT 'Hora de fin de atención',
  `appointment_interval` int(11) NOT NULL DEFAULT 30 COMMENT 'Intervalo entre citas en minutos',
  `working_days` varchar(20) NOT NULL DEFAULT '1,2,3,4,5,6' COMMENT 'Días laborales (1=Lunes, 7=Domingo)',
  `timezone` varchar(50) NOT NULL DEFAULT 'America/Santiago' COMMENT 'Zona horaria',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_store_schedule` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración de horarios por tienda';

-- Tabla de políticas de cancelación y gestión
CREATE TABLE IF NOT EXISTS `store_appointment_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `hours_before_cancellation` int(11) NOT NULL DEFAULT 24 COMMENT 'Horas mínimas antes de la cita para cancelar',
  `require_cancellation_reason` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indica si se requiere razón para cancelar',
  `auto_confirm_appointments` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Confirmar citas automáticamente',
  `max_daily_appointments` int(11) NOT NULL DEFAULT 20 COMMENT 'Máximo número de citas por día',
  `allow_double_booking` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Permitir múltiples citas simultáneas',
  `penalty_amount` decimal(10,2) DEFAULT NULL COMMENT 'Monto de penalización por cancelación tardía',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_store_policies` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Políticas de gestión de citas por tienda';

-- Tabla de configuración general de citas
CREATE TABLE IF NOT EXISTS `store_appointment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `require_cancellation_reason` tinyint(1) NOT NULL DEFAULT 1,
  `send_confirmation_sms` tinyint(1) NOT NULL DEFAULT 1,
  `send_reminder_sms` tinyint(1) NOT NULL DEFAULT 1,
  `reminder_hours_before` int(11) NOT NULL DEFAULT 24,
  `enable_online_booking` tinyint(1) NOT NULL DEFAULT 0,
  `booking_advance_days` int(11) NOT NULL DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_store_appointment_settings` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración general de citas por tienda';

-- Tabla de feriados
CREATE TABLE IF NOT EXISTS `store_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `date` date NOT NULL COMMENT 'Fecha del feriado',
  `name` varchar(255) NOT NULL COMMENT 'Nombre del feriado',
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si es un feriado recurrente',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_store_holiday` (`store_id`, `date`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Feriados por tienda';

-- Tabla de recordatorios automáticos
CREATE TABLE IF NOT EXISTS `appointment_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `reminder_type` enum('confirmation','reminder','follow_up') NOT NULL DEFAULT 'reminder',
  `reminder_date` datetime NOT NULL COMMENT 'Fecha y hora del recordatorio',
  `message` text NOT NULL COMMENT 'Mensaje del recordatorio',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de envío',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_reminder_date` (`reminder_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recordatorios automáticos para citas';

-- Tabla de historial de cambios de estado
CREATE TABLE IF NOT EXISTS `appointment_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `reason` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de cambios de estado de citas';

-- Tabla de disponibilidad de horarios
CREATE TABLE IF NOT EXISTS `appointment_time_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_time_slot` (`store_id`, `date`, `start_time`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Disponibilidad específica de horarios';

-- =========================================
-- TRIGGERS PARA AUDITORÍA AUTOMÁTICA
-- =========================================

-- Trigger para log de inserción de citas
DELIMITER //
CREATE TRIGGER `log_appointment_insert` AFTER INSERT ON `store_appointments`
FOR EACH ROW
BEGIN
    INSERT INTO `appointment_status_history` (
        `appointment_id`, `old_status`, `new_status`, 
        `changed_by`, `changed_at`
    ) VALUES (
        NEW.id, NULL, NEW.status, 
        NEW.created_by, NEW.created_at
    );
END//

-- Trigger para log de actualización de citas
CREATE TRIGGER `log_appointment_update` AFTER UPDATE ON `store_appointments`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO `appointment_status_history` (
            `appointment_id`, `old_status`, `new_status`, 
            `changed_by`, `changed_at`
        ) VALUES (
            NEW.id, OLD.status, NEW.status, 
            NEW.created_by, NEW.updated_at
        );
    END IF;
END//
DELIMITER ;

-- =========================================
-- VISTAS PARA ESTADÍSTICAS Y REPORTES
-- =========================================

-- Vista de estadísticas de citas por día
CREATE OR REPLACE VIEW `v_appointment_daily_stats` AS
SELECT 
    a.store_id,
    DATE(a.appointment_date) as appointment_date,
    COUNT(*) as total_appointments,
    COUNT(CASE WHEN a.status = 'completada' THEN 1 END) as completed_count,
    COUNT(CASE WHEN a.status = 'cancelada' THEN 1 END) as cancelled_count,
    COUNT(CASE WHEN a.status = 'no_asistio' THEN 1 END) as no_show_count,
    AVG(a.duration_hours) as avg_duration_hours,
    COUNT(CASE WHEN s.is_recurring = 1 THEN 1 END) as recurring_appointments
FROM store_appointments a
LEFT JOIN store_services s ON a.service_id = s.id
GROUP BY a.store_id, DATE(a.appointment_date);

-- Vista de utilización de horarios
CREATE OR REPLACE VIEW `v_schedule_utilization` AS
SELECT 
    sc.store_id,
    DATE(ss.date) as schedule_date,
    ss.start_time,
    ss.end_time,
    COUNT(a.id) as booked_slots,
    COUNT(CASE WHEN a.status = 'completada' THEN 1 END) as completed_slots,
    ROUND(
        COUNT(CASE WHEN a.status = 'completada' THEN 1 END) * 100.0 / 
        GREATEST(COUNT(*), 1), 2
    ) as utilization_percentage
FROM store_schedule_config sc
LEFT JOIN appointment_time_slots ss ON sc.store_id = ss.store_id 
    AND DATE(ss.date) >= CURDATE()
LEFT JOIN store_appointments a ON sc.store_id = a.store_id 
    AND DATE(a.appointment_date) = DATE(ss.date)
    AND TIME(a.appointment_date) BETWEEN ss.start_time AND ss.end_time
GROUP BY sc.store_id, DATE(ss.date), ss.start_time, ss.end_time;

-- Vista de servicios más populares
CREATE OR REPLACE VIEW `v_popular_services` AS
SELECT 
    s.store_id,
    s.id as service_id,
    s.name as service_name,
    COUNT(a.id) as total_appointments,
    COUNT(CASE WHEN a.status = 'completada' THEN 1 END) as completed_appointments,
    ROUND(
        COUNT(CASE WHEN a.status = 'completada' THEN 1 END) * 100.0 / 
        GREATEST(COUNT(*), 1), 2
    ) as completion_rate,
    AVG(a.duration_hours) as avg_actual_duration,
    s.default_duration_hours as scheduled_duration
FROM store_services s
LEFT JOIN store_appointments a ON s.id = a.service_id
WHERE s.is_active = 1
GROUP BY s.id, s.name
ORDER BY total_appointments DESC;

-- =========================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =========================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX `idx_appointments_store_status_date` ON `store_appointments` (`store_id`, `status`, `appointment_date`);
CREATE INDEX `idx_appointments_customer_date` ON `store_appointments` (`customer_phone`, `appointment_date`);
CREATE INDEX `idx_services_store_active` ON `store_services` (`store_id`, `is_active`);

-- Índice para búsqueda de disponibilidad
CREATE INDEX `idx_time_slots_store_date_available` ON `appointment_time_slots` (`store_id`, `date`, `is_available`);

-- =========================================
-- DATOS DE CONFIGURACIÓN POR DEFECTO
-- =========================================

-- Servicios de ejemplo
INSERT INTO `store_services` (`store_id`, `name`, `description`, `default_duration_hours`, `price`, `is_recurring`) VALUES
(1, 'Limpieza Doméstica Completa', 'Servicio integral de limpieza para hogares - Incluye dusting, aspirado, lavado de pisos y limpieza de baños', 4.0, 25000.00, 1),
(1, 'Limpieza de Ventanas', 'Limpieza profesional de ventanas interiores y exteriores', 2.0, 15000.00, 0),
(1, 'Mantenimiento de Jardines', 'Poda, riego, fertilización y mantenimiento general de jardines', 3.0, 20000.00, 1),
(1, 'Limpieza Post-Construcción', 'Limpieza especializada después de remodelaciones o construcciones', 6.0, 35000.00, 0),
(1, 'Desinfección y Sanitización', 'Servicio de desinfección profunda con productos certificados', 2.5, 18000.00, 1);

-- Configuración de horarios por defecto para tiendas existentes
INSERT INTO `store_schedule_config` (`store_id`, `start_time`, `end_time`, `appointment_interval`, `working_days`)
SELECT id, '09:00:00', '18:00:00', 30, '1,2,3,4,5,6' 
FROM stores 
WHERE id NOT IN (SELECT store_id FROM store_schedule_config);

-- Configuración de políticas por defecto
INSERT INTO `store_appointment_policies` (`store_id`, `hours_before_cancellation`, `require_cancellation_reason`, `auto_confirm_appointments`, `max_daily_appointments`, `allow_double_booking`)
SELECT id, 24, 1, 1, 20, 0 
FROM stores 
WHERE id NOT IN (SELECT store_id FROM store_appointment_policies);

-- Configuración general por defecto
INSERT INTO `store_appointment_settings` (`store_id`, `require_cancellation_reason`, `send_confirmation_sms`, `send_reminder_sms`, `reminder_hours_before`, `enable_online_booking`, `booking_advance_days`)
SELECT id, 1, 1, 1, 24, 0, 30 
FROM stores 
WHERE id NOT IN (SELECT store_id FROM store_appointment_settings);

-- Feriados chilenos comunes
INSERT INTO `store_holidays` (`store_id`, `date`, `name`, `is_recurring`) VALUES
(1, '2025-01-01', 'Año Nuevo', 1),
(1, '2025-04-18', 'Viernes Santo', 1),
(1, '2025-05-01', 'Día del Trabajador', 1),
(1, '2025-05-21', 'Día de las Glorias Navales', 1),
(1, '2025-06-26', 'San Pedro y San Pablo', 1),
(1, '2025-07-16', 'Día de la Virgen del Carmen', 1),
(1, '2025-08-15', 'Asunción de la Virgen', 1),
(1, '2025-09-18', 'Independencia Nacional', 1),
(1, '2025-09-19', 'Día de las Glorias del Ejército', 1),
(1, '2025-09-20', 'Fiestas Patrias', 1),
(1, '2025-10-12', 'Día del Descubrimiento de América', 1),
(1, '2025-10-31', 'Día de las Iglesias Evangélicas', 1),
(1, '2025-11-01', 'Día de Todos los Santos', 1),
(1, '2025-12-08', 'Inmaculada Concepción', 1),
(1, '2025-12-25', 'Navidad', 1);

-- =========================================
-- PROCEDIMIENTOS ALMACENADOS
-- =========================================

DELIMITER //

-- Procedimiento para verificar disponibilidad
CREATE PROCEDURE `check_appointment_availability`(
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
END//

-- Procedimiento para generar estadísticas
CREATE PROCEDURE `get_appointment_statistics`(
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
END//

DELIMITER ;

-- =========================================
-- VALIDACIONES Y RESTRICCIONES
-- =========================================

-- Constraint para duración mínima
ALTER TABLE `store_appointments` 
ADD CONSTRAINT `chk_duration_minimum` 
CHECK (`duration_hours` >= 0.5);

-- Constraint para fechas futuras
ALTER TABLE `store_appointments` 
ADD CONSTRAINT `chk_future_dates` 
CHECK (`appointment_date` > NOW());

-- Constraint para horarios válidos
ALTER TABLE `store_schedule_config` 
ADD CONSTRAINT `chk_valid_schedule` 
CHECK (`start_time` < `end_time`);

-- Constraint para intervalo mínimo
ALTER TABLE `store_schedule_config` 
ADD CONSTRAINT `chk_min_interval` 
CHECK (`appointment_interval` >= 15);

-- Constraint para máximo de citas diarias
ALTER TABLE `store_appointment_policies` 
ADD CONSTRAINT `chk_max_daily_appointments` 
CHECK (`max_daily_appointments` > 0 AND `max_daily_appointments` <= 100);

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================
-- INFORMACIÓN DE INSTALACIÓN
-- =========================================

SELECT 'Sistema de Citas instalado correctamente' as status,
       NOW() as installation_time,
       'Compatible con MySQL 8.0+' as compatibility;

-- Mostrar resumen de tablas creadas
SELECT 
    TABLE_NAME,
    TABLE_ROWS as estimated_rows,
    CREATE_TIME as created_at
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME LIKE 'store_%' 
  AND TABLE_NAME IN (
      'store_services', 'store_appointments', 'store_schedule_config',
      'store_appointment_policies', 'store_appointment_settings',
      'store_holidays', 'appointment_reminders', 'appointment_status_history',
      'appointment_time_slots'
  )
ORDER BY TABLE_NAME;