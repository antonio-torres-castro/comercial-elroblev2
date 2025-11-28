<?php
/**
 * FUNCIONES DE AUTENTICACIÓN Y AUTORIZACIÓN
 * Sistema de Gestión de Usuarios - Mall Virtual
 */

/**
 * INICIALIZACIÓN DE SESIÓN SEGURA
 */
function init_secure_session() {
    // Solo configurar opciones de sesión si NO está ya activa
    if (session_status() === PHP_SESSION_NONE) {
        // Configurar opciones de sesión ANTES de iniciar
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
    }
    
    // Regenerar ID de sesión periódicamente
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutos
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * OBTENER CONEXIÓN A BASE DE DATOS
 */
function get_auth_db() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    return $pdo;
}

/**
 * VERIFICAR SI EL USUARIO ESTÁ AUTENTICADO
 */
function isLoggedIn() {
    init_secure_session();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * OBTENER DATOS DEL USUARIO ACTUAL
 */
function getCurrentUser() {
    init_secure_session();
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = get_auth_db()->prepare("
        SELECT 
            u.id, u.email, u.status, u.email_verified_at,
            u.created_at, u.last_login_at,
            up.first_name, up.last_name, up.phone, up.birth_date, up.preferences
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user ? $user : null;
}

/**
 * OBTENER ROL DEL USUARIO
 */
function getUserRole($store_id = null) {
    init_secure_session();
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = get_auth_db()->prepare("
            SELECT role, store_id 
            FROM user_roles 
            WHERE user_id = ?
            ORDER BY 
                CASE role 
                    WHEN 'admin' THEN 1 
                    WHEN 'store_admin' THEN 2 
                    WHEN 'customer' THEN 3 
                END
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $roles = $stmt->fetchAll();
        
        // Admin tiene acceso a todo
        foreach ($roles as $role) {
            if ($role['role'] === 'admin') {
                return 'admin';
            }
        }
        
        // Verificar acceso a tienda específica
        if ($store_id !== null) {
            foreach ($roles as $role) {
                if ($role['role'] === 'store_admin' && $role['store_id'] == $store_id) {
                    return 'store_admin';
                }
            }
        }
        
        // Verificar si es customer
        foreach ($roles as $role) {
            if ($role['role'] === 'customer') {
                return 'customer';
            }
        }
        
        return 'guest';
    } catch (Exception $e) {
        error_log("Error getting user role: " . $e->getMessage());
        return 'guest';
    }
}

/**
 * VERIFICAR SI EL USUARIO TIENE UN ROL ESPECÍFICO
 */
function hasRole($role) {
    $user_role = getUserRole();
    return $user_role === $role;
}

/**
 * VERIFICAR SI EL USUARIO TIENE ALGUNO DE LOS ROLES ESPECIFICADOS
 */
function hasAnyRole($roles) {
    $user_role = getUserRole();
    return in_array($user_role, $roles);
}

/**
 * VERIFICAR SI EL USUARIO TIENE ACCESO A UNA TIENDA
 */
function hasStoreAccess($store_id) {
    $user_role = getUserRole($store_id);
    return in_array($user_role, ['admin', 'store_admin']);
}

/**
 * REQUERIR AUTENTICACIÓN
 */
function requireAuth($redirect_to = '/mer/public/login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . $redirect_to);
        exit;
    }
}

/**
 * REQUERIR ROL ESPECÍFICO
 */
function requireRole($role, $store_id = null) {
    requireAuth();
    
    if (!hasRole($role) && !hasStoreAccess($store_id)) {
        http_response_code(403);
        include __DIR__ . '/../public/errors/403.php';
        exit;
    }
}

/**
 * HASH DE CONTRASEÑA SEGURO
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iterations
        'threads' => 3,         // 3 threads
    ]);
}

/**
 * VERIFICAR CONTRASEÑA
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * AUTENTICAR USUARIO
 */
function authenticateUser($email, $password) {
    try {
        $stmt = get_auth_db()->prepare("
            SELECT id, email, password_hash, status, email_verified_at
            FROM users 
            WHERE email = ?
        ");
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        
        // Verificar estado de la cuenta
        if ($user['status'] === 'suspended') {
            return ['success' => false, 'message' => 'Cuenta suspendida. Contacte al administrador'];
        }
        
        if ($user['status'] === 'inactive') {
            return ['success' => false, 'message' => 'Cuenta inactiva'];
        }
        
        // Verificar contraseña
        if (!verifyPassword($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        
        // Verificar email si es requerido
        if ($user['status'] === 'pending_verification' && !$user['email_verified_at']) {
            return ['success' => false, 'message' => 'Debe verificar su email antes de iniciar sesión', 'needs_verification' => true];
        }
        
        // Crear sesión
        init_secure_session();
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['login_time'] = time();
        
        // Actualizar último login
        $update_stmt = get_auth_db()->prepare("
            UPDATE users SET last_login_at = NOW() WHERE id = ?
        ");
        $update_stmt->execute([$user['id']]);
        
        return ['success' => true, 'user_id' => $user['id']];
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del sistema'];
    }
}

/**
 * REGISTRAR NUEVO USUARIO
 */
function registerUser($email, $password, $first_name, $last_name, $phone = null) {
    try {
        // Verificar si el email ya existe
        $check_stmt = get_auth_db()->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        
        if ($check_stmt->fetch()) {
            return ['success' => false, 'message' => 'Este email ya está registrado'];
        }
        
        // Crear usuario
        $password_hash = hashPassword($password);
        $stmt = get_auth_db()->prepare("
            INSERT INTO users (email, password_hash, status) 
            VALUES (?, ?, 'pending_verification')
        ");
        
        $stmt->execute([$email, $password_hash]);
        $user_id = get_auth_db()->lastInsertId();
        
        // Crear perfil
        $profile_stmt = get_auth_db()->prepare("
            INSERT INTO user_profiles (user_id, first_name, last_name, phone) 
            VALUES (?, ?, ?, ?)
        ");
        $profile_stmt->execute([$user_id, $first_name, $last_name, $phone]);
        
        // Asignar rol de customer
        $role_stmt = get_auth_db()->prepare("
            INSERT INTO user_roles (user_id, role) VALUES (?, 'customer')
        ");
        $role_stmt->execute([$user_id]);
        
        // Generar token de verificación
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $verify_stmt = get_auth_db()->prepare("
            INSERT INTO email_verifications (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $verify_stmt->execute([$user_id, $token, $expires_at]);
        
        return [
            'success' => true, 
            'user_id' => $user_id,
            'verification_token' => $token
        ];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al crear la cuenta'];
    }
}

/**
 * VERIFICAR EMAIL
 */
function verifyEmail($token) {
    try {
        $stmt = get_auth_db()->prepare("
            SELECT user_id, expires_at, verified_at
            FROM email_verifications 
            WHERE token = ?
        ");
        
        $stmt->execute([$token]);
        $verification = $stmt->fetch();
        
        if (!$verification) {
            return ['success' => false, 'message' => 'Token de verificación inválido'];
        }
        
        if ($verification['verified_at']) {
            return ['success' => false, 'message' => 'Email ya verificado'];
        }
        
        if (strtotime($verification['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Token de verificación expirado'];
        }
        
        // Marcar email como verificado
        $update_user_stmt = get_auth_db()->prepare("
            UPDATE users 
            SET email_verified_at = NOW(), status = 'active' 
            WHERE id = ?
        ");
        $update_user_stmt->execute([$verification['user_id']]);
        
        // Marcar token como usado
        $update_token_stmt = get_auth_db()->prepare("
            UPDATE email_verifications 
            SET verified_at = NOW() 
            WHERE user_id = ?
        ");
        $update_token_stmt->execute([$verification['user_id']]);
        
        return ['success' => true, 'message' => 'Email verificado exitosamente'];
        
    } catch (Exception $e) {
        error_log("Email verification error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al verificar email'];
    }
}

/**
 * CERRAR SESIÓN
 */
function logoutUser() {
    init_secure_session();
    
    // Limpiar todas las variables de sesión
    $_SESSION = [];
    
    // Destruir cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sesión
    session_destroy();
}

/**
 * GENERAR TOKEN DE RECUPERACIÓN
 */
function generatePasswordResetToken($email) {
    try {
        // Buscar usuario
        $stmt = get_auth_db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email no encontrado'];
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Eliminar tokens anteriores
        $delete_stmt = get_auth_db()->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $delete_stmt->execute([$user['id']]);
        
        // Crear nuevo token
        $insert_stmt = get_auth_db()->prepare("
            INSERT INTO password_resets (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $insert_stmt->execute([$user['id'], $token, $expires_at]);
        
        return [
            'success' => true, 
            'token' => $token,
            'user_id' => $user['id']
        ];
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del sistema'];
    }
}

/**
 * VERIFICAR TOKEN DE RECUPERACIÓN
 */
function verifyPasswordResetToken($token) {
    try {
        $stmt = get_auth_db()->prepare("
            SELECT user_id, expires_at, used_at
            FROM password_resets 
            WHERE token = ?
        ");
        
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            return ['success' => false, 'message' => 'Token inválido'];
        }
        
        if ($reset['used_at']) {
            return ['success' => false, 'message' => 'Token ya utilizado'];
        }
        
        if (strtotime($reset['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Token expirado'];
        }
        
        return ['success' => true, 'user_id' => $reset['user_id']];
        
    } catch (Exception $e) {
        error_log("Password reset verification error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del sistema'];
    }
}

/**
 * RESETEAR CONTRASEÑA
 */
function resetPassword($token, $new_password) {
    try {
        $verification = verifyPasswordResetToken($token);
        
        if (!$verification['success']) {
            return $verification;
        }
        
        $user_id = $verification['user_id'];
        $password_hash = hashPassword($new_password);
        
        // Actualizar contraseña
        $update_stmt = get_auth_db()->prepare("
            UPDATE users SET password_hash = ? WHERE id = ?
        ");
        $update_stmt->execute([$password_hash, $user_id]);
        
        // Marcar token como usado
        $mark_used_stmt = get_auth_db()->prepare("
            UPDATE password_resets SET used_at = NOW() WHERE token = ?
        ");
        $mark_used_stmt->execute([$token]);
        
        return ['success' => true, 'message' => 'Contraseña actualizada exitosamente'];
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al actualizar contraseña'];
    }
}

/**
 * OBTENER DIRECCIONES DEL USUARIO
 */
function getUserAddresses($user_id = null) {
    if (!$user_id && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return [];
    }
    
    try {
        $stmt = get_auth_db()->prepare("
            SELECT * FROM addresses 
            WHERE user_id = ? 
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error getting user addresses: " . $e->getMessage());
        return [];
    }
}

/**
 * CREAR O ACTUALIZAR DIRECCIÓN
 */
function saveAddress($data, $user_id = null) {
    if (!$user_id && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return ['success' => false, 'message' => 'Usuario no autenticado'];
    }
    
    try {
        // Si es la dirección por defecto, desmarcar otras del mismo tipo
        if ($data['is_default']) {
            $stmt = get_auth_db()->prepare("
                UPDATE addresses 
                SET is_default = FALSE 
                WHERE user_id = ? AND type = ?
            ");
            $stmt->execute([$user_id, $data['type']]);
        }
        
        if (isset($data['id']) && $data['id']) {
            // Actualizar dirección existente
            $stmt = get_auth_db()->prepare("
                UPDATE addresses 
                SET type = ?, label = ?, street_address = ?, city = ?, 
                    state_province = ?, postal_code = ?, country = ?, is_default = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $data['type'], $data['label'], $data['street_address'],
                $data['city'], $data['state_province'], $data['postal_code'],
                $data['country'], $data['is_default'] ? 1 : 0,
                $data['id'], $user_id
            ]);
        } else {
            // Crear nueva dirección
            $stmt = get_auth_db()->prepare("
                INSERT INTO addresses 
                (user_id, type, label, street_address, city, state_province, 
                 postal_code, country, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, $data['type'], $data['label'], $data['street_address'],
                $data['city'], $data['state_province'], $data['postal_code'],
                $data['country'], $data['is_default'] ? 1 : 0
            ]);
        }
        
        return ['success' => true, 'message' => 'Dirección guardada exitosamente'];
        
    } catch (Exception $e) {
        error_log("Error saving address: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al guardar dirección'];
    }
}

/**
 * ELIMINAR DIRECCIÓN
 */
function deleteAddress($address_id, $user_id = null) {
    if (!$user_id && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return ['success' => false, 'message' => 'Usuario no autenticado'];
    }
    
    try {
        $stmt = get_auth_db()->prepare("
            DELETE FROM addresses 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$address_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Dirección eliminada'];
        } else {
            return ['success' => false, 'message' => 'Dirección no encontrada'];
        }
        
    } catch (Exception $e) {
        error_log("Error deleting address: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al eliminar dirección'];
    }
}

/**
 * ACTUALIZAR PERFIL DE USUARIO
 */
function updateUserProfile($data, $user_id = null) {
    if (!$user_id && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return ['success' => false, 'message' => 'Usuario no autenticado'];
    }
    
    try {
        $stmt = get_auth_db()->prepare("
            UPDATE user_profiles 
            SET first_name = ?, last_name = ?, phone = ?, birth_date = ?, preferences = ?
            WHERE user_id = ?
        ");
        
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['birth_date'] ?: null,
            $data['preferences'] ? json_encode($data['preferences']) : null,
            $user_id
        ]);
        
        return ['success' => true, 'message' => 'Perfil actualizado exitosamente'];
        
    } catch (Exception $e) {
        error_log("Error updating profile: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al actualizar perfil'];
    }
}