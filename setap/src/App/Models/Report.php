<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;

use PDO;
use Exception;

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
            $params[] = !isset($filters['fecha_desde']) || empty($filters['fecha_desde']) ? null : $filters['fecha_desde'];
            $params[] = !isset($filters['fecha_hasta']) || empty($filters['fecha_hasta']) ? null : $filters['fecha_hasta'];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Error fetching stats: " . $e->getMessage());
            return [];
        }
    }
}
