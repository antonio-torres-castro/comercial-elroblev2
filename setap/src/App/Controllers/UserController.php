<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\PermissionService;
use App\Services\ValidationService;
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
            $this->viewRenderer->render('users/list', [
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

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
            $clients = $this->userModel->getAvailableClients(); // GAP 1 y GAP 2: Obtener clientes

            // Usar ViewRenderer para renderizar la vista
            $this->viewRenderer->render('users/create', [
                'userTypes' => $userTypes,
                'estadosTipo' => $estadosTipo,
                'clients' => $clients,
                'currentUser' => $currentUser
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::create: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
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
                http_response_code(403);
                echo json_encode(['error' => AppConstants::ERROR_ACCESS_DENIED]);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Token CSRF inválido']);
                return;
            }

            // Sanitizar y validar datos usando ValidationService
            $validationResult = $this->validationService->validateUserDataComplete($_POST);

            if (!$validationResult['isValid']) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos', 'details' => $validationResult['errors']]);
                return;
            }

            // Si no hay errores, usar datos sanitizados
            $data = $validationResult['data'];

            // Crear usuario
            $userId = $this->userModel->create($data);

            if ($userId) {
                if (AuthHelper::isAjaxRequest()) {
                    echo json_encode(['success' => true, 'message' => AppConstants::SUCCESS_USER_CREATED, 'id' => $userId]);
                } else {
                    $this->redirectWithSuccess(AppConstants::ROUTE_USERS, AppConstants::SUCCESS_CREATED);
                }
            } else {
                throw new Exception('Error al crear el usuario');
            }
        } catch (Exception $e) {
            error_log("Error en UserController::store: " . $e->getMessage());
            http_response_code(500);

            if (AuthHelper::isAjaxRequest()) {
                echo json_encode(['error' => AppConstants::ERROR_INTERNAL_SERVER]);
            } else {
                $this->redirectWithError(AppConstants::ROUTE_USERS_CREATE, AppConstants::ERROR_SERVER);
            }
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
     * Mostrar/editar usuario específico
     */
    public function show($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de usuario individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                $this->viewRenderer->render('errors/403');
                return;
            }

            $userToEdit = null;
            if ($id) {
                $userToEdit = $this->userModel->getById((int)$id);
                if (!$userToEdit) {
                    http_response_code(404);
                    echo $this->renderError(AppConstants::ERROR_USER_NOT_FOUND);
                    return;
                }
            }

            // Obtener datos necesarios para el formulario
            $userTypes = $this->getUserTypes();
            $estadosTipo = $this->getEstadosTipo();
            $clients = $this->userModel->getAvailableClients(); // GAP 1 y GAP 2: Obtener clientes

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => $id ? 'Editar Usuario' : 'Nuevo Usuario',
                'subtitle' => $id ? "Editando usuario: {$userToEdit['nombre_completo']}" : 'Crear nuevo usuario en el sistema',
                'user_id' => $id,
                'user' => $userToEdit,
                'userTypes' => $userTypes,
                'estadosTipo' => $estadosTipo,
                'clients' => $clients
            ];

            $this->viewRenderer->render('users/form', $data);
        } catch (Exception $e) {
            error_log("Error en UserController::show: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
        }
    }

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
                $this->viewRenderer->render('errors/403');
                return;
            }

            // Obtener ID del parámetro GET si no se pasó como argumento
            $id = $id ?: (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_USERS, AppConstants::ERROR_INVALID_USER_ID);
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

            $data = [
                'userToEdit' => $userToEdit,  // Cambiar key para consistencia con edit.php
                'userTypes' => $userTypes,
                'estadosTipo' => $estadosTipo,
                'clients' => $clients,
                'error' => $_GET['error'] ?? '',
                'success' => $_GET['success'] ?? ''
            ];

            $this->viewRenderer->render('users/edit', $data);
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
                $this->viewRenderer->render('errors/403');
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

            // Validar datos usando ValidationService
            $errors = $this->validationService->validateUserDataForUpdate($_POST, $id);

            if (!empty($errors)) {
                $errorMsg = implode(', ', array_values($errors));
                Security::redirect("/users/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            // Si no hay errores, usar los datos del POST directamente
            $userData = $_POST;
            
            // Agregar campos adicionales que no están en la validación estándar
            $userData['estado_tipo_id'] = (int)($_POST['estado_tipo_id'] ?? 1);
            $userData['fecha_inicio'] = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
            $userData['fecha_termino'] = !empty($_POST['fecha_termino']) ? $_POST['fecha_termino'] : null;

            // Actualizar usuario
            if ($this->userModel->update($id, $userData)) {
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
                $this->viewRenderer->render('errors/403');
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
     * Validar datos del usuario para actualización
     */




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
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_users')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $userId = (int)($_POST['user_id'] ?? 0);
            $newStatus = (int)($_POST['new_status'] ?? 0);

            if (!$userId || !in_array($newStatus, [1, 2])) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                return;
            }

            // No permitir desactivar el propio usuario
            if ($userId == $currentUser['id']) {
                echo json_encode(['success' => false, 'message' => 'No puedes cambiar tu propio estado']);
                return;
            }

            $success = $this->userModel->updateStatus($userId, $newStatus);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
            }
        } catch (Exception $e) {
            error_log("Error en UserController::toggleStatus: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
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
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_users')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';

            if (!$userId || strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos o contraseña muy corta']);
                return;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $success = $this->userModel->updatePassword($userId, $hashedPassword);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña']);
            }
        } catch (Exception $e) {
            error_log("Error en UserController::changePassword: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
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
     * GAP 1 y GAP 2: Validar lógica de usuarios cliente
     */


    /**
     * Obtener nombre del tipo de usuario por ID
     */

}
