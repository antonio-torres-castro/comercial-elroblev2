<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Security;
use App\Helpers\Logger;
use PDO;
use Exception;

class Persona
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las personas con filtros opcionales
     */
    /**
     * Obtener todas las personas con filtros opcionales
     */
    public function getAll(array $filters = []): array
    {
        try {
            $sql = "SELECT p.id, p.rut, p.nombre, p.telefono, p.direccion,
                       et.nombre AS estado, p.estado_tipo_id,
                       p.fecha_creado, p.fecha_modificacion
                FROM personas p
                LEFT JOIN estado_tipos et ON p.estado_tipo_id = et.id
                WHERE p.estado_tipo_id != 4";

            $params = [];

            // Filtro por estado
            if (!empty($filters['estado_tipo_id'])) {
                $sql .= " AND p.estado_tipo_id = :estado_tipo_id";
                $params[':estado_tipo_id'] = $filters['estado_tipo_id'];
            }

            // Filtro por búsqueda general
            if (!empty($filters['search'])) {
                $sql .= " AND (p.nombre LIKE :search1 OR p.rut LIKE :search2 OR p.telefono LIKE :search3)";
                $params[':search1'] = '%' . $filters['search'] . '%';
                $params[':search2'] = '%' . $filters['search'] . '%';
                $params[':search3'] = '%' . $filters['search'] . '%';
            }

            $sql .= " ORDER BY p.fecha_creado DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error('Persona::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener una persona por ID
     */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.rut, p.nombre, p.telefono, p.direccion,
                       et.nombre as estado, p.estado_tipo_id,
                       p.fecha_Creado, p.fecha_modificacion
                FROM personas p
                LEFT JOIN estado_tipos et ON p.estado_tipo_id = et.id
                WHERE p.id = :id AND p.estado_tipo_id != 4
            ");

            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (Exception $e) {
            Logger::error('Persona::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear una nueva persona
     */
    public function create(array $data): ?int
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO personas (rut, nombre, telefono, direccion, estado_tipo_id)
                VALUES (:rut, :nombre, :telefono, :direccion, :estado_tipo_id)
            ");

            $success = $stmt->execute([
                ':rut' => $data['rut'],
                ':nombre' => $data['nombre'],
                ':telefono' => $data['telefono'] ?? null,
                ':direccion' => $data['direccion'] ?? null,
                ':estado_tipo_id' => $data['estado_tipo_id'] ?? 2 // Activo por defecto
            ]);

            if ($success) {
                $personaId = (int) $this->db->lastInsertId();
                $this->db->commit();
                return $personaId;
            }

            $this->db->rollBack();
            return null;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error('Persona::create error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar una persona
     */
    public function update(int $id, array $data): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE personas
                SET rut = :rut, nombre = :nombre, telefono = :telefono,
                    direccion = :direccion, estado_tipo_id = :estado_tipo_id
                WHERE id = :id AND estado_tipo_id != 4
            ");

            $success = $stmt->execute([
                ':id' => $id,
                ':rut' => $data['rut'],
                ':nombre' => $data['nombre'],
                ':telefono' => $data['telefono'] ?? null,
                ':direccion' => $data['direccion'] ?? null,
                ':estado_tipo_id' => $data['estado_tipo_id']
            ]);

            if ($success && $stmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            }

            $this->db->rollBack();
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error('Persona::update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar una persona (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            // Verificar si la persona está siendo usada en otras tablas
            if ($this->isPersonaInUse($id)) {
                return false;
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE personas
                SET estado_tipo_id = 4
                WHERE id = :id AND estado_tipo_id != 4
            ");

            $success = $stmt->execute([':id' => $id]);

            if ($success && $stmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            }

            $this->db->rollBack();
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error('Persona::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un RUT ya existe
     */
    public function rutExists(string $rut, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM personas WHERE rut = :rut AND estado_tipo_id != 4";
            $params = [':rut' => $rut];

            if ($excludeId) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            Logger::error('Persona::rutExists error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si la persona está siendo utilizada en otras tablas
     */
    private function isPersonaInUse(int $id): bool
    {
        try {
            // Verificar en usuarios
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE persona_id = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            // Verificar en cliente_contrapartes
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM cliente_contrapartes WHERE persona_id = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            Logger::error('Persona::isPersonaInUse error: ' . $e->getMessage());
            return true; // Por seguridad, asumimos que está en uso si hay error
        }
    }

    /**
     * Obtener estadísticas de personas
     */
    public function getStats(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN estado_tipo_id = 2 THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN estado_tipo_id = 3 THEN 1 ELSE 0 END) as inactivos,
                    SUM(CASE WHEN DATE(fecha_Creado) = CURDATE() THEN 1 ELSE 0 END) as creados_hoy
                FROM personas
                WHERE estado_tipo_id != 4
            ");

            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            Logger::error('Persona::getStats error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar personas para autocompletado
     */
    public function search(string $term, int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, rut
                FROM personas
                WHERE (nombre LIKE :term OR rut LIKE :term)
                AND estado_tipo_id = 2
                ORDER BY nombre
                LIMIT :limit
            ");

            $stmt->bindValue(':term', '%' . $term . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error('Persona::search error: ' . $e->getMessage());
            return [];
        }
    }
}
