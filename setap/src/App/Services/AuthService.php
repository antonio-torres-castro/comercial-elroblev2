<?php

namespace App\Services;

use App\Config\Database;
use App\Helpers\Security;
use PDO;
use PDOException;

class AuthService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Autenticar usuario por username/email y password
     */
    public function authenticate(string $identifier, string $password): array|false
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.nombre_usuario, u.email, u.clave_hash, u.estado_tipo_id,
                       p.nombre as nombre_completo, p.rut, p.telefono, p.direccion,
                       ut.nombre as rol, ut.id as usuario_tipo_id,
                       p.estado_tipo_id as persona_estado
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE (u.nombre_usuario = ? OR u.email = ?) 
                AND u.estado_tipo_id IN (1, 2) 
                AND p.estado_tipo_id IN (1, 2)
            ");

            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['clave_hash'])) {
                // No devolver el hash de la contraseña
                unset($user['clave_hash']);
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            error_log('AuthService::authenticate error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Iniciar sesión de usuario
     */
    public function login(array $userData): bool
    {
        try {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['nombre_usuario'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['user_role'] = $userData['rol'];
            $_SESSION['usuario_tipo_id'] = $userData['usuario_tipo_id'];
            $_SESSION['nombre_completo'] = $userData['nombre_completo'];
            $_SESSION['rut'] = $userData['rut'];
            $_SESSION['telefono'] = $userData['telefono'] ?? '';
            $_SESSION['direccion'] = $userData['direccion'] ?? '';
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time();

            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);

            return true;
        } catch (\Exception $e) {
            error_log('AuthService::login error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(): bool
    {
        try {
            // Limpiar todas las variables de sesión
            $_SESSION = [];

            // Destruir la cookie de sesión si existe
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Destruir la sesión
            session_destroy();

            return true;
        } catch (\Exception $e) {
            error_log('AuthService::logout error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public function isAuthenticated(): bool
    {
        return Security::isAuthenticated();
    }

    /**
     * Obtener datos del usuario actual
     */
    public function getCurrentUser(): array|null
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'usuario_tipo_id' => $_SESSION['usuario_tipo_id'] ?? null,
            'nombre_completo' => $_SESSION['nombre_completo'] ?? null,
            'rut' => $_SESSION['rut'] ?? null,
            'telefono' => $_SESSION['telefono'] ?? null,
            'direccion' => $_SESSION['direccion'] ?? null
        ];
    }

    /**
     * Cambiar contraseña del usuario actual
     */
    public function changePassword(string $currentPassword, string $newPassword): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        try {
            $userId = $_SESSION['user_id'];

            // Verificar contraseña actual
            $stmt = $this->db->prepare("SELECT clave_hash FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['clave_hash'])) {
                return false;
            }

            // Actualizar contraseña
            $newHash = Security::hashPassword($newPassword);
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET clave_hash = ?, fecha_modificacion = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");

            return $stmt->execute([$newHash, $userId]);
        } catch (PDOException $e) {
            error_log('AuthService::changePassword error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar último acceso del usuario
     */
    public function updateLastActivity(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $_SESSION['last_activity'] = time();

        try {
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET fecha_modificacion = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log('AuthService::updateLastActivity error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si la sesión ha expirado
     */
    public function isSessionExpired(): bool
    {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }

        $sessionLifetime = 3600; // 1 hora por defecto
        return (time() - $_SESSION['last_activity']) > $sessionLifetime;
    }

    /**
     * Obtener tiempo restante de sesión en minutos
     */
    public function getSessionTimeRemaining(): int
    {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }

        $sessionLifetime = 3600; // 1 hora
        $timeElapsed = time() - $_SESSION['last_activity'];
        $timeRemaining = $sessionLifetime - $timeElapsed;

        return max(0, intval($timeRemaining / 60));
    }

    /**
     * Verificar si un username está disponible
     */
    public function isUsernameAvailable(string $username, ?int $excludeUserId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ? AND estado_tipo_id != 4";
            $params = [$username];

            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            error_log('AuthService::isUsernameAvailable error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un email está disponible
     */
    public function isEmailAvailable(string $email, ?int $excludeUserId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ? AND estado_tipo_id != 4";
            $params = [$email];

            if ($excludeUserId) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            error_log('AuthService::isEmailAvailable error: ' . $e->getMessage());
            return false;
        }
    }
}
