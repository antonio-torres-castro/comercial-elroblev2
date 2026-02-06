<?php

namespace App\Controllers;

use App\Models\Project;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Config\Database;
use App\Constants\AppConstants;
use PDO;
use Exception;

class ProjectController extends BaseController
{
    private $projectModel;
    private $permissionService;
    private $db;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        $this->projectModel = new Project();
        $this->permissionService = new PermissionService();
        $this->db = Database::getInstance();
    }

    public function index()
    {
        // Verificar acceso al menú de gestión de proyectos
        $hUserId = isset($_SESSION['user_id']);
        $aManageProjects = $hUserId ? $this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_projects') : false;
        $aManageProject = $hUserId ? $this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project') : false;
        if (!$aManageProjects) {
            http_response_code(403);
            echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
            return;
        }

        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            $this->redirectToLogin();
            return;
        }
        $uti = $currentUser['usuario_tipo_id'];

        $rModify = $this->permissionService->hasPermission($currentUser['id'], 'Modify');
        $rCreate = $this->permissionService->hasPermission($currentUser['id'], 'Create');
        $rRead = $this->permissionService->hasPermission($currentUser['id'], 'Read');

        // Aplicar filtros si están presentes
        $filters = [];
        if (!empty($_GET['cliente_id'])) {
            $filters['cliente_id'] = (int)$_GET['cliente_id'];
        }
        if (!empty($_GET['estado_tipo_id'])) {
            $filters['estado_tipo_id'] = (int)$_GET['estado_tipo_id'];
        }
        if (!empty($_GET['fecha_desde'])) {
            $filters['fecha_desde'] = $_GET['fecha_desde'];
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filters['fecha_hasta'] = $_GET['fecha_hasta'];
        }

        $_GET['acceso_proyecto'] = $aManageProject;
        $_GET['show_btn_nuevo'] = $rCreate;
        $_GET['show_btn_editar'] = $rModify;
        $_GET['show_btn_gestionar_feriados'] = $rCreate && $rModify;
        $_GET['show_btn_ver'] = $rRead;

        $projects = $this->projectModel->getAll($filters);

        // Obtener datos para filtros
        $clients = $this->getClients();
        $projectStates = $this->getProjectStates();
        $taskTypes = $this->getTaskTypes();

        $this->view('projects/list', [
            'projects' => $projects,
            'clients' => $clients,
            'projectStates' => $projectStates,
            'taskTypes' => $taskTypes,
            'filters' => $filters,
            'success' => $_GET['success'] ?? '',
            'error' => $_GET['error'] ?? ''
        ]);
    }

    public function show(?int $id = 0)
    {
        // Verificar acceso al menú de gestión de proyecto individual
        $hUserId = isset($_SESSION['user_id']);
        $aManageProject = $hUserId ? $this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project') : false;
        if (!$aManageProject) {
            http_response_code(403);
            echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
            return;
        }

        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            $this->redirectToLogin();
            return;
        }
        $uti = $currentUser['usuario_tipo_id'];

        $rModify = $this->permissionService->hasPermission($currentUser['id'], 'Modify');
        $rCreate = $this->permissionService->hasPermission($currentUser['id'], 'Create');
        $rRead = $this->permissionService->hasPermission($currentUser['id'], 'Read');
        $rEliminate = $this->permissionService->hasPermission($currentUser['id'], 'Eliminate');

        $_GET['show_btn_nuevo'] = $rCreate;
        $_GET['show_btn_editar'] = $rModify;
        $_GET['show_btn_gestionar_feriados'] = $rCreate && $rModify;
        $_GET['show_btn_cambiar_estado'] = $rEliminate;
        $_GET['show_btn_ver'] = $rRead;

        $id = $id == 0 ? (int)($_GET['id'] ?? 0) : $id;
        if ($id <= 0) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_INVALID_PROJECT_ID);
            return;
        }

        $project = $this->projectModel->find($id);
        if (!$project) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_PROJECT_NOT_FOUND);
            return;
        }

        // Obtener estadísticas del proyecto
        $stats = $this->projectModel->getProjectStats($id);

        // Obtener feriados del proyecto
        $holidays = $this->projectModel->getProjectHolidaysForViewManager($id);

        $this->view('projects/show', [
            'project' => $project,
            'stats' => $stats,
            'holidays' => $holidays,
            'success' => $_GET['success'] ?? '',
            'error' => $_GET['error'] ?? ''
        ]);
    }

    public function refreshCardTasks(?int $id = 0)
    {
        try {
            $error = "";
            // Verificar acceso al menú de gestión de proyecto individual
            $hUserId = isset($_SESSION['user_id']);
            $aManageProject = $hUserId ? $this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project') : false;
            if (!$aManageProject) {
                http_response_code(403);
                $error .= "<br> " . AppConstants::ERROR_ACCESS_DENIED;
            }

            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $error .= "<br> No se encontro el usuario";
            }
            $uti = $currentUser['usuario_tipo_id'];

            $rModify = $this->permissionService->hasPermission($uti, 'Modify');
            $rCreate = $this->permissionService->hasPermission($uti, 'Create');
            $rRead = $this->permissionService->hasPermission($uti, 'Read');
            $rEliminate = $this->permissionService->hasPermission($uti, 'Eliminate');

            $_GET['show_btn_nuevo'] = $rCreate;
            $_GET['show_btn_editar'] = $rModify;
            $_GET['show_btn_gestionar_feriados'] = $rCreate && $rModify;
            $_GET['show_btn_cambiar_estado'] = $rEliminate;
            $_GET['show_btn_ver'] = $rRead;

            $fecha_inicio = $_POST['fecha_inicio'] ?? null;
            $fecha_fin = $_POST['fecha_fin'] ?? date("Y-m-d");
            $estado_tipo_id = null;
            if (isset($_POST['estado_tipo_id'])) {
                $estadoSeleccionado = (int)$_POST['estado_tipo_id'];
                $estado_tipo_id = in_array($estadoSeleccionado, [5, 6, 7, 8], true) ? $estadoSeleccionado : null;
            }
            //siempre con una fecha de tope a hoy, en caso que no se haya indicado
            if ($fecha_inicio != null) {
                $_GET['fecha_inicio'] = $fecha_inicio;
            }
            if ($fecha_fin != null) {
                $_GET['fecha_fin'] = $fecha_fin;
            }

            $id = $id == 0 ? (int)($_POST['proyecto_id'] ?? 0) : $id;
            if ($id <= 0) {
                $error .= "<br> " . AppConstants::ERROR_INVALID_PROJECT_ID;
            }

            $project = $this->projectModel->find($id);
            if (!$project) {
                $error .= "<br> " . AppConstants::ERROR_PROJECT_NOT_FOUND;
            }

            // Configuración de paginación
            $perPage = 7;
            $page = isset($_POST['page']) && is_numeric($_POST['page']) && $_POST['page'] > 0 ? (int)$_POST['page'] : 1;
            $offset = ($page - 1) * $perPage;
            // Contar total de registros según filtros
            $totalTareas = $this->projectModel->countProjectTasks($id, $fecha_inicio, $fecha_fin, $estado_tipo_id);
            $totalPages = max(1, ceil($totalTareas / $perPage));

            // Obtener tareas del proyecto paginadas
            $tasks = $this->projectModel->getProjectTasks($id, $perPage, $offset, $fecha_inicio, $fecha_fin, $estado_tipo_id);

            ob_start();
            include __DIR__ . '/../Views/projects/partials/card_tasks.php';
            $html = ob_get_clean();
            $this->jsonSuccess('Tarea actualizada', [
                'html' => $html,
                'page' => $page,
                'totalPages' => $totalPages,
            ]);
        } catch (Exception $e) {
            $this->jsonError('Error al actualizar tareas', [], 500);
        }
    }

    public function create()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
            return;
        }

        // Obtener datos necesarios para el formulario
        $clients = $this->getClients();
        $taskTypes = $this->getTaskTypes();
        $projectStates = $this->getProjectStates();
        $counterparts = $this->getCounterparts();

        $this->view('projects/create', [
            'clients' => $clients,
            'taskTypes' => $taskTypes,
            'projectStates' => $projectStates,
            'counterparts' => $counterparts,
            'error' => $_GET['error'] ?? ''
        ]);
    }

    public function store()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS_CREATE, AppConstants::ERROR_METHOD_NOT_ALLOWED);
            return;
        }

        // Validar token CSRF
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS_CREATE, AppConstants::ERROR_INVALID_SECURITY_TOKEN);
            return;
        }

        try {
            $errors = $this->validateProjectData($_POST);
            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/projects/create?error=" . urlencode($errorMsg));
                return;
            }

            $projectData = [
                'cliente_id' => (int)$_POST['cliente_id'],
                'direccion' => Security::sanitizeInput($_POST['direccion']),
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null,
                'tarea_tipo_id' => (int)$_POST['tarea_tipo_id'],
                'estado_tipo_id' => !empty($_POST['estado_tipo_id']) ? (int)$_POST['estado_tipo_id'] : 1, // Creado por defecto
                'contraparte_id' => (int)$_POST['contraparte_id']
            ];

            $projectId = $this->projectModel->create($projectData);
            if ($projectId) {
                Security::logSecurityEvent('project_created', [
                    'project_id' => $projectId,
                    'created_by' => $_SESSION['username']
                ]);
                $this->redirectWithSuccess(AppConstants::ROUTE_PROJECTS, 'Proyecto creado correctamente');
            } else {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS_CREATE, AppConstants::ERROR_CREATE_PROJECT);
            }
        } catch (\Exception $e) {
            Logger::error('ProjectController::store error: ' . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS_CREATE, AppConstants::ERROR_INTERNAL_SYSTEM);
        }
    }

    public function edit()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_INVALID_PROJECT_ID);
            return;
        }

        $project = $this->projectModel->find($id);
        if (!$project) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_PROJECT_NOT_FOUND);
            return;
        }

        // Obtener datos para el formulario
        $clients = $this->getClients();
        $taskTypes = $this->getTaskTypes();
        $counterparts = $this->getCounterparts();
        $projectStates = $this->getProjectStates();
        $holidays = $this->projectModel->getProjectHolidays($id);

        $this->view('projects/edit', [
            'project' => $project,
            'clients' => $clients,
            'taskTypes' => $taskTypes,
            'counterparts' => $counterparts,
            'projectStates' => $projectStates,
            'holidays' => $holidays,
            'error' => $_GET['error'] ?? ''
        ]);
    }

    public function update()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_METHOD_NOT_ALLOWED);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_INVALID_PROJECT_ID);
            return;
        }

        // Validar token CSRF
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Security::redirect("/projects/edit?id={$id}&error=Token de seguridad inválido");
            return;
        }

        try {
            $errors = $this->validateProjectData($_POST);
            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/projects/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            $projectData = [
                'cliente_id' => (int)$_POST['cliente_id'],
                'direccion' => Security::sanitizeInput($_POST['direccion']),
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null,
                'tarea_tipo_id' => (int)$_POST['tarea_tipo_id'],
                'estado_tipo_id' => (int)$_POST['estado_tipo_id'],
                'contraparte_id' => (int)$_POST['contraparte_id']
            ];

            if ($this->projectModel->update($id, $projectData)) {
                Security::logSecurityEvent('project_updated', [
                    'project_id' => $id,
                    'updated_by' => $_SESSION['username']
                ]);
                $this->redirectWithSuccess(AppConstants::ROUTE_PROJECTS, 'Proyecto actualizado correctamente');
            } else {
                Security::redirect("/projects/edit?id={$id}&error=Error al actualizar proyecto");
            }
        } catch (\Exception $e) {
            Logger::error('ProjectController::update error: ' . $e->getMessage());
            Security::redirect("/projects/edit?id={$id}&error=Error interno del sistema");
        }
    }

    public function delete()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_ACCESS_DENIED);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Método no permitido');
            return;
        }

        // Validar CSRF token
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Token CSRF inválido');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_INVALID_PROJECT_ID);
            return;
        }

        try {
            $project = $this->projectModel->find($id);
            if (!$project) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_PROJECT_NOT_FOUND);
                return;
            }

            if ($this->projectModel->delete($id)) {
                Security::logSecurityEvent('project_deleted', [
                    'project_id' => $id,
                    'deleted_by' => $_SESSION['username']
                ]);
                $this->redirectWithSuccess(AppConstants::ROUTE_PROJECTS, 'Proyecto eliminado correctamente');
            } else {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_DELETE_PROJECT);
            }
        } catch (\Exception $e) {
            Logger::error('ProjectController::delete error: ' . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_INTERNAL_SYSTEM);
        }
    }

    public function changeStatus()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_ACCESS_DENIED);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_METHOD_NOT_ALLOWED);
            return;
        }

        // Validar CSRF token
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'Token CSRF inválido');
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $newStatusId = (int)($_POST['new_status_id'] ?? 0);

        if ($projectId <= 0 || $newStatusId <= 0) {
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_INVALID_DATA);
            return;
        }

        try {
            if ($this->projectModel->changeStatus($projectId, $newStatusId)) {
                Security::logSecurityEvent('project_status_changed', [
                    'project_id' => $projectId,
                    'new_status_id' => $newStatusId,
                    'changed_by' => $_SESSION['username']
                ]);
                Security::redirect("/projects/show?id={$projectId}&success=Estado actualizado correctamente");
            } else {
                Security::redirect("/projects/show?id={$projectId}&error=Error al cambiar estado");
            }
        } catch (\Exception $e) {
            Logger::error('ProjectController::changeStatus error: ' . $e->getMessage());
            Security::redirect("/projects/show?id={$projectId}&error=Error interno del sistema");
        }
    }

    // ============ MÉTODOS PRIVADOS ============
    private function validateProjectData(array $data): array
    {
        $errors = [];

        // Validar cliente
        if (empty($data['cliente_id']) || !is_numeric($data['cliente_id'])) {
            $errors[] = 'Cliente es requerido';
        } else {
            // Verificar que el cliente existe, el cliente en 3 esta inactivo, y en 4 eliminado, inactivo existe, eliminado ya no existe
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE id = ? AND estado_tipo_id != 4");
            $stmt->execute([$data['cliente_id']]);
            if ($stmt->fetchColumn() == 0) {
                $errors[] = 'El cliente seleccionado no existe o está inactivo';
            }
        }

        // Validar fecha de inicio
        if (empty($data['fecha_inicio'])) {
            $errors[] = 'Fecha de inicio es requerida';
        } elseif (!$this->isValidDate($data['fecha_inicio'])) {
            $errors[] = 'Fecha de inicio inválida';
        }

        // Validar fecha de fin (si está presente)
        if (!empty($data['fecha_fin'])) {
            if (!$this->isValidDate($data['fecha_fin'])) {
                $errors[] = 'Fecha de fin inválida';
            } elseif (!empty($data['fecha_inicio']) && $data['fecha_fin'] < $data['fecha_inicio']) {
                $errors[] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }

        // Validar tipo de tarea
        if (empty($data['tarea_tipo_id']) || !is_numeric($data['tarea_tipo_id'])) {
            $errors[] = 'Tipo de tarea es requerido';
        } else {
            // Verificar que el tipo de tarea existe
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM tarea_tipos WHERE id = ?");
            $stmt->execute([$data['tarea_tipo_id']]);
            if ($stmt->fetchColumn() == 0) {
                $errors[] = 'El tipo de tarea seleccionado no existe o está inactivo';
            }
        }

        // Validar contraparte
        if (empty($data['contraparte_id']) || !is_numeric($data['contraparte_id'])) {
            $errors[] = 'Contraparte es requerida';
        } else {
            // Verificar que la contraparte existe
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM cliente_contrapartes WHERE id = ? AND estado_tipo_id != 3");
            $stmt->execute([$data['contraparte_id']]);
            if ($stmt->fetchColumn() == 0) {
                $errors[] = 'La contraparte seleccionada no existe o está inactiva';
            }
        }

        return $errors;
    }

    private function getClients(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, razon_social as nombre, rut
                FROM clientes
                WHERE estado_tipo_id != 4
                ORDER BY razon_social
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error('ProjectController::getClients error: ' . $e->getMessage());
            return [];
        }
    }

    private function getTaskTypes(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre FROM tarea_tipos ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error('ProjectController::getTaskTypes error: ' . $e->getMessage());
            return [];
        }
    }

    private function getCounterparts(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cc.id,
                       CONCAT(p.nombre, ' (', c.razon_social, ')') as nombre,
                       cc.email,
                       cc.cargo,
                       c.razon_social as cliente_nombre,
                       cc.cliente_id
                FROM cliente_contrapartes cc
                INNER JOIN personas p ON cc.persona_id = p.id
                INNER JOIN clientes c ON cc.cliente_id = c.id
                WHERE cc.estado_tipo_id != 4
                ORDER BY c.razon_social, p.nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error('ProjectController::getCounterparts error: ' . $e->getMessage());
            return [];
        }
    }

    private function getProjectStates(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM estado_tipos
                WHERE id IN (1, 2, 3, 5, 6, 8)
                ORDER BY id
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error('ProjectController::getProjectStates error: ' . $e->getMessage());
            return [];
        }
    }

    public function report()
    {
        try {
            // Verificar acceso al menú de gestión de proyectos
            if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_projects')) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            // Obtener ID del proyecto de los parámetros GET
            $projectId = isset($_GET['id']) ? (int)$_GET['id'] : null;
            if (!$projectId) {
                $this->redirectWithError(AppConstants::ROUTE_PROJECTS, 'ID del proyecto requerido');
                return;
            }

            // TODO: Implementar lógica de generación de reportes
            // Por ahora, redirigir con mensaje temporal
            $this->redirectWithSuccess(AppConstants::ROUTE_PROJECTS, "Generando reporte para el proyecto ID: " . $projectId);
        } catch (Exception $e) {
            Logger::error("ProjectController::report: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_PROJECTS, AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Endpoints AJAX para gestionar proyecto_usuarios_grupo
     */
    public function usuariosGrupoList(): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) { $this->redirectToLogin(); return; }
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_project')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_ACCESS_DENIED]);
                return;
            }

            $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (int)($_GET['id'] ?? 0);
            if ($projectId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de proyecto inválido']);
                return;
            }

            $assigned = $this->projectModel->getUsuariosGrupo($projectId);
            $users = $this->projectModel->getAllUsers();
            $grupos = $this->projectModel->getGrupoTipos();
            $projectActive = $this->projectModel->isActive($projectId);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'assigned' => $assigned,
                'users' => $users,
                'grupos' => $grupos,
                'projectActive' => $projectActive
            ]);
        } catch (Exception $e) {
            Logger::error('ProjectController::usuariosGrupoList: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    public function usuariosGrupoAdd(): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) { $this->redirectToLogin(); return; }
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_project') ||
                !$this->permissionService->hasPermission($currentUser['id'], 'Create')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_NO_PERMISSIONS]);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                return;
            }

            $projectId = (int)($_POST['project_id'] ?? 0);
            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $grupoId = (int)($_POST['grupo_id'] ?? 0);
            if ($projectId <= 0 || $usuarioId <= 0 || $grupoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
                return;
            }

            // Si el proyecto no está activo, no permitir modificar
            if (!$this->projectModel->isActive($projectId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Proyecto inactivo, no se puede modificar']);
                return;
            }

            $res = $this->projectModel->addUsuarioGrupo($projectId, $usuarioId, $grupoId);
            echo json_encode($res);
        } catch (Exception $e) {
            Logger::error('ProjectController::usuariosGrupoAdd: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    public function usuariosGrupoUpdate(): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) { $this->redirectToLogin(); return; }
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_project') ||
                !$this->permissionService->hasPermission($currentUser['id'], 'Modify')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_NO_PERMISSIONS]);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            $projectId = (int)($_POST['project_id'] ?? 0);
            $grupoId = (int)($_POST['grupo_id'] ?? 0);
            if ($id <= 0 || $projectId <= 0 || $grupoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
                return;
            }

            // Si el proyecto no está activo, no permitir modificar
            if (!$this->projectModel->isActive($projectId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Proyecto inactivo, no se puede modificar']);
                return;
            }

            $res = $this->projectModel->updateUsuarioGrupo($id, $grupoId);
            echo json_encode($res);
        } catch (Exception $e) {
            Logger::error('ProjectController::usuariosGrupoUpdate: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    public function usuariosGrupoDelete(): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) { $this->redirectToLogin(); return; }
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_project') ||
                !$this->permissionService->hasPermission($currentUser['id'], 'Eliminate')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_NO_PERMISSIONS]);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                return;
            }

            // Se permite eliminar incluso si el proyecto está inactivo
            $res = $this->projectModel->deleteUsuarioGrupo($id);
            echo json_encode($res);
        } catch (Exception $e) {
            Logger::error('ProjectController::usuariosGrupoDelete: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    private function view($view, $data = [])
    {
        extract($data);
        require __DIR__ . "/../Views/{$view}.php";
    }
}
