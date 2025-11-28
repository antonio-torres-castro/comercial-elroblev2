-- =====================================================
-- SISTEMA DE AUTENTICACIÓN Y AUTORIZACIÓN
-- Mall Virtual - Base de Datos
-- =====================================================

-- Tabla principal de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended', 'pending_verification') DEFAULT 'pending_verification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Tabla de roles de usuario
CREATE TABLE IF NOT EXISTS user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role ENUM('admin', 'store_admin', 'customer') NOT NULL,
    store_id INT NULL, -- Para store_admin, especifica la tienda
    granted_by INT NULL, -- Admin que otorgó el rol
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_role (user_id, role),
    INDEX idx_store_admin (store_id, role),
    
    UNIQUE KEY unique_user_store_role (user_id, store_id, role)
);

-- Perfil extendido del usuario
CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    birth_date DATE NULL,
    preferences JSON NULL, -- preferencias del usuario
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Direcciones de usuario
CREATE TABLE IF NOT EXISTS addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('billing', 'shipping', 'both') NOT NULL DEFAULT 'both',
    label VARCHAR(100) NULL, -- "Casa", "Oficina", etc.
    street_address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state_province VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Chile',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_addresses (user_id),
    INDEX idx_default_addresses (user_id, is_default)
);

-- Tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reset_token (token),
    INDEX idx_reset_user (user_id)
);

-- Tokens de verificación de email
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_verification_token (token),
    INDEX idx_verification_user (user_id)
);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Admin por defecto (password: Miclave.1)
INSERT INTO users (email, password_hash, status) VALUES 
('admin@mallvirtual.com', '$2y$10$Z4Cw0OOzs4DNGQbdeBWdse7l68fMbbFMnGUef3MUD9Bte0dLgwUYK', 'active');

-- Obtener el ID del admin creado
SET @admin_id = LAST_INSERT_ID();

-- Asignar rol de admin
INSERT INTO user_roles (user_id, role, granted_by) VALUES 
(@admin_id, 'admin', @admin_id);

-- Crear perfil para el admin
INSERT INTO user_profiles (user_id, first_name, last_name) VALUES 
(@admin_id, 'Administrador', 'Sistema');

-- =====================================================
-- PROCEDIMIENTOS Y FUNCIONES AUXILIARES
-- =====================================================

DELIMITER //

-- Función para obtener rol del usuario
CREATE FUNCTION get_user_role(user_id INT, store_id INT) 
RETURNS ENUM('admin', 'store_admin', 'customer') DETERMINISTIC
READS SQL DATA
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
END //

-- Función para verificar si un usuario tiene acceso a una tienda
CREATE FUNCTION has_store_access(user_id INT, store_id INT) 
RETURNS BOOLEAN DETERMINISTIC
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
END //

DELIMITER ;

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de usuarios con roles
CREATE VIEW user_roles_view AS
SELECT 
    u.id as user_id,
    u.email,
    u.status,
    u.created_at,
    up.first_name,
    up.last_name,
    ur.role,
    ur.store_id,
    ur.granted_at
FROM users u
LEFT JOIN user_profiles up ON u.id = up.user_id
LEFT JOIN user_roles ur ON u.id = ur.user_id;

-- Vista de direcciones con información del usuario
CREATE VIEW user_addresses_view AS
SELECT 
    a.id,
    a.user_id,
    u.email,
    a.type,
    a.label,
    a.street_address,
    a.city,
    a.state_province,
    a.postal_code,
    a.country,
    a.is_default
FROM addresses a
JOIN users u ON a.user_id = u.id;