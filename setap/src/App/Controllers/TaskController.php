<?php

namespace App\Controllers;

use App\Models\Task;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use Exception;

class TaskController
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
                'subtitle' => 'Crear nueva tarea',
                'projects' => $this->taskModel->getProjects(),
                'taskTypes' => $this->taskModel->getTaskTypes(),
                'users' => $this->taskModel->getUsers(),
                'taskStates' => $this->taskModel->getTaskStates(),
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
                'tarea_tipo_id' => (int)$_POST['tarea_tipo_id'],
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin' => $_POST['fecha_fin'],
                'usuario_id' => !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null,
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 1)
            ];

            // Crear tarea
            $taskId = $this->taskModel->create($taskData);
            if ($taskId) {
                Security::redirect("/tasks?success=Tarea creada correctamente");
            } else {
                Security::redirect("/tasks/create?error=Error al crear la tarea");
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

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                Security::redirect('/tasks?error=ID de tarea inválido');
                return;
            }

            // Validar datos
            $errors = $this->validateTaskData($_POST, true);

            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/tasks/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            // Preparar datos para actualización
            $taskData = [
                'proyecto_id' => (int)$_POST['proyecto_id'],
                'tarea_tipo_id' => (int)$_POST['tarea_tipo_id'],
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'fecha_inicio' => $_POST['fecha_inicio'],
                'fecha_fin' => $_POST['fecha_fin'],
                'usuario_id' => !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null,
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

            // Eliminar tarea
            if ($this->taskModel->delete($id)) {
                Security::redirect('/tasks?success=Tarea eliminada correctamente');
            } else {
                Security::redirect('/tasks?error=Error al eliminar la tarea');
            }

        } catch (Exception $e) {
            error_log("Error en TaskController::delete: " . $e->getMessage());
            Security::redirect('/tasks?error=Error interno del servidor');
        }
    }

    /**
     * Validar datos de tarea
     */
    private function validateTaskData(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre de la tarea es obligatorio';
        } elseif (strlen($data['nombre']) < 3) {
            $errors[] = 'El nombre debe tener al menos 3 caracteres';
        }

        // Validar proyecto
        if (empty($data['proyecto_id']) || !is_numeric($data['proyecto_id'])) {
            $errors[] = 'Debe seleccionar un proyecto válido';
        }

        // Validar tipo de tarea
        if (empty($data['tarea_tipo_id']) || !is_numeric($data['tarea_tipo_id'])) {
            $errors[] = 'Debe seleccionar un tipo de tarea válido';
        }

        // Validar fechas
        if (empty($data['fecha_inicio'])) {
            $errors[] = 'La fecha de inicio es obligatoria';
        }

        if (empty($data['fecha_fin'])) {
            $errors[] = 'La fecha de fin es obligatoria';
        }

        // Validar que fecha fin sea posterior a fecha inicio
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
            if (strtotime($data['fecha_fin']) < strtotime($data['fecha_inicio'])) {
                $errors[] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }

        return $errors;
    }

    private function getCurrentUser(): ?array
    {
        if (!Security::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'nombre_completo' => $_SESSION['nombre_completo'],
            'rol' => $_SESSION['rol'],
            'usuario_tipo_id' => $_SESSION['usuario_tipo_id']
        ];
    }

    private function renderError(string $message): string
    {
        return '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - SETAP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h4 class="mb-0">Error</h4>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">' . htmlspecialchars($message) . '</p>
                                <a href="/dashboard" class="btn btn-primary">Volver al Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
}