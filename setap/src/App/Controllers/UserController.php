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

        // Campos opcionales
        $validated['telefono'] = Security::sanitizeInput($data['telefono'] ?? '');
        $validated['direccion'] = Security::sanitizeInput($data['direccion'] ?? '');

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return $validated;
    }

    private function getUserTypes(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre FROM usuario_tipos WHERE estado_tipo_id = 1 ORDER BY nombre");
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
            $stmt = $this->db->prepare("SELECT id, nombre FROM estado_tipos WHERE estado_tipo_id = 1 ORDER BY nombre");
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

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Usuario',
                'subtitle' => $id ? "Editando usuario #$id" : 'Nuevo usuario',
                'user_id' => $id
            ];

            require_once __DIR__ . '/../Views/users/form.php';

        } catch (Exception $e) {
            error_log("Error en UserController::show: " . $e->getMessage());
            http_response_code(500);
            echo "Error interno del servidor";
        }
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
}
