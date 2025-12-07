-- =============================================================================
-- AÑADE ESTO AL INICIO DE TU SCRIPT (ANTES DE DROP TABLE IF EXISTS)
-- =============================================================================

-- Desactivar foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar constraints huérfanas de las tablas que vas a recrear
SET @cleanup_tables = 'delivery_addresses,delivery_schedules,delivery_status_history,delivery_notifications,delivery_tracking,delivery_zone_costs,deliery_zone_costs';

-- Procedimiento rápido de limpieza
DROP PROCEDURE IF EXISTS quick_orphan_cleanup;

DELIMITER $$

CREATE PROCEDURE quick_orphan_cleanup()
BEGIN
    DECLARE v_table_name VARCHAR(64);
    DECLARE v_constraint_name VARCHAR(64);
    DECLARE done INT DEFAULT FALSE;
    
    -- Cursor para todas las constraints huérfanas
    DECLARE cur_orphans CURSOR FOR
        SELECT tc.TABLE_NAME, tc.CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
        WHERE tc.CONSTRAINT_SCHEMA = DATABASE()
        AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
        AND FIND_IN_SET(tc.TABLE_NAME, @cleanup_tables) > 0;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur_orphans;
    
    cleanup_loop: LOOP
        FETCH cur_orphans INTO v_table_name, v_constraint_name;
        IF done THEN
            LEAVE cleanup_loop;
        END IF;
        
        -- Intentar crear tabla temporal y eliminar constraint
        BEGIN
            DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN END;
            
            -- 1. Crear tabla si no existe (o recrearla)
            SET @sql = CONCAT('CREATE TABLE IF NOT EXISTS `', v_table_name, 
                             '` (id INT PRIMARY KEY) ENGINE=InnoDB');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- 2. Intentar eliminar constraint
            SET @sql = CONCAT('ALTER TABLE `', v_table_name, 
                             '` DROP FOREIGN KEY `', v_constraint_name, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- 3. Eliminar tabla temporal
            SET @sql = CONCAT('DROP TABLE `', v_table_name, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END;
        
    END LOOP;
    
    CLOSE cur_orphans;
    
    SELECT 'Limpieza de constraints completada' AS resultado;
END $$

DELIMITER ;

-- Ejecutar limpieza
-- CALL quick_orphan_cleanup();

-- Eliminar procedimiento temporal
-- DROP PROCEDURE IF EXISTS quick_orphan_cleanup;