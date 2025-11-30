-- ==============================================
-- MIGRACIÓN DEL SISTEMA DE CONFIGURACIÓN DE TIENDA
-- Mall Virtual - Viña del Mar
-- Fecha: 2025-11-30
-- ==============================================
DROP TABLE IF EXISTS store_configurations;

-- Crear tabla de configuraciones de tienda
CREATE TABLE IF NOT EXISTS `store_configurations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `store_id` int(11) NOT NULL,
    `category` varchar(50) NOT NULL DEFAULT 'general',
    `config_key` varchar(100) NOT NULL,
    `config_value` text NOT NULL,
    `description` text NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_store_category_key` (`store_id`, `category`, `config_key`),
    KEY `idx_store_category` (`store_id`, `category`),
    KEY `idx_category_key` (`category`, `config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de logs de configuración (para auditoría)
CREATE TABLE IF NOT EXISTS `configuration_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `store_id` int(11) NOT NULL,
    `user_id` int(11) NULL,
    `action` varchar(50) NOT NULL,
    `category` varchar(50) NOT NULL,
    `config_key` varchar(100) NOT NULL,
    `old_value` text NULL,
    `new_value` text NULL,
    `ip_address` varchar(45) NULL,
    `user_agent` text NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_store_id` (`store_id`),
    KEY `idx_action_date` (`action`, `created_at`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones predeterminadas
INSERT INTO `store_configurations` (`store_id`, `category`, `config_key`, `config_value`, `description`) VALUES
-- Configuración de idioma por defecto
(1, 'language', 'default_language', 'es', 'Idioma predeterminado del sistema'),
(1, 'language', 'timezone', 'America/Santiago', 'Zona horaria predeterminada'),
(1, 'language', 'date_format', 'd/m/Y', 'Formato de fecha predeterminado'),
(1, 'language', 'currency', 'CLP', 'Moneda predeterminada'),
(1, 'language', 'decimal_separator', ',', 'Separador decimal para números'),
(1, 'language', 'thousands_separator', '.', 'Separador de miles para números'),

-- Configuración de permisos por defecto
(1, 'permissions', 'admin_enabled', 'true', 'Habilitar permisos de administrador'),
(1, 'permissions', 'manager_enabled', 'true', 'Habilitar permisos de gerente'),
(1, 'permissions', 'employee_enabled', 'true', 'Habilitar permisos de empleado'),

-- Configuración de Transbank por defecto (deshabilitado)
(1, 'payment_methods', 'transbank_enabled', 'false', 'Habilitar procesamiento de pagos Transbank'),
(1, 'payment_methods', 'transbank_commerce_code', '', 'Código de comercio Transbank'),
(1, 'payment_methods', 'transbank_api_key', '', 'API Key Transbank'),
(1, 'payment_methods', 'transbank_environment', 'Integration', 'Ambiente Transbank'),

-- Configuración de integraciones
(1, 'integrations', 'setap_enabled', 'false', 'Habilitar integración SETAP'),
(1, 'integrations', 'setap_api_endpoint', '', 'URL del endpoint API SETAP'),
(1, 'integrations', 'setap_api_key', '', 'API Key para integración SETAP'),

-- Configuración de notificaciones por defecto
(1, 'notifications', 'email_enabled', 'true', 'Habilitar notificaciones por email'),
(1, 'notifications', 'email_admin', '', 'Email del administrador'),
(1, 'notifications', 'email_sales', '', 'Email del departamento de ventas'),
(1, 'notifications', 'sms_enabled', 'false', 'Habilitar notificaciones por SMS'),
(1, 'notifications', 'order_confirmations', 'true', 'Enviar confirmaciones de pedidos'),
(1, 'notifications', 'delivery_updates', 'true', 'Enviar actualizaciones de entrega'),

-- Configuración general
(1, 'general', 'store_name', 'Tienda Principal', 'Nombre de la tienda'),
(1, 'general', 'store_description', 'Tienda principal del mall virtual', 'Descripción de la tienda'),
(1, 'general', 'default_shipping_cost', '0', 'Costo de envío predeterminado'),
(1, 'general', 'min_order_amount', '0', 'Monto mínimo para pedidos'),
(1, 'general', 'max_order_amount', '9999999', 'Monto máximo para pedidos')

ON DUPLICATE KEY UPDATE
    `updated_at` = CURRENT_TIMESTAMP;

-- Crear trigger para logging automático de configuraciones
DROP TRIGGER IF EXISTS log_config_changes_insert;
DROP TRIGGER IF EXISTS log_config_changes_update;

DELIMITER //

CREATE TRIGGER `log_config_changes_insert`
AFTER INSERT ON `store_configurations`
FOR EACH ROW
BEGIN
    INSERT INTO `configuration_logs` (
        `store_id`, 
        `action`, 
        `category`, 
        `config_key`, 
        `new_value`,
        `created_at`
    ) VALUES (
        NEW.store_id,
        'INSERT',
        NEW.category,
        NEW.config_key,
        NEW.config_value,
        NOW()
    );
END//

CREATE TRIGGER `log_config_changes_update`
AFTER UPDATE ON `store_configurations`
FOR EACH ROW
BEGIN
    INSERT INTO `configuration_logs` (
        `store_id`, 
        `action`, 
        `category`, 
        `config_key`, 
        `old_value`,
        `new_value`,
        `created_at`
    ) VALUES (
        NEW.store_id,
        'UPDATE',
        NEW.category,
        NEW.config_key,
        OLD.config_value,
        NEW.config_value,
        NOW()
    );
END//

DELIMITER ;

-- Crear índices adicionales para optimizar consultas
-- ============================================
-- DROP INDEX idx_config_created_at (si existe)
-- ============================================
SET @exists := (
    SELECT COUNT(*) 
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'store_configurations'
      AND index_name = 'idx_config_created_at'
);

SET @sql := IF(@exists > 0,
               'ALTER TABLE store_configurations DROP INDEX idx_config_created_at;',
               'SELECT "idx_config_created_at no existe";');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================
-- DROP INDEX idx_config_updated_at (si existe)
-- ============================================
SET @exists := (
    SELECT COUNT(*) 
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'store_configurations'
      AND index_name = 'idx_config_updated_at'
);

SET @sql := IF(@exists > 0,
               'ALTER TABLE store_configurations DROP INDEX idx_config_updated_at;',
               'SELECT "idx_config_updated_at no existe";');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================
-- DROP INDEX idx_logs_created_at (si existe)
-- ============================================
SET @exists := (
    SELECT COUNT(*) 
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'configuration_logs'
      AND index_name = 'idx_logs_created_at'
);

SET @sql := IF(@exists > 0,
               'ALTER TABLE configuration_logs DROP INDEX idx_logs_created_at;',
               'SELECT "idx_logs_created_at no existe";');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================
-- CREATE INDEX idx_config_created_at
-- ============================================
SET @exists := (
    SELECT COUNT(*) 
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'store_configurations'
      AND index_name = 'idx_config_created_at'
);

SET @sql := IF(@exists = 0,
               'CREATE INDEX idx_config_created_at ON store_configurations (created_at);',
               'SELECT "idx_config_created_at ya existe";');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================
-- CREATE INDEX idx_config_updated_at
-- ============================================
SET @exists := (
    SELECT COUNT(*) 
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'store_configurations'
      AND index_name = 'idx_config_updated_at'
);

SET @sql := IF(@exists = 0,
               'CREATE INDEX idx_config_updated_at ON store_configurations (updated_at);',
               'SELECT "idx_config_updated_at ya existe";');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================
-- CREATE INDEX idx_logs_created_at
-- ============================================
SET @exists := (
    SELECT COUNT(*) 
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'configuration_logs'
      AND index_name = 'idx_logs_created_at'
);

SET @sql := IF(@exists = 0,
               'CREATE INDEX idx_logs_created_at ON configuration_logs (created_at);',
               'SELECT "idx_logs_created_at ya existe";');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- Crear vista para obtener resumen de configuraciones por tienda
CREATE OR REPLACE VIEW `store_config_summary` AS
SELECT 
    s.id as store_id,
    s.name as store_name,
    COUNT(sc.id) as total_configs,
    COUNT(CASE WHEN sc.category = 'payment_methods' THEN 1 END) as payment_configs,
    COUNT(CASE WHEN sc.category = 'language' THEN 1 END) as language_configs,
    COUNT(CASE WHEN sc.category = 'permissions' THEN 1 END) as permission_configs,
    COUNT(CASE WHEN sc.category = 'integrations' THEN 1 END) as integration_configs,
    COUNT(CASE WHEN sc.category = 'notifications' THEN 1 END) as notification_configs,
    COUNT(CASE WHEN sc.category = 'general' THEN 1 END) as general_configs,
    MAX(sc.updated_at) as last_updated
FROM stores s
LEFT JOIN store_configurations sc ON s.id = sc.store_id
GROUP BY s.id, s.name;

-- Insertar configuración para tienda B (ejemplo)
INSERT INTO `store_configurations` (`store_id`, `category`, `config_key`, `config_value`, `description`) VALUES
-- Configuración de idioma por defecto
(2, 'language', 'default_language', 'es', 'Idioma predeterminado del sistema'),
(2, 'language', 'timezone', 'America/Santiago', 'Zona horaria predeterminada'),
(2, 'language', 'date_format', 'd/m/Y', 'Formato de fecha predeterminado'),
(2, 'language', 'currency', 'CLP', 'Moneda predeterminada'),
(2, 'language', 'decimal_separator', ',', 'Separador decimal para números'),
(2, 'language', 'thousands_separator', '.', 'Separador de miles para números'),

-- Configuración de permisos por defecto
(2, 'permissions', 'admin_enabled', 'true', 'Habilitar permisos de administrador'),
(2, 'permissions', 'manager_enabled', 'true', 'Habilitar permisos de gerente'),
(2, 'permissions', 'employee_enabled', 'true', 'Habilitar permisos de empleado'),

-- Configuración de Transbank por defecto (deshabilitado)
(2, 'payment_methods', 'transbank_enabled', 'false', 'Habilitar procesamiento de pagos Transbank'),
(2, 'payment_methods', 'transbank_commerce_code', '', 'Código de comercio Transbank'),
(2, 'payment_methods', 'transbank_api_key', '', 'API Key Transbank'),
(2, 'payment_methods', 'transbank_environment', 'Integration', 'Ambiente Transbank'),

-- Configuración de integraciones
(2, 'integrations', 'setap_enabled', 'false', 'Habilitar integración SETAP'),
(2, 'integrations', 'setap_api_endpoint', '', 'URL del endpoint API SETAP'),
(2, 'integrations', 'setap_api_key', '', 'API Key para integración SETAP'),

-- Configuración de notificaciones por defecto
(2, 'notifications', 'email_enabled', 'true', 'Habilitar notificaciones por email'),
(2, 'notifications', 'email_admin', '', 'Email del administrador'),
(2, 'notifications', 'email_sales', '', 'Email del departamento de ventas'),
(2, 'notifications', 'sms_enabled', 'false', 'Habilitar notificaciones por SMS'),
(2, 'notifications', 'order_confirmations', 'true', 'Enviar confirmaciones de pedidos'),
(2, 'notifications', 'delivery_updates', 'true', 'Enviar actualizaciones de entrega'),

-- Configuración general
(2, 'general', 'store_name', 'Tienda B', 'Nombre de la tienda'),
(2, 'general', 'store_description', 'Segunda tienda del mall virtual', 'Descripción de la tienda'),
(2, 'general', 'default_shipping_cost', '0', 'Costo de envío predeterminado'),
(2, 'general', 'min_order_amount', '0', 'Monto mínimo para pedidos'),
(2, 'general', 'max_order_amount', '9999999', 'Monto máximo para pedidos')

ON DUPLICATE KEY UPDATE
    `updated_at` = CURRENT_TIMESTAMP;

DROP TABLE IF EXISTS config_definitions;

-- Crear tabla para definiciones de configuración (metadatos)
CREATE TABLE IF NOT EXISTS `config_definitions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category` varchar(50) NOT NULL,
    `config_key` varchar(100) NOT NULL,
    `display_name` varchar(255) NOT NULL,
    `description` text NULL,
    `data_type` enum('string','integer','boolean','json','email','url') NOT NULL DEFAULT 'string',
    `default_value` text NULL,
    `validation_rules` json NULL,
    `options` json NULL,
    `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
    `is_required` tinyint(1) NOT NULL DEFAULT 0,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_category_key` (`category`, `config_key`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar definiciones de configuración
INSERT INTO `config_definitions` (`category`, `config_key`, `display_name`, `description`, `data_type`, `default_value`, `validation_rules`, `options`, `sort_order`) VALUES
-- Idioma
('language', 'default_language', 'Idioma Predeterminado', 'Idioma principal del sistema', 'string', 'es', '{"required": true}', '{"es": "Español", "en": "English"}', 1),
('language', 'timezone', 'Zona Horaria', 'Zona horaria del servidor', 'string', 'America/Santiago', '{"required": true}', '{"America/Santiago": "America/Santiago (GMT-3)", "America/Valparaiso": "America/Valparaiso (GMT-3)", "UTC": "UTC (GMT+0)"}', 2),
('language', 'date_format', 'Formato de Fecha', 'Formato para mostrar fechas', 'string', 'd/m/Y', '{"required": true}', '{"d/m/Y": "DD/MM/AAAA", "Y-m-d": "AAAA-MM-DD", "m/d/Y": "MM/DD/AAAA"}', 3),
('language', 'currency', 'Moneda', 'Moneda predeterminada para transacciones', 'string', 'CLP', '{"required": true}', '{"CLP": "Peso Chileno (CLP)", "USD": "Dólar (USD)", "EUR": "Euro (EUR)"}', 4),
('language', 'decimal_separator', 'Separador Decimal', 'Carácter para separar decimales', 'string', ',', '{"required": true}', '{",": "Coma (,)", ".": "Punto (.)"}', 5),
('language', 'thousands_separator', 'Separador de Miles', 'Carácter para separar miles', 'string', '.', '{"required": true}', '{".": "Punto (.)", ",": "Coma (,)", " ": "Espacio"}', 6),

-- Permisos
('permissions', 'admin_enabled', 'Administrador', 'Habilitar permisos de administrador', 'boolean', 'true', '{"required": true}', NULL, 1),
('permissions', 'manager_enabled', 'Gerente de Tienda', 'Habilitar permisos de gerente', 'boolean', 'true', '{"required": true}', NULL, 2),
('permissions', 'employee_enabled', 'Empleado', 'Habilitar permisos de empleado', 'boolean', 'true', '{"required": true}', NULL, 3),

-- Métodos de pago
('payment_methods', 'transbank_enabled', 'Transbank Habilitado', 'Activar procesamiento de pagos Transbank', 'boolean', 'false', '{"required": true}', NULL, 1),
('payment_methods', 'transbank_commerce_code', 'Código de Comercio', 'Código de comercio de Transbank', 'string', '', '{"pattern": "^[0-9]+$", "minLength": 6, "maxLength": 20}', NULL, 2),
('payment_methods', 'transbank_api_key', 'API Key', 'API Key de Transbank', 'string', '', '{"requiredIf": {"field": "transbank_enabled", "value": "true"}}', NULL, 3),
('payment_methods', 'transbank_environment', 'Ambiente', 'Ambiente de Transbank', 'string', 'Integration', '{"required": true}', '{"Integration": "Integración (Pruebas)", "Production": "Producción"}', 4),

-- Integraciones
('integrations', 'setap_enabled', 'SETAP Habilitado', 'Activar integración con SETAP', 'boolean', 'false', '{"required": true}', NULL, 1),
('integrations', 'setap_api_endpoint', 'Endpoint API', 'URL del endpoint API de SETAP', 'url', '', '{"requiredIf": {"field": "setap_enabled", "value": "true"}}', NULL, 2),
('integrations', 'setap_api_key', 'API Key SETAP', 'API Key para integración con SETAP', 'string', '', '{"requiredIf": {"field": "setap_enabled", "value": "true"}}', NULL, 3),

-- Notificaciones
('notifications', 'email_enabled', 'Email Habilitado', 'Activar notificaciones por email', 'boolean', 'true', '{"required": true}', NULL, 1),
('notifications', 'email_admin', 'Email Admin', 'Email del administrador', 'email', '', '{"requiredIf": {"field": "email_enabled", "value": "true"}}', NULL, 2),
('notifications', 'email_sales', 'Email Ventas', 'Email del departamento de ventas', 'email', '', '{"requiredIf": {"field": "email_enabled", "value": "true"}}', NULL, 3),
('notifications', 'sms_enabled', 'SMS Habilitado', 'Activar notificaciones por SMS', 'boolean', 'false', '{"required": true}', NULL, 4),
('notifications', 'order_confirmations', 'Confirmaciones de Pedidos', 'Enviar confirmaciones de pedidos', 'boolean', 'true', '{"required": true}', NULL, 5),
('notifications', 'delivery_updates', 'Actualizaciones de Entrega', 'Enviar actualizaciones de entrega', 'boolean', 'true', '{"required": true}', NULL, 6);

-- Actualizar estadísticas
UPDATE stores SET 
    config_count = (
        SELECT COUNT(*) FROM store_configurations WHERE store_id = stores.id
    ),
    updated_at = NOW()
WHERE id IN (1, 2);

-- Verificar que las tablas se crearon correctamente
SELECT 
    'store_configurations' as table_name,
    COUNT(*) as record_count
FROM store_configurations

UNION ALL

SELECT 
    'configuration_logs' as table_name,
    COUNT(*) as record_count
FROM configuration_logs

UNION ALL

SELECT 
    'config_definitions' as table_name,
    COUNT(*) as record_count
FROM config_definitions;

-- Mostrar resumen de migraciones
SELECT 
    'CONFIG_SYSTEM_MIGRATION' as migration_name,
    'completed' as status,
    NOW() as completed_at,
    'Sistema de configuración de tienda implementado exitosamente' as message;
