<?php

namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

/**
 * Servicio especializado en filtros y búsquedas
 * Responsabilidad única: Gestionar filtros de datos
 */
class FilterService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Filtrar usuarios con criterios específicos
     */
    public function filterUsers(array $filters = []): array
    {
        try {
            $sql = "
                SELECT u.id, u.nombre_usuario, u.email, u.fecha_Creado, u.cliente_id,
                       p.nombre as nombre_completo, p.rut, p.telefono, p.direccion,
                       ut.nombre as rol, ut.id as usuario_tipo_id,
                       et.nombre as estado,
                       c.razon_social as cliente_nombre
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                INNER JOIN estado_tipos et ON u.estado_tipo_id = et.id
                LEFT JOIN clientes c ON u.cliente_id = c.id
            ";

            $whereConditions = [];
            $params = [];

            // Filtro por tipo de usuario
            if (!empty($filters['usuario_tipo_id'])) {
                $whereConditions[] = "u.usuario_tipo_id = ?";
                $params[] = $filters['usuario_tipo_id'];
            }

            // Filtro por estado
            if (!empty($filters['estado_tipo_id'])) {
                $whereConditions[] = "u.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            // Filtro por cliente
            if (!empty($filters['cliente_id'])) {
                $whereConditions[] = "u.cliente_id = ?";
                $params[] = $filters['cliente_id'];
            }

            // Filtro por búsqueda de texto
            if (!empty($filters['search'])) {
                $whereConditions[] = "(p.nombre LIKE ? OR p.rut LIKE ? OR u.email LIKE ? OR u.nombre_usuario LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }

            // Filtro por fecha de creación
            if (!empty($filters['fecha_desde'])) {
                $whereConditions[] = "DATE(u.fecha_Creado) >= ?";
                $params[] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $whereConditions[] = "DATE(u.fecha_Creado) <= ?";
                $params[] = $filters['fecha_hasta'];
            }

            // Aplicar condiciones WHERE
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }

            // Ordenamiento
            $orderBy = $filters['order_by'] ?? 'u.fecha_Creado';
            $orderDirection = $filters['order_direction'] ?? 'DESC';
            $sql .= " ORDER BY {$orderBy} {$orderDirection}";

            // Paginación
            if (isset($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = (int)$filters['limit'];

                if (isset($filters['offset'])) {
                    $sql .= " OFFSET ?";
                    $params[] = (int)$filters['offset'];
                }
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error filtrando usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar usuarios con filtros aplicados
     */
    public function countFilteredUsers(array $filters = []): int
    {
        try {
            $sql = "
                SELECT COUNT(*)
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                INNER JOIN estado_tipos et ON u.estado_tipo_id = et.id
                LEFT JOIN clientes c ON u.cliente_id = c.id
            ";

            $whereConditions = [];
            $params = [];

            // Aplicar los mismos filtros que en filterUsers
            if (!empty($filters['usuario_tipo_id'])) {
                $whereConditions[] = "u.usuario_tipo_id = ?";
                $params[] = $filters['usuario_tipo_id'];
            }

            if (!empty($filters['estado_tipo_id'])) {
                $whereConditions[] = "u.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            if (!empty($filters['cliente_id'])) {
                $whereConditions[] = "u.cliente_id = ?";
                $params[] = $filters['cliente_id'];
            }

            if (!empty($filters['search'])) {
                $whereConditions[] = "(p.nombre LIKE ? OR p.rut LIKE ? OR u.email LIKE ? OR u.nombre_usuario LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }

            if (!empty($filters['fecha_desde'])) {
                $whereConditions[] = "DATE(u.fecha_Creado) >= ?";
                $params[] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $whereConditions[] = "DATE(u.fecha_Creado) <= ?";
                $params[] = $filters['fecha_hasta'];
            }

            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error contando usuarios filtrados: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Filtrar clientes disponibles
     */
    public function getAvailableClients(array $filters = []): array
    {
        try {
            $sql = "
                SELECT id, razon_social, rut, email, telefono
                FROM clientes 
                WHERE estado_tipo_id IN (1, 2)
            ";

            $params = [];

            // Filtro por búsqueda
            if (!empty($filters['search'])) {
                $sql .= " AND (razon_social LIKE ? OR rut LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params = [$searchTerm, $searchTerm];
            }

            $sql .= " ORDER BY razon_social";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo clientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de usuario para filtros
     */
    public function getUserTypes(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion 
                FROM usuario_tipos 
                ORDER BY id
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo tipos de usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estados para filtros
     */
    public function getEstadosTipo(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion 
                FROM estado_tipos 
                WHERE id IN (1, 2, 3, 4) 
                ORDER BY id
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo estados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Construir filtros para URL
     */
    public function buildFilterParams(array $filters): string
    {
        $params = [];
        
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $params[] = urlencode($key) . '=' . urlencode($value);
            }
        }
        
        return !empty($params) ? '?' . implode('&', $params) : '';
    }

    /**
     * Limpiar filtros vacíos
     */
    public function cleanFilters(array $filters): array
    {
        return array_filter($filters, function($value) {
            return !empty($value) && $value !== '';
        });
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function getUserStats(): array
    {
        try {
            $stats = [];

            // Total de usuarios
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE estado_tipo_id != 4");
            $stmt->execute();
            $stats['total_users'] = $stmt->fetchColumn();

            // Usuarios por tipo
            $stmt = $this->db->prepare("
                SELECT ut.nombre, COUNT(*) as cantidad
                FROM usuarios u
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE u.estado_tipo_id != 4
                GROUP BY ut.id, ut.nombre
                ORDER BY cantidad DESC
            ");
            $stmt->execute();
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Usuarios por estado
            $stmt = $this->db->prepare("
                SELECT et.nombre, COUNT(*) as cantidad
                FROM usuarios u
                INNER JOIN estado_tipos et ON u.estado_tipo_id = et.id
                GROUP BY et.id, et.nombre
                ORDER BY cantidad DESC
            ");
            $stmt->execute();
            $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Usuarios creados en el último mes
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM usuarios 
                WHERE fecha_Creado >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                AND estado_tipo_id != 4
            ");
            $stmt->execute();
            $stats['recent_users'] = $stmt->fetchColumn();

            return $stats;
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [];
        }
    }
}
