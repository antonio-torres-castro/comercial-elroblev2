<?php

namespace App\Controllers;

use App\Models\Process;
use App\Models\Suppliers;
use App\Models\Task;
use App\Services\PermissionService;
use App\Core\ViewRenderer;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;
use Exception;

class ProcessController extends BaseController
{
    private $processModel;
    private $supplierModel;
    private $taskModel;
    private $permissionService;
    private $viewRenderer;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->processModel = new Process();
        $this->supplierModel = new Suppliers();
        $this->taskModel = new Task();
        $this->permissionService = new PermissionService();
        $this->viewRenderer = new ViewRenderer();
    }

    /**
     * Lista de procesos
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_projects')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $filters = [
                'proveedor_id' => $_GET['proveedor_id'] ?? ($currentUser['id'] == 1 ? '' : $currentUser['proveedor_id'] ?? ''),
                'nombre' => $_GET['nombre'] ?? ''
            ];

            $processes = $this->processModel->getAll($filters);
            $suppliers = $this->getSuppliersForUser($currentUser);

            echo $this->viewRenderer->render('process/list', [
                'user' => $currentUser,
                'title' => 'Gestion de Procesos',
                'subtitle' => 'Lista de procesos',
                'processes' => $processes,
                'suppliers' => $suppliers,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::error("ProcessController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar formulario para crear proceso
     */
    public function create()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_projects')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $suppliers = $this->getSuppliersForUser($currentUser);
            $categories = $this->processModel->getTaskCategories();
            $tasks = [];

            echo $this->viewRenderer->render('process/create', [
                'user' => $currentUser,
                'title' => 'Nuevo Proceso',
                'subtitle' => 'Crear nuevo proceso',
                'process' => null,
                'processTasks' => [],
                'suppliers' => $suppliers,
                'categories' => $categories,
                'tasks' => $tasks,
                'action' => 'create'
            ]);
        } catch (Exception $e) {
            Logger::error("ProcessController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Guardar nuevo proceso
     */
    public function store()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $errors = $this->validateProcessData($_POST);
            if (!empty($errors)) {
                $suppliers = $this->getSuppliersForUser($currentUser);
                $categories = $this->processModel->getTaskCategories();
                $tasks = $this->getFilteredTasks($_POST);

                echo $this->viewRenderer->render('process/create', [
                    'user' => $currentUser,
                    'title' => 'Nuevo Proceso',
                    'subtitle' => 'Crear nuevo proceso',
                    'process' => $_POST,
                    'processTasks' => json_decode($_POST['process_tasks_json'] ?? '[]', true),
                    'suppliers' => $suppliers,
                    'categories' => $categories,
                    'tasks' => $tasks,
                    'action' => 'create',
                    'errors' => $errors
                ]);
                return;
            }

            $processId = $this->processModel->create($_POST);

            // Guardar las tareas del proceso
            $tasksData = json_decode($_POST['process_tasks_json'] ?? '[]', true);
            if (!empty($tasksData)) {
                foreach ($tasksData as $task) {
                    $this->processModel->addTaskToProcess($processId, $task['tarea_id'], $task['hh']);
                }
            }

            $this->redirectWithSuccess(AppConstants::ROUTE_PROCESSES, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ProcessController::store: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_SAVE_PROCESS . ': ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para editar proceso
     */
    public function edit($id)
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_projects')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $process = $this->processModel->find((int)$id);
            if (!$process) {
                http_response_code(404);
                echo $this->renderError('Proceso no encontrado');
                return;
            }

            $suppliers = $this->getSuppliersForUser($currentUser);
            $categories = $this->processModel->getTaskCategories();
            $processTasks = $this->processModel->getProcessTasks((int)$id);
            $tasks = $this->processModel->getTasksByProvider($process['proveedor_id']);

            echo $this->viewRenderer->render('process/edit', [
                'user' => $currentUser,
                'title' => 'Editar Proceso',
                'subtitle' => 'Editando: ' . $process['nombre'],
                'process_id' => $id,
                'process' => $process,
                'processTasks' => $processTasks,
                'suppliers' => $suppliers,
                'categories' => $categories,
                'tasks' => $tasks,
                'action' => 'edit'
            ]);
        } catch (Exception $e) {
            Logger::error("ProcessController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Actualizar proceso
     */
    public function update()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de proceso requerido');
                return;
            }

            $errors = $this->validateProcessData($_POST);
            if (!empty($errors)) {
                $process = $this->processModel->find($id);
                $suppliers = $this->getSuppliersForUser($currentUser);
                $categories = $this->processModel->getTaskCategories();
                $tasks = $this->getFilteredTasks($_POST);

                echo $this->viewRenderer->render('process/edit', [
                    'user' => $currentUser,
                    'title' => 'Editar Proceso',
                    'subtitle' => 'Editando: ' . ($process['nombre'] ?? ''),
                    'process' => array_merge($process ?? [], $_POST),
                    'processTasks' => json_decode($_POST['process_tasks_json'] ?? '[]', true),
                    'suppliers' => $suppliers,
                    'categories' => $categories,
                    'tasks' => $tasks,
                    'action' => 'edit',
                    'errors' => $errors
                ]);
                return;
            }

            $success = $this->processModel->update($id, $_POST);

            // Actualizar las tareas del proceso
            if ($success) {
                // Eliminar tareas existentes y agregar las nuevas
                $this->processModel->clearProcessTasks($id);
                $tasksData = json_decode($_POST['process_tasks_json'] ?? '[]', true);
                if (!empty($tasksData)) {
                    foreach ($tasksData as $task) {
                        $this->processModel->addTaskToProcess($id, $task['tarea_id'], $task['hh']);
                    }
                }
            }

            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_PROCESSES, AppConstants::SUCCESS_UPDATED);
            } else {
                throw new Exception('No se pudo actualizar el proceso');
            }
        } catch (Exception $e) {
            Logger::error("ProcessController::update: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_UPDATE_PROCESS . ': ' . $e->getMessage());
        }
    }

    /**
     * Eliminar proceso
     */
    public function delete()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de proceso requerido');
                return;
            }

            $success = $this->processModel->delete($id);
            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_PROCESSES, AppConstants::SUCCESS_DELETED);
            } else {
                throw new Exception('No se pudo eliminar el proceso');
            }
        } catch (Exception $e) {
            Logger::error("ProcessController::delete: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_PROCESSES, $e->getMessage());
        }
    }

    /**
     * Ver detalle de proceso
     */
    public function show($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_projects')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $suppliers = $this->getSuppliersForUser($currentUser);
            $process = null;
            $processTasks = [];

            if ($id) {
                $process = $this->processModel->find((int)$id);
                if ($process) {
                    $processTasks = $this->processModel->getProcessTasks((int)$id);
                }
            }

            echo $this->viewRenderer->render('process/view', [
                'user' => $currentUser,
                'title' => 'Ver Proceso',
                'subtitle' => 'Detalle del proceso',
                'process' => $process,
                'processTasks' => $processTasks,
                'suppliers' => $suppliers,
                'action' => 'view'
            ]);
        } catch (Exception $e) {
            Logger::error("ProcessController::show: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Obtener tareas filtradas por proveedor y categoria (API)
     */
    public function getTasks()
    {
        try {
            header('Content-Type: application/json');

            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                echo json_encode(['error' => 'No autenticado']);
                return;
            }

            $proveedorId = $_GET['proveedor_id'] ?? null;
            $categoriaId = $_GET['categoria_id'] ?? null;

            if (!$proveedorId) {
                echo json_encode(['error' => 'Proveedor requerido']);
                return;
            }

            $tasks = $this->processModel->getTasksFiltered($proveedorId, $categoriaId);
            echo json_encode(['tasks' => $tasks]);
        } catch (Exception $e) {
            Logger::error("ProcessController::getTasks: " . $e->getMessage());
            echo json_encode(['error' => 'Error al obtener tareas']);
        }
    }

    /**
     * Obtener procesos por proveedor (API)
     */
    public function getProcesses()
    {
        try {
            header('Content-Type: application/json');

            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                echo json_encode(['error' => 'No autenticado']);
                return;
            }

            $proveedorId = $_GET['proveedor_id'] ?? null;

            if (!$proveedorId) {
                echo json_encode(['error' => 'Proveedor requerido']);
                return;
            }

            $processes = $this->processModel->getByProvider($proveedorId);
            echo json_encode(['processes' => $processes]);
        } catch (Exception $e) {
            Logger::error("ProcessController::getProcesses: " . $e->getMessage());
            echo json_encode(['error' => 'Error al obtener procesos']);
        }
    }

    /**
     * Obtener detalle de tarea (API)
     */
    public function getTaskDetail()
    {
        try {
            header('Content-Type: application/json');

            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                echo json_encode(['error' => 'No autenticado']);
                return;
            }

            $tareaId = $_GET['tarea_id'] ?? null;

            if (!$tareaId) {
                echo json_encode(['error' => 'Tarea requerida']);
                return;
            }

            $task = $this->taskModel->getTaskById((int)$tareaId);
            echo json_encode(['task' => $task]);
        } catch (Exception $e) {
            Logger::error("ProcessController::getTaskDetail: " . $e->getMessage());
            echo json_encode(['error' => 'Error al obtener detalle de tarea']);
        }
    }

    /**
     * Obtener proveedores segun el tipo de usuario
     */
    private function getSuppliersForUser(array $currentUser): array
    {

        $filters = ['estado_tipo_id' => 2]; // Solo proveedores activos

        if ($currentUser['id'] == 1) {
            // Admin puede ver todos los proveedores
            return $this->supplierModel->getAll($filters);
        } else {
            // Otros usuarios solo ven su proveedor
            $supplierId = $currentUser['proveedor_id'] ?? null;
            if ($supplierId) {
                $supplier = $this->supplierModel->find((int)$supplierId);
                return $supplier ? [$supplier] : [];
            }
            return [];
        }
    }

    /**
     * Obtener tareas filtradas segun POST
     */
    private function getFilteredTasks(array $post): array
    {
        $proveedorId = $post['proveedor_id'] ?? null;
        $categoriaId = $post['categoria_id'] ?? null;

        if (!$proveedorId) {
            return [];
        }

        return $this->processModel->getTasksFiltered($proveedorId, $categoriaId);
    }

    /**
     * Validar datos del proceso
     */
    private function validateProcessData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty($data['proveedor_id'])) {
            $errors[] = 'El proveedor es requerido';
        }

        if (empty($data['nombre'])) {
            $errors[] = 'El nombre del proceso es requerido';
        } elseif (strlen($data['nombre']) > 100) {
            $errors[] = 'El nombre no puede exceder 100 caracteres';
        }

        if (!empty($data['descripcion']) && strlen($data['descripcion']) > 65535) {
            $errors[] = 'La descripcion no puede exceder el tamano maximo permitido';
        }

        return $errors;
    }
}
