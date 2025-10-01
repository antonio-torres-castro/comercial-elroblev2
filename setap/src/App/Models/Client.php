<?php

namespace App\Models;

use App\Database\Database;
use PDO;
use Exception;

class Client
{
    private $db;
    private $table = 'clientes';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los clientes activos
     */
    public function getAll(array $filters = []): array
    {
        try {
            $query = "
                SELECT 
                    c.*,
                    et.nombre as estado_nombre,
                    (SELECT COUNT(*) FROM cliente_contrapartes cc WHERE cc.cliente_id = c.id AND cc.estado_tipo_id != 3) as total_contrapartes
                FROM {$this->table} c
                LEFT JOIN estado_tipos et ON c.estado_tipo_id = et.id
                WHERE c.estado_tipo_id != 3
            ";

            $params = [];

            // Filtros opcionales
            if (!empty($filters['rut'])) {
                $query .= " AND c.rut LIKE ?";
                $params[] = '%' . $filters['rut'] . '%';
            }

            if (!empty($filters['razon_social'])) {
                $query .= " AND c.razon_social LIKE ?";
                $params[] = '%' . $filters['razon_social'] . '%';
            }

            if (!empty($filters['estado_tipo_id'])) {
                $query .= " AND c.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            $query .= " ORDER BY c.razon_social ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error en Client::getAll: " . $e->getMessage());
            throw new Exception("Error al obtener la lista de clientes");
        }
    }

    /**
     * Obtener un cliente por ID
     */
    public function find(int $id): ?array
    {
        try {
            $query = "
                SELECT 
                    c.*,
                    et.nombre as estado_nombre
                FROM {$this->table} c
                LEFT JOIN estado_tipos et ON c.estado_tipo_id = et.id
                WHERE c.id = ? AND c.estado_tipo_id != 3
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;

        } catch (Exception $e) {
            error_log("Error en Client::find: " . $e->getMessage());
            throw new Exception("Error al obtener el cliente");
        }
    }

    /**
     * Crear un nuevo cliente
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
                $data['fecha_facturacion'] ?? null,
                $data['fecha_termino_contrato'] ?? null,
                $data['estado_tipo_id'] ?? 1 // 1 = Activo por defecto
            ]);

            return (int) $this->db->lastInsertId();

        } catch (Exception $e) {
            error_log("Error en Client::create: " . $e->getMessage());
            throw new Exception("Error al crear el cliente");
        }
    }

    /**
     * Actualizar un cliente
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
                WHERE id = ? AND estado_tipo_id != 3
            ";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['rut'] ?? null,
                $data['razon_social'],
                $data['direccion'] ?? null,
                $data['email'] ?? null,
                $data['telefono'] ?? null,
                $data['fecha_inicio_contrato'] ?? null,
                $data['fecha_facturacion'] ?? null,
                $data['fecha_termino_contrato'] ?? null,
                $data['estado_tipo_id'] ?? 1,
                $id
            ]);

            return $result && $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error en Client::update: " . $e->getMessage());
            throw new Exception("Error al actualizar el cliente");
        }
    }

    /**
     * Eliminar cliente (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            // Verificar si tiene proyectos asociados
            if ($this->hasAssociatedProjects($id)) {
                throw new Exception("No se puede eliminar el cliente porque tiene proyectos asociados");
            }

            // Realizar soft delete
            $query = "
                UPDATE {$this->table} SET
                    estado_tipo_id = 3,
                    fecha_modificacion = CURRENT_TIMESTAMP
                WHERE id = ? AND estado_tipo_id != 3
            ";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);

            // También desactivar sus contrapartes
            if ($result && $stmt->rowCount() > 0) {
                $this->deactivateCounterparties($id);
            }

            return $result && $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error en Client::delete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si el RUT ya existe
     */
    public function rutExists(string $rut, int $excludeId = null): bool
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
            error_log("Error en Client::rutExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener contrapartes de un cliente
     */
    public function getCounterparties(int $clientId): array
    {
        try {
            $query = "
                SELECT 
                    cc.*,
                    p.rut as persona_rut,
                    p.nombre as persona_nombre,
                    p.telefono as persona_telefono,
                    et.nombre as estado_nombre
                FROM cliente_contrapartes cc
                JOIN personas p ON cc.persona_id = p.id
                LEFT JOIN estado_tipos et ON cc.estado_tipo_id = et.id
                WHERE cc.cliente_id = ? AND cc.estado_tipo_id != 3
                ORDER BY p.nombre ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$clientId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error en Client::getCounterparties: " . $e->getMessage());
            throw new Exception("Error al obtener las contrapartes del cliente");
        }
    }

    /**
     * Agregar contraparte a un cliente
     */
    public function addCounterpartie(array $data): int
    {
        try {
            $query = "
                INSERT INTO cliente_contrapartes (
                    cliente_id, persona_id, telefono, email, cargo, estado_tipo_id
                ) VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['cliente_id'],
                $data['persona_id'],
                $data['telefono'] ?? null,
                $data['email'] ?? null,
                $data['cargo'] ?? null,
                $data['estado_tipo_id'] ?? 1
            ]);

            return (int) $this->db->lastInsertId();

        } catch (Exception $e) {
            error_log("Error en Client::addCounterpartie: " . $e->getMessage());
            throw new Exception("Error al agregar la contraparte");
        }
    }

    /**
     * Verificar si tiene proyectos asociados
     */
    private function hasAssociatedProjects(int $clientId): bool
    {
        try {
            $query = "SELECT COUNT(*) FROM proyectos WHERE cliente_id = ? AND estado_tipo_id != 3";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$clientId]);

            return $stmt->fetchColumn() > 0;

        } catch (Exception $e) {
            error_log("Error en Client::hasAssociatedProjects: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactivar contrapartes del cliente
     */
    private function deactivateCounterparties(int $clientId): void
    {
        try {
            $query = "
                UPDATE cliente_contrapartes SET
                    estado_tipo_id = 3,
                    fecha_modificacion = CURRENT_TIMESTAMP
                WHERE cliente_id = ? AND estado_tipo_id != 3
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$clientId]);

        } catch (Exception $e) {
            error_log("Error en Client::deactivateCounterparties: " . $e->getMessage());
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
            error_log("Error en Client::getStatusTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar formato de RUT chileno
     */
    public function validateRut(string $rut): bool
    {
        // Limpiar el RUT
        $rut = preg_replace('/[^0-9kK]/', '', $rut);
        
        if (strlen($rut) < 2) {
            return false;
        }

        $body = substr($rut, 0, -1);
        $dv = strtoupper(substr($rut, -1));

        // Calcular dígito verificador
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

        // Formatear con puntos
        $formattedBody = number_format(intval($body), 0, '', '.');

        return $formattedBody . '-' . $dv;
    }
}