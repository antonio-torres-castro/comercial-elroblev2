<?php

namespace App\Controllers;

use App\Models\Task;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;
use DateTime;
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
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de tareas
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_tasks')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
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

            $filters['current_usuario_id'] = $currentUser['id'];

            if (!empty($_GET['fecha_inicio'])) {
                $filters['fecha_inicio'] = $_GET['fecha_inicio'];
            }
            if (!empty($_GET['fecha_fin'])) {
                $filters['fecha_fin'] = $_GET['fecha_fin'];
            }
            if (empty($_GET['fecha_fin'])) {
                $filters['fecha_fin'] = date('Y-m-d');
                $_GET['fecha_fin'] = $filters['fecha_fin'];
            }
            if (!empty($currentUser['usuario_tipo_id'])) {
                $filters['current_usuario_tipo_id'] = $currentUser['usuario_tipo_id'];
                if ($currentUser['usuario_tipo_id'] == 1 || $currentUser['usuario_tipo_id'] == 2) {
                    $_GET['show_col_acciones'] = true;
                } else {
                    $_GET['show_col_acciones'] = false;
                }
            }

            $projects = $this->taskModel->getProjects($filters);
            if (count($projects) == 1) {
                $_GET['proyecto_id'] = $projects[0]['id'];
            }

            $users = $this->taskModel->getExecutorUsers();
            if (count($users) == 1) {
                $_GET['usuario_id'] = $users[0]['id'];
            }

            if (!empty($_GET['proyecto_id'])) {
                $_GET['show_col_proyecto'] = false;
            } else {
                $_GET['show_col_proyecto'] = true;
            }

            if (!empty($_GET['usuario_id'])) {
                $_GET['show_col_ejecuta'] = false;
            } else {
                $_GET['show_col_ejecuta'] = true;
            }

            // Obtener datos
            $tasks = $this->taskModel->getAll($filters);

            $taskStates = $this->taskModel->getTaskStates($filters);


            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'tasks' => $tasks,
                'projects' => $projects,
                'taskStates' => $taskStates,
                'users' => $users,
                'filters' => $filters,
                'title' => AppConstants::UI_TASK_MANAGEMENT,
                'subtitle' => 'Lista de todas las tareas',
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/list.php';
        } catch (Exception $e) {
            Logger::error("TaskController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Lista de tareas (plural) - Para Ejecutor
     */
    public function myIndex()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de tareas
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'my_tasks')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Obtener filtros
            $filters = [];
            if (!empty($_GET['proyecto_id'])) {
                $filters['proyecto_id'] = (int)$_GET['proyecto_id'];
            }
            $projects = $this->taskModel->getProjectsActivos($currentUser['id']);
            if (count($projects) == 1) {
                $_GET['proyecto_id'] = $projects[0]['id'];
            }

            if (!empty($_GET['estado_tipo_id'])) {
                $filters['estado_tipo_id'] = (int)$_GET['estado_tipo_id'];
            }
            if (!empty($_GET['usuario_id'])) {
                $filters['usuario_id'] = (int)$_GET['usuario_id'];
            }
            if (empty($_GET['usuario_id']) && $currentUser['usuario_tipo_id'] != '1') {
                $filters['usuario_id'] = $currentUser['id'];
                $_GET['usuario_id'] = $filters['usuario_id'];
            }
            if (!empty($_GET['fecha_inicio'])) {
                $filters['fecha_inicio'] = $_GET['fecha_inicio'];
            }
            if (!empty($_GET['fecha_fin'])) {
                $filters['fecha_fin'] = $_GET['fecha_fin'];
            }
            if (empty($_GET['fecha_fin'])) {
                $filters['fecha_fin'] = date('Y-m-d');
                $_GET['fecha_fin'] = $filters['fecha_fin'];
            }

            if (!empty($currentUser['usuario_tipo_id'])) {
                $filters['current_usuario_tipo_id'] = $currentUser['usuario_tipo_id'];
                if ($currentUser['usuario_tipo_id'] == 1 || $currentUser['usuario_tipo_id'] == 2) {
                    $_GET['show_col_acciones'] = true;
                } else {
                    $_GET['show_col_acciones'] = false;
                }
            }

            // Obtener datos
            $tasks = $this->taskModel->getAll($filters);
            $taskStates = $this->taskModel->getTaskStatesMyListFilter();
            $users = $this->taskModel->getUsers();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'tasks' => $tasks,
                'projects' => $projects,
                'taskStates' => $taskStates,
                'users' => $users,
                'filters' => $filters,
                'title' => AppConstants::UI_MY_TASK_MANAGEMENT,
                'subtitle' => 'Lista de todas las tareas',
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/myList.php';
        } catch (Exception $e) {
            Logger::error("TaskController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar/editar tarea individual (singular)
     */
    public function show(?int $id = 0)
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de tarea individual
            if (!$this->permissionService->hasPermission($currentUser['id'], 'Read')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            $task = $id ? $this->taskModel->getById($id) : null;
            // Datos para la vista - ESTANDARIZADO
            $data = [
                'user' => $currentUser,
                'title' => 'Tarea',
                'subtitle' => $task['tarea_nombre'],
                'task_id' => $id,
                'task' => $task,
                'action' => 'view'
            ];

            require_once __DIR__ . '/../Views/tasks/porjectTaskView.php';
        } catch (Exception $e) {
            Logger::error("TaskController::show: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function newTask()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_NEW_TASK_TYPE,
                'subtitle' => 'Definición',
                'tasks' => $this->taskModel->getAllTasks(), // Catálogo de tareas existentes
                'taskStates' => $this->taskModel->getTaskStatesForNewTask(),
                'success' => $_GET['success'] ?? '',
                'error' => $_GET['error'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/newTask.php';
        } catch (Exception $e) {
            Logger::error("TaskController::newTask: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
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
                $this->redirectToLogin();
                return;
            }
            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $project_id = isset($_POST['proyecto_id']) && !empty($_POST['proyecto_id']) ? (int)$_POST['proyecto_id'] : 0;
            $project_id = isset($_GET['project_id']) && !empty($_GET['project_id']) ? (int)$_GET['project_id'] : $project_id;
            if ($project_id > 0) {
                $projects = $this->taskModel->getProjectById($project_id);
            } else {
                $projects = $this->taskModel->getProjects();
            }

            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_PROJECT_TASK,
                'subtitle' => 'Asignar',
                'projects' => $projects,
                'tasks' => $this->taskModel->getTasksForCreate(),
                'taskTypes' => $this->taskModel->getTaskTypes(), // Catálogo de tareas existentes
                'executor_users' => $this->taskModel->getExecutorUsers(),
                'supervisor_users' => $this->taskModel->getSupervisorUsers(),
                'taskStates' => $this->taskModel->getTaskStatesForCreate(),
                'task' => null,
                'task_id' => null,
                'project_id' => $project_id,
                'error' => $_GET['error'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/create.php';
        } catch (Exception $e) {
            Logger::error("TaskController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Guardar nueva tarea
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_TASKS);
                return;
            }
            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Security::redirect("/tasks/create?error=" . urlencode('Token de seguridad inválido'));
                return;
            }
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            // Validar datos
            $errors = $this->validateTaskData($_POST);
            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/tasks/create?error=" . urlencode($errorMsg));
                return;
            }

            $tipoOcurr = $_POST['optionOcurrencia'];

            $fechaInicio = "";
            $fechaFin = "";
            if ($tipoOcurr == '1') {
                $fechaInicio = $_POST['fecha_inicio_masivo'];
                $fechaFin = $_POST['fecha_fin_masivo'];
            }
            if ($tipoOcurr == '2') {
                $fechaInicio = $_POST['fecha_especifica_inicio'];
                $fechaFin = $_POST['fecha_especifica_fin'] ?? $_POST['fecha_especifica_inicio'];
            }
            if ($tipoOcurr == '3') {
                $fechaInicio = $_POST['fecha_inicio_rango'];
                $fechaFin = $_POST['fecha_fin_rango'];
            }
            // Determinar si usar tarea existente o crear nueva
            $tareaId = 0;
            if (!empty($_POST['tarea_id']) && $_POST['tarea_id'] !== 'nueva') {
                $tareaId = (int)$_POST['tarea_id'];
            } else {
                $tareaId = $this->taskModel->taskCreate(trim($_POST['nueva_tarea_nombre']), trim($_POST['nueva_tarea_descripcion'] ?? ''));
                if ($tareaId == null) {
                    Security::redirect("/tasks/create?error=" . urlencode('Error al crear la nueva tarea'));
                    return;
                }
            }
            // Preparar datos para creación
            $taskData = [
                'proyecto_id' => (int)$_POST['proyecto_id'],
                'tarea_id' => $tareaId,
                'planificador_id' => $currentUser['id'], // El usuario actual es quien planifica
                'ejecutor_id' => !empty($_POST['ejecutor_id']) ? (int)$_POST['ejecutor_id'] : null,
                'supervisor_id' => !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null,
                'fecha_inicio' => $fechaInicio,
                'duracion_horas' => (float)($_POST['duracion_horas'] ?? 1.0),
                'fecha_fin' => $fechaFin,
                'prioridad' => (int)($_POST['prioridad'] ?? 0),
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 1),
                'tipo_ocurrencia' => $tipoOcurr,
                'dias_semana' => $_POST['dias']
            ];

            $result = false;
            $result = $this->taskModel->create($taskData);
            if ($result) {
                Security::redirect("/tasks?success=Tarea asignada al proyecto correctamente");
            } else {
                Security::redirect("/tasks/create?error=Error al asignar la tarea al proyecto");
            }
        } catch (Exception $e) {
            Logger::error("TaskController::store: " . $e->getMessage());
            Security::redirect("/tasks/create?error=Error interno del servidor");
        }
    }

    /**
     * Guardar nueva tarea tipo
     */
    public function storeT()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_TASKS);
                return;
            }
            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Security::redirect("/tasks/create?error=" . urlencode('Token de seguridad inválido'));
                return;
            }
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $nueva_tarea_nombre = trim($_POST['nueva_tarea_nombre']);
            $nueva_tarea_descripcion = trim($_POST['nueva_tarea_descripcion'] ?? '');
            // Crear tarea
            $taskId = $this->taskModel->taskCreate($nueva_tarea_nombre, $nueva_tarea_descripcion);
            if ($taskId) {
                Security::redirect("/task/newTask?success=Tarea tipo creada");
            } else {
                Security::redirect("/task/newTask?error=Error creando tarea tipo");
            }
        } catch (Exception $e) {
            Logger::error("TaskController::storeT: " . $e->getMessage());
            Security::redirect("/tasks/storeT?error=Error interno del servidor");
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
                $this->redirectToLogin();
                return;
            }
            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_INVALID_TASK_ID);
                return;
            }

            $task = $this->taskModel->getById($id);
            if (!$task) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_TASK_NOT_FOUND);
                return;
            }

            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_EDIT_TASK_TITLE,
                'subtitle' => "Editando: {$task['tarea_nombre']}",
                'projects' => $this->taskModel->getProjects(),
                'taskTypes' => $this->taskModel->getTaskTypes(),
                'executor_users' => $this->taskModel->getExecutorUsers(),
                'supervisor_users' => $this->taskModel->getSupervisorUsers(),
                'taskStates' => $this->taskModel->getTasksForCreate(),
                'task' => $task,
                'task_id' => $id,  // Mantener para compatibilidad
                'action' => 'edit',  // Estandarizar
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/edit.php';
        } catch (Exception $e) {
            Logger::error("TaskController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Actualizar tarea proyecto
     */
    public function update()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_TASKS);
                return;
            }

            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_INVALID_TASK_ID);
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
            Logger::error("TaskController::update: " . $e->getMessage());
            $id = (int)($_POST['id'] ?? 0);
            Security::redirect("/tasks/edit?id={$id}&error=Error interno del servidor");
        }
    }

    /**
     * Actualizar tarea
     */
    public function updateT()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_TASKS);
                return;
            }
            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->jsonError('CsrfToken invalido', [], 500);
                return;
            }
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $this->jsonError('Id invalido', [], 500);
                return;
            }
            //Si el nombre de tarea existe y tiene un id diferente no se puede modificar esta ultima tarea
            $tareaByName = $this->taskModel->getTaskByName($_POST['editTareaNombre']);
            if (!empty($tareaByName) && $tareaByName[0]['id'] != $_POST['id']) {
                $this->jsonError('El nombre de tarea ya existe en otra tarea', [], 500);
            }
            // Preparar datos para actualización
            $taskData = [
                'id' => (int)$_POST['id'],
                'nombre' => $_POST['editTareaNombre'],
                'descripcion' => !empty($_POST['editTareaDescripcion']) ? $_POST['editTareaDescripcion'] : '',
                'estado_tipo_id' => (int)$_POST['editEstadoTipoId']
            ];

            // Actualizar tarea
            if ($this->taskModel->updateT($id, $taskData)) {
                $this->jsonSuccess('Tarea actualizada');
            } else {
                $this->jsonError('Error al actualizar tarea', [], 500);
            }
        } catch (Exception $e) {
            Logger::error("TaskController::update: " . $e->getMessage());
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
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_TASKS);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_INVALID_TASK_ID);
                return;
            }

            // Validar si la tarea puede ser eliminada (GAP 5)
            $task = $this->taskModel->getById($id);
            if (!$task) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_TASK_NOT_FOUND);
                return;
            }

            // Solo admin y planner pueden eliminar tareas aprobadas
            if ($task['estado_tipo_id'] == 8 && !in_array($currentUser['rol'], ['admin', 'planner'])) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_CANNOT_DELETE_APPROVED_TASK);
                return;
            }

            // Eliminar tarea
            if ($this->taskModel->delete($id)) {
                // Si es petición AJAX, devolver JSON
                if (
                    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
                ) {
                    $this->jsonSuccess('Tarea eliminada correctamente');
                } else {
                    $this->redirectWithSuccess(AppConstants::ROUTE_TASKS, AppConstants::SUCCESS_TASK_DELETED);
                }
            } else {
                // Si es petición AJAX, devolver JSON
                if (
                    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
                ) {
                    $this->jsonError('Error al eliminar la tarea', [], 500);
                } else {
                    $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_DELETE_TASK);
                }
            }
        } catch (Exception $e) {
            Logger::error("TaskController::delete: " . $e->getMessage());
            // Si es petición AJAX, devolver JSON
            if (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) {
                http_response_code(500);
                $this->jsonInternalError();
            } else {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_INTERNAL_SERVER);
            }
        }
    }

    /**
     * Eliminar tarea
     */
    public function deleteT()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_TASKS);
                return;
            }
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_INVALID_TASK_ID);
                return;
            }

            // Validar si la tarea puede ser eliminada (GAP 5)
            $task = $this->taskModel->getTaskById($id);
            if (!$task) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_TASK_NOT_FOUND);
                return;
            }
            // Solo admin y planner pueden eliminar tareas aprobadas
            if ($task[0]['estado_tipo_id'] == 8 && !in_array($currentUser['rol'], ['admin', 'planner'])) {
                $this->redirectWithError(AppConstants::ROUTE_TASKS, AppConstants::ERROR_CANNOT_DELETE_APPROVED_TASK);
                return;
            }

            // Eliminar tarea
            if ($this->taskModel->deleteT($id)) {
                $this->jsonSuccess('Tarea eliminada');
            } else {
                $this->jsonError('Error al eliminar la tarea', [], 500);
            }
        } catch (Exception $e) {
            Logger::error("TaskController::deleteT: " . $e->getMessage());
            http_response_code(500);
            $this->jsonInternalError();
        }
    }

    /**
     * Tabla Feriados Vista principal del mantenedor de feriados
     */
    public function refreshTasksTable()
    {
        try {
            $tasks = $this->taskModel->getAllTasks();
            $this->jsonSuccess('Tareas cargadas correctamente', ['tareas' => $tasks]);
        } catch (Exception $e) {
            Logger::error("UserController::refreshTasksTable: " . $e->getMessage());
            $this->jsonError('Error al actualizar lista de tareas', [], 500);
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
                $this->redirectToLogin();
                return;
            }
            $aManageTask = $this->permissionService->hasMenuAccess($currentUser['id'], 'manage_tasks');
            $aMyTasks = $this->permissionService->hasMenuAccess($currentUser['id'], 'my_tasks');
            // Verificar accesos
            if (!$aManageTask && !$aMyTasks) {
                $this->jsonError('Sin permisos suficientes');
                return;
            }

            if (!$this->permissionService->hasPermission($currentUser['id'], 'Register activity')) {
                $this->jsonError('Sin permisos suficientes');
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }
            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->jsonError('Token CSRF inválido');
                return;
            }
            // Obtener datos
            $taskId = (int)($_POST['task_id'] ?? 0);
            $newState = (int)($_POST['new_state'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');

            if ($taskId <= 0 || $newState <= 0) {
                $this->jsonError('Error al cambiar estado de la tarea');
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

            if ($result['success']) {
                $this->jsonSuccess($result['message'] ?? 'Estado de tarea actualizado correctamente');
            } else {
                $this->jsonError($result['message'] ?? 'Error al cambiar estado de la tarea');
            }
        } catch (Exception $e) {
            Logger::error("TaskController::changeState: " . $e->getMessage());
            $this->jsonError('Error interno del servidor');
        }
    }

    /**
     * Redireccionar a la vista de tareas - verificación ejecutable se maneja en frontend
     * (Convertido desde método API para cumplir con reglas de no-Ajax)
     */
    public function checkExecutable()
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            $this->redirectToLogin();
            return;
        }

        $taskId = (int)($_GET['task_id'] ?? 0);
        if ($taskId <= 0) {
            $this->redirectWithError(AppConstants::ROUTE_TASKS, 'ID de tarea inválido');
            return;
        }

        // Redirigir a la vista de tareas donde se puede verificar ejecutabilidad sin Ajax
        $this->redirectToRoute(AppConstants::ROUTE_TASKS . "?task_id={$taskId}");
    }

    /**
     * Redireccionar a la vista de tareas - transiciones válidas se manejan en frontend
     * (Convertido desde método API para cumplir con reglas de no-Ajax)
     */
    public function getValidTransitions()
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            $this->redirectToLogin();
            return;
        }

        $uti = $currentUser['usuario_tipo_id'];

        $taskId = (int)($_GET['task_id'] ?? 0);
        if ($taskId <= 0) {
            $this->redirectWithError(AppConstants::ROUTE_TASKS, 'ID de tarea inválido');
            return;
        }
        // Obtener estado_tipo_id
        $projectTaskState = $this->taskModel->getProjectTaskState($taskId) ?? -1;
        if ($projectTaskState == 1) { // creado
            $transitions = [['id' => 2, 'nombre' => 'activo'], ['id' => 4, 'nombre' => 'eliminado']];
        }
        if ($projectTaskState == 2 && ($uti == '1' || $uti == '2')) { // activo
            $transitions = [['id' => 5, 'nombre' => 'iniciado'], ['id' => 3, 'nombre' => 'inactivo']];
        } elseif ($projectTaskState == 2) { // activo
            $transitions = [['id' => 5, 'nombre' => 'iniciado']];
        }

        if ($projectTaskState == 3) { // inactivo
            $transitions = [['id' => 2, 'nombre' => 'activo']];
        }
        if ($projectTaskState == 4) { // eliminado
            $transitions = [['id' => 1, 'nombre' => 'creado']];
        }
        if ($projectTaskState == 5 && ($uti == '1' || $uti == '2')) { // iniciado
            $transitions = [['id' => 2, 'nombre' => 'activo'], ['id' => 6, 'nombre' => 'terminado']];
        } elseif ($projectTaskState == 5) {
            $transitions = [['id' => 6, 'nombre' => 'terminado']];
        }
        if ($projectTaskState == 6 && ($uti == '1' || $uti == '2' || $uti == '3')) { // terminado
            $transitions = [['id' => 8, 'nombre' => 'aprobado'], ['id' => 7, 'nombre' => 'rechazado']];
        }
        if ($projectTaskState == 7) { // rechazado
            $transitions = [['id' => 6, 'nombre' => 'terminado']];
        }
        if ($projectTaskState == 8 && ($uti == '1' || $uti == '2' || $uti == '3')) { // aprobado
            $transitions = [['id' => 7, 'nombre' => 'rechazado']];
        }

        // Datos para la vista
        $data = [
            'transitions' => $transitions
        ];

        // 2. Set the Content-Type header
        header('Content-Type: application/json');

        // 3. Encode the PHP array to JSON
        $jsonOutput = json_encode($data);

        // 4. Output the JSON string
        echo $jsonOutput;
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

        $tipoOcurrencia = $data['optionOcurrencia'];

        $diasSemana = $data['dias'] ?? [];
        $diasSemana = array_map('intval', $diasSemana); // Convertir días a array de enteros

        $fechaInicio = "";
        $fechaFin = "";
        if ($tipoOcurrencia == '1') {
            $fechaInicio = $data['fecha_inicio_masivo'];
            $fechaFin = $data['fecha_fin_masivo'];
        }
        if ($tipoOcurrencia == '2') {
            $fechaInicio = $data['fecha_especifica_inicio'];
            $fechaFin = $data['fecha_especifica_fin'] ?? $data['fecha_especifica_inicio'];
        }
        if ($tipoOcurrencia == '3') {
            $fechaInicio = $data['fecha_inicio_rango'];
            $fechaFin = $data['fecha_fin_rango'];
        }

        // Validar fecha de inicio
        if (empty($fechaInicio)) {
            $errors[] = 'La fecha de inicio es obligatoria';
        } elseif (!$this->isValidDate($fechaInicio)) {
            $errors[] = 'La fecha de inicio debe tener un formato válido (YYYY-MM-DD)';
        }
        $start = new \DateTime($fechaInicio);
        $end = new \DateTime($fechaFin);
        if ($end < $start) {
            $errors[] = 'Fecha fin es menor a fecha inicio';
        }
        if ($tipoOcurrencia == '1') {
            $fechasGeneradas = [];
            while ($start <= $end) {
                $dayOfWeek = (int)$start->format('w'); // 0=domingo, 1=lunes, etc.
                if (in_array($dayOfWeek, $diasSemana)) {
                    $fechasGeneradas[] = $start->format('Y-m-d');
                }
                $start->add(new \DateInterval('P1D'));
            }
            if (empty($fechasGeneradas)) {
                $errors[] = 'La configuracion masiva entregada no genera fechas para tarea';
            }
        }

        $maximoHoras = 9; //siempre son nueve por la cantidad de horas laborales y las tareas son diarias, no hay tareas que duren mas de un dia
        // Validar duración en horas
        if (!empty($data['duracion_horas'])) {
            if (!is_numeric($data['duracion_horas']) || (float)$data['duracion_horas'] <= 0) {
                $errors[] = 'La duración debe ser un número positivo';
            } elseif ((float)$data['duracion_horas'] > $maximoHoras) {
                $errors[] = 'Duración excede ' . strval($maximoHoras) . ' horas por tarea';
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
                if (!in_array($estadoId, [1, 2])) {
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
