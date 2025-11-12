<?php

namespace App\Services;

use App\Helpers\Logger;

use DateTime;
use DateInterval;

use PDO;
use Exception;

class ReportService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener estadísticas básicas para la página principal de reportes
     */
    public function getBasicStats()
    {
        try {
            $stats = [];

            // Total de proyectos
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM proyectos Where estado_tipo_id != 4");
            $stats['total_projects'] = $stmt->fetchColumn();

            // Total de tareas
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM tareas Where estado_tipo_id != 4");
            $stats['total_tasks'] = $stmt->fetchColumn();

            // Total de usuarios
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE estado_tipo_id = 2");
            $stats['total_users'] = $stmt->fetchColumn();

            // Total de clientes
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM clientes WHERE estado_tipo_id = 2");
            $stats['total_clients'] = $stmt->fetchColumn();

            return $stats;
        } catch (Exception $e) {
            Logger::error("ReportService::getBasicStats: " . $e->getMessage());
            return [
                'total_projects' => 0,
                'total_tasks' => 0,
                'total_users' => 0,
                'total_clients' => 0
            ];
        }
    }

    /**
     * Generar reporte según el tipo y parámetros especificados
     */
    public function generateReport($reportType, $parameters = [])
    {
        try {
            switch ($reportType) {
                case 'projects_summary':
                    return $this->generateProjectsSummary($parameters);

                case 'tasks_summary':
                    return $this->generateTasksSummary($parameters);

                case 'users_activity':
                    return $this->generateUsersActivity($parameters);

                case 'clients_summary':
                    return $this->generateClientsSummary($parameters);

                case 'custom':
                    return $this->generateCustomReport($parameters);

                default:
                    throw new Exception("Tipo de reporte no válido: $reportType");
            }
        } catch (Exception $e) {
            Logger::error("ReportService::generateReport: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar reporte de resumen de proyectos
     * Todo: Agregar recursos asignados al proyecto y progreso (revisar como se hace en la vista de proyecto)
     */
    private function generateProjectsSummary($parameters)
    {
        $sql = "SELECT 
                    p.id,
                    c.razon_social as cliente_nombre,
                    p.fecha_inicio,
                    p.fecha_fin,
                    et.nombre as estado,
                    tt.nombre as tipo_tarea,
                    p.direccion
                FROM proyectos p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN estado_tipos et ON p.estado_tipo_id = et.id
                LEFT JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                WHERE 1=1";

        $params = [];

        // Filtros de fecha
        if (!empty($parameters['date_from'])) {
            $sql .= " AND p.fecha_inicio >= ?";
            $params[] = $parameters['date_from'];
        }

        if (!empty($parameters['date_to'])) {
            $sql .= " AND p.fecha_inicio <= ?";
            $params[] = $parameters['date_to'];
        }

        // Filtro por cliente
        if (!empty($parameters['client_id'])) {
            $sql .= " AND p.cliente_id = ?";
            $params[] = $parameters['client_id'];
        }

        $sql .= " ORDER BY p.fecha_inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular resumen
        $summary = [
            'total_projects' => count($data),
            'active_projects' => 0,
            'completed_projects' => 0,
            'average_duration' => 0
        ];
        $suma_duracion = 0;
        foreach ($data as $project) {
            if (strpos(strtolower($project['estado']), 'activo') !== false) {
                $summary['active_projects']++;
            }

            if (
                strpos(strtolower($project['estado']), 'terminado') !== false ||
                strpos(strtolower($project['estado']), 'aprobado') !== false
            ) {
                $summary['completed_projects']++;
            }
            $di = new DateTime($project['fecha_inicio']);
            $df = new DateTime($project['fecha_fin']);
            $intervalo = $di->diff($df);
            $suma_duracion += $intervalo->days;
        }

        $summary['average_duration'] = $suma_duracion / $summary['total_projects'];

        return [
            'title' => 'Resumen Proyectos',
            'summary' => $summary,
            'data' => $data,
            'total_records' => count($data)
        ];
    }

    /**
     * Generar reporte de resumen de tareas
     * ToDo: se debe indicar el periodo del reporte fecha inicio y fin.
     * ToDo: se agregar un filtro por proyecto
     * ToDo: solo desplegar los proyectos relacionados al usuario
     * ToDo: El detalle solo debe tener el proyecto y su estadistica a la fecha de hoy
     * ToDo: Hacer un Query para el Summary
     * ToDo: Hacer un Segundo Query para entregar las tareas pendientes y las tareas del dia
     */
    private function generateTasksSummary($parameters)
    {
        $fecha = $parameters['date_to'] ?? date('Y-m-d');
        $projectId = !empty($parameters['project_id']) ? (int)$parameters['project_id'] : 0;

        try {
            // --- 1) Resumen de totales (posicionales) ---
            $sqlTotal = "
            SELECT
                COUNT(pt.id) AS total,
                SUM(pt.estado_tipo_id IN (2,5,6,7)
                    AND pt.fecha_inicio < ?
                    AND (? = 0 OR pt.proyecto_id = ?)
                ) AS pending,
                SUM(pt.estado_tipo_id = 8
                    AND pt.fecha_inicio <= ?
                    AND (? = 0 OR pt.proyecto_id = ?)
                ) AS complete,
                SUM(pt.estado_tipo_id IN (2,5,6,7)
                    AND pt.fecha_inicio = ?
                    AND (? = 0 OR pt.proyecto_id = ?)
                ) AS progress
            FROM proyecto_tareas pt
            WHERE pt.fecha_inicio <= ?
              AND (? = 0 OR pt.proyecto_id = ?)
              AND pt.estado_tipo_id IN (2,3,5,6,7,8);
        ";

            // Parámetros para sqlTotal (orden importa)
            $paramsTotal = [
                // pending: fecha, projectId, projectId
                $fecha,
                $projectId,
                $projectId,
                // complete: fecha, projectId, projectId
                $fecha,
                $projectId,
                $projectId,
                // progress: fecha, projectId, projectId
                $fecha,
                $projectId,
                $projectId,
                // WHERE: fecha, projectId, projectId
                $fecha,
                $projectId,
                $projectId
            ];

            $stmt = $this->db->prepare($sqlTotal);
            $stmt->execute($paramsTotal);
            $summaryData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // --- 2) Detalle de tareas (posicionales) ---
            $sqlTasks = "
            SELECT
                pt.id, t.nombre, DATE_FORMAT(pt.fecha_inicio, '%Y-%m-%d') AS inicio, pt.duracion_horas AS dura,
                pt.prioridad, e.nombre AS estado, c.razon_social,
                p.fecha_inicio, p.fecha_fin, tt.nombre AS tipo_tarea
            FROM proyecto_tareas pt
            INNER JOIN estado_tipos e ON e.id = pt.estado_tipo_id
            INNER JOIN proyectos p ON p.id = pt.proyecto_id
            INNER JOIN clientes c ON c.id = p.cliente_id
            INNER JOIN tareas t ON t.id = pt.tarea_id
            INNER JOIN tarea_tipos tt ON tt.id = p.tarea_tipo_id
            WHERE pt.estado_tipo_id IN (2,5,6,7)
              AND (pt.fecha_inicio <= ?)
              AND (? = 0 OR pt.proyecto_id = ?);
        ";

            $paramsTasks = [
                $fecha,
                $projectId,
                $projectId
            ];

            $stmt = $this->db->prepare($sqlTasks);
            $stmt->execute($paramsTasks);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // --- 3) Construcción de resultado ---
            $summary = [
                'total_tasks'       => (int)($summaryData['total'] ?? 0),
                'pending_tasks'     => (int)($summaryData['pending'] ?? 0),
                'completed_tasks'   => (int)($summaryData['complete'] ?? 0),
                'in_progress_tasks' => (int)($summaryData['progress'] ?? 0),
            ];

            return [
                'summary'       => $summary,
                'data'          => $data,
                'total_records' => $summary['total_tasks'],
            ];
        } catch (\PDOException $e) {
            // Logging detallado de depuración
            error_log('PDOException in generateTasksSummary: ' . $e->getMessage());
            error_log('Last SQL: ' . ($stmt->queryString ?? 'n/a'));
            // Si quieres, loggear también parámetros (cuidado con datos sensibles)
            error_log('paramsTotal: ' . json_encode($paramsTotal ?? []));
            error_log('paramsTasks: ' . json_encode($paramsTasks ?? []));
            throw $e;
        }
    }



    /**
     * Generar reporte de actividad de usuarios
     */
    private function generateUsersActivity($parameters)
    {
        $sql = "SELECT 
                    u.id,
                    u.nombre_usuario as username,
                    p.nombre as nombre_completo,
                    u.email,
                    ut.nombre as rol,
                    u.fecha_Creado
                FROM usuarios u
                LEFT JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                LEFT JOIN personas p ON u.persona_id = p.id
                WHERE 1=1";

        $params = [];

        // Filtros de fecha
        if (!empty($parameters['date_from'])) {
            $sql .= " AND u.fecha_Creado >= ?";
            $params[] = $parameters['date_from'];
        }

        if (!empty($parameters['date_to'])) {
            $sql .= " AND u.fecha_Creado <= ?";
            $params[] = $parameters['date_to'];
        }

        $sql .= " ORDER BY u.fecha_Creado DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular resumen
        $summary = [
            'total_users' => count($data),
            'active_users' => 0,
            'recent_logins' => 0,
            'new_users' => 0
        ];

        $weekAgo = date('Y-m-d', strtotime('-1 week'));
        $monthAgo = date('Y-m-d', strtotime('-1 month'));

        foreach ($data as $user) {
            if ($user['activo']) {
                $summary['active_users']++;
            }

            if ($user['fecha_Creado'] && $user['fecha_Creado'] >= $weekAgo) {
                $summary['recent_logins']++;
            }

            if ($user['fecha_Creado'] && $user['fecha_Creado'] >= $monthAgo) {
                $summary['new_users']++;
            }
        }

        return [
            'summary' => $summary,
            'data' => $data,
            'total_records' => count($data)
        ];
    }

    /**
     * Generar reporte de resumen de clientes
     */
    private function generateClientsSummary(?array $parameters = [])
    {
        $sql = "SELECT 
                    c.id,
                    c.razon_social as nombre,
                    c.rut,
                    c.email,
                    c.telefono,
                    (CASE WHEN c.estado_tipo_id = 2 THEN 1 ELSE 0 END) as activo,
                    c.fecha_Creado as fecha_creacion,
                    COUNT(p.id) as total_proyectos
                FROM clientes c
                LEFT JOIN proyectos p ON c.id = p.cliente_id and p.estado_tipo_id = 2";

        $params = [];

        // Filtros de fecha
        if (!empty($parameters['date_to'])) {
            $sql .= " AND c.fecha_termino_contrato <= ?";
            $params[] = $parameters['date_to'];
        }

        $sql .= " GROUP BY c.id ORDER BY c.razon_social";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular resumen
        $summary = [
            'total_clients' => count($data),
            'active_clients' => 0,
            'clients_with_projects' => 0,
            'new_clients' => 0
        ];

        $monthAgo = date('Y-m-d', strtotime('-1 month'));

        foreach ($data as $client) {
            if ($client['activo']) {
                $summary['active_clients']++;
            }

            if ($client['total_proyectos'] > 0) {
                $summary['clients_with_projects']++;
            }

            if ($client['fecha_creacion'] && $client['fecha_creacion'] >= $monthAgo) {
                $summary['new_clients']++;
            }
        }

        return [
            'summary' => $summary,
            'data' => $data,
            'total_records' => count($data)
        ];
    }

    /**
     * Generar reporte personalizado
     */
    private function generateCustomReport(?array $parametros = [])
    {
        // Implementación básica para reporte personalizado
        // En una implementación real, esto podría ser mucho más complejo
        return [
            'summary' => [
                'message' => 'Reporte personalizado',
                'status' => 'En desarrollo',
                'parameters' => count($parametros),
                'generated' => date('Y-m-d H:i:s')
            ],
            'data' => [
                [
                    'parameter' => 'Tipo de reporte',
                    'value' => 'Personalizado',
                    'status' => 'Configurado'
                ],
                [
                    'parameter' => 'Fecha de generación',
                    'value' => date('d/m/Y H:i:s'),
                    'status' => 'Actual'
                ]
            ],
            'total_records' => 2
        ];
    }
}
