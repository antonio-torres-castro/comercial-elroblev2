<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT u.*, p.nombre as persona_nombre, p.rut, ut.nombre as tipo_usuario
            FROM usuarios u
            INNER JOIN personas p ON u.persona_id = p.id
            INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
            WHERE u.estado_tipo_id != 4
            ORDER BY p.nombre
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("
            SELECT u.*, p.nombre as persona_nombre, p.rut, p.telefono, p.direccion,
                   ut.nombre as tipo_usuario, ut.id as tipo_usuario_id
            FROM usuarios u
            INNER JOIN personas p ON u.persona_id = p.id
            INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
            WHERE u.id = ? AND u.estado_tipo_id != 4
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($personaData, $usuarioData)
    {
        $this->db->beginTransaction();

        try {
            // Insertar persona
            $stmtPersona = $this->db->prepare("
                INSERT INTO personas (rut, nombre, telefono, direccion, estado_tipo_id)
                VALUES (?, ?, ?, ?, 2)
            ");
            $stmtPersona->execute([
                $personaData['rut'],
                $personaData['nombre'],
                $personaData['telefono'],
                $personaData['direccion']
            ]);
            $personaId = $this->db->lastInsertId();

            // Insertar usuario
            $stmtUsuario = $this->db->prepare("
                INSERT INTO usuarios (persona_id, usuario_tipo_id, email, nombre_usuario, clave_hash, estado_tipo_id)
                VALUES (?, ?, ?, ?, ?, 2)
            ");
            $stmtUsuario->execute([
                $personaId,
                $usuarioData['usuario_tipo_id'],
                $usuarioData['email'],
                $usuarioData['nombre_usuario'],
                password_hash($usuarioData['password'], PASSWORD_DEFAULT)
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Error creating user: ' . $e->getMessage());
            return false;
        }
    }

    public function update($id, $personaData, $usuarioData)
    {
        // Implementar lógica de actualización
    }

    public function delete($id)
    {
        // Eliminación lógica
        $stmt = $this->db->prepare("
            UPDATE usuarios SET estado_tipo_id = 4 WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
}
