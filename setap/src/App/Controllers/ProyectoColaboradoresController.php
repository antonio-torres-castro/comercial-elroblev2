<?php

namespace App\Controllers;

use App\Models\Project;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;

use App\Models\ProyectoColaboradores;
use App\Models\Task;
use App\Constants\AppConstants;
use Exception;

class ProyectoColaboradoresController extends BaseController
{
    private $proyectoColaboradoresModel;
    private $projectModel;
    private $taskModel;
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        $this->proyectoColaboradoresModel = new ProyectoColaboradores();
        $this->projectModel = new Project();
        $this->taskModel = new Task();
        $this->permissionService = new PermissionService();
    }

    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $userId = (int)$currentUser['id'];
            $uti = (int)$currentUser['usuario_tipo_id'];

            $canAccess = $this->permissionService->hasMenuAccess($userId, 'manage_project')
                || $this->permissionService->hasMenuAccess($userId, 'manage_projects');
            if (!$canAccess) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $filters = [
                'current_usuario_tipo_id' => $uti,
                'current_usuario_id' => $userId,
            ];

            if (!empty($_GET['proyecto_id'])) {
                $filters['proyecto_id'] = (int)$_GET['proyecto_id'];
            }

            if ($uti > 1 && !empty($currentUser['proveedor_id'])) {
                $filters['proveedor_id'] = (int)$currentUser['proveedor_id'];
            }

            if ($uti === 6) {
                $filters['contraparte_id'] = $currentUser['contraparte_id'];
            }
            if ($uti === 3) {
                $filters['supervisor_id'] = $userId;
            }
            if ($uti === 2) {
                $filters['planificador_id'] = $userId;
            }

            $projects = $this->taskModel->getProjects($filters);
            if (count($projects) === 1) {
                $_GET['proyecto_id'] = $projects[0]['id'];
            } elseif (count($projects) > 0 && empty($filters['proyecto_id'])) {
                $filters['proyecto_id'] = (int)$projects[0]['id'];
                $_GET['proyecto_id'] = $filters['proyecto_id'];
            }

            $projectId = (int)($_GET['proyecto_id'] ?? 0);
            if ($projectId <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'ID de proyecto requerido');
                return;
            }

            $project = $this->projectModel->find($projectId);
            $projectHolidays = $this->projectModel->getProjectHolidays($projectId);
            if (!$project) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_PROJECT_NOT_FOUND);
                return;
            }

            $holidayPerPage = 5;
            $holidayTotalRows = count($projectHolidays);
            $holidayTotalPages = max(1, (int)ceil($holidayTotalRows / $holidayPerPage));
            $holidayCurrentPage = isset($_GET['holiday_page']) && is_numeric($_GET['holiday_page']) && (int)$_GET['holiday_page'] > 0
                ? (int)$_GET['holiday_page']
                : 1;
            if ($holidayCurrentPage > $holidayTotalPages) {
                $holidayCurrentPage = $holidayTotalPages;
            }
            $holidayOffset = ($holidayCurrentPage - 1) * $holidayPerPage;
            $projectHolidaysPage = $holidayTotalRows > 0 ? array_slice($projectHolidays, $holidayOffset, $holidayPerPage) : [];

            $fechaInicio = $_GET['fecha_inicio'] ?? $project['fecha_inicio'];
            $fechaFin = $_GET['fecha_fin'] ?? $project['fecha_fin'];
            if (!$fechaFin) {
                $fechaFin = $fechaInicio;
            }

            if (!$this->isValidDate($fechaInicio) || !$this->isValidDate($fechaFin)) {
                $fechaInicio = $project['fecha_inicio'];
                $fechaFin = $project['fecha_fin'] ?: $project['fecha_inicio'];
            }

            if (strtotime($fechaInicio) > strtotime($fechaFin)) {
                $tmp = $fechaInicio;
                $fechaInicio = $fechaFin;
                $fechaFin = $tmp;
            }

            $tiposFecha = $this->proyectoColaboradoresModel->getTiposFecha();
            $selectedTipos = $_GET['tipos'] ?? [];
            if (!is_array($selectedTipos)) {
                $selectedTipos = [];
            }
            $selectedTipos = array_map('intval', $selectedTipos);

            $executors = $this->proyectoColaboradoresModel->getProjectExecutors($projectId);
            $isAdmin = $uti === 1;
            $availableExecutors = $this->proyectoColaboradoresModel->getAvailableExecutors(
                $projectId,
                (int)($project['proveedor_id'] ?? 0),
                $isAdmin
            );

            $selectedUserId = (int)($_GET['usuario_id'] ?? 0);
            $executorIds = array_map(fn($row) => (int)$row['usuario_id'], $executors);
            if ($selectedUserId > 0 && !in_array($selectedUserId, $executorIds, true)) {
                $selectedUserId = 0;
            }

            $calendarExists = false;
            if ($selectedUserId > 0) {
                $calendarExists = $this->proyectoColaboradoresModel->countDisponibilidadByUserProject(
                    $projectId,
                    $selectedUserId,
                    4
                ) > 0;
            }

            $workingDays = $this->proyectoColaboradoresModel->getWorkingDays($fechaInicio, $fechaFin);

            if (!empty($projectHolidays)) {
                $holidayMap = array_flip($projectHolidays);
                $workingDays = array_values(array_filter($workingDays, function ($fecha) use ($holidayMap) {
                    return !isset($holidayMap[$fecha]);
                }));
            }

            $overassignedMap = [];
            if (!empty($executors) && !empty($workingDays)) {
                $dispoRows = $this->proyectoColaboradoresModel->getDisponibilidadByUsers(
                    $projectId,
                    $executorIds,
                    4,
                    $fechaInicio,
                    $fechaFin
                );
                $dispoMap = [];
                foreach ($dispoRows as $row) {
                    $uid = (int)$row['usuario_id'];
                    $fecha = $row['fecha'];
                    $dispoMap[$uid][$fecha] = [
                        'hh' => (float)$row['hh'],
                        'tipo_fecha_id' => (int)$row['tipo_fecha_id'],
                    ];
                }

                $otrosRows = $this->proyectoColaboradoresModel->getHorasOtrosProyectosByUsers(
                    $executorIds,
                    $projectId,
                    $fechaInicio,
                    $fechaFin
                );
                $otrosMap = [];
                foreach ($otrosRows as $row) {
                    $uid = (int)$row['usuario_id'];
                    $fecha = $row['fecha'];
                    $otrosMap[$uid][$fecha] = (float)$row['hh_op'];
                }

                foreach ($executors as $executor) {
                    $uid = (int)$executor['usuario_id'];
                    $defaultHh = isset($executor['hh_default']) ? (float)$executor['hh_default'] : 9.0;
                    $isOver = false;
                    foreach ($workingDays as $fecha) {
                        $hhBase = $dispoMap[$uid][$fecha]['hh'] ?? $defaultHh;
                        $hhOp = $otrosMap[$uid][$fecha] ?? 0.0;
                        if (($hhBase + $hhOp) > 9) {
                            $isOver = true;
                            break;
                        }
                    }
                    $overassignedMap[$uid] = $isOver;
                }
            }

            $calendarRows = [];
            if ($selectedUserId > 0) {
                $tipoMap = [];
                foreach ($tiposFecha as $tipo) {
                    $tipoMap[(int)$tipo['id']] = $tipo['nombre'];
                }

                $dispoRows = $this->proyectoColaboradoresModel->getDisponibilidad(
                    $projectId,
                    $selectedUserId,
                    4,
                    $fechaInicio,
                    $fechaFin
                );
                $dispoMap = [];
                foreach ($dispoRows as $row) {
                    $dispoMap[$row['fecha']] = [
                        'id' => (int)$row['id'],
                        'hh' => (float)$row['hh'],
                        'tipo_fecha_id' => (int)$row['tipo_fecha_id'],
                        'tipo_fecha_nombre' => $row['tipo_fecha_nombre'] ?? ($tipoMap[(int)$row['tipo_fecha_id']] ?? ''),
                    ];
                }

                $otrosRows = $this->proyectoColaboradoresModel->getHorasOtrosProyectos(
                    $selectedUserId,
                    $projectId,
                    $fechaInicio,
                    $fechaFin
                );
                $otrosMap = [];
                foreach ($otrosRows as $row) {
                    $otrosMap[$row['fecha']] = (float)$row['hh_op'];
                }

                $defaultHh = 9.0;
                foreach ($executors as $executor) {
                    if ((int)$executor['usuario_id'] === $selectedUserId) {
                        $defaultHh = isset($executor['hh_default']) ? (float)$executor['hh_default'] : 9.0;
                        break;
                    }
                }

                $allDates = $workingDays;
                foreach (array_keys($dispoMap) as $fecha) {
                    if (!in_array($fecha, $allDates, true)) {
                        $allDates[] = $fecha;
                    }
                }
                sort($allDates);

                foreach ($allDates as $fecha) {
                    $row = $dispoMap[$fecha] ?? null;
                    $tipoId = $row ? (int)$row['tipo_fecha_id'] : 1;
                    if (!empty($selectedTipos) && !in_array($tipoId, $selectedTipos, true)) {
                        continue;
                    }

                    $tipoNombre = $row ? $row['tipo_fecha_nombre'] : ($tipoMap[$tipoId] ?? 'laboral');
                    $hh = $row ? (float)$row['hh'] : $defaultHh;
                    $hhOp = $otrosMap[$fecha] ?? 0.0;
                    $total = $hh + $hhOp;

                    $calendarRows[] = [
                        'id' => $row['id'] ?? 0,
                        'fecha' => $fecha,
                        'tipo_fecha_id' => $tipoId,
                        'tipo_fecha_nombre' => $tipoNombre,
                        'hh' => $hh,
                        'hh_op' => $hhOp,
                        'total' => $total,
                    ];
                }
            }

            $calendarPerPage = 12;
            $calendarTotalRows = count($calendarRows);
            $calendarTotalPages = max(1, (int)ceil($calendarTotalRows / $calendarPerPage));
            $calendarCurrentPage = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0
                ? (int)$_GET['page']
                : 1;
            if ($calendarCurrentPage > $calendarTotalPages) {
                $calendarCurrentPage = $calendarTotalPages;
            }
            $calendarOffset = ($calendarCurrentPage - 1) * $calendarPerPage;
            if ($calendarTotalRows > 0) {
                $calendarRows = array_slice($calendarRows, $calendarOffset, $calendarPerPage);
            }

            $data = [
                'project' => $project,
                'projects' => $projects,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tipos_fecha' => $tiposFecha,
                'selected_tipos' => $selectedTipos,
                'executors' => $executors,
                'available_executors' => $availableExecutors,
                'selected_user_id' => $selectedUserId,
                'calendar_rows' => $calendarRows,
                'calendar_total_rows' => $calendarTotalRows,
                'calendar_total_pages' => $calendarTotalPages,
                'calendar_current_page' => $calendarCurrentPage,
                'calendar_per_page' => $calendarPerPage,
                'calendar_exists' => $calendarExists,
                'overassigned_map' => $overassignedMap,
                'project_holidays_page' => $projectHolidaysPage,
                'holiday_total_rows' => $holidayTotalRows,
                'holiday_total_pages' => $holidayTotalPages,
                'holiday_current_page' => $holidayCurrentPage,
                'holiday_per_page' => $holidayPerPage,
            ];

            extract($data);
            require __DIR__ . '/../Views/proyecto-colaboradores/index.php';
        } catch (Exception $e) {
            Logger::error('ProyectoColaboradoresController::index error: ' . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function addExecutor()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $userId = (int)$currentUser['id'];
            $canAccess = $this->permissionService->hasMenuAccess($userId, 'manage_project')
                || $this->permissionService->hasMenuAccess($userId, 'manage_projects');
            if (!$canAccess || !$this->permissionService->hasPermission($userId, 'Create')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_NO_PERMISSIONS]);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $hhDefault = (float)($_POST['hh_default'] ?? 9);

            if ($projectId <= 0 || $usuarioId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parametros incompletos']);
                return;
            }

            $result = $this->proyectoColaboradoresModel->addExecutorToProject(
                $projectId,
                $usuarioId,
                4,
                $hhDefault
            );

            if (!$result['success']) {
                echo json_encode(['success' => false, 'message' => $result['message'] ?? 'No se pudo agregar']);
                return;
            }

            echo json_encode(['success' => true, 'message' => 'Ejecutor agregado al proyecto']);
        } catch (Exception $e) {
            Logger::error('ProyectoColaboradoresController::addExecutor error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    public function saveCalendar()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $userId = (int)$currentUser['id'];
            $canAccess = $this->permissionService->hasMenuAccess($userId, 'manage_project')
                || $this->permissionService->hasMenuAccess($userId, 'manage_projects');
            if (!$canAccess || !$this->permissionService->hasPermission($userId, 'Modify')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_NO_PERMISSIONS]);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            if ($projectId <= 0 || $usuarioId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parametros incompletos']);
                return;
            }

            $existing = $this->proyectoColaboradoresModel->countDisponibilidadByUserProject($projectId, $usuarioId, 4);
            if ($existing > 0) {
                echo json_encode(['success' => false, 'message' => 'El calendario ya existe']);
                return;
            }

            $project = $this->projectModel->find($projectId);
            if (!$project) {
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_PROJECT_NOT_FOUND]);
                return;
            }

            $defaultHh = $this->proyectoColaboradoresModel->getExecutorDefaultHh($projectId, $usuarioId, 4);
            if ($defaultHh <= 0) {
                $defaultHh = 9.0;
            }

            $projectHolidays = $this->projectModel->getProjectHolidays($projectId);
            $result = $this->proyectoColaboradoresModel->seedAvailabilityForUser(
                $projectId,
                $usuarioId,
                4,
                $project['fecha_inicio'],
                $project['fecha_fin'] ?: $project['fecha_inicio'],
                $defaultHh,
                1,
                $projectHolidays
            );

            if (!empty($result['error'])) {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar el calendario']);
                return;
            }

            echo json_encode(['success' => true, 'message' => 'Calendario guardado correctamente']);
        } catch (Exception $e) {
            Logger::error('ProyectoColaboradoresController::saveCalendar error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    public function addDate()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $userId = (int)$currentUser['id'];
            $canAccess = $this->permissionService->hasMenuAccess($userId, 'manage_project')
                || $this->permissionService->hasMenuAccess($userId, 'manage_projects');
            if (!$canAccess || !$this->permissionService->hasPermission($userId, 'Modify')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_NO_PERMISSIONS]);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $hh = (float)($_POST['hh'] ?? 0);
            $tipoFechaId = (int)($_POST['tipo_fecha_id'] ?? 6);

            if ($projectId <= 0 || $usuarioId <= 0 || !$fecha) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parametros incompletos']);
                return;
            }

            if (!$this->isValidDate($fecha)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Fecha invalida']);
                return;
            }

            $project = $this->projectModel->find($projectId);
            if (!$project) {
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_PROJECT_NOT_FOUND]);
                return;
            }

            if (strtotime($fecha) < strtotime($project['fecha_inicio']) || strtotime($fecha) > strtotime($project['fecha_fin'])) {
                echo json_encode(['success' => false, 'message' => 'La fecha debe estar dentro del periodo del proyecto']);
                return;
            }

            $success = $this->proyectoColaboradoresModel->upsertDisponibilidad(
                $projectId,
                $usuarioId,
                4,
                $fecha,
                $hh,
                $tipoFechaId
            );

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Fecha agregada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo agregar la fecha']);
            }
        } catch (Exception $e) {
            Logger::error('ProyectoColaboradoresController::addDate error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    public function updateDay()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            $userId = (int)$currentUser['id'];
            $canAccess = $this->permissionService->hasMenuAccess($userId, 'manage_project')
                || $this->permissionService->hasMenuAccess($userId, 'manage_projects');
            if (!$canAccess || !$this->permissionService->hasPermission($userId, 'Modify')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_NO_PERMISSIONS]);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INVALID_CSRF_TOKEN]);
                return;
            }

            $projectId = (int)($_POST['proyecto_id'] ?? 0);
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $fecha = $_POST['fecha'] ?? '';
            $hh = (float)($_POST['hh'] ?? 0);
            $tipoFechaId = (int)($_POST['tipo_fecha_id'] ?? 1);

            if ($projectId <= 0 || $usuarioId <= 0 || !$fecha) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parametros incompletos']);
                return;
            }

            if (!$this->isValidDate($fecha)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Fecha invalida']);
                return;
            }

            $success = $this->proyectoColaboradoresModel->upsertDisponibilidad(
                $projectId,
                $usuarioId,
                4,
                $fecha,
                $hh,
                $tipoFechaId
            );

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Disponibilidad actualizada']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar']);
            }
        } catch (Exception $e) {
            Logger::error('ProyectoColaboradoresController::updateDay error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }
}
