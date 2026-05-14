<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use PDO;
use Exception;

class GrupoTipo
{
    private $db;
    private $table = 'grupo_tipos';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los tipos de grupo
     */
    public function getAll(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM {$this->table} ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error('GrupoTipo::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar por id
     */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            Logger::error('GrupoTipo::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si existe un nombre (unicidad)
     */
    public function existsByNombre(string $nombre, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE nombre = ?";
            $params = [$nombre];
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            Logger::error('GrupoTipo::existsByNombre error: ' . $e->getMessage());
            return false;
        }
    }

    /** Crear */
    public function create(array $data): bool
    {
        try {
            if ($this->existsByNombre($data['nombre'])) {
                return false;
            }
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (nombre, descripcion) VALUES (?, ?)");
            return $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
            ]);
        } catch (Exception $e) {
            Logger::error('GrupoTipo::create error: ' . $e->getMessage());
            return false;
        }
    }

    /** Actualizar */
    public function update(int $id, array $data): bool
    {
        try {
            if ($this->existsByNombre($data['nombre'], $id)) {
                return false;
            }
            $stmt = $this->db->prepare("UPDATE {$this->table} SET nombre = ?, descripcion = ? WHERE id = ?");
            return $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                $id
            ]);
        } catch (Exception $e) {
            Logger::error('GrupoTipo::update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar login/logout en base de datos
     * @param int|null $userId
     * @param int $tipoRegistro 1=login, 2=logout
     */
    public function logUserEvent(?int $userId, int $tipoRegistro): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if ($ip === null || $ip === '') {
                $ip = '0.0.0.0';
            }

            $stmt = $this->db->prepare("
                INSERT INTO usuario_logs (usuario_id, accion_id, fecha, IP)
                VALUES (:user_id, :tipo, CURRENT_TIMESTAMP, :ip)
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':tipo' => $tipoRegistro,
                ':ip' => $ip
            ]);
        } catch (Exception $e) {
            Logger::error("AuthService::logUserEvent: " . $e->getMessage());
        }
    }
}
