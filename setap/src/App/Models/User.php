<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;
use Exception;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los usuarios con información relacionada
     */
    public function getAll(): array
    {
        try {
            $sql = "
                SELECT u.id, u.nombre_usuario, u.email, u.fecha_Creado,
                       p.nombre as nombre_completo, p.rut, p.telefono, p.direccion,
                       ut.nombre as rol, ut.id as usuario_tipo_id,
                       et.nombre as estado
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                LEFT JOIN estado_tipos et ON u.estado_tipo_id = et.id
                ORDER BY u.fecha_Creado DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en User::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function create(array $data): ?int
    {
        try {
            $this->db->beginTransaction();

            // 1. Crear persona
            $personaId = $this->createPersona($data);
            if (!$personaId) {
                throw new Exception('Error creando persona');
            }

            // 2. Crear usuario
            $userId = $this->createUsuario($data, $personaId);
            if (!$userId) {
                throw new Exception('Error creando usuario');
            }

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en User::create: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear registro en tabla personas
     */
    private function createPersona(array $data): ?int
    {
        try {
            $sql = "
                INSERT INTO personas (nombre, rut, telefono, direccion, estado_tipo_id, fecha_Creado) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nombre'],
                $data['rut'],
                $data['telefono'] ?? '',
                $data['direccion'] ?? ''
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando persona: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear registro en tabla usuarios
     */
    private function createUsuario(array $data, int $personaId): ?int
    {
        try {
            $sql = "
                INSERT INTO usuarios (persona_id, nombre_usuario, email, clave_hash, usuario_tipo_id, estado_tipo_id, fecha_Creado) 
                VALUES (?, ?, ?, ?, ?, 1, NOW())
            ";

            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $personaId,
                $data['nombre_usuario'],
                $data['email'],
                $hashedPassword,
                $data['usuario_tipo_id']
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener usuario por ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "
                SELECT u.*, p.nombre as nombre_completo, p.rut, p.telefono, p.direccion,
                       ut.nombre as rol
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE u.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error en User::getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(int $id, array $data): bool
    {
        try {
            $this->db->beginTransaction();

            // Actualizar persona
            $personaSql = "
                UPDATE personas p
                INNER JOIN usuarios u ON p.id = u.persona_id
                SET p.nombre = ?, p.telefono = ?, p.direccion = ?, p.fecha_modificacion = NOW()
                WHERE u.id = ?
            ";

            $stmt = $this->db->prepare($personaSql);
            $stmt->execute([
                $data['nombre'],
                $data['telefono'] ?? '',
                $data['direccion'] ?? '',
                $id
            ]);

            // Actualizar usuario
            $usuarioSql = "
                UPDATE usuarios 
                SET email = ?, usuario_tipo_id = ?, fecha_modificacion = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($usuarioSql);
            $stmt->execute([
                $data['email'],
                $data['usuario_tipo_id'],
                $id
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en User::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar usuario (cambiar estado), el estado id = 4 es eliminado
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE usuarios SET estado_tipo_id = 4, fecha_modificacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error en User::delete: " . $e->getMessage());
            return false;
        }
    }
}
