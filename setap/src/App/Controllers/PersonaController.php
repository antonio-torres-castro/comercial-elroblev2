<?php

namespace App\Controllers;

use App\Models\Persona;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Config\Database;
use App\Constants\AppConstants;
use PDO;
use Exception;

class PersonaController extends BaseController
{
    private $personaModel;
    private $permissionService;
    private $db;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();

        $this->personaModel = new Persona();
        $this->permissionService = new PermissionService();
        $this->db = Database::getInstance();
    }

    /**
     * Lista de personas (plural) - Para administradores
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de personas
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_personas')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Aplicar filtros si están presentes
            $filters = [];

            if (!empty($_GET['estado_tipo_id'])) {
                $filters['estado_tipo_id'] = (int)$_GET['estado_tipo_id'];
            }

            if (!empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }

            $personas = $this->personaModel->getAll($filters);
            $estadosTipo = $this->getEstadosTipo();
            $stats = $this->personaModel->getStats();

            $this->view('personas/list', [
                'personas' => $personas,
                'estadosTipo' => $estadosTipo,
                'stats' => $stats,
                'filters' => $filters,
                'success' => $_GET['success'] ?? '',
                'error' => $_GET['error'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Error en PersonaController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar formulario de creación de persona
     */
    public function create()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de persona individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_persona')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $estadosTipo = $this->getEstadosTipo();

            $this->view('personas/create', [
                'estadosTipo' => $estadosTipo,
                'error' => $_GET['error'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Error en PersonaController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Procesar creación de persona
     */
    public function store()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_persona')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_ACTION_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS_CREATE, AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Validar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS_CREATE, AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $errors = $this->validatePersonaData($_POST);

            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/personas/create?error=" . urlencode($errorMsg));
                return;
            }

            $personaData = [
                'rut' => Security::sanitizeInput($_POST['rut_clean'] ?? $_POST['rut']),
                'nombre' => Security::sanitizeInput($_POST['nombre']),
                'telefono' => Security::sanitizeInput($_POST['telefono'] ?? ''),
                'direccion' => Security::sanitizeInput($_POST['direccion'] ?? ''),
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 2)
            ];

            $personaId = $this->personaModel->create($personaData);

            if ($personaId) {
                Security::logSecurityEvent('persona_created', [
                    'persona_id' => $personaId,
                    'created_by' => $_SESSION['username']
                ]);

                $this->redirectWithSuccess(AppConstants::ROUTE_PERSONAS, 'Persona creada correctamente');
            } else {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS_CREATE, AppConstants::ERROR_CREATE_PERSONA);
            }
        } catch (Exception $e) {
            error_log('PersonaController::store error: ' . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_PERSONAS_CREATE, AppConstants::ERROR_INTERNAL_SYSTEM);
        }
    }

    /**
     * Mostrar formulario de edición de persona
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
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_persona')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $id = (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_INVALID_PERSONA_ID);
                return;
            }

            $persona = $this->personaModel->find($id);
            if (!$persona) {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_PERSONA_NOT_FOUND);
                return;
            }

            $estadosTipo = $this->getEstadosTipo();

            $this->view('personas/edit', [
                'persona' => $persona,
                'estadosTipo' => $estadosTipo,
                'error' => $_GET['error'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Error en PersonaController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Procesar actualización de persona
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
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_persona')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_ACTION_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_INVALID_PERSONA_ID);
                return;
            }

            // Validar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Security::redirect("/personas/edit?id={$id}&error=Token de seguridad inválido");
                return;
            }

            $errors = $this->validatePersonaData($_POST, $id);

            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/personas/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            $personaData = [
                'rut' => Security::sanitizeInput($_POST['rut_clean'] ?? $_POST['rut']),
                'nombre' => Security::sanitizeInput($_POST['nombre']),
                'telefono' => Security::sanitizeInput($_POST['telefono'] ?? ''),
                'direccion' => Security::sanitizeInput($_POST['direccion'] ?? ''),
                'estado_tipo_id' => (int)$_POST['estado_tipo_id']
            ];

            if ($this->personaModel->update($id, $personaData)) {
                Security::logSecurityEvent('persona_updated', [
                    'persona_id' => $id,
                    'updated_by' => $_SESSION['username']
                ]);

                $this->redirectWithSuccess(AppConstants::ROUTE_PERSONAS, 'Persona actualizada correctamente');
            } else {
                Security::redirect("/personas/edit?id={$id}&error=Error al actualizar persona");
            }
        } catch (Exception $e) {
            error_log('PersonaController::update error: ' . $e->getMessage());
            $id = (int)($_POST['id'] ?? 0);
            Security::redirect("/personas/edit?id={$id}&error=Error interno del sistema");
        }
    }

    /**
     * Eliminar persona (soft delete)
     */
    public function delete()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['error' => 'No autenticado']);
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_persona')) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de persona inválido']);
                return;
            }

            // Validar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Token de seguridad inválido']);
                return;
            }

            if ($this->personaModel->delete($id)) {
                Security::logSecurityEvent('persona_deleted', [
                    'persona_id' => $id,
                    'deleted_by' => $_SESSION['username']
                ]);

                $this->redirectWithSuccess(AppConstants::ROUTE_PERSONAS, 'Persona eliminada correctamente');
            } else {
                $this->redirectWithError(AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_PERSONA_IN_USE);
            }
        } catch (Exception $e) {
            error_log('PersonaController::delete error: ' . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_PERSONAS, AppConstants::ERROR_INTERNAL_SYSTEM);
        }
    }

    /**
     * Mostrar/editar persona individual (singular) - Legacy
     */
    public function show($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de persona individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_persona')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            if ($id) {
                // Redirigir al método edit
                Security::redirect("/personas/edit?id={$id}");
                return;
            } else {
                // Redirigir al método create
                $this->redirectToRoute(AppConstants::ROUTE_PERSONAS_CREATE);
                return;
            }
        } catch (Exception $e) {
            error_log("Error en PersonaController::show: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Validar datos de persona
     */
    private function validatePersonaData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Validar RUT
        if (empty($data['rut'])) {
            $errors[] = 'El RUT es obligatorio';
        } elseif (!Security::validateRut($data['rut'])) {
            $errors[] = 'El RUT no es válido';
        } elseif ($this->personaModel->rutExists($data['rut'], $excludeId)) {
            $errors[] = 'El RUT ya está registrado';
        }

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['nombre']) > 150) {
            $errors[] = 'El nombre no puede tener más de 150 caracteres';
        }

        // Validar teléfono (opcional)
        if (!empty($data['telefono'])) {
            if (strlen($data['telefono']) > 20) {
                $errors[] = 'El teléfono no puede tener más de 20 caracteres';
            } elseif (!preg_match('/^[+]?[0-9\s\-\(\)]{8,15}$/', $data['telefono'])) {
                $errors[] = 'El formato del teléfono no es válido';
            }
        }

        // Validar dirección (opcional)
        if (!empty($data['direccion']) && strlen($data['direccion']) > 255) {
            $errors[] = 'La dirección no puede tener más de 255 caracteres';
        }

        // Validar estado
        if (empty($data['estado_tipo_id']) || !is_numeric($data['estado_tipo_id'])) {
            $errors[] = 'Debe seleccionar un estado válido';
        }

        return $errors;
    }

    /**
     * Obtener tipos de estado
     */
    private function getEstadosTipo(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM estado_tipos WHERE id IN (1, 2, 3, 4) ORDER BY id
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PersonaController::getEstadosTipo error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Renderizar vista
     */
    private function view($view, $data = [])
    {
        extract($data);
        require __DIR__ . "/../Views/{$view}.php";
    }


}
