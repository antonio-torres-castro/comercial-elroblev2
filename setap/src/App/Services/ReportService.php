<?php

namespace App\Services;

use App\Helpers\Logger;

use DateTime;
use DateInterval;

use PDO;
use PDOException;
use Exception;

class ReportService
{
    private const API_MAX_LIMIT = 500;

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
     * Autentica un consumidor API contra usuarios activos del sistema.
     */
    public function authenticateApiConsumer(string $identifier, string $password): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT
                    u.id,
                    u.nombre_usuario,
                    u.email,
                    u.clave_hash,
                    u.estado_tipo_id,
                    u.usuario_tipo_id,
                    COALESCE(u.proveedor_id, 0) AS proveedor_id,
                    COALESCE(u.cliente_id, 0) AS cliente_id,
                    p.estado_tipo_id AS persona_estado,
                    p.nombre AS nombre_completo
                FROM usuarios u
                INNER JOIN personas p ON p.id = u.persona_id
                WHERE (u.nombre_usuario = :identifier OR u.email = :identifier2)
                  AND u.estado_tipo_id = 2
                  AND p.estado_tipo_id = 2
                LIMIT 1
            ");
            $stmt->execute([':identifier' => $identifier, ':identifier2' => $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, (string)$user['clave_hash'])) {
                return [
                    'success' => false,
                    'status' => 401,
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Usuario o clave invalidos.'
                ];
            }

            if ((int)$user['proveedor_id'] <= 0) {
                return [
                    'success' => false,
                    'status' => 403,
                    'code' => 'PROVIDER_REQUIRED',
                    'message' => 'El usuario autenticado no tiene proveedor asociado.'
                ];
            }

            unset($user['clave_hash']);

            return [
                'success' => true,
                'user' => $user
            ];
        } catch (PDOException $e) {
            Logger::error("ReportService::authenticateApiConsumer: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 503,
                'code' => 'DATABASE_UNAVAILABLE',
                'message' => 'No fue posible validar las credenciales contra la base de datos.'
            ];
        } catch (Exception $e) {
            Logger::error("ReportService::authenticateApiConsumer: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'code' => 'AUTHENTICATION_ERROR',
                'message' => 'Ocurrio un error inesperado al validar las credenciales.'
            ];
        }
    }

    /**
     * Entrega tareas de un proyecto acotadas al proveedor del consumidor API.
     */
    public function getProjectTasksForApi(array $user, array $filters): array
    {
        $limit = min(max((int)($filters['limit'] ?? 100), 1), self::API_MAX_LIMIT);
        $offset = max((int)($filters['offset'] ?? 0), 0);

        $sql = "
            WITH RECURSIVE espacio_ancestros AS (
                SELECT
                    e.id AS espacio_id,
                    e.id AS ancestro_id,
                    e.nombre AS ancestro_nombre,
                    e.espacio_padre_id,
                    0 AS profundidad
                FROM espacios e

                UNION ALL

                SELECT
                    ea.espacio_id,
                    ep.id AS ancestro_id,
                    ep.nombre AS ancestro_nombre,
                    ep.espacio_padre_id,
                    ea.profundidad + 1 AS profundidad
                FROM espacio_ancestros ea
                INNER JOIN espacios ep ON ep.id = ea.espacio_padre_id
                WHERE ea.profundidad < 20
            ),
            espacio_raiz AS (
                SELECT espacio_id, ancestro_id, ancestro_nombre
                FROM (
                    SELECT
                        ea.*,
                        ROW_NUMBER() OVER (
                            PARTITION BY ea.espacio_id
                            ORDER BY ea.profundidad DESC
                        ) AS rn
                    FROM espacio_ancestros ea
                ) ranked
                WHERE rn = 1
            )
            SELECT
                pt.id,
                pt.tarea_id,
                t.nombre AS tarea_nombre,
                t.descripcion,
                pt.fecha_inicio,
                pt.duracion_horas,
                pt.prioridad,
                pt.espacio_id,
                e.nombre AS espacio_nombre,
                e.codigo AS espacio_codigo,
                e.nivel AS espacio_nivel,
                e.orden AS espacio_orden,
                ep.nombre AS espacio_padre_nombre,
                d.id AS direccion_id,
                d.calle AS direccion_calle,
                d.numero AS direccion_numero,
                d.letra AS direccion_letra,
                co.nombre AS direccion_comuna,
                prv.nombre AS direccion_provincia,
                rg.nombre AS direccion_region,
                er.ancestro_id AS espacio_padre_mas_alto_id,
                er.ancestro_nombre AS espacio_padre_mas_alto_nombre,
                pt.fecha_Creado,
                p.id AS proyecto_id,
                CONCAT(c.razon_social, '.', DATE_FORMAT(p.fecha_inicio, '%Y-%m-%d'), '.', DATE_FORMAT(p.fecha_fin, '%Y-%m-%d')) AS proyecto_nombre,
                c.razon_social AS cliente_nombre,
                tt.nombre AS tipo_tarea,
                et.nombre AS estado,
                et.id AS estado_tipo_id,
                plan.nombre_usuario AS planificador_nombre,
                exec.nombre_usuario AS ejecutor_nombre,
                super.nombre_usuario AS supervisor_nombre
            FROM proyecto_tareas pt
            INNER JOIN tareas t ON t.id = pt.tarea_id
            INNER JOIN proyectos p ON p.id = pt.proyecto_id
            INNER JOIN clientes c ON c.id = p.cliente_id
            INNER JOIN tarea_tipos tt ON tt.id = p.tarea_tipo_id
            INNER JOIN estado_tipos et ON et.id = pt.estado_tipo_id
            INNER JOIN usuarios plan ON plan.id = pt.planificador_id
            LEFT JOIN usuarios exec ON exec.id = pt.ejecutor_id
            LEFT JOIN usuarios super ON super.id = pt.supervisor_id
            LEFT JOIN espacios e ON e.id = pt.espacio_id
            LEFT JOIN espacios ep ON ep.id = e.espacio_padre_id
            LEFT JOIN espacio_raiz er ON er.espacio_id = e.id
            LEFT JOIN direcciones d ON d.id = e.direccion_id
            LEFT JOIN comunas co ON co.id = d.comuna_id
            LEFT JOIN provincia prv ON prv.id = co.provincia_id
            LEFT JOIN regiones rg ON rg.id = prv.region_id
            WHERE pt.proyecto_id = :proyecto_id
              AND p.proveedor_id = :proveedor_id
              AND pt.fecha_inicio >= :fecha_desde
              AND pt.fecha_inicio < DATE_ADD(:fecha_hasta, INTERVAL 1 DAY)
              AND EXISTS (
                  SELECT 1
                  FROM proyecto_usuarios_grupo pug
                  WHERE pug.proyecto_id = p.id
                    AND pug.usuario_id = :usuario_id
                    AND pug.estado_tipo_id = 2
                    AND pug.grupo_id BETWEEN 1 AND 5
              )
            ORDER BY
                pt.proyecto_id ASC,
                COALESCE(d.id, 0) ASC,
                COALESCE(er.ancestro_nombre, e.nombre, 'Sin espacio') ASC,
                COALESCE(e.nivel, 999999) ASC,
                COALESCE(e.orden, 999999) ASC,
                pt.fecha_inicio ASC,
                pt.id ASC
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':proyecto_id', (int)$filters['proyecto_id'], PDO::PARAM_INT);
            $stmt->bindValue(':proveedor_id', (int)$user['proveedor_id'], PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', (int)$user['id'], PDO::PARAM_INT);
            $stmt->bindValue(':fecha_desde', $filters['fecha_desde']);
            $stmt->bindValue(':fecha_hasta', $filters['fecha_hasta']);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'data' => $rows,
                'meta' => [
                    'total_records' => count($rows),
                    'limit' => $limit,
                    'offset' => $offset,
                    'proyecto_id' => (int)$filters['proyecto_id'],
                    'fecha_desde' => $filters['fecha_desde'],
                    'fecha_hasta' => $filters['fecha_hasta'],
                    'proveedor_id' => (int)$user['proveedor_id']
                ]
            ];
        } catch (PDOException $e) {
            Logger::error("ReportService::getProjectTasksForApi: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 503,
                'code' => 'DATABASE_UNAVAILABLE',
                'message' => 'No fue posible recuperar las tareas del proyecto desde la base de datos.'
            ];
        } catch (Exception $e) {
            Logger::error("ReportService::getProjectTasksForApi: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'code' => 'REPORT_ERROR',
                'message' => 'Ocurrio un error inesperado al recuperar el reporte.'
            ];
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

        if (!empty($parameters['date_to'])) {
            $sql .= " AND p.fecha_inicio <= ?";
            $params[] = $parameters['date_to'];
        }

        // Filtro por cliente
        if (!empty($parameters['client_id'])) {
            $sql .= " AND p.cliente_id = ?";
            $params[] = $parameters['client_id'];
        }

        if (!empty($parameters['proveedor_id'])) {
            $sql .= " AND p.proveedor_id = ?";
            $params[] = $parameters['proveedor_id'];
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
        $proveedorId = !empty($parameters['proveedor_id']) ? (int)$parameters['proveedor_id'] : 0;

        try {
            // --- 1) Resumen de totales (posicionales) ---
            $sqlTotal = "SELECT
                COUNT(pt.id) AS total,
                SUM(pt.estado_tipo_id IN (2,5,6,7)
                    AND pt.fecha_inicio < ?
                    AND (? = 0 OR pt.proyecto_id = ?)
                    AND (? = 0 OR p.proveedor_id = ?)
                ) AS pending,
                SUM(pt.estado_tipo_id = 8
                    AND pt.fecha_inicio <= ?
                    AND (? = 0 OR pt.proyecto_id = ?)
                    AND (? = 0 OR p.proveedor_id = ?)
                ) AS complete,
                SUM(pt.estado_tipo_id IN (5,6,7)
                    AND pt.fecha_inicio <= ?
                    AND (? = 0 OR pt.proyecto_id = ?)
                    AND (? = 0 OR p.proveedor_id = ?)
                ) AS progress
                  FROM proyecto_tareas pt
            INNER JOIN proyectos       p  ON pt.proyecto_id = p.id
            WHERE pt.fecha_inicio <= ?
              AND (? = 0 OR pt.proyecto_id = ?)
              AND (? = 0 OR p.proveedor_id = ?)
              AND pt.estado_tipo_id IN (2,3,5,6,7,8);
        ";

            // Parámetros para sqlTotal (orden importa)
            $paramsTotal = [
                // pending: fecha, projectId, projectId
                $fecha,
                $projectId,
                $projectId,
                $proveedorId,
                $proveedorId,
                // complete: fecha, projectId, projectId
                $fecha,
                $projectId,
                $projectId,
                $proveedorId,
                $proveedorId,
                // progress: fecha, projectId, projectId
                $fecha,
                $projectId,
                $projectId,
                $proveedorId,
                $proveedorId,
                // WHERE: fecha, projectId, projectId
                $fecha,
                $projectId,
                $projectId,
                $proveedorId,
                $proveedorId,
            ];

            $stmt = $this->db->prepare($sqlTotal);
            $stmt->execute($paramsTotal);
            $summaryData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // --- 2) Detalle de tareas (posicionales) ---
            $sqlTasks = "SELECT count(x.id) recurrencias, x.nombre,
                DATE_FORMAT( min(x.fecha_inicio) , '%Y-%m-%d') AS inicio, 
                avg(x.duracion_horas) AS dura,
                x.prioridad, x.estado, x.razon_social,
                DATE_FORMAT( max(x.fecha_fin) , '%Y-%m-%d') AS fin,
                x.atraso,
                x.categoria
            FROM (SELECT pt.id, t.nombre,
                pt.fecha_inicio, 
                pt.duracion_horas,
                pt.prioridad, 
                e.nombre AS estado, 
                c.razon_social,
                pt.fecha_fin,
                case when (pt.fecha_fin < ? and pt.estado_tipo_id < 8) then 'Si' else '--' end AS atraso,
                tc.nombre AS categoria
            FROM proyecto_tareas pt
            INNER JOIN estado_tipos e ON e.id = pt.estado_tipo_id
            INNER JOIN proyectos p ON p.id = pt.proyecto_id
            INNER JOIN clientes c ON c.id = p.cliente_id
            INNER JOIN tareas t ON t.id = pt.tarea_id
            INNER JOIN tarea_categorias tc ON tc.id = t.tarea_categoria_id
            WHERE pt.estado_tipo_id IN (2,5,6,7,8)
              AND (pt.fecha_inicio <= ?)
              AND (? = 0 OR pt.proyecto_id = ?)
              AND (? = 0 OR p.proveedor_id = ?)
			) x
            Group by x.nombre, x.prioridad, x.estado, x.razon_social, x.categoria, x.atraso;
        ";

            $paramsTasks = [
                $fecha,
                $fecha,
                $projectId,
                $projectId,
                $proveedorId,
                $proveedorId
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

        if (!empty($parameters['proveedor_id'])) {
            $sql .= " WHERE c.proveedor_id = ?";
            $params[] = $parameters['proveedor_id'];
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
