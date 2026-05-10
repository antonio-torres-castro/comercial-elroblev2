<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;

use PDO;
use Exception;
use PDOException;

class Report
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get Stats.
     *
     * @return array An array of reports.
     */
    public function getStats(array $filters = []): array
    {
        try {
            $sql = "CALL stats_reports_projects(?, ?, ?, ?);";
            $params = [];

            // Aplicar filtros
            $params[] = !isset($filters['cliente_id']) || empty($filters['cliente_id']) ? 0 : $filters['cliente_id'];
            $params[] = !isset($filters['proveedor_id']) || empty($filters['proveedor_id']) ? 0 : $filters['proveedor_id'];
            $fechaDesde = $filters['fecha_desde'] ?? $filters['fecha_inicio'] ?? null;
            $fechaHasta = $filters['fecha_hasta'] ?? $filters['fecha_fin'] ?? null;

            $params[] = empty($fechaDesde) ? null : $fechaDesde;
            $params[] = empty($fechaHasta) ? null : $fechaHasta;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Error fetching stats: " . $e->getMessage());
            return [];
        }
    }

    public function getSuppliers(array $filters = []): array
    {
        try {
            $filtroProveedor = "";
            if (isset($filters['proveedor_id']) && is_numeric($filters['proveedor_id'])) {
                $filtroProveedor = "AND id = " . (int)$filters['proveedor_id'];
            }
            $stmt = $this->db->prepare("SELECT id, razon_social as nombre, rut FROM proveedores
                                        WHERE estado_tipo_id != 4
                                        $filtroProveedor
                                        ORDER BY razon_social ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error('ModelTask::getSuppliers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener proyectos disponibles
     */
    public function getProjects(?array $filters = []): array
    {
        $uti = $filters['current_usuario_tipo_id'] ?? 0;
        $myFilters = [];

        try {
            $sql = "SELECT DISTINCT p.id, 
                                    CONCAT(c.razon_social, ' (', p.fecha_inicio, '.', p.fecha_fin, ')') as nombre, 
                                    c.razon_social as cliente_nombre, p.proveedor_id
                    FROM proyectos p 
                    INNER JOIN clientes c ON p.cliente_id = c.id ";

            if ($uti == 1 || $uti == 2) {
                $sql .= PHP_EOL . " WHERE p.estado_tipo_id IN (1, 2, 5)";
            } else {
                $sql .= PHP_EOL . " WHERE p.estado_tipo_id = 2";
            }

            $sql .= PHP_EOL . " AND EXISTS (SELECT 1 FROM proyecto_usuarios_grupo pug WHERE pug.proyecto_id = p.id AND pug.usuario_id = ? AND pug.estado_tipo_id = 2 AND pug.grupo_id in (1, 2, 3, 4, 5, 7)) ";
            $myFilters[] = $filters['current_usuario_id'];

            if (!empty($filters['proveedor_id'])) {
                $sql .= PHP_EOL . " AND p.proveedor_id = ? ";
                $myFilters[] = $filters['proveedor_id'];
            }

            $sql .= PHP_EOL . " ORDER BY c.razon_social";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($myFilters);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getProjects: " . $e->getMessage());
            return [];
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
                INSERT INTO usuario_logs (usuario_id, tipo_registro, fecha, IP)
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
