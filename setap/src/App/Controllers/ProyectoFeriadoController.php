<?php

namespace App\Controllers;

use App\Models\ProyectoFeriado;
use App\Models\Project;
use App\Helpers\Security;
use App\Helpers\Logger;
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
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            // Obtener datos
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $diasSemana = $_POST['dias'] ?? [];
            $fechaInicio = $_POST['fecha_inicio'] ?? '';
            $fechaFin = $_POST['fecha_fin'] ?? '';
            $indIrrenunciable = (int)($_POST['irrenunciable'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validar que tenemos un proyecto válido para construir la URL de retorno
            if (!$projectId) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Proyecto no válido');
                return;
            }

            $returnUrl = "/proyecto-feriados?proyecto_id={$projectId}";

            // Validaciones
            if (empty($diasSemana) || !$fechaInicio || !$fechaFin) {
                $this->redirectWithError($returnUrl, 'Datos incompletos');
                return;
            }

            if (strtotime($fechaInicio) > strtotime($fechaFin)) {
                $this->redirectWithError($returnUrl, 'La fecha de inicio debe ser menor a la fecha fin');
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
                $this->redirectWithError($returnUrl, $result['error']);
                return;
            }

            $message = "Feriados creados exitosamente: {$result['created']} nuevos, {$result['updated']} actualizados";

            // Si hay conflictos con tareas, incluirlos en el mensaje
            if (!empty($result['conflicts'])) {
                $message .= '. Se detectaron conflictos con tareas existentes.';
            }

            $this->redirectWithSuccess($returnUrl, $message);
        } catch (\Exception $e) {
            Logger::error('ProyectoFeriadoController::createMasivo error: ' . $e->getMessage());
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;
            $this->redirectWithError($returnUrl, 'Error interno del servidor');
        }
    }

    /**
     * Crear feriado en fecha específica
     */
    public function createEspecifico()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            // Obtener datos
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $indIrrenunciable = (int)($_POST['irrenunciable'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validar que tenemos un proyecto válido para construir la URL de retorno
            if (!$projectId) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Proyecto no válido');
                return;
            }

            $returnUrl = "/proyecto-feriados?proyecto_id={$projectId}";

            // Validaciones
            if (!$fecha) {
                $this->redirectWithError($returnUrl, AppConstants::ERROR_PROJECT_DATE_REQUIRED);
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
                $this->redirectWithError($returnUrl, $result['error']);
                return;
            }

            $message = $result['action'] === 'created' ? AppConstants::SUCCESS_HOLIDAY_CREATED : AppConstants::SUCCESS_HOLIDAY_UPDATED;

            // Si hay conflictos con tareas, incluirlos en el mensaje
            if (!empty($result['task_conflicts'])) {
                $message .= '. Se detectaron conflictos con tareas existentes.';
            }

            $this->redirectWithSuccess($returnUrl, $message);
        } catch (\Exception $e) {
            Logger::error('ProyectoFeriadoController::createEspecifico error: ' . $e->getMessage());
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;
            $this->redirectWithError($returnUrl, 'Error interno del servidor');
        }
    }

    /**
     * Crear feriados en rango de fechas
     */
    public function createRango()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            // Obtener datos
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $fechaInicio = $_POST['fecha_inicio'] ?? '';
            $fechaFin = $_POST['fecha_fin'] ?? '';
            $indIrrenunciable = (int)($_POST['irrenunciable'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validar que tenemos un proyecto válido para construir la URL de retorno
            if (!$projectId) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Proyecto no válido');
                return;
            }

            $returnUrl = "/proyecto-feriados?proyecto_id={$projectId}";

            // Validaciones
            if (!$fechaInicio || !$fechaFin) {
                $this->redirectWithError($returnUrl, 'Datos incompletos');
                return;
            }

            if (strtotime($fechaInicio) > strtotime($fechaFin)) {
                $this->redirectWithError($returnUrl, 'La fecha de inicio debe ser menor a la fecha fin');
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
                $this->redirectWithError($returnUrl, $result['error']);
                return;
            }

            $message = "Feriados en rango creados exitosamente: {$result['created']} nuevos, {$result['updated']} actualizados";

            // Si hay conflictos con tareas, incluirlos en el mensaje
            if (!empty($result['conflicts'])) {
                $message .= '. Se detectaron conflictos con tareas existentes.';
            }

            $this->redirectWithSuccess($returnUrl, $message);
        } catch (\Exception $e) {
            Logger::error('ProyectoFeriadoController::createRango error: ' . $e->getMessage());
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;
            $this->redirectWithError($returnUrl, 'Error interno del servidor');
        }
    }

    /**
     * Redireccionar a la vista principal de feriados de un proyecto
     * (Convertido desde método API para cumplir con reglas de no-Ajax)
     */
    public function list()
    {
        $projectId = (int)($_GET['proyecto_id'] ?? 0);
        if (!$projectId) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'ID de proyecto requerido');
            return;
        }

        // Redirigir a la vista principal de feriados del proyecto
        $this->redirectToRoute("/proyecto-feriados?proyecto_id={$projectId}");
    }

    /**
     * Actualizar feriado existente
     */
    public function update()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            $projectId = (int)($_POST['proyecto_id'] ?? 0);

            if (!$id) {
                $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;
                $this->redirectWithError($returnUrl, 'ID de feriado requerido');
                return;
            }

            $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;

            $data = [
                'tipo_feriado' => $_POST['tipo_feriado'] ?? 'especifico',
                'ind_irrenunciable' => (int)($_POST['irrenunciable'] ?? 0),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 2)
            ];

            $success = $this->proyectoFeriadoModel->update($id, $data);
            if ($success) {
                $this->redirectWithSuccess($returnUrl, AppConstants::SUCCESS_HOLIDAY_UPDATED);
            } else {
                $this->redirectWithError($returnUrl, 'Error al actualizar feriado');
            }
        } catch (\Exception $e) {
            Logger::error('ProyectoFeriadoController::update error: ' . $e->getMessage());
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;
            $this->redirectWithError($returnUrl, 'Error interno del servidor');
        }
    }

    /**
     * Eliminar feriado (eliminación lógica)
     */
    public function delete()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            $projectId = (int)($_POST['proyecto_id'] ?? 0);

            if (!$id) {
                $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;
                $this->redirectWithError($returnUrl, 'ID de feriado requerido');
                return;
            }

            $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;

            $success = $this->proyectoFeriadoModel->delete($id);
            if ($success) {
                $this->redirectWithSuccess($returnUrl, AppConstants::SUCCESS_HOLIDAY_DELETED);
            } else {
                $this->redirectWithError($returnUrl, 'Error al eliminar feriado');
            }
        } catch (\Exception $e) {
            Logger::error('ProyectoFeriadoController::delete error: ' . $e->getMessage());
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;
            $this->redirectWithError($returnUrl, 'Error interno del servidor');
        }
    }

    /**
     * Redireccionar a la vista principal - detección de conflictos se maneja en el frontend
     * (Convertido desde método API para cumplir con reglas de no-Ajax)
     */
    public function checkConflicts()
    {
        $projectId = (int)($_GET['proyecto_id'] ?? 0);
        if (!$projectId) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Parámetros incompletos');
            return;
        }

        // Redirigir a la vista principal de feriados del proyecto
        // La detección de conflictos debería manejarse en la vista sin Ajax
        $this->redirectToRoute("/proyecto-feriados?proyecto_id={$projectId}");
    }

    /**
     * Mover tareas conflictivas
     */
    public function moveTasks()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Validar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_HOME, AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $taskIds = $_POST['task_ids'] ?? [];
            $diasAMover = (int)($_POST['dias_a_mover'] ?? 1);

            if (!$projectId) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Proyecto no válido');
                return;
            }

            $returnUrl = "/proyecto-feriados?proyecto_id={$projectId}";

            if (empty($taskIds)) {
                $this->redirectWithError($returnUrl, 'Parámetros incompletos');
                return;
            }

            // Convertir task_ids a array de enteros
            if (is_string($taskIds)) {
                $taskIds = explode(',', $taskIds);
            }
            $taskIds = array_map('intval', $taskIds);

            $success = $this->proyectoFeriadoModel->moveTasksForward($projectId, $taskIds, $diasAMover);
            if ($success) {
                $message = 'Tareas movidas exitosamente (' . count($taskIds) . ' tareas)';
                $this->redirectWithSuccess($returnUrl, $message);
            } else {
                $this->redirectWithError($returnUrl, 'Error al mover tareas');
            }
        } catch (\Exception $e) {
            Logger::error('ProyectoFeriadoController::moveTasks error: ' . $e->getMessage());
            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $returnUrl = $projectId ? "/proyecto-feriados?proyecto_id={$projectId}" : AppConstants::ROUTE_PROJECTS;
            $this->redirectWithError($returnUrl, 'Error interno del servidor');
        }
    }

    /**
     * Redireccionar a la vista principal - días laborables se calculan en el frontend
     * (Convertido desde método API para cumplir con reglas de no-Ajax)
     */
    public function getWorkingDays()
    {
        $projectId = (int)($_GET['proyecto_id'] ?? 0);
        if (!$projectId) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Parámetros incompletos');
            return;
        }

        // Redirigir a la vista principal de feriados del proyecto
        // El cálculo de días laborables debería manejarse en la vista sin Ajax
        $this->redirectToRoute("/proyecto-feriados?proyecto_id={$projectId}");
    }
}
