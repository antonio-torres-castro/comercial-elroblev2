<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\Process;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;
use DateTime;
use Exception;
use RuntimeException;

class TaskController extends BaseController
{
    private $taskModel;
    private $processModel;
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        $this->taskModel = new Task();
        $this->processModel = new Process();
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

            $uti = $currentUser['usuario_tipo_id'];
            $cu = $currentUser['id'];
            $contraparteId = $currentUser['contraparte_id'];

            $aManageTask = $this->permissionService->hasMenuAccess($cu, 'manage_tasks');
            $rActivity = $this->permissionService->hasPermission($cu, 'Register activity');
            $rModify = $this->permissionService->hasPermission($cu, 'Modify');
            $rCreate = $this->permissionService->hasPermission($cu, 'Create');
            $rEliminate = $this->permissionService->hasPermission($cu, 'Eliminate');
            $rApruve = $this->permissionService->hasPermission($cu, 'Apruve');

            // Verificar permisos para gestión de tareas
            if (!$aManageTask) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Obtener filtros
            $filters = [];

            $filters['current_usuario_tipo_id'] = $uti;
            $filters['current_usuario_id'] = $cu;

            $filters['proyecto_id'] = empty($_GET['proyecto_id']) ? 0 : (int)$_GET['proyecto_id'];

            if (isset($_GET['estado_tipo_id']) && !empty($_GET['estado_tipo_id'])) {
                $filters['estado_tipo_id'] = $_GET['estado_tipo_id'];
            }
            if (!empty($_GET['usuario_id']) && $_GET['usuario_id'] != -1) {
                $filters['usuario_id'] = (int)$_GET['usuario_id'];
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

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            }
            if ($uti == 6) {
                $filters['contraparte_id'] = $contraparteId;
            }
            if ($uti == 3) {
                $filters['supervisor_id'] = $cu;
            }
            if ($uti == 2) {
                $filters['planificador_id'] = $cu;
            }

            if (empty($_GET['usuario_id'])) {
                if ($uti == 4) {
                    $filters['ejecutor_id'] = $cu;
                    $_GET['usuario_id'] = $filters['ejecutor_id'];
                }
            }

            $_GET['show_col_acciones'] = $rModify && $rEliminate;
            $_GET['show_btn_aprobar'] = $rApruve;
            $_GET['show_btn_terminar'] = $rActivity && $rApruve;
            $_GET['show_btn_nuevo'] = $rCreate;
            $_GET['show_btn_activity'] = $rActivity;

            if ($uti > 1 && isset($currentUser['proveedor_id'])) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            } elseif ($uti == 1 && isset($_GET['proveedor_id']) && !empty($_GET['proveedor_id'])) {
                $filters['proveedor_id'] = $_GET['proveedor_id'];
            }

            $suppliers = $this->taskModel->getSuppliers($filters);

            $projects = $this->taskModel->getProjects($filters);
            if (count($projects) == 1) {
                $_GET['proyecto_id'] = $projects[0]['id'];
            }

            if (!empty($_GET['excluye_eliminados'])) {
                $filters['excluye_eliminados'] = $_GET['excluye_eliminados'];
            }

            if (!empty($_GET['excluye_no_asignados'])) {
                $filters['excluye_no_asignados'] = $_GET['excluye_no_asignados'];
            }

            if (!empty($_GET['tarea_nombre'])) {
                $filters['tarea_nombre'] = $_GET['tarea_nombre'];
            }

            $users = $this->taskModel->getExecutorUsers($filters);
            if (count($users) == 1 && $uti > 1) {
                $_GET['usuario_id'] = $users[0]['id'];
            }

            $_GET['show_col_proyecto'] = empty($_GET['proyecto_id']);
            $_GET['show_col_ejecuta'] = empty($_GET['usuario_id']) || $_GET['usuario_id'] == -1;


            // Configuración de paginación
            $perPage = 7;
            $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
            $offset = ($currentPage - 1) * $perPage;
            // Contar total de registros según filtros
            $totalRows = $this->taskModel->countAll($filters);
            $totalPages = max(1, ceil($totalRows / $perPage));

            // Obtener registros paginados
            $tasks = $this->taskModel->getAll($filters, $perPage, $offset);

            $taskStates = $this->taskModel->getTaskStates($filters);

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'suppliers' => $suppliers,
                'tasks' => $tasks,
                'totalRecords' => $totalRows,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
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
     * Vista de horas planificadas (dia/semana/mes)
     */
    public function horas()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];
            $cu = $currentUser['id'];
            $contraparteId = $currentUser['contraparte_id'];

            $aManageTask = $this->permissionService->hasMenuAccess($cu, 'manage_tasks');
            if (!$aManageTask) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $filters = [];
            $filters['current_usuario_tipo_id'] = $uti;
            $filters['current_usuario_id'] = $cu;

            if (!empty($_GET['proyecto_id'])) {
                $filters['proyecto_id'] = (int)$_GET['proyecto_id'];
            }
            if (isset($_GET['estado_tipo_id']) && !empty($_GET['estado_tipo_id'])) {
                $filters['estado_tipo_id'] = $_GET['estado_tipo_id'];
            }
            if (!empty($_GET['usuario_id'])) {
                $filters['usuario_id'] = (int)$_GET['usuario_id'];
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

            if ($uti == 6) {
                $filters['contraparte_id'] = $contraparteId;
            }
            if ($uti == 3) {
                $filters['supervisor_id'] = $cu;
            }
            if ($uti == 2) {
                $filters['planificador_id'] = $cu;
            }

            if (empty($_GET['usuario_id'])) {
                if ($uti == 4) {
                    $filters['ejecutor_id'] = $cu;
                    $_GET['usuario_id'] = $filters['ejecutor_id'];
                }
            }

            $projects = $this->taskModel->getProjects($filters);
            if (count($projects) == 1) {
                $_GET['proyecto_id'] = $projects[0]['id'];
            } elseif (isset($projects) && count($projects) > 0 && !isset($filters['proyecto_id'])) {
                $filters['proyecto_id'] = (int)$projects[0]['id'];
            }

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            } elseif (isset($_GET['proyecto_id']) && !empty($_GET['proyecto_id'])) {
                $filters['proveedor_id'] = (int)$projects[array_search($_GET['proyecto_id'], array_column($projects, 'id'))]['proveedor_id'];
            }
            $usersHh = $this->taskModel->getHhUsersProyecto($filters);
            $users = $this->taskModel->getExecutorUsers($filters);

            $filters['excluye_eliminados'] = "1"; //Esta funcionalidad no maneja eliminados porque es solo para los que ejecutan las tareas, y no deberían ver las eliminadas aunque tengan permisos para eso en la vista general
            $_GET['excluye_eliminados'] = $filters['excluye_eliminados'];

            $taskStates = $this->taskModel->getTaskStates($filters);

            $hh = $this->taskModel->DailyCapacity($filters);

            $filters['solo_excedidos'] = isset($_GET['solo_excedidos']) && $_GET['solo_excedidos'] == 1 ? '1' : '0';

            $filters['hh_daily_capacity'] = $hh;

            $modo = $_GET['modo'] ?? 'dia';
            if (!in_array($modo, ['dia', 'semana', 'mes'], true)) {
                $modo = 'dia';
            }
            $_GET['modo'] = $modo;

            $perPage = 7;
            $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
            $offset = ($currentPage - 1) * $perPage;

            switch ($modo) {
                case 'semana':
                    $totalRows = $this->taskModel->countPeriodosSemanales($filters);
                    $rows = $this->taskModel->getHorasSemanales($filters, $perPage, $offset);
                    break;
                case 'mes':
                    $totalRows = $this->taskModel->countPeriodosMensuales($filters);
                    $rows = $this->taskModel->getHorasMensuales($filters, $perPage, $offset);
                    break;
                case 'dia':
                default:
                    $totalRows = $this->taskModel->countPeriodosDiarios($filters);
                    $rows = $this->taskModel->getHorasDiarias($filters, $perPage, $offset);
                    break;
            }

            $totalPages = max(1, ceil($totalRows / $perPage));

            $hoursRows = [];
            foreach ($rows as $row) {
                if ($modo === 'semana') {
                    $inicio = $row['semana_inicio'] ?? '';
                    $fin = $inicio ? (new DateTime($inicio))->modify('+6 days')->format('Y-m-d') : '';
                } elseif ($modo === 'mes') {
                    $inicio = !empty($row['mes']) ? $row['mes'] . '-01' : '';
                    $fin = $inicio ? (new DateTime($inicio))->modify('last day of this month')->format('Y-m-d') : '';
                } else {
                    $inicio = $row['fecha_inicio'] ?? '';
                    $fin = $inicio;
                }

                $row['periodo_inicio'] = $inicio;
                $row['periodo_fin'] = $fin;
                $hoursRows[] = $row;
            }

            $data = [
                'user' => $currentUser,
                'userHh' => $usersHh,
                'hoursRows' => $hoursRows,
                'hh' => $hh,
                'totalRecords' => $totalRows,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'projects' => $projects,
                'taskStates' => $taskStates,
                'users' => $users,
                'filters' => $filters,
                'mode' => $modo,
                'title' => 'Horas planificadas',
                'subtitle' => 'Vista por dia/semana/mes',
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/hours.php';
        } catch (Exception $e) {
            Logger::error("TaskController::horas: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Obtener personas asignadas a tareas en un periodo (JSON)
     */
    public function personasPeriodo()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->jsonUnauthorized('Sesion no valida');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->jsonError(AppConstants::ERROR_METHOD_NOT_ALLOWED, [], 405);
                return;
            }

            $cu = $currentUser['id'];
            $uti = $currentUser['usuario_tipo_id'];
            $contraparteId = $currentUser['contraparte_id'];

            $aManageTask = $this->permissionService->hasMenuAccess($cu, 'manage_tasks');
            if (!$aManageTask) {
                $this->jsonForbidden();
                return;
            }

            $filters = [];
            $filters['current_usuario_tipo_id'] = $uti;
            $filters['current_usuario_id'] = $cu;

            if (!empty($_GET['proyecto_id'])) {
                $filters['proyecto_id'] = (int)$_GET['proyecto_id'];
            }
            if (isset($_GET['estado_tipo_id']) && !empty($_GET['estado_tipo_id'])) {
                $filters['estado_tipo_id'] = $_GET['estado_tipo_id'];
            }
            if (!empty($_GET['usuario_id'])) {
                $filters['usuario_id'] = (int)$_GET['usuario_id'];
            }

            if (!empty($_GET['fecha_inicio'])) {
                $filters['fecha_inicio'] = $_GET['fecha_inicio'];
            }
            if (!empty($_GET['fecha_fin'])) {
                $filters['fecha_fin'] = $_GET['fecha_fin'];
            }
            if (empty($filters['fecha_inicio']) || empty($filters['fecha_fin'])) {
                $this->jsonError('Fechas requeridas', [], 422);
                return;
            }

            if ($uti == 6) {
                $filters['contraparte_id'] = $contraparteId;
            }
            if ($uti == 3) {
                $filters['supervisor_id'] = $cu;
            }
            if ($uti == 2) {
                $filters['planificador_id'] = $cu;
            }
            if (empty($_GET['usuario_id'])) {
                if ($uti == 4) {
                    $filters['ejecutor_id'] = $cu;
                }
            }

            $personas = $this->taskModel->getPersonasTareasPeriodo($filters, 200, 0);
            $this->jsonSuccess('Colaboradores cargados', ['personas' => $personas]);
        } catch (Exception $e) {
            Logger::error("TaskController::personasPeriodo: " . $e->getMessage());
            $this->jsonInternalError();
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

            $uti = $currentUser['usuario_tipo_id'];
            $cu = $currentUser['id'];
            $contraparteId = $currentUser['contraparte_id'];

            $aMyTasks = $this->permissionService->hasMenuAccess($currentUser['id'], 'my_tasks');
            $rRead = $this->permissionService->hasPermission($currentUser['id'], 'Read');

            // Verificar permisos para gestión de tareas
            if (!$aMyTasks) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }
            if (!$rRead) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Obtener filtros
            $filters = [];

            $filters['current_usuario_tipo_id'] = $uti;
            $filters['current_usuario_id'] = $cu;
            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            }

            if (!empty($_GET['proyecto_id'])) {
                $filters['proyecto_id'] = (int)$_GET['proyecto_id'];
            }

            $projects = $this->taskModel->getProjectsActivos($filters);
            if (count($projects) == 1) {
                $_GET['proyecto_id'] = $projects[0]['id'];
            }

            if (!empty($_GET['estado_tipo_id'])) {
                $filters['estado_tipo_id'] = (int)$_GET['estado_tipo_id'];
            }
            if (!empty($_GET['usuario_id'])) {
                $filters['usuario_id'] = (int)$_GET['usuario_id'];
            }

            if ($uti == 3) {
                $filters['supervisor_id'] = $currentUser['id'];
            }
            if ($uti == 2) {
                $filters['planificador_id'] = $currentUser['id'];
            }

            if (empty($_GET['usuario_id'])) {
                if ($uti == 4) {
                    $filters['ejecutor_id'] = $currentUser['id'];
                    $_GET['usuario_id'] = $filters['ejecutor_id'];
                }
            }

            $filters['excluye_eliminados'] = "1"; //Esta funcionalidad no maneja eliminados porque es solo para los que ejecutan las tareas, y no deberían ver las eliminadas aunque tengan permisos para eso en la vista general

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

            if (!empty($_GET['tarea_nombre'])) {
                $filters['tarea_nombre'] = $_GET['tarea_nombre'];
            }

            // Configuración de paginación
            $perPage = 7;
            $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
            $offset = ($currentPage - 1) * $perPage;
            // Contar total de registros según filtros
            $totalRows = $this->taskModel->countAll($filters);
            $totalPages = max(1, ceil($totalRows / $perPage));

            // Obtener registros paginados
            $tasks = $this->taskModel->getAll($filters, $perPage, $offset);

            $taskStates = $this->taskModel->getTaskStatesMyListFilter();
            $users = $this->taskModel->getUsers();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'tasks' => $tasks,
                'totalRecords' => $totalRows,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
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

            // Obtener historial de la tarea
            $taskHistory = $id ? $this->taskModel->getTaskHistory($id) : [];

            if (!empty($taskHistory)) {
                $historialIds = array_column($taskHistory, 'id');
                $historyPhotos = $this->taskModel->getTaskHistoryPhotos($historialIds);

                foreach ($taskHistory as &$historyItem) {
                    $historyId = (int) ($historyItem['id'] ?? 0);
                    $historyItem['fotos'] = $historyPhotos[$historyId] ?? [];
                }
                unset($historyItem);
            }

            // Datos para la vista - ESTANDARIZADO
            $data = [
                'user' => $currentUser,
                'title' => 'Tarea',
                'subtitle' => $task['tarea_nombre'],
                'task_id' => $id,
                'task' => $task,
                'task_history' => $taskHistory,
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
     * Eliminar evidencias temporales de una tarea desde public/uploads
     */
    public function clearHistoryUploads()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->jsonError('Sesión no válida', [], 401);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError(AppConstants::ERROR_METHOD_NOT_ALLOWED, [], 405);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->jsonError('Token CSRF inválido', [], 419);
                return;
            }

            if (!$this->permissionService->hasPermission($currentUser['id'], 'Read')) {
                $this->jsonError(AppConstants::ERROR_NO_PERMISSIONS, [], 403);
                return;
            }

            $taskId = (int) ($_POST['task_id'] ?? 0);
            if ($taskId <= 0) {
                $this->jsonError('Tarea inválida', [], 422);
                return;
            }

            $deletedCount = $this->taskModel->clearTaskHistoryUploads($taskId);
            $this->jsonSuccess('Limpieza de evidencias ejecutada', ['deleted_files' => $deletedCount]);
        } catch (Exception $e) {
            Logger::error('TaskController::clearHistoryUploads: ' . $e->getMessage());
            $this->jsonError('Error interno del servidor', [], 500);
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

            $uti = $currentUser['usuario_tipo_id'];

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $filters = [];

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            }

            $suppliers = $this->taskModel->getSuppliers($filters);

            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_NEW_TASK_TYPE,
                'subtitle' => 'Definición',
                'suppliers' => $suppliers,
                'tasks' => $this->taskModel->getAllTasks($filters), // Catálogo de tareas existentes
                'taskStates' => $this->taskModel->getTaskStatesForNewTask(),
                'taskCategorys' => $this->taskModel->getTaskCategorys(),
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

            $uti = $currentUser['usuario_tipo_id'];
            $cu = $currentUser['id'];
            $contraparteId = $currentUser['contraparte_id'];
            $filters = [];
            $filters['current_usuario_tipo_id'] = $uti;
            $filters['current_usuario_id'] = $cu;
            $filters['contraparte_id'] = $contraparteId;

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            }

            $suppliers = $this->taskModel->getSuppliers($filters);

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
                $projects = $this->taskModel->getProjects($filters);
                if (count($projects) == 1) {
                    $project_id = $projects[0]['id'];
                }
            }

            $supervisors = $this->taskModel->getSupervisorUsers($filters);
            $supervisorId = 0;
            if (count($supervisors) == 1) {
                $supervisorId = $supervisors[0]['id'];
            }

            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_PROJECT_TASK,
                'subtitle' => 'Asignar',
                'suppliers' => $suppliers,
                'projects' => $projects,
                'tasks' => $this->taskModel->getTasksForCreate(),
                'executor_users' => $this->taskModel->getExecutorUsers($filters),
                'supervisor_users' => $supervisors,
                'taskStates' => $this->taskModel->getTaskStatesForCreate(),
                'taskCategorys' => $this->taskModel->getTaskCategorys(),
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
     * Mostrar formulario de creación por proceso
     */
    public function createByProcess()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];
            $cu = $currentUser['id'];
            $contraparteId = $currentUser['contraparte_id'];
            $filters = [];
            $filters['current_usuario_tipo_id'] = $uti;
            $filters['current_usuario_id'] = $cu;
            $filters['contraparte_id'] = $contraparteId;

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            }

            $proveedor_id = isset($_POST['proveedor_id']) && !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : 0;
            $proveedor_id = isset($_GET['proveedor_id']) && !empty($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : $proveedor_id;
            if ($proveedor_id > 0) {
                $filters['proveedor_id'] = $proveedor_id;
            }

            $suppliers = $this->taskModel->getSuppliers($filters);

            if ($uti == 1 && ($suppliers[0]['id'] ?? 0) > 0) {
                $provider_id = $suppliers[0]['id'];
            } else {
                $provider_id = $currentUser['proveedor_id'];
            }
            $filters['proveedor_id'] = $provider_id;

            $processes = $this->processModel->getByProvider($provider_id);

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
                $projects = $this->taskModel->getProjects($filters);
                if (count($projects) == 1) {
                    $project_id = $projects[0]['id'];
                }
            }

            $supervisors = $this->taskModel->getSupervisorUsers($filters);
            $supervisorId = 0;
            if (count($supervisors) == 1) {
                $supervisorId = $supervisors[0]['id'];
            }
            $adresses = $this->taskModel->getDireccionByProyecto($project_id);
            $direccion_id = 0;
            if (count($adresses) == 1) {
                $direccion_id = $adresses[0]['id'];
            }
            $spaces = $this->taskModel->getEspaciosByProyecto($direccion_id);

            $data = [
                'user' => $currentUser,
                'title' => 'Asignar Proceso a Proyecto',
                'subtitle' => 'Asignar',
                'suppliers' => $suppliers,
                'projects' => $projects,
                'processes' => $processes,
                'projectAdresses' => $adresses,
                'projectSpaces' => $spaces,
                'supervisor_users' => $supervisors,
                'taskStates' => $this->taskModel->getTaskStatesForCreate(),
                'project_id' => $project_id,
                'provider_id' => $provider_id,
                'supervisor_id' => $supervisorId,
                'error' => $_GET['error'] ?? ''
            ];

            require_once __DIR__ . '/../Views/tasks/create-task-by-process.php';
        } catch (Exception $e) {
            Logger::error("TaskController::createByProcess: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * AJAX para obtener tareas de un proceso
     */
    public function getProcessTasksJson()
    {
        try {
            $processId = (int)($_GET['process_id'] ?? 0);
            if ($processId <= 0) {
                $this->jsonError('Proceso inválido');
                return;
            }
            $tasks = $this->processModel->getProcessTasks($processId);
            $this->jsonSuccess('Tareas cargadas', ['tasks' => $tasks]);
        } catch (Exception $e) {
            Logger::error("TaskController::getProcessTasksJson: " . $e->getMessage());
            $this->jsonError('Error al obtener tareas del proceso');
        }
    }

    /**
     * AJAX para obtener espacios de una dirección
     */
    public function getEspaciosByDireccionJson()
    {
        try {
            $direccionId = (int)($_GET['direccion_id'] ?? 0);
            if ($direccionId <= 0) {
                $this->jsonError('Dirección inválida');
                return;
            }
            // Usamos el método existente en TaskModel o EspaciosModel
            // Veo que TaskController ya usa $this->taskModel
            $spaces = $this->taskModel->getEspaciosByProyecto($direccionId);
            $this->jsonSuccess('Espacios cargados', ['spaces' => $spaces]);
        } catch (Exception $e) {
            Logger::error("TaskController::getEspaciosByDireccionJson: " . $e->getMessage());
            $this->jsonError('Error al obtener espacios de la dirección');
        }
    }

    /**
     * Guardar tareas por proceso
     */
    public function storeByProcess()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_TASKS);
                return;
            }
            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Security::redirect("/tasks/createByProcess?error=" . urlencode('Token de seguridad inválido'));
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

            $processId = (int)($_POST['proceso_id'] ?? 0);
            $projectId = (int)($_POST['proyecto_id'] ?? 0);

            if ($processId <= 0 || $projectId <= 0) {
                Security::redirect("/tasks/createByProcess?error=" . urlencode('Proceso y Proyecto son obligatorios'));
                return;
            }

            $processTasks = $this->processModel->getProcessTasks($processId);
            if (empty($processTasks)) {
                Security::redirect("/tasks/createByProcess?error=" . urlencode('El proceso no tiene tareas asociadas'));
                return;
            }

            $tipoOcurr = $_POST['optionOcurrencia'];
            $fechaInicio = "";
            $fechaFin = "";
            if ($tipoOcurr == '1') {
                $fechaInicio = $_POST['fecha_inicio_masivo'];
                $fechaFin = $_POST['fecha_fin_masivo'];
            } elseif ($tipoOcurr == '2') {
                $fechaInicio = $_POST['fecha_especifica_inicio'];
                $fechaFin = $_POST['fecha_especifica_fin'] ?? $_POST['fecha_especifica_inicio'];
            } elseif ($tipoOcurr == '3') {
                $fechaInicio = $_POST['fecha_inicio_rango'];
                $fechaFin = $_POST['fecha_fin_rango'];
            } elseif ($tipoOcurr == '4') {
                $fechaInicio = $_POST['fecha_inicio_intervalo'];
                $fechaFin = $_POST['fecha_fin_intervalo'];
            }

            $countSuccess = 0;
            $countTotal = 0;

            // Si recibimos tareas específicas desde el modal
            if (!empty($_POST['tasks_process'])) {
                $countTotal = count($_POST['tasks_process']);
                foreach ($_POST['tasks_process'] as $taskItem) {
                    $taskData = [
                        'proyecto_id' => $projectId,
                        'tarea_id' => (int)$taskItem['tarea_id'],
                        'planificador_id' => $currentUser['id'],
                        'ejecutor_id' => null,
                        'supervisor_id' => !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null,
                        'espacio_id' => !empty($taskItem['espacio_id']) ? (int)$taskItem['espacio_id'] : null,
                        'fecha_inicio' => $fechaInicio,
                        'duracion_horas' => (float)$taskItem['hh'],
                        'fecha_fin' => $fechaFin,
                        'prioridad' => (int)($taskItem['prioridad'] ?? 5),
                        'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 2),
                        'tipo_ocurrencia' => $tipoOcurr,
                        'dias_semana' => $_POST['dias'] ?? [],
                        'intervalo_dias' => (int)($_POST['intervalo_dias'] ?? 0),
                        'duracion_bloque_dias' => (int)($_POST['duracion_bloque_dias'] ?? 0),
                        'dias_semana_intervalo' => $_POST['dias_intervalo'] ?? [],
                        'ajustar_feriados' => !empty($_POST['ajustar_feriados']) ? 1 : 0
                    ];

                    if ($this->taskModel->create($taskData)) {
                        $countSuccess++;
                    }
                }
            } else {
                // Fallback: usar todas las tareas del proceso si no se enviaron detalles (no debería ocurrir con el nuevo JS)
                $countTotal = count($processTasks);
                foreach ($processTasks as $pTask) {
                    $taskData = [
                        'proyecto_id' => $projectId,
                        'tarea_id' => $pTask['tarea_id'],
                        'planificador_id' => $currentUser['id'],
                        'ejecutor_id' => null,
                        'supervisor_id' => !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null,
                        'espacio_id' => null, // No hay espacio_id individual en el fallback
                        'fecha_inicio' => $fechaInicio,
                        'duracion_horas' => (float)$pTask['hh'],
                        'fecha_fin' => $fechaFin,
                        'prioridad' => $pTask['prioridad'] ?? 5,
                        'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 2),
                        'tipo_ocurrencia' => $tipoOcurr,
                        'dias_semana' => $_POST['dias'] ?? [],
                        'intervalo_dias' => (int)($_POST['intervalo_dias'] ?? 0),
                        'duracion_bloque_dias' => (int)($_POST['duracion_bloque_dias'] ?? 0),
                        'dias_semana_intervalo' => $_POST['dias_intervalo'] ?? [],
                        'ajustar_feriados' => !empty($_POST['ajustar_feriados']) ? 1 : 0
                    ];

                    if ($this->taskModel->create($taskData)) {
                        $countSuccess++;
                    }
                }
            }

            if ($countSuccess > 0) {
                Security::redirect("/tasks?success=Se asignaron $countSuccess de $countTotal tareas del proceso correctamente");
            } else {
                Security::redirect("/tasks/createByProcess?error=Error al asignar las tareas del proceso");
            }
        } catch (Exception $e) {
            Logger::error("TaskController::storeByProcess: " . $e->getMessage());
            Security::redirect("/tasks/createByProcess?error=Error interno del servidor");
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
            if ($tipoOcurr == '4') {
                $fechaInicio = $_POST['fecha_inicio_intervalo'];
                $fechaFin = $_POST['fecha_fin_intervalo'];
            }
            // Determinar si usar tarea existente o crear nueva
            $tareaId = 0;
            if (!empty($_POST['tarea_id']) && $_POST['tarea_id'] !== 'nueva') {
                $tareaId = (int)$_POST['tarea_id'];
            } else {
                $tareaId = $this->taskModel->taskCreate(trim($_POST['nueva_tarea_nombre']), trim($_POST['nueva_tarea_descripcion'] ?? ''), (int)($_POST['tarea_categoria_id'] ?? 0), (int)($_POST['proveedor_id'] ?? 0));
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
                'espacio_id' => !empty($_POST['espacio_id']) ? (int)$_POST['espacio_id'] : null,
                'fecha_inicio' => $fechaInicio,
                'duracion_horas' => (float)($_POST['duracion_horas'] ?? 1.0),
                'fecha_fin' => $fechaFin,
                'prioridad' => (int)($_POST['prioridad'] ?? 0),
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 1),
                'tipo_ocurrencia' => $tipoOcurr,
                'dias_semana' => $_POST['dias'] ?? [],
                'intervalo_dias' => (int)($_POST['intervalo_dias'] ?? 0),
                'duracion_bloque_dias' => (int)($_POST['duracion_bloque_dias'] ?? 0),
                'dias_semana_intervalo' => $_POST['dias_intervalo'] ?? [],
                'ajustar_feriados' => !empty($_POST['ajustar_feriados']) ? 1 : 0
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
            $nueva_tarea_categoria_id = trim($_POST['tarea_categoria_id'] ?? '');
            $proveedorId = (int)($_POST['proveedor_id'] ?? 0);
            // Crear tarea
            $taskId = $this->taskModel->taskCreate($nueva_tarea_nombre, $nueva_tarea_descripcion, $nueva_tarea_categoria_id, $proveedorId);
            if ($taskId) {
                Security::redirect("/task/newTask?success=Tarea tipo creada");
            } elseif ($taskId === null) {
                Security::redirect("/task/newTask?error=Error creando tarea tipo, nombre ya existe");
            } else {
                Security::redirect("/task/newTask?error=Error creando tarea tipo");
            }
        } catch (Exception $e) {
            Logger::error("TaskController::storeT: " . $e->getMessage());
            Security::redirect("/tasks/storeT?error=Error interno del servidor");
        }
    }

    /**
     * Guardar nueva tarea tipo (AJAX)
     */
    public function storeTP()
    {
        try {
            // Asegurar respuesta JSON
            header('Content-Type: application/json');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'error' => 'Método no permitido'
                ]);
                return;
            }

            // Verificar CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Token de seguridad inválido'
                ]);
                return;
            }

            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Usuario no autenticado'
                ]);
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_task')) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Sin permisos'
                ]);
                return;
            }

            // Obtener datos
            $nombre = trim($_POST['nueva_tarea_nombre'] ?? '');
            $descripcion = trim($_POST['nueva_tarea_descripcion'] ?? '');
            $categoriaId = trim($_POST['tarea_categoria_id'] ?? '');
            $estadoId = trim($_POST['estado_tipo_id'] ?? '');
            $proveedorId = (int)($_POST['proveedor_id'] ?? 0);

            // Validaciones básicas backend (no confíes solo en JS)
            if (!$nombre) {
                echo json_encode([
                    'success' => false,
                    'error' => 'El nombre es obligatorio'
                ]);
                return;
            }

            if (!$proveedorId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Proveedor inválido'
                ]);
                return;
            }

            // Crear tarea
            $taskId = $this->taskModel->taskCreate(
                $nombre,
                $descripcion,
                $categoriaId,
                $proveedorId,
                $estadoId // asegúrate que tu modelo lo soporte
            );

            if ($taskId) {
                echo json_encode([
                    'success' => true,
                    'task_id' => $taskId,
                    'message' => 'Tarea creada correctamente'
                ]);
            } elseif ($taskId === null) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Error creando tarea, nombre ya existe'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'No se pudo crear la tarea'
                ]);
            }
        } catch (Exception $e) {
            Logger::error("TaskController::storeTP: " . $e->getMessage());

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor'
            ]);
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

            $uti = $currentUser['usuario_tipo_id'];
            $cu = $currentUser['id'];
            $contraparteId = $currentUser['contraparte_id'];
            $filters = [];
            $filters['current_usuario_tipo_id'] = $uti;
            $filters['current_usuario_id'] = $cu;
            $filters['contraparte_id'] = $contraparteId;

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

            // Capturar filtros para propagar
            $filterParams = $_GET;
            unset($filterParams['id'], $filterParams['error'], $filterParams['success']);

            $adresses = $this->taskModel->getDireccionByProyecto($task['proyecto_id']);
            $spaces = $this->taskModel->getEspaciosByProyecto($task['direccion_id'] ?? 0);

            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_EDIT_TASK_TITLE,
                'subtitle' => "Editando: {$task['tarea_nombre']}",
                'projects' => $this->taskModel->getProjects($filters),
                'projectAdresses' => $adresses,
                'projectSpaces' => $spaces,
                'taskTypes' => $this->taskModel->getTaskTypes(),
                'executor_users' => $this->taskModel->getExecutorUsers($filters),
                'supervisor_users' => $this->taskModel->getSupervisorUsers(),
                'taskStates' => $this->taskModel->getTasksForCreate(),
                'task' => $task,
                'task_id' => $id,  // Mantener para compatibilidad
                'action' => 'edit',  // Estandarizar
                'filters' => $filterParams, // Pasar filtros a la vista
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
                'espacio_id' => !empty($_POST['espacio_id']) ? (int)$_POST['espacio_id'] : null,
                'fecha_inicio' => $_POST['fecha_inicio'],
                'duracion_horas' => (float)($_POST['duracion_horas'] ?? 1.0),
                'prioridad' => (int)($_POST['prioridad'] ?? 0),
                'espacio_id' => !empty($_POST['espacio_id']) ? (int)$_POST['espacio_id'] : null,
                'estado_tipo_id' => (int)$_POST['estado_tipo_id']
            ];

            // Actualizar tarea
            if ($this->taskModel->update($id, $taskData)) {
                $queryString = "";
                if (!empty($_POST['filters'])) {
                    $queryString = "&" . http_build_query($_POST['filters']);
                }
                Security::redirect("/tasks?success=Tarea actualizada correctamente" . $queryString);
            } else {
                $queryString = "";
                if (!empty($_POST['filters'])) {
                    $queryString = "&" . http_build_query($_POST['filters']);
                }
                Security::redirect("/tasks/edit?id={$id}&error=Error al actualizar la tarea" . $queryString);
            }
        } catch (Exception $e) {
            Logger::error("TaskController::update: " . $e->getMessage());
            $id = (int)($_POST['id'] ?? 0);
            $queryString = "";
            if (!empty($_POST['filters'])) {
                $queryString = "&" . http_build_query($_POST['filters']);
            }
            Security::redirect("/tasks/edit?id={$id}&error=Error interno del servidor" . $queryString);
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
                'estado_tipo_id' => (int)$_POST['editEstadoTipoId'],
                'tarea_categoria_id' => (int)$_POST['editCategoriaId']
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
            $deleteAllOccurrences = (int)($_POST['delete_all_occurrences'] ?? 0) === 1;
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
            if ($this->taskModel->delete($id, $deleteAllOccurrences)) {
                // Si es petición AJAX, devolver JSON
                if (
                    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
                ) {
                    $this->jsonSuccess(
                        $deleteAllOccurrences
                            ? 'Tareas eliminadas correctamente para el proyecto'
                            : 'Tarea eliminada correctamente'
                    );
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
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];

            $idCategoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
            $idProveedor = isset($_GET['proveedor']) ? (int)$_GET['proveedor'] : 0;

            $filters = [];

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            } else {
                if ($idProveedor > 0) {
                    $filters['proveedor_id'] = $idProveedor;
                }
            }

            if ($idCategoria > 0) {
                $filters['categoria_id'] = $idCategoria;
            }

            $tasks = $idCategoria == 0 ? $this->taskModel->getAllTasks($filters) : $this->taskModel->getGroupTasks($filters);
            $this->jsonSuccess('Tareas cargadas correctamente', ['tareas' => $tasks]);
        } catch (Exception $e) {
            Logger::error("UserController::refreshTasksTable: " . $e->getMessage());
            $this->jsonError('Error al actualizar lista de tareas', [], 500);
        }
    }


    /**
     * Cargar tareas para el select de asignación (JSON)
     */
    public function refreshTaskSelect()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];

            $idCategoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
            $idProveedor = isset($_GET['proveedor']) ? (int)$_GET['proveedor'] : 0;

            $filters = [];

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            } else {
                if ($idProveedor > 0) {
                    $filters['proveedor_id'] = $idProveedor;
                }
            }

            if ($idCategoria > 0) {
                $filters['categoria_id'] = $idCategoria;
            }

            $tasks = $this->taskModel->getTasksForCreate($filters);
            $this->jsonSuccess('Tareas cargadas correctamente', ['tareas' => $tasks]);
        } catch (Exception $e) {
            Logger::error("TaskController::refreshTaskSelect: " . $e->getMessage());
            $this->jsonError('Error al cargar tareas', [], 500);
        }
    }

    /**
     * Cargar proyectos por proveedor (JSON)
     */
    public function refreshProjectsSelect()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];
            $cu = $currentUser['id'];
            $contraparteId = $currentUser['contraparte_id'];

            $idProveedor = isset($_GET['proveedor']) ? (int)$_GET['proveedor'] : 0;

            $filters = [
                'current_usuario_tipo_id' => $uti,
                'current_usuario_id' => $cu,
                'contraparte_id' => $contraparteId
            ];

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            } else {
                if ($idProveedor > 0) {
                    $filters['proveedor_id'] = $idProveedor;
                }
            }

            $projects = $this->taskModel->getProjects($filters);
            $this->jsonSuccess('Proyectos cargados correctamente', ['projects' => $projects]);
        } catch (Exception $e) {
            Logger::error("TaskController::refreshProjectsSelect: " . $e->getMessage());
            $this->jsonError('Error al cargar proyectos', [], 500);
        }
    }

    /**
     * Cargar espacios por proyecto (JSON)
     */
    public function refreshDireccionSelect()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $projectId = (isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0);
            if ($projectId <= 0) {
                $this->jsonSuccess('Proyecto no seleccionado', ['direcciones' => []]);
                return;
            }

            if (!$this->taskModel->userHasProjectAccess((int)$currentUser['id'], $projectId)) {
                $this->jsonError('Proyecto invalido', [], 403);
                return;
            }

            $direcciones = $this->taskModel->getDireccionByProyecto($projectId);
            $this->jsonSuccess('Direcciones cargadas correctamente', ['direcciones' => $direcciones]);
        } catch (Exception $e) {
            Logger::error("TaskController::refreshDireccionSelect: " . $e->getMessage());
            $this->jsonError('Error al cargar direcciones', [], 500);
        }
    }

    /**
     * Cargar espacios por proyecto (JSON)
     */
    public function refreshSpacesSelect()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $projectId = isset($_GET['direccion_id']) ? (int)$_GET['direccion_id'] : 0;
            if ($projectId <= 0) {
                $this->jsonSuccess('Dirección no seleccionada', ['espacios' => []]);
                return;
            }

            if (!$this->taskModel->userHasProjectAccess((int)$currentUser['id'], $projectId)) {
                $this->jsonError('Dirección invalida', [], 403);
                return;
            }

            $espacios = $this->taskModel->getEspaciosByProyecto($projectId);
            $this->jsonSuccess('Espacios cargados correctamente', ['espacios' => $espacios]);
        } catch (Exception $e) {
            Logger::error("TaskController::refreshSpacesSelect: " . $e->getMessage());
            $this->jsonError('Error al cargar espacios', [], 500);
        }
    }

    /**
     * Cargar supervisores por proveedor o proyecto (JSON)
     */
    public function refreshSupervisorSelect()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];
            $idProveedor = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;
            $idProyecto = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;

            $filters = [];

            if ($uti > 1) {
                $filters['proveedor_id'] = $currentUser['proveedor_id'];
            } else {
                if ($idProveedor > 0) {
                    $filters['proveedor_id'] = $idProveedor;
                }
            }

            if ($idProyecto > 0) {
                $filters['proyecto_id'] = $idProyecto;
            }

            $supervisors = $this->taskModel->getSupervisorUsers($filters);
            $this->jsonSuccess('Supervisores cargados correctamente', ['supervisors' => $supervisors]);
        } catch (Exception $e) {
            Logger::error("TaskController::refreshSupervisorSelect: " . $e->getMessage());
            $this->jsonError('Error al cargar supervisores', [], 500);
        }
    }

    /**
     * Cargar procesos por proveedor (JSON)
     */
    public function refreshProcessesSelect()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];
            $idProveedor = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;

            if ($uti > 1) {
                $providerId = $currentUser['proveedor_id'];
            } else {
                $providerId = $idProveedor;
            }

            if ($providerId <= 0) {
                $this->jsonSuccess('Proveedor no especificado', ['processes' => []]);
                return;
            }

            $processes = $this->processModel->getByProvider($providerId);
            $this->jsonSuccess('Procesos cargados correctamente', ['processes' => $processes]);
        } catch (Exception $e) {
            Logger::error("TaskController::refreshProcessesSelect: " . $e->getMessage());
            $this->jsonError('Error al cargar procesos', [], 500);
        }
    }

    /**
     * Buscar tareas para autocompletar (JSON)
     */
    public function searchTasksAutocomplete()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->jsonUnauthorized();
                return;
            }

            $term = $_GET['term'] ?? '';
            if (strlen($term) < 2) {
                $this->jsonSuccess('Término muy corto', ['tasks' => []]);
                return;
            }

            $filters = [
                'current_usuario_tipo_id' => $currentUser['usuario_tipo_id'],
                'current_usuario_id' => $currentUser['id'],
                'term' => $term
            ];

            // Aplicar filtros adicionales de la URL si existen
            if (!empty($_GET['proyecto_id'])) $filters['proyecto_id'] = (int)$_GET['proyecto_id'];
            if (!empty($_GET['proveedor_id'])) $filters['proveedor_id'] = (int)$_GET['proveedor_id'];
            if (!empty($_GET['fecha_inicio'])) $filters['fecha_inicio'] = $_GET['fecha_inicio'];
            if (!empty($_GET['fecha_fin'])) $filters['fecha_fin'] = $_GET['fecha_fin'];
            if (!empty($_GET['usuario_id']) && $_GET['usuario_id'] != -1) $filters['usuario_id'] = (int)$_GET['usuario_id'];
            if (isset($_GET['excluye_eliminados']) && $_GET['excluye_eliminados'] == 1) $filters['excluye_eliminados'] = 1;

            $tasks = $this->taskModel->searchAutocomplete($filters);
            $this->jsonSuccess('Tareas encontradas', ['tasks' => $tasks]);
        } catch (Exception $e) {
            Logger::error("TaskController::searchTasksAutocomplete: " . $e->getMessage());
            $this->jsonError('Error al buscar tareas', [], 500);
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
            $rActivity = $this->permissionService->hasPermission($currentUser['id'], 'Register activity');
            $rApruve = $this->permissionService->hasPermission($currentUser['id'], 'Apruve');
            // Verificar accesos
            if (!$aManageTask && !$aMyTasks) {
                $this->jsonError('Sin acceso suficiente');
                return;
            }
            if (!$rActivity) {
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

            if ($newState == 8 && !$rApruve) {
                $this->jsonError('Sin permisos para aprobar');
                return;
            }

            $reason = trim($_POST['reason'] ?? '');
            $photoProcessing = $this->processEvidencePhotos($_FILES['photos'] ?? null);
            if (!$photoProcessing['success']) {
                $this->jsonError($photoProcessing['message'] ?? 'No fue posible procesar las fotos de evidencia');
                return;
            }

            if ($taskId <= 0 || $newState <= 0) {
                $this->removeEvidenceFiles($photoProcessing['photos'] ?? []);
                $this->jsonError('Error al cambiar estado de la tarea');
                return;
            }

            // Cambiar estado usando el modelo con validaciones
            $result = $this->taskModel->changeState(
                $taskId,
                $newState,
                $currentUser['id'],
                $currentUser['rol'],
                $reason,
                $photoProcessing['photos'] ?? []
            );

            if ($result['success']) {
                $this->jsonSuccess($result['message'] ?? 'Estado de tarea actualizado correctamente');
            } else {
                $this->removeEvidenceFiles($photoProcessing['photos'] ?? []);
                $this->jsonError($result['message'] ?? 'Error al cambiar estado de la tarea');
            }
        } catch (Exception $e) {
            Logger::error("TaskController::changeState: " . $e->getMessage());
            $this->jsonError('Error interno del servidor');
        }
    }

    private function processEvidencePhotos($uploadedPhotos): array
    {
        $files = $this->normalizeUploadedPhotos($uploadedPhotos);
        if (empty($files)) {
            return ['success' => true, 'photos' => []];
        }

        $photoPaths = [];
        try {
            $photoDir = dirname(__DIR__, 3) . '/storage/fotos';
            if (!is_dir($photoDir) && !mkdir($photoDir, 0775, true) && !is_dir($photoDir)) {
                throw new RuntimeException('No fue posible preparar el directorio de fotos.');
            }

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

            foreach ($files as $file) {
                $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($uploadError === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($uploadError !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Error subiendo una de las fotos de evidencia.');
                }

                $tmpPath = (string)($file['tmp_name'] ?? '');
                if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                    throw new RuntimeException('Archivo de foto inválido o no recibido correctamente.');
                }

                $imgInfo = @getimagesize($tmpPath);
                $mimeType = $imgInfo['mime'] ?? '';
                if (!in_array($mimeType, $allowedMimeTypes, true)) {
                    throw new RuntimeException('Solo se permiten fotos JPG, PNG o WEBP.');
                }

                $relativePath = $this->optimizeAndSaveImage($tmpPath, $mimeType, $photoDir);
                $photoPaths[] = $relativePath;
            }

            return ['success' => true, 'photos' => $photoPaths];
        } catch (Exception $e) {
            $this->removeEvidenceFiles($photoPaths);
            Logger::error('TaskController::processEvidencePhotos: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function normalizeUploadedPhotos($uploadedPhotos): array
    {
        if (empty($uploadedPhotos) || !isset($uploadedPhotos['error'])) {
            return [];
        }

        if (!is_array($uploadedPhotos['error'])) {
            return [$uploadedPhotos];
        }

        $normalized = [];
        $count = count($uploadedPhotos['error']);

        for ($i = 0; $i < $count; $i++) {
            $normalized[] = [
                'name' => $uploadedPhotos['name'][$i] ?? '',
                'type' => $uploadedPhotos['type'][$i] ?? '',
                'tmp_name' => $uploadedPhotos['tmp_name'][$i] ?? '',
                'error' => $uploadedPhotos['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $uploadedPhotos['size'][$i] ?? 0,
            ];
        }

        return $normalized;
    }

    private function optimizeAndSaveImage(string $tmpPath, string $mimeType, string $photoDir): string
    {
        $maxDimension = 1280;

        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($tmpPath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($tmpPath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($tmpPath);
                break;
            default:
                throw new RuntimeException('Formato de imagen no soportado.');
        }

        if (!$sourceImage) {
            throw new RuntimeException('No fue posible leer una de las imágenes enviadas.');
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $scale = min($maxDimension / max($width, 1), $maxDimension / max($height, 1), 1);

        $newWidth = (int)max(1, floor($width * $scale));
        $newHeight = (int)max(1, floor($height * $scale));

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        if (!$resizedImage) {
            imagedestroy($sourceImage);
            throw new RuntimeException('No fue posible optimizar una imagen.');
        }

        $isTransparent = in_array($mimeType, ['image/png', 'image/webp'], true);
        if ($isTransparent) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $fileName = sprintf('evidencia_%s_%s.jpg', date('YmdHis'), bin2hex(random_bytes(8)));
        $fullPath = $photoDir . '/' . $fileName;
        $saved = imagejpeg($resizedImage, $fullPath, 75);

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        if (!$saved) {
            throw new RuntimeException('No fue posible almacenar una foto de evidencia.');
        }

        return 'storage/fotos/' . $fileName;
    }

    private function removeEvidenceFiles(array $photos): void
    {
        if (empty($photos)) {
            return;
        }

        $basePath = dirname(__DIR__, 3) . '/';
        foreach ($photos as $relativePath) {
            $fullPath = $basePath . ltrim($relativePath, '/');
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    /**
     * Cambiar estado de un grupo de tareas (GAP 5)
     */
    public function changeStateFSR()
    {
        try {
            $errorMsg = "";
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $aManageTask = $this->permissionService->hasMenuAccess($currentUser['id'], 'manage_tasks');
            $aMyTasks = $this->permissionService->hasMenuAccess($currentUser['id'], 'my_tasks');
            $rActivity = $this->permissionService->hasPermission($currentUser['id'], 'Register activity');
            $rApruve = $this->permissionService->hasPermission($currentUser['id'], 'Apruve');
            // Verificar accesos
            if (!$aManageTask && !$aMyTasks) {
                $this->jsonError('Sin acceso suficiente');
                return;
            }
            if (!$rActivity) {
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
            $newState = (int)($_POST['new_state'] ?? 0);
            $newStateAccionName = $newState == 8 ? 'Aprobadas' : ($newState == 6 ? 'Terminadas' : '');
            $reason = trim($_POST['reason'] ?? '');

            if ($newState !== 8 && $newState !== 6) {
                $this->jsonError('Masivamente solo puedes cambiar a Aprobado/Terminado');
                return;
            }
            if ($newState == 8 && !$rApruve) {
                $this->jsonError('Sin permisos para aprobar');
                return;
            }

            $taskIdsRaw = $_POST['task_ids'] ?? null;
            $taskIdSingle = (int)($_POST['task_id'] ?? 0);
            $taskIds = [];

            if (!empty($taskIdsRaw)) {
                if (is_array($taskIdsRaw)) {
                    $taskIds = array_map('intval', $taskIdsRaw);
                } else {
                    $decoded = json_decode($taskIdsRaw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $taskIds = array_map('intval', $decoded);
                    } else {
                        $taskIds = array_map('intval', explode(',', (string)$taskIdsRaw));
                    }
                }
            } elseif ($taskIdSingle > 0) {
                $taskIds = [$taskIdSingle];
            }

            if (empty($taskIds)) {
                $this->jsonError('No se recibieron tareas válidas para aprobar');
                return;
            }

            $allowedStates = [5, 6, 7];
            $approved = 0;
            $skipped = 0;
            $updatedIds = [];

            foreach ($taskIds as $tid) {
                if ($tid <= 0) {
                    $skipped++;
                    continue;
                }

                $currentState = (int)$this->taskModel->getProjectTaskState($tid);
                if (!in_array($currentState, $allowedStates)) {
                    $skipped++;
                    continue;
                }

                $result = $this->taskModel->changeState(
                    $tid,
                    $newState,
                    $currentUser['id'],
                    $currentUser['rol'],
                    $reason
                );

                if (!empty($result['success'])) {
                    $approved++;
                    $updatedIds[] = $tid;
                } else {
                    $skipped++;
                    $errorMsg .= (!empty($errorMsg) ? '; ' : '') . $result['message'];
                }
            }

            if ($approved > 0) {
                $message = $newStateAccionName . " {$approved} tareas";
                if ($skipped > 0) {
                    $message .= "; omitidas {$skipped}";
                }
                $this->jsonSuccess($message, ['updated_ids' => $updatedIds]);
            } else {
                $this->jsonError($errorMsg);
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
        $transitions = [];
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

        // Validar espacio (opcional)
        if (!empty($data['espacio_id'])) {
            if (!is_numeric($data['espacio_id'])) {
                $errors[] = "El espacio seleccionado no es valido";
            } elseif (!empty($data['proyecto_id']) && is_numeric($data['proyecto_id'])) {
                if (!$this->taskModel->isEspacioInProyecto((int)$data['espacio_id'], (int)$data['proyecto_id'])) {
                    $errors[] = "El espacio seleccionado no pertenece al proyecto";
                }
            }
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

        $diasIntervalo = $data['dias_intervalo'] ?? [];
        $diasIntervalo = array_map('intval', $diasIntervalo);

        $fechaInicio = "";
        $fechaFin = "";
        if ($tipoOcurrencia == null) {
            $fechaInicio = $data['fecha_inicio'];
            $fechaFin = $data['fecha_fin'];
        }
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
        if ($tipoOcurrencia == '4') {
            $fechaInicio = $data['fecha_inicio_intervalo'];
            $fechaFin = $data['fecha_fin_intervalo'];
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

        if ($tipoOcurrencia == '4') {
            $intervaloDias = (int)($data['intervalo_dias'] ?? 0);
            $duracionBloque = (int)($data['duracion_bloque_dias'] ?? 0);
            if ($intervaloDias < 1) {
                $errors[] = 'El intervalo en dias debe ser mayor o igual a 1';
            }
            if ($duracionBloque < 1) {
                $errors[] = 'La duracion del bloque debe ser mayor o igual a 1';
            }

            $cursor = new \DateTime($fechaInicio);
            $endCheck = new \DateTime($fechaFin);
            $hasDate = false;
            $useFilter = !empty($diasIntervalo);
            while ($cursor <= $endCheck) {
                for ($i2 = 0; $i2 < $duracionBloque; $i2++) {
                    $candidate = (clone $cursor)->add(new \DateInterval('P' . $i2 . 'D'));
                    if ($candidate > $endCheck) {
                        break;
                    }
                    if ($useFilter) {
                        $dayOfWeek = (int)$candidate->format('w');
                        if (!in_array($dayOfWeek, $diasIntervalo)) {
                            continue;
                        }
                    }
                    $hasDate = true;
                    break 2;
                }
                $cursor->add(new \DateInterval('P' . max($intervaloDias, 1) . 'D'));
            }

            if (!$hasDate) {
                $errors[] = 'La configuracion de intervalo no genera fechas para tarea';
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
