<?php
namespace App\Controllers;

use App\Models\ProyectoFeriado;
use App\Models\Project;
use App\Helpers\Security;
use App\Constants\AppConstants;
use PDO;

class ProyectoFeriadoController extends BaseController
{
    private $proyectoFeriadoModel;
    private $projectModel;

    public function __construct()
    {
        // Verificar autenticación
        if (!Security::isAuthenticated()) {
            $this->redirectToLogin();
            return;
        }

        $this->proyectoFeriadoModel = new ProyectoFeriado();
        $this->projectModel = new Project();
    }

    /**
     * Mostrar vista principal del mantenedor de feriados
     */
    public function index()
    {
        $projectId = $_GET['proyecto_id'] ?? null;
        if (!$projectId) {
            $this->redirectToRoute(AppConstants::ROUTE_PROJECTS);
            return;
        }

        // Obtener información del proyecto
        $project = $this->projectModel->find((int)$projectId);
        if (!$project) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_PROJECT_NOT_FOUND);
            return;
        }

        // Obtener feriados del proyecto
        $feriados = $this->proyectoFeriadoModel->getByProject((int)$projectId);

        // Obtener estadísticas
        $stats = $this->proyectoFeriadoModel->getProjectHolidayStats((int)$projectId);

        // Cargar vista
        $title = "Gestión de Feriados - {$project['cliente_nombre']}";
        $data = [
            'project' => $project,
            'feriados' => $feriados,
            'stats' => $stats,
            'title' => $title
        ];

        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/proyecto-feriados/index.php';
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    /**
     * Crear feriados masivamente por días de la semana
     */
    public function createMasivo()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            // Obtener datos
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $diasSemana = $_POST['dias'] ?? [];
            $fechaInicio = $_POST['fecha_inicio'] ?? '';
            $fechaFin = $_POST['fecha_fin'] ?? '';
            $indIrrenunciable = (int)($_POST['irrenunciable'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validaciones
            if (!$projectId || empty($diasSemana) || !$fechaInicio || !$fechaFin) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                return;
            }

            if (strtotime($fechaInicio) > strtotime($fechaFin)) {
                echo json_encode(['success' => false, 'message' => 'La fecha de inicio debe ser menor a la fecha fin']);
                return;
            }

            // Convertir días a array de enteros
            $diasSemana = array_map('intval', $diasSemana);

            // Crear feriados
            $result = $this->proyectoFeriadoModel->createRecurrentHolidays(
                $projectId,
                $diasSemana,
                $fechaInicio,
                $fechaFin,
                $indIrrenunciable,
                $observaciones
            );

            if (isset($result['error'])) {
                echo json_encode(['success' => false, 'message' => $result['error']]);
                return;
            }

            $message = "Feriados creados exitosamente: {$result['created']} nuevos, {$result['updated']} actualizados";
            $response = [
                'success' => true,
                'message' => $message,
                'data' => $result
            ];

            // Si hay conflictos con tareas, incluirlos en la respuesta
            if (!empty($result['conflicts'])) {
                $response['conflicts'] = $result['conflicts'];
                $response['message'] .= '. Se detectaron conflictos con tareas existentes.';
            }

            echo json_encode($response);
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::createMasivo error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Crear feriado en fecha específica
     */
    public function createEspecifico()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            // Obtener datos
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $indIrrenunciable = (int)($_POST['irrenunciable'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validaciones
            if (!$projectId || !$fecha) {
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_PROJECT_DATE_REQUIRED]);
                return;
            }

            // Crear feriado
            $result = $this->proyectoFeriadoModel->createSpecificHoliday(
                $projectId,
                $fecha,
                $indIrrenunciable,
                $observaciones
            );

            if (isset($result['error'])) {
                echo json_encode(['success' => false, 'message' => $result['error']]);
                return;
            }

            $message = $result['action'] === 'created' ? AppConstants::SUCCESS_HOLIDAY_CREATED : AppConstants::SUCCESS_HOLIDAY_UPDATED;
            $response = [
                'success' => true,
                'message' => $message,
                'data' => $result
            ];

            // Si hay conflictos con tareas, incluirlos en la respuesta
            if (!empty($result['task_conflicts'])) {
                $response['conflicts'] = [['fecha' => $fecha, 'tasks' => $result['task_conflicts']]];
                $response['message'] .= '. Se detectaron conflictos con tareas existentes.';
            }

            echo json_encode($response);
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::createEspecifico error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Crear feriados en rango de fechas
     */
    public function createRango()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            // Obtener datos
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $fechaInicio = $_POST['fecha_inicio'] ?? '';
            $fechaFin = $_POST['fecha_fin'] ?? '';
            $indIrrenunciable = (int)($_POST['irrenunciable'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validaciones
            if (!$projectId || !$fechaInicio || !$fechaFin) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                return;
            }

            if (strtotime($fechaInicio) > strtotime($fechaFin)) {
                echo json_encode(['success' => false, 'message' => 'La fecha de inicio debe ser menor a la fecha fin']);
                return;
            }

            // Crear feriados
            $result = $this->proyectoFeriadoModel->createRangeHolidays(
                $projectId,
                $fechaInicio,
                $fechaFin,
                $indIrrenunciable,
                $observaciones
            );

            if (isset($result['error'])) {
                echo json_encode(['success' => false, 'message' => $result['error']]);
                return;
            }

            $message = "Feriados en rango creados exitosamente: {$result['created']} nuevos, {$result['updated']} actualizados";
            $response = [
                'success' => true,
                'message' => $message,
                'data' => $result
            ];

            // Si hay conflictos con tareas, incluirlos en la respuesta
            if (!empty($result['conflicts'])) {
                $response['conflicts'] = $result['conflicts'];
                $response['message'] .= '. Se detectaron conflictos con tareas existentes.';
            }

            echo json_encode($response);
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::createRango error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Listar feriados de un proyecto (API)
     */
    public function list()
    {
        try {
            $projectId = (int)($_GET['proyecto_id'] ?? 0);
            if (!$projectId) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                return;
            }

            $feriados = $this->proyectoFeriadoModel->getByProject($projectId);
            $stats = $this->proyectoFeriadoModel->getProjectHolidayStats($projectId);

            echo json_encode([
                'success' => true,
                'feriados' => $feriados,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::list error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Actualizar feriado existente
     */
    public function update()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de feriado requerido']);
                return;
            }

            $data = [
                'tipo_feriado' => $_POST['tipo_feriado'] ?? 'especifico',
                'ind_irrenunciable' => (int)($_POST['irrenunciable'] ?? 0),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 2)
            ];

            $success = $this->proyectoFeriadoModel->update($id, $data);
            if ($success) {
                echo json_encode(['success' => true, 'message' => AppConstants::SUCCESS_HOLIDAY_UPDATED]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar feriado']);
            }
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::update error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Eliminar feriado (eliminación lógica)
     */
    public function delete()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de feriado requerido']);
                return;
            }

            $success = $this->proyectoFeriadoModel->delete($id);
            if ($success) {
                echo json_encode(['success' => true, 'message' => AppConstants::SUCCESS_HOLIDAY_DELETED]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar feriado']);
            }
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::delete error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Detectar conflictos con tareas
     */
    public function checkConflicts()
    {
        try {
            $projectId = (int)($_GET['proyecto_id'] ?? 0);
            $fechas = $_GET['fechas'] ?? '';

            if (!$projectId || !$fechas) {
                echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
                return;
            }

            $fechasArray = explode(',', $fechas);
            $conflicts = $this->proyectoFeriadoModel->detectTaskConflicts($projectId, $fechasArray);

            echo json_encode([
                'success' => true,
                'conflicts' => $conflicts
            ]);
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::checkConflicts error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Mover tareas conflictivas
     */
    public function moveTasks()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $taskIds = $_POST['task_ids'] ?? [];
            $diasAMover = (int)($_POST['dias_a_mover'] ?? 1);

            if (!$projectId || empty($taskIds)) {
                echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
                return;
            }

            // Convertir task_ids a array de enteros
            if (is_string($taskIds)) {
                $taskIds = explode(',', $taskIds);
            }
            $taskIds = array_map('intval', $taskIds);

            $success = $this->proyectoFeriadoModel->moveTasksForward($projectId, $taskIds, $diasAMover);
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Tareas movidas exitosamente',
                    'moved_tasks' => count($taskIds)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al mover tareas']);
            }
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::moveTasks error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Obtener días laborables en un rango
     */
    public function getWorkingDays()
    {
        try {
            $projectId = (int)($_GET['proyecto_id'] ?? 0);
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';

            if (!$projectId || !$startDate || !$endDate) {
                echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
                return;
            }

            $workingDays = $this->proyectoFeriadoModel->getWorkingDays($projectId, $startDate, $endDate);

            echo json_encode([
                'success' => true,
                'working_days' => $workingDays,
                'total_working_days' => count($workingDays)
            ]);
        } catch (\Exception $e) {
            error_log('ProyectoFeriadoController::getWorkingDays error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }
}
