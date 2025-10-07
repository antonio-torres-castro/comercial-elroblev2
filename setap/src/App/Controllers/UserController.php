<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\PermissionService;
use App\Services\ValidationService;
use App\Services\UserValidationService;
use App\Services\ClientBusinessLogic;
use App\Core\ViewRenderer;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Security\AuthHelper;
use App\Config\Database;
use App\Constants\AppConstants;
use PDO;
use Exception;

class UserController extends BaseController
{
    private $userModel;
    private $authService;
    private $permissionService;
    private $validationService;
    private $userValidationService;
    private $clientBusinessLogic;
    private $viewRenderer;
    private $db;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();

        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->permissionService = new PermissionService();
        $this->validationService = new ValidationService();
        $this->userValidationService = new UserValidationService();
        $this->clientBusinessLogic = new ClientBusinessLogic();
        $this->viewRenderer = new ViewRenderer();
        $this->db = Database::getInstance();
    }

    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú primero
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_users')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            $users = $this->userModel->getAll();

            // Obtener tipos de usuario para filtro
            $userTypes = $this->getUserTypes();

            // Usar ViewRenderer para renderizar la vista
            echo $this->viewRenderer->render('users/list', [
                'users' => $users,
                'userTypes' => $userTypes,
                'currentUser' => $currentUser
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::index: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
        }
    }

    public function create()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú de gestión de usuario individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }



            // Obtener datos necesarios para el formulario
            $userTypes = $this->getUserTypes();
            $estadosTipo = $this->getEstadosTipo();
            $clients = $this->userModel->getAvailableClients();

            // Obtener todas las personas disponibles
            $availablePersonas = $this->userModel->getAllPersonas();

            // Usar ViewRenderer para renderizar la vista
            echo $this->viewRenderer->render('users/create', [
                'userTypes' => $userTypes,
                'estadosTipo' => $estadosTipo,
                'clients' => $clients,
                'availablePersonas' => $availablePersonas,
                'currentUser' => $currentUser
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::create: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
        }
    }

    public function seekPersonas()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú de gestión de usuario individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_USERS_CREATE, 'Token CSRF inválido');
                return;
            }

            // Manejar búsqueda de persona
            $this->handlePersonaSearch();
            
        } catch (Exception $e) {
            error_log("Error en UserController::seekPersonas: " . $e->getMessage());
            $_SESSION['errors'] = ['Error al buscar personas'];
            $this->redirectTo(AppConstants::ROUTE_USERS_CREATE);
        }
    }

    public function store()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú de gestión de usuario individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                $this->redirectWithError(AppConstants::ROUTE_USERS_CREATE, AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_USERS_CREATE, 'Token CSRF inválido');
                return;
            }

            // Validar datos simplificado
            $errors = $this->validateUserDataSimplified($_POST);

            if (!empty($errors)) {
                // Guardar errores y datos antiguos en sesión
                $_SESSION['errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->redirectTo(AppConstants::ROUTE_USERS_CREATE);
                return;
            }

            // Si no hay errores, sanitizar datos
            $data = [
                'persona_id' => (int)($_POST['persona_id'] ?? $_POST['persona_id_hidden']),
                'email' => Security::sanitizeInput($_POST['email']),
                'nombre_usuario' => Security::sanitizeInput($_POST['nombre_usuario']),
                'password' => $_POST['password'], // No sanitizar contraseñas
                'usuario_tipo_id' => (int)$_POST['usuario_tipo_id'],
                'cliente_id' => !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null,
                'fecha_inicio' => !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null,
                'fecha_termino' => !empty($_POST['fecha_termino']) ? $_POST['fecha_termino'] : null
            ];

            // Crear usuario
            $userId = $this->userModel->create($data);

            if ($userId) {
                $this->redirectWithSuccess(AppConstants::ROUTE_USERS, AppConstants::SUCCESS_USER_CREATED);
            } else {
                throw new Exception('Error al crear el usuario');
            }
        } catch (Exception $e) {
            error_log("Error en UserController::store: " . $e->getMessage());
            $_SESSION['errors'] = [AppConstants::ERROR_INTERNAL_SERVER];
            $_SESSION['old_input'] = $_POST;
            $this->redirectTo(AppConstants::ROUTE_USERS_CREATE);
        }
    }

    public function validateField()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['valid' => false, 'message' => 'No autorizado']);
                return;
            }

            // Verificar acceso al menú de gestión de usuario individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo json_encode(['valid' => false, 'message' => AppConstants::ERROR_ACCESS_DENIED]);
                return;
            }

            $field = $_GET['field'] ?? '';
            $value = $_GET['value'] ?? '';
            $excludeUserId = isset($_GET['exclude_user_id']) ? (int)$_GET['exclude_user_id'] : 0;

            $isValid = true;
            $message = '';

            switch ($field) {
                case 'username':
                    $isValid = $this->validationService->isUsernameAvailable($value, $excludeUserId);
                    $message = $isValid ? 'Nombre de usuario disponible' : 'Nombre de usuario ya existe';
                    break;

                case 'email':
                    $isValid = $this->validationService->isEmailAvailable($value, $excludeUserId);
                    $message = $isValid ? 'Email disponible' : 'Email ya registrado';
                    break;

                case 'rut':
                    $isValid = Security::validateRut($value);
                    $message = $isValid ? 'RUT válido' : 'RUT inválido';
                    if ($isValid) {
                        $isValid = $this->validationService->isRutAvailable($value, $excludeUserId);
                        $message = $isValid ? 'RUT disponible' : 'RUT ya registrado';
                    }
                    break;

                default:
                    $isValid = false;
                    $message = 'Campo no válido';
            }

            echo json_encode([
                'valid' => $isValid,
                'message' => $message
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::validateField: " . $e->getMessage());
            echo json_encode(['valid' => false, 'message' => 'Error de validación']);
        }
    }

    /**
     * API: Buscar personas disponibles
     */
    public function searchPersonas()
    {
        // Establecer header JSON
        header('Content-Type: application/json');

        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $search = $_GET['search'] ?? '';
            $personas = $this->userModel->getAvailablePersonas($search);

            echo json_encode([
                'success' => true,
                'personas' => $personas
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::searchPersonas: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error en búsqueda']);
        }
    }

    /**
     * API: Obtener contrapartes disponibles para un cliente
     */
    public function getClientCounterparties()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $clientId = (int)($_GET['client_id'] ?? 0);
            if (!$clientId) {
                echo json_encode(['success' => false, 'message' => 'ID de cliente requerido']);
                return;
            }

            $counterparties = $this->userModel->getAvailableCounterparties($clientId);

            echo json_encode([
                'success' => true,
                'counterparties' => $counterparties
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::getClientCounterparties: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error obteniendo contrapartes']);
        }
    }

    /**
     * API: Obtener información de una persona
     */
    public function getPersonaInfo()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $personaId = (int)($_GET['persona_id'] ?? 0);
            if (!$personaId) {
                echo json_encode(['success' => false, 'message' => 'ID de persona requerido']);
                return;
            }

            $persona = $this->userModel->getPersonaById($personaId);
            if (!$persona) {
                echo json_encode(['success' => false, 'message' => 'Persona no encontrada']);
                return;
            }

            echo json_encode([
                'success' => true,
                'persona' => $persona
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::getPersonaInfo: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error obteniendo información']);
        }
    }

    private function getUserTypes(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM usuario_tipos ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo tipos de usuario: " . $e->getMessage());
            return [];
        }
    }

    private function getEstadosTipo(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM estado_tipos WHERE id in (1, 2, 3, 4) ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo estados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * OBSOLETO: Mostrar/editar usuario específico
     * Este método ha sido eliminado como parte de la refactorización AJAX.
     * Ahora las rutas /user/{id} redirigen a /users/edit?id={id} o /users/create
     * 
     * @deprecated Eliminado en refactorización AJAX. Usar edit() o create() en su lugar.
     */
    // public function show($id = null) - MÉTODO ELIMINADO - Ver commit history para implementación original

    /**
     * Mostrar formulario de edición de usuario
     */
    public function edit($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para edición de usuarios
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo $this->viewRenderer->render('errors/403');
                return;
            }

            // Obtener ID del parámetro GET si no se pasó como argumento
            $id = $id ?: (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_USERS, AppConstants::ERROR_INVALID_USER_ID);
                return;
            }

            // Manejar búsqueda de persona para edición
            if (isset($_POST['search_persona'])) {
                $_POST['current_user_id'] = $id; // Agregar ID del usuario en edición
                $this->handlePersonaSearch();
                return;
            }

            // Obtener datos del usuario a editar
            $userToEdit = $this->userModel->getById($id);
            if (!$userToEdit) {
                $this->redirectWithError(AppConstants::ROUTE_USERS, AppConstants::ERROR_USER_NOT_FOUND);
                return;
            }

            // Obtener datos necesarios para el formulario
            $userTypes = $this->getUserTypes();
            $estadosTipo = $this->getEstadosTipo();

            // Obtener clientes para la asignación
            $clients = $this->userModel->getAvailableClients();

            // Obtener personas disponibles (incluyendo la actual del usuario)
            $availablePersonas = $this->userModel->getAllPersonas($id);

            $data = [
                'userToEdit' => $userToEdit,
                'userTypes' => $userTypes,
                'estadosTipo' => $estadosTipo,
                'clients' => $clients,
                'availablePersonas' => $availablePersonas,
                'currentUser' => $currentUser,
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? ''
            ];

            echo $this->viewRenderer->render('users/edit', $data);
        } catch (Exception $e) {
            error_log("Error en UserController::edit: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
        }
    }

    /**
     * Actualizar usuario
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
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo $this->viewRenderer->render('errors/403');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_USERS);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_USERS, AppConstants::ERROR_INVALID_USER_ID);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Security::redirect("/users/edit?id={$id}&error=Token de seguridad inválido");
                return;
            }

            // Validar datos usando ValidationService
            $errors = $this->validationService->validateUserDataForUpdate($_POST, $id);

            if (!empty($errors)) {
                $errorMsg = implode(', ', array_values($errors));
                Security::redirect("/users/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            // Validaciones adicionales para la actualización
            $additionalErrors = $this->validateUserUpdateSpecific($_POST, $id);
            if (!empty($additionalErrors)) {
                $errorMsg = implode(', ', $additionalErrors);
                Security::redirect("/users/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            // Si no hay errores, usar los datos del POST directamente
            $userData = $_POST;

            // Manejar cambio de persona si se seleccionó una nueva
            if (!empty($_POST['new_persona_id'])) {
                $userData['persona_id'] = (int)$_POST['new_persona_id'];
            }

            // Agregar campos adicionales que no están en la validación estándar
            $userData['estado_tipo_id'] = (int)($_POST['estado_tipo_id'] ?? 1);
            $userData['fecha_inicio'] = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
            $userData['fecha_termino'] = !empty($_POST['fecha_termino']) ? $_POST['fecha_termino'] : null;

            // Actualizar usuario
            if ($this->userModel->update($id, $userData)) {
                Security::logSecurityEvent('user_updated', [
                    'user_id' => $id,
                    'updated_by' => $_SESSION['username']
                ]);
                Security::redirect("/users?success=Usuario actualizado correctamente");
            } else {
                Security::redirect("/users/edit?id={$id}&error=Error al actualizar el usuario");
            }
        } catch (Exception $e) {
            error_log("Error en UserController::update: " . $e->getMessage());
            $id = (int)($_POST['id'] ?? 0);
            Security::redirect("/users/edit?id={$id}&error=Error interno del servidor");
        }
    }

    /**
     * Eliminar usuario (soft delete)
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
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo $this->viewRenderer->render('errors/403');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToRoute(AppConstants::ROUTE_USERS);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_USERS, AppConstants::ERROR_INVALID_USER_ID);
                return;
            }

            // Validar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_USERS, 'Token de seguridad inválido');
                return;
            }

            // No permitir que el usuario se elimine a sí mismo
            if ($id == $currentUser['id']) {
                $this->redirectWithError(AppConstants::ROUTE_USERS, AppConstants::ERROR_CANNOT_DELETE_OWN_USER);
                return;
            }

            // Eliminar usuario (soft delete)
            if ($this->userModel->delete($id)) {
                $this->redirectWithSuccess(AppConstants::ROUTE_USERS, AppConstants::SUCCESS_USER_DELETED);
            } else {
                $this->redirectWithError(AppConstants::ROUTE_USERS, AppConstants::ERROR_DELETE_USER);
            }
        } catch (Exception $e) {
            error_log("Error en UserController::delete: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_USERS, AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * API: Obtener detalles de un usuario
     */
    public function getUserDetails()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_users')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $userId = (int)($_GET['id'] ?? 0);
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
                return;
            }

            $user = $this->userModel->getById($userId);
            if (!$user) {
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_USER_NOT_FOUND]);
                return;
            }

            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::getUserDetails: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    /**
     * Cambiar estado de usuario (activar/desactivar)
     */
    public function toggleStatus()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_users')) {
                $this->redirectWithError('/users', 'Sin permisos para esta acción');
                return;
            }

            // Verificar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError('/users', 'Método no permitido');
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError('/users', 'Token de seguridad inválido');
                return;
            }

            $userId = (int)($_POST['user_id'] ?? 0);
            $newStatus = (int)($_POST['new_status'] ?? 0);

            if (!$userId || !in_array($newStatus, [1, 2])) {
                $this->redirectWithError('/users', 'Datos inválidos');
                return;
            }

            // No permitir desactivar el propio usuario
            if ($userId == $currentUser['id']) {
                $this->redirectWithError('/users', 'No puedes cambiar tu propio estado');
                return;
            }

            $success = $this->userModel->updateStatus($userId, $newStatus);
            if ($success) {
                $statusText = $newStatus == 2 ? 'activado' : 'desactivado';
                $this->redirectWithSuccess('/users', "Usuario $statusText correctamente");
            } else {
                $this->redirectWithError('/users', 'Error al actualizar el estado');
            }
        } catch (Exception $e) {
            error_log("Error en UserController::toggleStatus: " . $e->getMessage());
            $this->redirectWithError('/users', 'Error interno del servidor');
        }
    }

    /**
     * Cambiar contraseña de usuario
     */
    public function changePassword()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_users')) {
                $this->redirectWithError('/users', 'Sin permisos para esta acción');
                return;
            }

            // Verificar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectWithError('/users', 'Método no permitido');
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError('/users', 'Token de seguridad inválido');
                return;
            }

            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';

            if (!$userId || strlen($newPassword) < 6) {
                $this->redirectWithError('/users', 'Datos inválidos o contraseña muy corta (mínimo 6 caracteres)');
                return;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $success = $this->userModel->updatePassword($userId, $hashedPassword);

            if ($success) {
                $this->redirectWithSuccess('/users', 'Contraseña actualizada correctamente');
            } else {
                $this->redirectWithError('/users', 'Error al actualizar la contraseña');
            }
        } catch (Exception $e) {
            error_log("Error en UserController::changePassword: " . $e->getMessage());
            $this->redirectWithError('/users', 'Error interno del servidor');
        }
    }

    /**
     * API: Validar campos de usuario (para create.php)
     */
    public function validateUserCheck()
    {
        try {
            // Configurar headers para respuesta JSON
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');

            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['valid' => false, 'available' => false, 'message' => 'No autorizado']);
                return;
            }

            $type = $_GET['type'] ?? '';
            $value = $_GET['value'] ?? '';
            $excludeUserId = isset($_GET['exclude_user_id']) ? (int)$_GET['exclude_user_id'] : 0;

            $isValid = true;
            $message = '';

            switch ($type) {
                case 'email':
                    if (Security::validateEmail($value)) {
                        $isValid = $this->validationService->isEmailAvailable($value, $excludeUserId);
                        $message = $isValid ? 'Email disponible' : 'Email ya registrado';
                    } else {
                        $isValid = false;
                        $message = 'Email inválido';
                    }
                    break;

                case 'username':
                    $isValid = $this->validationService->isUsernameAvailable($value, $excludeUserId);
                    $message = $isValid ? 'Nombre de usuario disponible' : 'Nombre de usuario ya existe';
                    break;

                case 'rut':
                    $isValid = Security::validateRut($value);
                    $message = $isValid ? 'RUT válido' : 'RUT inválido';
                    if ($isValid) {
                        $isValid = $this->validationService->isRutAvailable($value, $excludeUserId);
                        $message = $isValid ? 'RUT disponible' : 'RUT ya registrado';
                    }
                    break;

                default:
                    $isValid = false;
                    $message = 'Tipo de validación no válido';
            }

            echo json_encode([
                'valid' => $isValid,
                'available' => $isValid,  // Para compatibilidad con el JavaScript
                'message' => $message
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::validateUserCheck: " . $e->getMessage());
            echo json_encode(['valid' => false, 'available' => false, 'message' => 'Error de validación']);
        }
    }

    /**
     * Obtener nombre del tipo de usuario por ID
     */
    private function getUserTypeName(int $userTypeId): string
    {
        try {
            $stmt = $this->db->prepare("SELECT nombre FROM usuario_tipos WHERE id = ?");
            $stmt->execute([$userTypeId]);
            return $stmt->fetchColumn() ?: '';
        } catch (Exception $e) {
            error_log("Error obteniendo tipo de usuario: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Validaciones específicas para actualización de usuario
     */
    private function validateUserUpdateSpecific(array $data, int $userId): array
    {
        $errors = [];

        try {
            // Validar que si se cambia persona_id, la nueva persona esté disponible
            if (isset($data['persona_id'])) {
                $currentUserData = $this->userModel->getById($userId);
                if (!$currentUserData) {
                    $errors[] = 'Usuario no encontrado';
                    return $errors;
                }

                $newPersonaId = (int)$data['persona_id'];
                $currentPersonaId = (int)$currentUserData['persona_id'];

                // Si se está cambiando la persona
                if ($newPersonaId !== $currentPersonaId) {
                    // Verificar que la nueva persona esté disponible (excluyendo el usuario actual)
                    if (!$this->userModel->isPersonaAvailableForUser($newPersonaId, $userId)) {
                        $errors[] = 'La persona seleccionada ya tiene un usuario asociado';
                    }
                }
            }

            // Validar reglas de negocio según tipo de usuario
            if (isset($data['usuario_tipo_id'])) {
                $userType = $this->getUserTypeNameById((int)$data['usuario_tipo_id']);

                // Validar usuarios tipo 'client'
                if ($userType === 'client') {
                    if (empty($data['cliente_id'])) {
                        $errors[] = 'Usuario tipo client debe tener un cliente asociado';
                    } else {
                        // Validar que el RUT de la persona coincida con el RUT del cliente
                        $personaId = isset($data['persona_id']) ? (int)$data['persona_id'] : null;
                        if ($personaId) {
                            $persona = $this->userModel->getPersonaById($personaId);
                            if ($persona && !$this->userModel->validateClientUserRut($persona['rut'], (int)$data['cliente_id'])) {
                                $errors[] = 'El RUT de la persona debe coincidir con el RUT del cliente';
                            }
                        }
                    }
                }

                // Validar usuarios tipo 'counterparty'
                if ($userType === 'counterparty') {
                    if (empty($data['cliente_id'])) {
                        $errors[] = 'Usuario tipo counterparty debe tener un cliente asociado';
                    } else {
                        // Validar que la persona esté registrada como contraparte del cliente
                        $personaId = isset($data['persona_id']) ? (int)$data['persona_id'] : null;
                        if ($personaId && !$this->userModel->validateCounterpartyExists($personaId, (int)$data['cliente_id'])) {
                            $errors[] = 'La persona debe estar registrada como contraparte del cliente seleccionado';
                        }
                    }
                }

                // Validar usuarios internos (no deben tener cliente_id)
                if (in_array($userType, ['admin', 'planner', 'supervisor', 'executor'])) {
                    if (!empty($data['cliente_id'])) {
                        $errors[] = "Usuario tipo $userType no debe tener cliente asociado";
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error en validación específica de usuario: " . $e->getMessage());
            $errors[] = 'Error en validación del usuario';
        }

        return $errors;
    }

    /**
     * Obtener nombre del tipo de usuario por ID (para validaciones)
     */
    private function getUserTypeNameById(int $userTypeId): string
    {
        try {
            $stmt = $this->db->prepare("SELECT nombre FROM usuario_tipos WHERE id = ?");
            $stmt->execute([$userTypeId]);
            return strtolower($stmt->fetchColumn() ?: '');
        } catch (Exception $e) {
            error_log("Error obteniendo nombre de tipo de usuario: " . $e->getMessage());
            return '';
        }
    }

    /**
     * API: Obtener personas disponibles para asociar a usuarios
     */
    public function getAvailablePersonas()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $currentUserId = (int)($_GET['current_user_id'] ?? 0);
            $search = $_GET['search'] ?? '';

            // Obtener personas disponibles (sin usuario asociado) o la persona actual del usuario
            $personas = $this->userModel->getAvailablePersonas($search);

            // Si estamos editando un usuario, incluir su persona actual aunque tenga usuario asociado
            if ($currentUserId > 0) {
                $currentUserData = $this->userModel->getById($currentUserId);
                if ($currentUserData) {
                    // Verificar si la persona actual no está ya en la lista de disponibles
                    $personaExists = false;
                    foreach ($personas as $persona) {
                        if ($persona['id'] == $currentUserData['persona_id']) {
                            $personaExists = true;
                            break;
                        }
                    }

                    // Si no está en la lista, agregarla al principio
                    if (!$personaExists) {
                        $currentPersona = [
                            'id' => $currentUserData['persona_id'],
                            'rut' => $currentUserData['rut'],
                            'nombre' => $currentUserData['nombre_completo'],
                            'telefono' => $currentUserData['telefono'],
                            'direccion' => $currentUserData['direccion']
                        ];
                        array_unshift($personas, $currentPersona);
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'personas' => $personas
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::getAvailablePersonas: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Buscar personas disponibles en la base de datos (sin usuario asociado)
     */
    private function searchAvailablePersonas(string $search): array
    {
        $sql = "SELECT id, nombre_completo, rut, telefono
                FROM personas 
                WHERE (nombre_completo LIKE :search 
                   OR rut LIKE :search) 
                AND id NOT IN (SELECT persona_id FROM usuarios WHERE persona_id IS NOT NULL)
                ORDER BY nombre_completo
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['search' => "%{$search}%"]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Manejar búsqueda mejorada de personas
     */
    private function handlePersonaSearch()
    {
        $search = trim($_POST['persona_search'] ?? '');
        $searchType = $_POST['search_type'] ?? 'all'; // 'all', 'rut', 'name'
        $currentUserId = (int)($_POST['current_user_id'] ?? 0); // Para edición
        
        try {
            $personas = [];
            
            // Si no hay término de búsqueda, traer todas las personas
            if (empty($search)) {
                $personas = $this->userModel->getAllPersonas($currentUserId > 0 ? $currentUserId : null);
            } else {
                // Buscar según el tipo especificado
                switch ($searchType) {
                    case 'rut':
                        $personas = $this->userModel->searchPersonasByRut($search, $currentUserId > 0 ? $currentUserId : null);
                        break;
                    case 'name':
                        $personas = $this->userModel->searchPersonasByName($search, $currentUserId > 0 ? $currentUserId : null);
                        break;
                    case 'all':
                    default:
                        $personas = $this->userModel->searchPersonasAdvanced($search, 'all', true, $currentUserId > 0 ? $currentUserId : null);
                        break;
                }
            }

            if (empty($personas)) {
                $_SESSION['errors'] = empty($search) 
                    ? ['No se encontraron personas en el sistema'] 
                    : ['No se encontraron personas con ese criterio de búsqueda'];
            } else {
                $_SESSION['persona_results'] = $personas;
                $_SESSION['search_stats'] = [
                    'total' => count($personas),
                    'available' => count(array_filter($personas, function($p) { return $p['has_user'] == 0; })),
                    'assigned' => count(array_filter($personas, function($p) { return $p['has_user'] == 1; }))
                ];
            }

            $_SESSION['old_input'] = $_POST;
            
            // Redireccionar según el contexto
            if ($currentUserId > 0) {
                $this->redirectTo("/users/edit?id={$currentUserId}");
            } else {
                $this->redirectTo(AppConstants::ROUTE_USERS_CREATE);
            }
        } catch (Exception $e) {
            error_log("Error en búsqueda de personas: " . $e->getMessage());
            $_SESSION['errors'] = ['Error al buscar personas'];
            $_SESSION['old_input'] = $_POST;
            
            if ($currentUserId > 0) {
                $this->redirectTo("/users/edit?id={$currentUserId}");
            } else {
                $this->redirectTo(AppConstants::ROUTE_USERS_CREATE);
            }
        }
    }

    /**
     * Validar datos del usuario (método simplificado)
     */
    private function validateUserDataSimplified(array $data): array
    {
        $errors = [];

        // Validar campos requeridos
        $personaId = $data['persona_id'] ?? $data['persona_id_hidden'] ?? '';
        if (empty($personaId)) {
            $errors[] = 'Debe seleccionar una persona';
        }

        if (empty($data['email'])) {
            $errors[] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido';
        } elseif (!$this->validationService->isEmailAvailable($data['email'])) {
            $errors[] = 'El email ya está registrado';
        }

        if (empty($data['nombre_usuario'])) {
            $errors[] = 'El nombre de usuario es requerido';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['nombre_usuario'])) {
            $errors[] = 'El nombre de usuario debe tener entre 3 y 20 caracteres alfanuméricos o guiones bajos';
        } elseif (!$this->validationService->isUsernameAvailable($data['nombre_usuario'])) {
            $errors[] = 'El nombre de usuario ya existe';
        }

        if (empty($data['password'])) {
            $errors[] = 'La contraseña es requerida';
        } else {
            $passwordErrors = $this->validationService->validatePasswordStrength($data['password']);
            if (!empty($passwordErrors)) {
                $errors = array_merge($errors, $passwordErrors);
            }
        }

        if (empty($data['password_confirm'])) {
            $errors[] = 'Debe confirmar la contraseña';
        } elseif ($data['password'] !== $data['password_confirm']) {
            $errors[] = 'Las contraseñas no coinciden';
        }

        if (empty($data['usuario_tipo_id'])) {
            $errors[] = 'Debe seleccionar un tipo de usuario';
        }

        // Validar fechas si se proporcionan
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_termino'])) {
            if ($data['fecha_inicio'] > $data['fecha_termino']) {
                $errors[] = 'La fecha de inicio no puede ser posterior a la fecha de término';
            }
        }

        return $errors;
    }

    /**
     * Mostrar mantenedor de permisos de usuario
     */
    public function permissions()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú de gestión de usuarios
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_users')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            // Obtener user_id del parámetro GET
            $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
            
            if (!$userId) {
                http_response_code(400);
                echo $this->renderError('ID de usuario requerido');
                return;
            }

            // Verificar que el usuario existe
            $user = $this->userModel->getById($userId);
            if (!$user) {
                http_response_code(404);
                echo $this->renderError('Usuario no encontrado');
                return;
            }

            // Obtener datos para el mantenedor de permisos
            $userPermissions = $this->getUserPermissions($userId);
            $userMenus = $this->getUserMenus($userId);
            $allPermissions = $this->getAllPermissions();
            $allMenus = $this->getAllMenus();

            // Renderizar la vista
            echo $this->viewRenderer->render('users/permissions', [
                'user' => $user,
                'userPermissions' => $userPermissions,
                'userMenus' => $userMenus,
                'allPermissions' => $allPermissions,
                'allMenus' => $allMenus,
                'currentUser' => $currentUser
            ]);

        } catch (Exception $e) {
            error_log("Error en UserController::permissions: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
        }
    }

    /**
     * Obtener permisos del usuario
     */
    private function getUserPermissions($userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.nombre, p.descripcion, utp.fecha_creacion
                FROM permiso_tipos p
                INNER JOIN usuario_tipo_permisos utp ON p.id = utp.permiso_id
                INNER JOIN usuarios u ON u.usuario_tipo_id = utp.usuario_tipo_id
                WHERE u.id = :user_id AND utp.estado_tipo_id = 1
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo permisos del usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener menús del usuario
     */
    private function getUserMenus($userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.id, m.nombre, m.descripcion, utm.fecha_creacion
                FROM menus m
                INNER JOIN usuario_tipo_menus utm ON m.id = utm.menu_id
                INNER JOIN usuarios u ON u.usuario_tipo_id = utm.usuario_tipo_id
                WHERE u.id = :user_id AND utm.estado_tipo_id = 1
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo menús del usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los permisos disponibles
     */
    private function getAllPermissions()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM permiso_tipos
                WHERE estado_tipo_id = 1
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo todos los permisos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los menús disponibles
     */
    private function getAllMenus()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM menus
                WHERE estado_tipo_id = 1
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo todos los menús: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Redireccionar simple
     */
    private function redirectTo(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
