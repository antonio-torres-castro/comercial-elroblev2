DELIMITER $$
CREATE DEFINER=root@localhost FUNCTION get_user_role(user_id INT, store_id INT) RETURNS enum('admin','store_admin','customer') CHARSET utf8mb4
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE user_role ENUM('admin', 'store_admin', 'customer');
    
    -- Verificar si es admin
    IF EXISTS (SELECT 1 FROM user_roles WHERE user_id = user_id AND role = 'admin') THEN
        RETURN 'admin';
    END IF;
    
    -- Verificar si es store_admin para esta tienda específica
    IF store_id IS NOT NULL AND EXISTS (
        SELECT 1 FROM user_roles 
        WHERE user_id = user_id AND role = 'store_admin' AND store_id = store_id
    ) THEN
        RETURN 'store_admin';
    END IF;
    
    -- Verificar si es customer
    IF EXISTS (SELECT 1 FROM user_roles WHERE user_id = user_id AND role = 'customer') THEN
        RETURN 'customer';
    END IF;
    
    RETURN 'customer'; -- Por defecto
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=root@localhost FUNCTION has_store_access(user_id INT, store_id INT) RETURNS tinyint(1)
    DETERMINISTIC
BEGIN
    -- Admin tiene acceso a todo
    IF EXISTS (SELECT 1 FROM user_roles WHERE user_id = user_id AND role = 'admin') THEN
        RETURN TRUE;
    END IF;
    
    -- Store admin específico
    IF EXISTS (
        SELECT 1 FROM user_roles 
        WHERE user_id = user_id AND role = 'store_admin' AND store_id = store_id
    ) THEN
        RETURN TRUE;
    END IF;
    
    RETURN FALSE;
END$$
DELIMITER ;
