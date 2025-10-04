<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Helpers\Security;
use PDO;
use Exception;

class ReportController
{
    private $db;
    private $reportService;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->reportService = new ReportService($db);
    }

    /**
     * Mostrar lista de reportes disponibles
     */
    public function index()
    {
        try {
            // Verificar permisos
            if (!Security::hasPermission('Read')) {
                http_response_code(403);
                include __DIR__ . '/../Views/errors/403.php';
                return;
            }

            // Obtener estadísticas básicas
            $stats = $this->reportService->getBasicStats();

            include __DIR__ . '/../Views/reports/list.php';
        } catch (Exception $e) {
            error_log("Error en ReportController::index: " . $e->getMessage());
            $error = "Error al cargar la página de reportes.";
            include __DIR__ . '/../Views/reports/list.php';
        }
    }

    /**
     * Mostrar formulario para crear reporte personalizado
     */
    public function create()
    {
        try {
            // Verificar permisos
            if (!Security::hasPermission('Write')) {
                http_response_code(403);
                include __DIR__ . '/../Views/errors/403.php';
                return;
            }

            include __DIR__ . '/../Views/reports/create.php';
        } catch (Exception $e) {
            error_log("Error en ReportController::create: " . $e->getMessage());
            $error = "Error al cargar el formulario de reportes.";
            include __DIR__ . '/../Views/reports/create.php';
        }
    }

    /**
     * Generar reporte según los parámetros recibidos
     */
    public function generate()
    {
        try {
            // Verificar permisos
            if (!Security::hasPermission('Write')) {
                http_response_code(403);
                include __DIR__ . '/../Views/errors/403.php';
                return;
            }

            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                $error = "Token de seguridad inválido.";
                include __DIR__ . '/../Views/reports/create.php';
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
                $error = "Debe seleccionar un tipo de reporte.";
                include __DIR__ . '/../Views/reports/create.php';
                return;
            }

            // Generar el reporte
            $reportData = $this->reportService->generateReport($reportType, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'client_id' => $clientId,
                'project_id' => $projectId
            ]);

            // Preparar datos para la vista
            $reportTitle = $this->getReportTitle($reportType);
            $generatedAt = date('Y-m-d H:i:s');

            include __DIR__ . '/../Views/reports/view.php';
        } catch (Exception $e) {
            error_log("Error en ReportController::generate: " . $e->getMessage());
            $error = "Error al generar el reporte: " . $e->getMessage();
            include __DIR__ . '/../Views/reports/create.php';
        }
    }

    /**
     * Exportar reporte en formato especificado
     */
    public function export()
    {
        try {
            // Verificar permisos
            if (!Security::hasPermission('Read')) {
                http_response_code(403);
                return;
            }

            $format = $_GET['format'] ?? 'pdf';
            $reportType = $_GET['type'] ?? '';
            
            // Aquí implementarías la lógica de exportación
            // Por ahora, devolvemos un mensaje simple
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Funcionalidad de exportación en desarrollo.'
            ]);
        } catch (Exception $e) {
            error_log("Error en ReportController::export: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al exportar el reporte.'
            ]);
        }
    }

    /**
     * Obtener título del reporte según el tipo
     */
    private function getReportTitle($reportType)
    {
        $titles = [
            'projects_summary' => 'Resumen de Proyectos',
            'tasks_summary' => 'Resumen de Tareas',
            'users_activity' => 'Actividad de Usuarios',
            'clients_summary' => 'Resumen de Clientes',
            'custom' => 'Reporte Personalizado'
        ];

        return $titles[$reportType] ?? 'Reporte';
    }
}
