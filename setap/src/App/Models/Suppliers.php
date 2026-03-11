<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use PDO;
use Exception;

class Suppliers
{
    private $db;
    private $table = 'proveedores';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los proveedores
     */
    public function getAll(array $filters = []): array
    {
        try {
            $query = "
                SELECT
                    p.*,
                    et.nombre as estado_nombre
                FROM {$this->table} p
                LEFT JOIN estado_tipos et ON p.estado_tipo_id = et.id
                WHERE 1=1
            ";

            $params = [];

            if (!empty($filters['rut'])) {
                $query .= " AND p.rut LIKE ?";
                $params[] = '%' . $filters['rut'] . '%';
            }

            if (!empty($filters['razon_social'])) {
                $query .= " AND p.razon_social LIKE ?";
                $params[] = '%' . $filters['razon_social'] . '%';
            }

            if (!empty($filters['estado_tipo_id'])) {
                $query .= " AND p.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            $query .= " ORDER BY p.razon_social ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Suppliers::getAll: " . $e->getMessage());
            throw new Exception("Error al obtener la lista de proveedores");
        }
    }

    /**
     * Obtener un proveedor por ID
     */
    public function find(int $id): ?array
    {
        try {
            $query = "
                SELECT
                    p.*,
                    et.nombre as estado_nombre
                FROM {$this->table} p
                LEFT JOIN estado_tipos et ON p.estado_tipo_id = et.id
                WHERE p.id = ?
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            Logger::error("Suppliers::find: " . $e->getMessage());
            throw new Exception("Error al obtener el proveedor");
        }
    }

    /**
     * Crear un nuevo proveedor
     */
    public function create(array $data): int
    {
        try {
            $query = "
                INSERT INTO {$this->table} (
                    rut, razon_social, direccion, email, telefono,
                    fecha_inicio_contrato, fecha_facturacion, fecha_termino_contrato,
                    estado_tipo_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['rut'] ?? null,
                $data['razon_social'],
                $data['direccion'] ?? null,
                $data['email'] ?? null,
                $data['telefono'] ?? null,
                $data['fecha_inicio_contrato'] ?? null,
                empty($data['fecha_facturacion']) ? null : $data['fecha_facturacion'],
                empty($data['fecha_termino_contrato']) ? null : $data['fecha_termino_contrato'],
                $data['estado_tipo_id'] ?? 1
            ]);

            return (int) $this->db->lastInsertId();
        } catch (Exception $e) {
            Logger::error("Suppliers::create: " . $e->getMessage());
            throw new Exception("Error al crear el proveedor");
        }
    }

    /**
     * Actualizar un proveedor
     */
    public function update(int $id, array $data): bool
    {
        try {
            $query = "
                UPDATE {$this->table} SET
                    rut = ?,
                    razon_social = ?,
                    direccion = ?,
                    email = ?,
                    telefono = ?,
                    fecha_inicio_contrato = ?,
                    fecha_facturacion = ?,
                    fecha_termino_contrato = ?,
                    estado_tipo_id = ?,
                    fecha_modificacion = CURRENT_TIMESTAMP
                WHERE id = ? AND estado_tipo_id != 4
            ";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['rut'] ?? null,
                $data['razon_social'],
                $data['direccion'] ?? null,
                $data['email'] ?? null,
                $data['telefono'] ?? null,
                $data['fecha_inicio_contrato'] ?? null,
                empty($data['fecha_facturacion']) ? null : $data['fecha_facturacion'],
                empty($data['fecha_termino_contrato']) ? null : $data['fecha_termino_contrato'],
                $data['estado_tipo_id'] ?? 1,
                $id
            ]);

            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            Logger::error("Suppliers::update: " . $e->getMessage());
            throw new Exception("Error al actualizar el proveedor");
        }
    }

    /**
     * Eliminar proveedor (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $query = "
                UPDATE {$this->table} SET
                    estado_tipo_id = 4,
                    fecha_modificacion = CURRENT_TIMESTAMP
                WHERE id = ? AND estado_tipo_id != 4
            ";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);

            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            Logger::error("Suppliers::delete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si el RUT ya existe
     */
    public function rutExists(string $rut, ?int $excludeId = null): bool
    {
        try {
            $query = "SELECT id FROM {$this->table} WHERE rut = ? AND estado_tipo_id != 3";
            $params = [$rut];

            if ($excludeId) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            Logger::error("Suppliers::rutExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tipos de estado disponibles
     */
    public function getStatusTypes(): array
    {
        try {
            $query = "SELECT id, nombre FROM estado_tipos WHERE id IN (0, 1, 2) ORDER BY id ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Suppliers::getStatusTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar formato de RUT chileno
     */
    public function validateRut(string $rut): bool
    {
        $rut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($rut) < 2) {
            return false;
        }

        $body = substr($rut, 0, -1);
        $dv = strtoupper(substr($rut, -1));

        $sum = 0;
        $multiplier = 2;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += intval($body[$i]) * $multiplier;
            $multiplier = $multiplier == 7 ? 2 : $multiplier + 1;
        }

        $expectedDv = 11 - ($sum % 11);

        if ($expectedDv == 11) $expectedDv = '0';
        elseif ($expectedDv == 10) $expectedDv = 'K';
        else $expectedDv = strval($expectedDv);

        return $dv == $expectedDv;
    }

    /**
     * Formatear RUT para mostrar
     */
    public function formatRut(string $rut): string
    {
        $rut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($rut) < 2) {
            return $rut;
        }

        $body = substr($rut, 0, -1);
        $dv = strtoupper(substr($rut, -1));

        $formattedBody = number_format(intval($body), 0, '', '.');

        return $formattedBody . '-' . $dv;
    }
}
