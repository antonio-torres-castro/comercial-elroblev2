<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Constants\AppConstants;
use App\Config\Database;
use PDO;
use Exception;

class ReportController extends BaseController
{
    private $db;
    private $reportService;
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        $this->db = Database::getInstance();
        $this->reportService = new ReportService($this->db);
        $this->permissionService = new PermissionService();
    }

    /**
     * Mostrar formulario para crear reporte personalizado
     */
    public function create()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos - ESTANDARIZADO
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'create_reports')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Datos para la vista - ESTANDARIZADO
            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_CREATE_REPORT,
                'subtitle' => 'Generar reporte personalizado',
                'action' => 'create'
            ];

            require_once __DIR__ . '/../Views/reports/create.php';
        } catch (Exception $e) {
            error_log("Error en ReportController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Generar reporte según los parámetros recibidos
     */
    public function generate()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos - ESTANDARIZADO
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'generate_reports')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_REPORTS);
                return;
            }

            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            // Obtener parámetros del formulario
            $reportType = $_POST['report_type'] ?? '';
            $dateFrom = $_POST['date_from'] ?? '';
            $dateTo = $_POST['date_to'] ?? '';
            $clientId = $_POST['client_id'] ?? null;
            $projectId = $_POST['project_id'] ?? null;

            // Validar parámetros obligatorios
            if (empty($reportType)) {
                $this->redirectWithError(AppConstants::ROUTE_REPORTS . '/create', 'Debe seleccionar un tipo de reporte');
                return;
            }

            // Generar el reporte
            $reportData = $this->reportService->generateReport($reportType, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'client_id' => $clientId,
                'project_id' => $projectId
            ]);

            // Preparar datos para la vista - ESTANDARIZADO
            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_REPORT_GENERATED,
                'subtitle' => $this->getReportTitle($reportType),
                'reportData' => $reportData,
                'reportType' => $reportType,
                'generatedAt' => date('Y-m-d H:i:s'),
                'action' => 'view'
            ];

            require_once __DIR__ . '/../Views/reports/view.php';
        } catch (Exception $e) {
            error_log("Error en ReportController::generate: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_REPORTS . '/create', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Descargar un reporte generado
     */
    public function download()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'view_reports')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $filename = $_GET['file'] ?? '';
            if (empty($filename)) {
                http_response_code(400);
                echo $this->renderError('Archivo no especificado');
                return;
            }

            // Validar que el archivo esté en el directorio de reportes
            $reportPath = __DIR__ . '/../../storage/reports/' . basename($filename);

            if (!file_exists($reportPath)) {
                http_response_code(404);
                echo $this->renderError('Archivo no encontrado');
                return;
            }

            // Configurar headers para descarga
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($reportPath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');

            // Enviar archivo
            readfile($reportPath);
            exit;
        } catch (Exception $e) {
            error_log("Error en ReportController::download: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Listar reportes disponibles - ESTANDARIZADO
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'view_reports')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Obtener reportes generados
            $reports = $this->getGeneratedReports();

            // Datos para la vista - ESTANDARIZADO
            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_SYSTEM_REPORTS,
                'subtitle' => 'Gestión y descarga de reportes generados',
                'reports' => $reports,
                'action' => 'index'
            ];

            require_once __DIR__ . '/../Views/reports/index.php';
        } catch (Exception $e) {
            error_log("Error en ReportController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Generar reporte de usuarios
     */
    public function usersReport()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'generate_reports')) {
                $this->redirectWithError(AppConstants::ROUTE_REPORTS, AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_REPORTS, 'Método no permitido');
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_REPORTS, 'Token CSRF inválido');
                return;
            }

            // Generar reporte de usuarios
            $userData = $this->getUsersData();

            // Crear archivo Excel o CSV
            $filename = $this->generateUsersExcelReport($userData);

            if ($filename) {
                $this->redirectWithSuccess(AppConstants::ROUTE_REPORTS, "Reporte de usuarios generado correctamente: {$filename}");
            } else {
                throw new Exception('Error al generar el archivo del reporte');
            }
        } catch (Exception $e) {
            error_log("Error en ReportController::usersReport: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_REPORTS, 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Generar reporte de proyectos
     */
    public function projectsReport()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'generate_reports')) {
                $this->redirectWithError(AppConstants::ROUTE_REPORTS, AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_REPORTS, 'Método no permitido');
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_REPORTS, 'Token CSRF inválido');
                return;
            }

            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';

            // Obtener datos de proyectos
            $projectData = $this->getProjectsData($startDate, $endDate);

            // Generar archivo
            $filename = $this->generateProjectsExcelReport($projectData, $startDate, $endDate);

            if ($filename) {
                $this->redirectWithSuccess(AppConstants::ROUTE_REPORTS, "Reporte de proyectos generado correctamente: {$filename}");
            } else {
                throw new Exception('Error al generar el archivo del reporte');
            }
        } catch (Exception $e) {
            error_log("Error en ReportController::projectsReport: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_REPORTS, 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Obtener reportes generados del directorio
     */
    private function getGeneratedReports(): array
    {
        $reportsDir = __DIR__ . '/../../storage/reports/';
        $reports = [];

        if (is_dir($reportsDir)) {
            $files = scandir($reportsDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'xlsx') {
                    $reports[] = [
                        'filename' => $file,
                        'size' => filesize($reportsDir . $file),
                        'created' => filemtime($reportsDir . $file)
                    ];
                }
            }
        }

        // Ordenar por fecha de creación (más recientes primero)
        usort($reports, function ($a, $b) {
            return $b['created'] - $a['created'];
        });

        return $reports;
    }

    /**
     * Obtener datos de usuarios para reporte
     */
    private function getUsersData(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    p.nombre as nombre_completo,
                    p.rut,
                    u.email,
                    u.nombre_usuario,
                    ut.nombre as tipo_usuario,
                    et.nombre as estado,
                    u.fecha_Creado,
                    u.fecha_modificacion
                FROM usuarios u
                INNER JOIN personas p ON u.persona_id = p.id
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                INNER JOIN estado_tipos et ON u.estado_tipo_id = et.id
                ORDER BY u.fecha_Creado DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo datos de usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener datos de proyectos para reporte
     */
    private function getProjectsData(string $startDate = '', string $endDate = ''): array
    {
        try {
            $sql = "
                SELECT 
                    p.id,
                    p.nombre as proyecto_nombre,
                    c.nombre as cliente_nombre,
                    p.descripcion,
                    p.fecha_inicio,
                    p.fecha_termino,
                    et.nombre as estado,
                    p.fecha_creacion
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN estado_tipos et ON p.estado_tipo_id = et.id
            ";

            $params = [];

            if (!empty($startDate) && !empty($endDate)) {
                $sql .= " WHERE p.fecha_inicio BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
            }

            $sql .= " ORDER BY p.fecha_creacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo datos de proyectos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generar archivo Excel para reporte de usuarios
     */
    private function generateUsersExcelReport(array $data): ?string
    {
        // Implementación simplificada - en producción usar PhpSpreadsheet
        $filename = 'usuarios_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = __DIR__ . '/../../storage/reports/' . $filename;

        // Crear directorio si no existe
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Headers CSV
        fputcsv($file, [
            'ID',
            'Nombre Completo',
            'RUT',
            'Email',
            'Usuario',
            'Tipo Usuario',
            'Estado',
            'Fecha Creación',
            'Último Acceso'
        ]);

        // Datos
        foreach ($data as $row) {
            fputcsv($file, [
                $row['id'],
                $row['nombre_completo'],
                $row['rut'],
                $row['email'],
                $row['nombre_usuario'],
                $row['tipo_usuario'],
                $row['estado'],
                $row['fecha_Creado'],
                $row['fecha_modificacion']
            ]);
        }

        fclose($file);
        return $filename;
    }

    /**
     * Generar archivo Excel para reporte de proyectos
     */
    private function generateProjectsExcelReport(array $data, string $startDate = '', string $endDate = ''): ?string
    {
        $dateRange = '';
        if (!empty($startDate) && !empty($endDate)) {
            $dateRange = "_{$startDate}_a_{$endDate}";
        }

        $filename = "proyectos{$dateRange}_" . date('Y-m-d_H-i-s') . '.csv';
        $filepath = __DIR__ . '/../../storage/reports/' . $filename;

        // Crear directorio si no existe
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Headers CSV
        fputcsv($file, [
            'ID',
            'Proyecto',
            'Cliente',
            'Descripción',
            'Fecha Inicio',
            'Fecha Término',
            'Estado',
            'Fecha Creación'
        ]);

        // Datos
        foreach ($data as $row) {
            fputcsv($file, [
                $row['id'],
                $row['proyecto_nombre'],
                $row['cliente_nombre'],
                $row['descripcion'],
                $row['fecha_inicio'],
                $row['fecha_termino'],
                $row['estado'],
                $row['fecha_creacion']
            ]);
        }

        fclose($file);
        return $filename;
    }

    /**
     * Obtener título del reporte según el tipo
     */
    private function getReportTitle(string $reportType): string
    {
        $titles = [
            'users' => 'Reporte de Usuarios',
            'projects' => 'Reporte de Proyectos',
            'tasks' => 'Reporte de Tareas',
            'clients' => 'Reporte de Clientes',
            'general' => 'Reporte General del Sistema'
        ];

        return $titles[$reportType] ?? 'Reporte Personalizado';
    }
}
