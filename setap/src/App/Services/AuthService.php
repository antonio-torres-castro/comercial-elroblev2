<?php

namespace App\Services;

use App\Config\Database;
use PDO;
use PDOException;
use Exception;

class AuthService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Autenticar usuario por username/email y password
     * @return array|null
     */
    public function authenticate(string $identifier, string $password)
    {
        try {
            //El estado estado_tipo_id = 2, es un registro activo, que en el contexto de persona y usuario debe estar en ese punto para poder ser un usuario valido
            $stmt = $this->db->prepare("
                SELECT u.id, u.nombre_usuario, u.email, u.clave_hash, u.estado_tipo_id,
                       p.nombre as nombre_completo, p.rut, p.telefono, p.direccion,
                       ut.nombre as rol, ut.id as usuario_tipo_id,
                       p.estado_tipo_id as persona_estado
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE (u.nombre_usuario = ? OR u.email = ?) 
                AND p.estado_tipo_id = 2 AND u.estado_tipo_id = 2
            ");

            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if (!$user) {
                return null;
            }

            // Verificar password
            if (!password_verify($password, $user['clave_hash'])) {
                return null;
            }

            return $user;
        } catch (PDOException $e) {
            error_log("Error en autenticación: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Iniciar sesión del usuario
     */
    public function login(array $userData): bool
    {
        try {
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);

            // Almacenar datos en sesión
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['nombre_usuario'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['nombre_completo'] = $userData['nombre_completo'];
            $_SESSION['rol'] = $userData['rol'];
            $_SESSION['usuario_tipo_id'] = $userData['usuario_tipo_id'];
            $_SESSION['login_time'] = time();

            return true;
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(): bool
    {
        try {
            // Limpiar datos de sesión
            session_unset();
            session_destroy();

            // Iniciar nueva sesión limpia
            session_start();
            session_regenerate_id(true);

            return true;
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si hay una sesión activa
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtener datos del usuario autenticado
     * @return array|null
     */
    public function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'nombre_completo' => $_SESSION['nombre_completo'],
            'rol' => $_SESSION['rol'],
            'usuario_tipo_id' => $_SESSION['usuario_tipo_id'],
            'login_time' => $_SESSION['login_time']
        ];
    }

    /**
     * Cambiar contraseña del usuario
     */
    public function changePassword(int $userId, string $newPassword): bool
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET clave_hash = ?, updated_at = NOW() 
                WHERE id = ?
            ");

            return $stmt->execute([$hashedPassword, $userId]);
        } catch (PDOException $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si la sesión ha expirado
     */
    public function isSessionExpired(): bool
    {
        if (!isset($_SESSION['login_time'])) {
            return true;
        }

        $sessionLifetime = 3600; // 1 hora por defecto
        return (time() - $_SESSION['login_time']) > $sessionLifetime;
    }
}
