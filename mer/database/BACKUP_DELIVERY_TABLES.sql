-- =============================================================================
-- SCRIPT DE BACKUP PARA TABLAS DELIVERY ANTES DE CORRECCIÓN
-- Fecha: 2025-12-08
-- Autor: MiniMax Agent
-- Descripción: Crea copias de respaldo de las tablas de delivery antes de aplicar correcciones
-- =============================================================================

USE comerci3_bdmer;

-- Crear backup de todas las tablas de delivery
SELECT 'INICIANDO BACKUP DE TABLAS DELIVERY' as Status;

-- Backup delivery_groups
DROP TABLE IF EXISTS backup_delivery_groups;
CREATE TABLE backup_delivery_groups AS SELECT * FROM delivery_groups;
SELECT 'Backup delivery_groups completado' as Status;

-- Backup deliveries  
DROP TABLE IF EXISTS backup_deliveries;
CREATE TABLE backup_deliveries AS SELECT * FROM deliveries;
SELECT 'Backup deliveries completado' as Status;

-- Backup delivery_drivers
DROP TABLE IF EXISTS backup_delivery_drivers;
CREATE TABLE backup_delivery_drivers AS SELECT * FROM delivery_drivers;
SELECT 'Backup delivery_drivers completado' as Status;

-- Backup delivery_methods
DROP TABLE IF EXISTS backup_delivery_methods;
CREATE TABLE backup_delivery_methods AS SELECT * FROM delivery_methods;
SELECT 'Backup delivery_methods completado' as Status;

-- Backup delivery_group_items
DROP TABLE IF EXISTS backup_delivery_group_items;
CREATE TABLE backup_delivery_group_items AS SELECT * FROM delivery_group_items;
SELECT 'Backup delivery_group_items completado' as Status;

-- Backup delivery_activity_log
DROP TABLE IF EXISTS backup_delivery_activity_log;
CREATE TABLE backup_delivery_activity_log AS SELECT * FROM delivery_activity_log;
SELECT 'Backup delivery_activity_log completado' as Status;

-- Backup products (por si necesita restauración)
DROP TABLE IF EXISTS backup_products;
CREATE TABLE backup_products AS SELECT * FROM products;
SELECT 'Backup products completado' as Status;

-- Verificar backups creados
SELECT 'BACKUP COMPLETADO - Verificación:' as Status;
SELECT 
    TABLE_NAME, 
    TABLE_ROWS as 'Registros Estimados',
    CREATE_TIME as 'Fecha Creación Backup'
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'comerci3_bdmer' 
AND TABLE_NAME LIKE 'backup_%'
ORDER BY TABLE_NAME;

-- Mostrar estadísticas de las tablas originales
SELECT 'ESTADÍSTICAS DE TABLAS ORIGINALES:' as Info;
SELECT 
    TABLE_NAME, 
    TABLE_ROWS as 'Registros',
    CREATE_TIME as 'Fecha Creación'
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'comerci3_bdmer' 
AND TABLE_NAME IN (
    'delivery_groups', 'deliveries', 'delivery_drivers', 
    'delivery_methods', 'delivery_group_items', 'delivery_activity_log', 'products'
)
ORDER BY TABLE_NAME;

-- Restaurar desde backup (solo si es necesario)
-- USE comerci3_bdmer;
-- DROP TABLE IF EXISTS delivery_groups;
-- CREATE TABLE delivery_groups AS SELECT * FROM backup_delivery_groups;