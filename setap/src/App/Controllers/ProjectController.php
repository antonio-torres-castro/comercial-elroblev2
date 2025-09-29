<?php

namespace App\Controllers;

use App\Models\Project;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Config\Database;
use PDO;
use Exception;

class ProjectController
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
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_projects')) {
            http_response_code(403);
            echo "No tienes acceso a esta sección.";
            return;
        }

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

    public function show()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo "No tienes acceso a esta sección.";
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            Security::redirect('/projects?error=ID de proyecto inválido');
            return;
        }

        $project = $this->projectModel->find($id);
        if (!$project) {
            Security::redirect('/projects?error=Proyecto no encontrado');
            return;
        }

        // Obtener tareas del proyecto
        $tasks = $this->projectModel->getProjectTasks($id);
        
        // Obtener estadísticas del proyecto
        $stats = $this->projectModel->getProjectStats($id);
        
        // Obtener feriados del proyecto
        $holidays = $this->projectModel->getProjectHolidays($id);

        $this->view('projects/show', [
            'project' => $project,
            'tasks' => $tasks,
            'stats' => $stats,
            'holidays' => $holidays,
            'success' => $_GET['success'] ?? '',
            'error' => $_GET['error'] ?? ''
        ]);
    }

    public function create()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo "No tienes acceso a esta sección.";
            return;
        }

        // Obtener datos necesarios para el formulario
        $clients = $this->getClients();
        $taskTypes = $this->getTaskTypes();
        $counterparts = $this->getCounterparts();
        
        $this->view('projects/create', [
            'clients' => $clients,
            'taskTypes' => $taskTypes,
            'counterparts' => $counterparts,
            'error' => $_GET['error'] ?? ''
        ]);
    }

    public function store()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo "No tienes acceso a esta sección.";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Security::redirect('/projects/create?error=Método no permitido');
            return;
        }

        // Validar token CSRF
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Security::redirect('/projects/create?error=Token de seguridad inválido');
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
                'estado_tipo_id' => 1, // Activo por defecto
                'contraparte_id' => (int)$_POST['contraparte_id']
            ];

            // Agregar feriados si están presentes
            if (!empty($_POST['feriados'])) {
                $projectData['feriados'] = explode(',', $_POST['feriados']);
            }

            $projectId = $this->projectModel->create($projectData);
            
            if ($projectId) {
                Security::logSecurityEvent('project_created', [
                    'project_id' => $projectId,
                    'created_by' => $_SESSION['username']
                ]);
                
                Security::redirect('/projects?success=Proyecto creado correctamente');
            } else {
                Security::redirect('/projects/create?error=Error al crear proyecto');
            }
        } catch (\Exception $e) {
            error_log('ProjectController::store error: ' . $e->getMessage());
            Security::redirect('/projects/create?error=Error interno del sistema');
        }
    }

    public function edit()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo "No tienes acceso a esta sección.";
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            Security::redirect('/projects?error=ID de proyecto inválido');
            return;
        }

        $project = $this->projectModel->find($id);
        if (!$project) {
            Security::redirect('/projects?error=Proyecto no encontrado');
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
            echo "No tienes acceso a esta sección.";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Security::redirect('/projects?error=Método no permitido');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            Security::redirect('/projects?error=ID de proyecto inválido');
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

            // Agregar feriados si están presentes
            if (isset($_POST['feriados'])) {
                $projectData['feriados'] = !empty($_POST['feriados']) ? explode(',', $_POST['feriados']) : [];
            }

            if ($this->projectModel->update($id, $projectData)) {
                Security::logSecurityEvent('project_updated', [
                    'project_id' => $id,
                    'updated_by' => $_SESSION['username']
                ]);
                
                Security::redirect('/projects?success=Proyecto actualizado correctamente');
            } else {
                Security::redirect("/projects/edit?id={$id}&error=Error al actualizar proyecto");
            }
        } catch (\Exception $e) {
            error_log('ProjectController::update error: ' . $e->getMessage());
            Security::redirect("/projects/edit?id={$id}&error=Error interno del sistema");
        }
    }

    public function delete()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo "No tienes acceso a esta sección.";
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            Security::redirect('/projects?error=ID de proyecto inválido');
            return;
        }

        try {
            $project = $this->projectModel->find($id);
            if (!$project) {
                Security::redirect('/projects?error=Proyecto no encontrado');
                return;
            }

            if ($this->projectModel->delete($id)) {
                Security::logSecurityEvent('project_deleted', [
                    'project_id' => $id,
                    'deleted_by' => $_SESSION['username']
                ]);
                
                Security::redirect('/projects?success=Proyecto eliminado correctamente');
            } else {
                Security::redirect('/projects?error=Error al eliminar proyecto');
            }
        } catch (\Exception $e) {
            error_log('ProjectController::delete error: ' . $e->getMessage());
            Security::redirect('/projects?error=Error interno del sistema');
        }
    }

    public function changeStatus()
    {
        // Verificar acceso al menú de gestión de proyecto individual
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_project')) {
            http_response_code(403);
            echo "No tienes acceso a esta sección.";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Security::redirect('/projects?error=Método no permitido');
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $newStatusId = (int)($_POST['new_status_id'] ?? 0);

        if ($projectId <= 0 || $newStatusId <= 0) {
            Security::redirect('/projects?error=Datos inválidos');
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
            error_log('ProjectController::changeStatus error: ' . $e->getMessage());
            Security::redirect("/projects/show?id={$projectId}&error=Error interno del sistema");
        }
    }

    public function search()
    {
        // Verificar acceso al menú de gestión de proyectos
        if (!isset($_SESSION['user_id']) || !$this->permissionService->hasMenuAccess($_SESSION['user_id'], 'manage_projects')) {
            http_response_code(403);
            echo "No tienes acceso a esta sección.";
            return;
        }

        $term = Security::sanitizeInput($_GET['q'] ?? '');
        
        if (empty($term) || strlen($term) < 3) {
            Security::redirect('/projects?error=El término de búsqueda debe tener al menos 3 caracteres');
            return;
        }

        $projects = $this->projectModel->search($term);
        
        $this->view('projects/search', [
            'projects' => $projects,
            'searchTerm' => $term,
            'success' => $_GET['success'] ?? '',
            'error' => $_GET['error'] ?? ''
        ]);
    }

    // ============ MÉTODOS PRIVADOS ============

    private function validateProjectData(array $data): array
    {
        $errors = [];

        // Validar cliente
        if (empty($data['cliente_id']) || !is_numeric($data['cliente_id'])) {
            $errors[] = 'Cliente es requerido';
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
        }

        // Validar contraparte
        if (empty($data['contraparte_id']) || !is_numeric($data['contraparte_id'])) {
            $errors[] = 'Contraparte es requerida';
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }

    private function getClients(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, rut 
                FROM clientes 
                WHERE estado_tipo_id != 4 
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('ProjectController::getClients error: ' . $e->getMessage());
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
            error_log('ProjectController::getTaskTypes error: ' . $e->getMessage());
            return [];
        }
    }

    private function getCounterparts(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cp.id, cp.nombre, cp.email, cp.cargo, c.nombre as cliente_nombre
                FROM cliente_contrapartes cp
                INNER JOIN clientes c ON cp.cliente_id = c.id
                WHERE cp.estado_tipo_id != 4 
                ORDER BY c.nombre, cp.nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('ProjectController::getCounterparts error: ' . $e->getMessage());
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
            error_log('ProjectController::getProjectStates error: ' . $e->getMessage());
            return [];
        }
    }

    private function view($view, $data = [])
    {
        extract($data);
        require __DIR__ . "/../Views/{$view}.php";
    }
}