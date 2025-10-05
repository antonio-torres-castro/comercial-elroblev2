<?php

namespace App\Controllers;

use App\Models\Task;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use Exception;

class TaskController extends BaseController
{
    private $taskModel;
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        
        $this->taskModel = new Task();
        $this->permissionService = new PermissionService();
    }

    /**
     * Lista de tareas (plural) - Para administradores
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para gestión de tareas
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_tasks')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            // Obtener filtros
            $filters = [];
            if (!empty($_GET['proyecto_id'])) {
                $filters['proyecto_id'] = (int)$_GET['proyecto_id'];
            }
            if (!empty($_GET['estado_tipo_id'])) {
                $filters['estado_tipo_id'] = (int)$_GET['estado_tipo_id'];
            }
            if (!empty($_GET['usuario_id'])) {
                $filters['usuario_id'] = (int)$_GET['usuario_id'];
            }

            // Obtener datos
            $tasks = $this->taskModel->getAll($filters);
            $projects = $this->taskModel->getProjects();
            $taskStates = $this->taskModel->getTaskStates();
            $users = $this->taskModel->getUsers();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'tasks' => $tasks,
                'projects' => $projects,
                'taskStates' => $taskStates,
                'users' => $users,
                'filters' => $filters,
                'title' => 'Gestión de Tareas',
                'subtitle' => 'Lista de todas las tareas',
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/list.php';

        } catch (Exception $e) {
            error_log("Error en TaskController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    /**
     * Mostrar/editar tarea individual (singular)
     */
    public function show($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para gestión de tarea individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Tarea',
                'subtitle' => $id ? "Editando tarea #$id" : 'Nueva tarea',
                'task_id' => $id
            ];

            require_once __DIR__ . '/../Views/tasks/form.php';

        } catch (Exception $e) {
            error_log("Error en TaskController::show: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            $data = [
                'user' => $currentUser,
                'title' => 'Nueva Tarea',
                'subtitle' => 'Asignar tarea a proyecto',
                'projects' => $this->taskModel->getProjects(),
                'taskTypes' => $this->taskModel->getTaskTypes(), // Catálogo de tareas existentes
                'users' => $this->taskModel->getUsers(),
                'taskStates' => $this->taskModel->getTaskStates(),
                'task' => null,
                'task_id' => null,
                'error' => $_GET['error'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/create.php';

        } catch (Exception $e) {
            error_log("Error en TaskController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    /**
     * Guardar nueva tarea
     */
    public function store()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Security::redirect('/tasks');
                return;
            }

            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Security::redirect("/tasks/create?error=" . urlencode('Token de seguridad inválido'));
                return;
            }

            // Validar datos
            $errors = $this->validateTaskData($_POST);

            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/tasks/create?error=" . urlencode($errorMsg));
                return;
            }

            // Preparar datos para creación
            $taskData = [
                'proyecto_id' => (int)$_POST['proyecto_id'],
                'planificador_id' => $currentUser['id'], // El usuario actual es quien planifica
                'ejecutor_id' => !empty($_POST['ejecutor_id']) ? (int)$_POST['ejecutor_id'] : null,
                'supervisor_id' => !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null,
                'fecha_inicio' => $_POST['fecha_inicio'],
                'duracion_horas' => (float)($_POST['duracion_horas'] ?? 1.0),
                'prioridad' => (int)($_POST['prioridad'] ?? 0),
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 1)
            ];

            // Determinar si usar tarea existente o crear nueva
            if (!empty($_POST['tarea_id']) && $_POST['tarea_id'] !== 'nueva') {
                $taskData['tarea_id'] = (int)$_POST['tarea_id'];
            } else {
                $taskData['nueva_tarea_nombre'] = trim($_POST['nueva_tarea_nombre']);
                $taskData['nueva_tarea_descripcion'] = trim($_POST['nueva_tarea_descripcion'] ?? '');
            }

            // Crear tarea
            $taskId = $this->taskModel->create($taskData);
            if ($taskId) {
                Security::redirect("/tasks?success=Tarea asignada al proyecto correctamente");
            } else {
                Security::redirect("/tasks/create?error=Error al asignar la tarea al proyecto");
            }

        } catch (Exception $e) {
            error_log("Error en TaskController::store: " . $e->getMessage());
            Security::redirect("/tasks/create?error=Error interno del servidor");
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                Security::redirect('/tasks?error=ID de tarea inválido');
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                Security::redirect('/tasks?error=Tarea no encontrada');
                return;
            }

            $data = [
                'user' => $currentUser,
                'task' => $task,
                'task_id' => $id,
                'title' => 'Editar Tarea',
                'subtitle' => "Editando: {$task['nombre']}",
                'projects' => $this->taskModel->getProjects(),
                'taskTypes' => $this->taskModel->getTaskTypes(),
                'users' => $this->taskModel->getUsers(),
                'taskStates' => $this->taskModel->getTaskStates(),
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/edit.php';

        } catch (Exception $e) {
            error_log("Error en TaskController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    /**
     * Actualizar tarea
     */
    public function update()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Security::redirect('/tasks');
                return;
            }

            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Security::redirect("/tasks?error=" . urlencode('Token de seguridad inválido'));
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                Security::redirect('/tasks?error=ID de tarea inválido');
                return;
            }

            // Validar datos básicos
            $errors = $this->validateTaskData($_POST, true);

            // Validar datos específicos de estado (GAP 5)
            $stateErrors = $this->taskModel->validateUpdateData($id, $_POST, $currentUser['rol']);
            $errors = array_merge($errors, $stateErrors);

            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/tasks/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            // Preparar datos para actualización
            $taskData = [
                'proyecto_id' => (int)$_POST['proyecto_id'],
                'planificador_id' => $currentUser['id'], // Mantener planificador actual
                'ejecutor_id' => !empty($_POST['ejecutor_id']) ? (int)$_POST['ejecutor_id'] : null,
                'supervisor_id' => !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null,
                'fecha_inicio' => $_POST['fecha_inicio'],
                'duracion_horas' => (float)($_POST['duracion_horas'] ?? 1.0),
                'prioridad' => (int)($_POST['prioridad'] ?? 0),
                'estado_tipo_id' => (int)$_POST['estado_tipo_id']
            ];

            // Actualizar tarea
            if ($this->taskModel->update($id, $taskData)) {
                Security::redirect("/tasks?success=Tarea actualizada correctamente");
            } else {
                Security::redirect("/tasks/edit?id={$id}&error=Error al actualizar la tarea");
            }

        } catch (Exception $e) {
            error_log("Error en TaskController::update: " . $e->getMessage());
            $id = (int)($_POST['id'] ?? 0);
            Security::redirect("/tasks/edit?id={$id}&error=Error interno del servidor");
        }
    }

    /**
     * Eliminar tarea
     */
    public function delete()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Security::redirect('/tasks');
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                Security::redirect('/tasks?error=ID de tarea inválido');
                return;
            }

            // Validar si la tarea puede ser eliminada (GAP 5)
            $task = $this->taskModel->getById($id);
            if (!$task) {
                Security::redirect('/tasks?error=Tarea no encontrada');
                return;
            }

            // Solo admin y planner pueden eliminar tareas aprobadas
            if ($task['estado_tipo_id'] == 8 && !in_array($currentUser['rol'], ['admin', 'planner'])) {
                Security::redirect('/tasks?error=Solo usuarios Admin y Planner pueden eliminar tareas aprobadas');
                return;
            }

            // Eliminar tarea
            if ($this->taskModel->delete($id)) {
                // Si es petición AJAX, devolver JSON
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    echo json_encode(['success' => true, 'message' => 'Tarea eliminada correctamente']);
                } else {
                    Security::redirect('/tasks?success=Tarea eliminada correctamente');
                }
            } else {
                // Si es petición AJAX, devolver JSON
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar la tarea']);
                } else {
                    Security::redirect('/tasks?error=Error al eliminar la tarea');
                }
            }

        } catch (Exception $e) {
            error_log("Error en TaskController::delete: " . $e->getMessage());
            
            // Si es petición AJAX, devolver JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
            } else {
                Security::redirect('/tasks?error=Error interno del servidor');
            }
        }
    }

    /**
     * Cambiar estado de una tarea (GAP 5)
     */
    public function changeState()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autenticado']);
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }

            // Obtener datos
            $taskId = (int)($_POST['task_id'] ?? 0);
            $newState = (int)($_POST['new_state'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');

            if ($taskId <= 0 || $newState <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                return;
            }

            // Cambiar estado usando el modelo con validaciones
            $result = $this->taskModel->changeState(
                $taskId, 
                $newState, 
                $currentUser['id'], 
                $currentUser['rol'], 
                $reason
            );

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error en TaskController::changeState: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Verificar si una tarea puede ejecutarse (GAP 5)
     */
    public function checkExecutable()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['valid' => false, 'message' => 'No autenticado']);
                return;
            }

            $taskId = (int)($_GET['task_id'] ?? 0);
            if ($taskId <= 0) {
                http_response_code(400);
                echo json_encode(['valid' => false, 'message' => 'ID de tarea inválido']);
                return;
            }

            // Verificar si la tarea puede ejecutarse
            $result = $this->taskModel->canExecuteTask($taskId);
            
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error en TaskController::checkExecutable: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['valid' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Obtener transiciones de estado válidas para una tarea (GAP 5)
     */
    public function getValidTransitions()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['transitions' => [], 'message' => 'No autenticado']);
                return;
            }

            $taskId = (int)($_GET['task_id'] ?? 0);
            if ($taskId <= 0) {
                http_response_code(400);
                echo json_encode(['transitions' => [], 'message' => 'ID de tarea inválido']);
                return;
            }

            $task = $this->taskModel->getById($taskId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['transitions' => [], 'message' => 'Tarea no encontrada']);
                return;
            }

            $currentState = (int)$task['estado_tipo_id'];
            $userRole = $currentUser['rol'];
            
            // Obtener todos los estados posibles
            $allStates = $this->taskModel->getTaskStates();
            $validTransitions = [];

            foreach ($allStates as $state) {
                $stateId = (int)$state['id'];
                
                if ($stateId === $currentState) {
                    continue; // No incluir el estado actual
                }

                // Verificar si la transición es válida
                $transitionCheck = $this->taskModel->isValidStateTransition($currentState, $stateId);
                if (!$transitionCheck['valid']) {
                    continue;
                }

                // Verificar permisos del usuario
                $userCheck = $this->taskModel->canUserChangeState($currentState, $stateId, $userRole);
                if (!$userCheck['valid']) {
                    continue;
                }

                $validTransitions[] = [
                    'id' => $stateId,
                    'nombre' => $state['nombre'],
                    'descripcion' => $state['descripcion']
                ];
            }

            echo json_encode([
                'transitions' => $validTransitions,
                'current_state' => $currentState,
                'message' => 'Transiciones obtenidas correctamente'
            ]);

        } catch (Exception $e) {
            error_log("Error en TaskController::getValidTransitions: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['transitions' => [], 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Validar datos de tarea
     */
    private function validateTaskData(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Validar proyecto
        if (empty($data['proyecto_id']) || !is_numeric($data['proyecto_id'])) {
            $errors[] = 'Debe seleccionar un proyecto válido';
        }

        // Para creación de tareas - validar tarea existente o nueva
        if (!$isUpdate) {
            // Validar tarea - debe seleccionar existente o crear nueva
            if (empty($data['tarea_id']) || $data['tarea_id'] === 'nueva') {
                // Crear nueva tarea - validar nombre
                if (empty($data['nueva_tarea_nombre'])) {
                    $errors[] = 'El nombre de la nueva tarea es obligatorio';
                } elseif (strlen($data['nueva_tarea_nombre']) < 3) {
                    $errors[] = 'El nombre de la tarea debe tener al menos 3 caracteres';
                }
            } else {
                // Usar tarea existente - validar que sea numérica
                if (!is_numeric($data['tarea_id'])) {
                    $errors[] = 'Debe seleccionar una tarea válida del catálogo';
                }
            }
        }

        // Validar fecha de inicio
        if (empty($data['fecha_inicio'])) {
            $errors[] = 'La fecha de inicio es obligatoria';
        } elseif (!$this->isValidDate($data['fecha_inicio'])) {
            $errors[] = 'La fecha de inicio debe tener un formato válido (YYYY-MM-DD)';
        }

        // Validar duración en horas
        if (!empty($data['duracion_horas'])) {
            if (!is_numeric($data['duracion_horas']) || (float)$data['duracion_horas'] <= 0) {
                $errors[] = 'La duración debe ser un número positivo';
            } elseif ((float)$data['duracion_horas'] > 24) {
                $errors[] = 'La duración no puede exceder 24 horas por tarea';
            }
        }

        // Validar prioridad
        if (!empty($data['prioridad'])) {
            if (!is_numeric($data['prioridad']) || (int)$data['prioridad'] < 0 || (int)$data['prioridad'] > 10) {
                $errors[] = 'La prioridad debe ser un número entre 0 y 10';
            }
        }

        // Validar estado (GAP 5) - Solo para actualizaciones
        if ($isUpdate && !empty($data['estado_tipo_id'])) {
            if (!is_numeric($data['estado_tipo_id'])) {
                $errors[] = 'Estado de tarea inválido';
            } else {
                $estadoId = (int)$data['estado_tipo_id'];
                // Estados válidos para proyecto_tareas: 1, 2, 3, 4, 5, 6, 7, 8
                if (!in_array($estadoId, [1, 2, 3, 4, 5, 6, 7, 8])) {
                    $errors[] = 'El estado seleccionado no es válido para tareas';
                }
            }
        }

        // Validar asignaciones de usuarios
        if (!empty($data['ejecutor_id']) && !is_numeric($data['ejecutor_id'])) {
            $errors[] = 'Ejecutor seleccionado inválido';
        }

        if (!empty($data['supervisor_id']) && !is_numeric($data['supervisor_id'])) {
            $errors[] = 'Supervisor seleccionado inválido';
        }

        return $errors;
    }


}