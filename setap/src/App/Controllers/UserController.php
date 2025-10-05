<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Config\Database;
use PDO;
use Exception;

class UserController
{
    private $userModel;
    private $authService;
    private $permissionService;
    private $db;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();

        $this->userModel = new User();
        $this->authService = new AuthService();
        $this->permissionService = new PermissionService();
        $this->db = Database::getInstance();
    }

    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar acceso al menú primero
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_users')) {
                http_response_code(403);
                echo $this->renderError('No tienes acceso a esta sección.');
                return;
            }

            $users = $this->userModel->getAll();

            // Obtener tipos de usuario para filtro
            $userTypes = $this->getUserTypes();

            require_once __DIR__ . '/../Views/users/list.php';
        } catch (Exception $e) {
            error_log("Error en UserController::index: " . $e->getMessage());
            http_response_code(500);
            echo "Error interno del servidor";
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
                Security::redirect('/login');
                return;
            }

            // Verificar acceso al menú de gestión de usuario individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo $this->renderError('No tienes acceso a esta sección.');
                return;
            }

            // Obtener datos necesarios para el formulario
            $userTypes = $this->getUserTypes();
            $estadosTipo = $this->getEstadosTipo();
            $clients = $this->userModel->getAvailableClients(); // GAP 1 y GAP 2: Obtener clientes

            require_once __DIR__ . '/../Views/users/create.php';
        } catch (Exception $e) {
            error_log("Error en UserController::create: " . $e->getMessage());
            http_response_code(500);
            echo "Error interno del servidor";
        }
    }

    public function store()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar acceso al menú de gestión de usuario individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes acceso a esta sección']);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'Token CSRF inválido']);
                return;
            }

            // Sanitizar y validar datos
            $data = $this->validateUserData($_POST);

            if (isset($data['errors'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos', 'details' => $data['errors']]);
                return;
            }

            // Crear usuario
            $userId = $this->userModel->create($data);

            if ($userId) {
                if ($this->isAjaxRequest()) {
                    echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente', 'id' => $userId]);
                } else {
                    Security::redirect('/users?success=created');
                }
            } else {
                throw new Exception('Error al crear el usuario');
            }
        } catch (Exception $e) {
            error_log("Error en UserController::store: " . $e->getMessage());
            http_response_code(500);

            if ($this->isAjaxRequest()) {
                echo json_encode(['error' => 'Error interno del servidor']);
            } else {
                Security::redirect('/users/create?error=server');
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
                echo json_encode(['valid' => false, 'message' => 'No tienes acceso a esta sección']);
                return;
            }

            $field = $_GET['field'] ?? '';
            $value = $_GET['value'] ?? '';

            $isValid = true;
            $message = '';

            switch ($field) {
                case 'username':
                    $isValid = $this->isUsernameAvailable($value);
                    $message = $isValid ? 'Nombre de usuario disponible' : 'Nombre de usuario ya existe';
                    break;

                case 'email':
                    $isValid = $this->isEmailAvailable($value);
                    $message = $isValid ? 'Email disponible' : 'Email ya registrado';
                    break;

                case 'rut':
                    $isValid = Security::validateRut($value);
                    $message = $isValid ? 'RUT válido' : 'RUT inválido';
                    if ($isValid) {
                        $isValid = $this->isRutAvailable($value);
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

    private function validateUserData(array $data): array
    {
        $errors = [];
        $validated = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es requerido';
        } else {
            $validated['nombre'] = Security::sanitizeInput($data['nombre']);
        }

        // Validar RUT
        if (empty($data['rut'])) {
            $errors['rut'] = 'El RUT es requerido';
        } elseif (!Security::validateRut($data['rut'])) {
            $errors['rut'] = 'El RUT no es válido';
        } elseif (!$this->isRutAvailable($data['rut'])) {
            $errors['rut'] = 'El RUT ya está registrado';
        } else {
            $validated['rut'] = preg_replace('/[^0-9kK]/', '', $data['rut']);
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!Security::validateEmail($data['email'])) {
            $errors['email'] = 'El email no es válido';
        } elseif (!$this->isEmailAvailable($data['email'])) {
            $errors['email'] = 'El email ya está registrado';
        } else {
            $validated['email'] = strtolower(trim($data['email']));
        }

        // Validar username
        if (empty($data['nombre_usuario'])) {
            $errors['nombre_usuario'] = 'El nombre de usuario es requerido';
        } elseif (!$this->isUsernameAvailable($data['nombre_usuario'])) {
            $errors['nombre_usuario'] = 'El nombre de usuario ya existe';
        } else {
            $validated['nombre_usuario'] = Security::sanitizeInput($data['nombre_usuario']);
        }

        // Validar contraseña
        if (empty($data['password'])) {
            $errors['password'] = 'La contraseña es requerida';
        } else {
            $passwordErrors = Security::validatePasswordStrength($data['password']);
            if (!empty($passwordErrors)) {
                $errors['password'] = implode(', ', $passwordErrors);
            } else {
                $validated['password'] = $data['password'];
            }
        }

        // Validar tipo de usuario
        if (empty($data['usuario_tipo_id'])) {
            $errors['usuario_tipo_id'] = 'El tipo de usuario es requerido';
        } else {
            $validated['usuario_tipo_id'] = (int)$data['usuario_tipo_id'];
        }

        // GAP 1 y GAP 2: Validaciones especiales para usuarios cliente
        $clientValidationErrors = $this->validateClientLogic($data, $validated);
        $errors = array_merge($errors, $clientValidationErrors);

        // Campos opcionales
        $validated['telefono'] = Security::sanitizeInput($data['telefono'] ?? '');
        $validated['direccion'] = Security::sanitizeInput($data['direccion'] ?? '');
        
        // Cliente_id se valida en validateClientLogic
        if (!empty($data['cliente_id'])) {
            $validated['cliente_id'] = (int)$data['cliente_id'];
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return $validated;
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

    private function isUsernameAvailable(string $username): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ?");
            $stmt->execute([$username]);
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            error_log("Error verificando username: " . $e->getMessage());
            return false;
        }
    }

    private function isEmailAvailable(string $email): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            error_log("Error verificando email: " . $e->getMessage());
            return false;
        }
    }

    private function isRutAvailable(string $rut): bool
    {
        try {
            $cleanRut = preg_replace('/[^0-9kK]/', '', $rut);
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM personas WHERE rut = ?");
            $stmt->execute([$cleanRut]);
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            error_log("Error verificando RUT: " . $e->getMessage());
            return false;
        }
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Mostrar/editar usuario específico
     */
    public function show($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para gestión de usuario individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                require_once __DIR__ . '/../Views/errors/403.php';
                return;
            }

            $userToEdit = null;
            if ($id) {
                $userToEdit = $this->userModel->getById((int)$id);
                if (!$userToEdit) {
                    http_response_code(404);
                    echo $this->renderError('Usuario no encontrado');
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

            require_once __DIR__ . '/../Views/users/form.php';
        } catch (Exception $e) {
            error_log("Error en UserController::show: " . $e->getMessage());
            http_response_code(500);
            echo "Error interno del servidor";
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
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para edición de usuarios
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                require_once __DIR__ . '/../Views/errors/403.php';
                return;
            }

            // Obtener ID del parámetro GET si no se pasó como argumento
            $id = $id ?: (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                Security::redirect('/users?error=ID de usuario inválido');
                return;
            }

            // Obtener datos del usuario a editar
            $userToEdit = $this->userModel->getById($id);
            if (!$userToEdit) {
                Security::redirect('/users?error=Usuario no encontrado');
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

            require_once __DIR__ . '/../Views/users/edit.php';
        } catch (Exception $e) {
            error_log("Error en UserController::edit: " . $e->getMessage());
            http_response_code(500);
            echo "Error interno del servidor";
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
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                require_once __DIR__ . '/../Views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Security::redirect('/users');
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                Security::redirect('/users?error=ID de usuario inválido');
                return;
            }

            // Validar datos
            $errors = $this->validateUserDataForUpdate($_POST, true); // true indica que es actualización

            if (!empty($errors)) {
                $errorMsg = implode(', ', $errors);
                Security::redirect("/users/edit?id={$id}&error=" . urlencode($errorMsg));
                return;
            }

            // Preparar datos para actualización
            $userData = [
                'nombre' => trim($_POST['nombre']),
                'email' => trim($_POST['email']),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? ''),
                'usuario_tipo_id' => (int)$_POST['usuario_tipo_id'],
                'estado_tipo_id' => (int)($_POST['estado_tipo_id'] ?? 1),
                'fecha_inicio' => !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null,
                'fecha_termino' => !empty($_POST['fecha_termino']) ? $_POST['fecha_termino'] : null,
                'cliente_id' => !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null
            ];

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
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_user')) {
                http_response_code(403);
                require_once __DIR__ . '/../Views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Security::redirect('/users');
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                Security::redirect('/users?error=ID de usuario inválido');
                return;
            }

            // No permitir que el usuario se elimine a sí mismo
            if ($id == $currentUser['id']) {
                Security::redirect('/users?error=No puedes eliminar tu propio usuario');
                return;
            }

            // Eliminar usuario (soft delete)
            if ($this->userModel->delete($id)) {
                Security::redirect('/users?success=Usuario eliminado correctamente');
            } else {
                Security::redirect('/users?error=Error al eliminar el usuario');
            }
        } catch (Exception $e) {
            error_log("Error en UserController::delete: " . $e->getMessage());
            Security::redirect('/users?error=Error interno del servidor');
        }
    }

    /**
     * Validar datos del usuario para actualización
     */
    private function validateUserDataForUpdate(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        $validated = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) < 2) {
            $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
        } else {
            $validated['nombre'] = Security::sanitizeInput($data['nombre']);
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es obligatorio';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no tiene un formato válido';
        } else {
            $validated['email'] = strtolower(trim($data['email']));
        }

        // Validar usuario_tipo_id
        if (empty($data['usuario_tipo_id']) || !is_numeric($data['usuario_tipo_id'])) {
            $errors['usuario_tipo_id'] = 'Debe seleccionar un tipo de usuario válido';
        } else {
            $validated['usuario_tipo_id'] = (int)$data['usuario_tipo_id'];
        }

        // Validaciones específicas para creación (no actualización)
        if (!$isUpdate) {
            // Validar nombre_usuario
            if (empty($data['nombre_usuario'])) {
                $errors['nombre_usuario'] = 'El nombre de usuario es obligatorio';
            } elseif (strlen($data['nombre_usuario']) < 3) {
                $errors['nombre_usuario'] = 'El nombre de usuario debe tener al menos 3 caracteres';
            } else {
                $validated['nombre_usuario'] = Security::sanitizeInput($data['nombre_usuario']);
            }

            // Validar password
            if (empty($data['password'])) {
                $errors['password'] = 'La contraseña es obligatoria';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
            } else {
                $validated['password'] = $data['password'];
            }

            // Validar confirmación de password
            if ($data['password'] !== ($data['password_confirm'] ?? '')) {
                $errors['password_confirm'] = 'Las contraseñas no coinciden';
            }
            
            // Validar RUT para creación
            if (!empty($data['rut'])) {
                if (!Security::validateRut($data['rut'])) {
                    $errors['rut'] = 'El RUT no es válido';
                } else {
                    $validated['rut'] = preg_replace('/[^0-9kK]/', '', $data['rut']);
                }
            }
        }
        
        // GAP 1 y GAP 2: Validaciones especiales para usuarios cliente
        if (!empty($validated['usuario_tipo_id'])) {
            $clientValidationErrors = $this->validateClientLogic($data, $validated);
            $errors = array_merge($errors, $clientValidationErrors);
        }
        
        // Campos opcionales
        $validated['telefono'] = Security::sanitizeInput($data['telefono'] ?? '');
        $validated['direccion'] = Security::sanitizeInput($data['direccion'] ?? '');
        
        // Cliente_id se valida en validateClientLogic
        if (!empty($data['cliente_id'])) {
            $validated['cliente_id'] = (int)$data['cliente_id'];
        }

        return !empty($errors) ? $errors : $validated;
    }

    /**
     * Obtener información del usuario actual
     */
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
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                return;
            }

            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } catch (Exception $e) {
            error_log("Error en UserController::getUserDetails: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
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
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
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
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Renderizar página de error
     */
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
                                <a href="/home" class="btn btn-primary">Volver al Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
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

            $isValid = true;
            $message = '';

            switch ($type) {
                case 'email':
                    if (Security::validateEmail($value)) {
                        $isValid = $this->isEmailAvailable($value);
                        $message = $isValid ? 'Email disponible' : 'Email ya registrado';
                    } else {
                        $isValid = false;
                        $message = 'Email inválido';
                    }
                    break;

                case 'username':
                    $isValid = $this->isUsernameAvailable($value);
                    $message = $isValid ? 'Nombre de usuario disponible' : 'Nombre de usuario ya existe';
                    break;

                case 'rut':
                    $isValid = Security::validateRut($value);
                    $message = $isValid ? 'RUT válido' : 'RUT inválido';
                    if ($isValid) {
                        $isValid = $this->isRutAvailable($value);
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
    private function validateClientLogic(array $data, array $validated): array
    {
        $errors = [];
        
        if (empty($validated['usuario_tipo_id'])) {
            return $errors; // No se puede validar sin tipo de usuario
        }
        
        $tipoUsuario = $this->getUserTypeName($validated['usuario_tipo_id']);
        
        // GAP 2: Usuarios de empresa propietaria NO deben tener cliente_id
        if (in_array($tipoUsuario, ['admin', 'planner', 'supervisor', 'executor'])) {
            if (!empty($data['cliente_id'])) {
                $errors['cliente_id'] = "Usuarios tipo '$tipoUsuario' no deben tener cliente asignado";
            }
        }
        
        // GAP 1: Usuarios de cliente deben tener cliente_id y validaciones especiales
        if (in_array($tipoUsuario, ['client', 'counterparty'])) {
            if (empty($data['cliente_id'])) {
                $errors['cliente_id'] = "Usuario tipo '$tipoUsuario' debe tener un cliente asignado";
            } else {
                $clientId = (int)$data['cliente_id'];
                
                // Validar que el cliente existe
                if (!$this->clientExists($clientId)) {
                    $errors['cliente_id'] = 'El cliente seleccionado no existe';
                } else {
                    // Validaciones especiales según tipo de usuario
                    if ($tipoUsuario === 'client') {
                        // GAP 1: Usuario 'client' debe tener mismo RUT que empresa
                        if (!empty($validated['rut']) && !$this->userModel->validateClientUserRut($validated['rut'], $clientId)) {
                            $errors['rut'] = 'El RUT de la persona debe coincidir con el RUT del cliente seleccionado';
                        }
                    } elseif ($tipoUsuario === 'counterparty') {
                        // GAP 1: Usuario 'counterparty' debe estar en cliente_contrapartes
                        // Para creación, necesitamos validar esto después de crear la persona
                        // Marcar para validación posterior
                        $validated['_validate_counterparty'] = true;
                    }
                }
            }
        }
        
        return $errors;
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
     * Verificar si un cliente existe
     */
    private function clientExists(int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE id = ? AND estado_tipo_id IN (1, 2)");
            $stmt->execute([$clientId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error verificando cliente: " . $e->getMessage());
            return false;
        }
    }
}
