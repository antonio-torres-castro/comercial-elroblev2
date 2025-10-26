<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;
use Exception;

class PerfilController extends BaseController
{
    private $permissionService;
    private $userModel;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        $this->permissionService = new PermissionService();
        $this->userModel = new User();
    }

    /**
     * Ver perfil del usuario actual
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para ver perfil
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'view_perfil')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Obtener datos completos del usuario
            $fullUserData = $this->userModel->findComplete($currentUser['id']);
            if (!$fullUserData) {
                http_response_code(404);
                echo $this->renderError(AppConstants::ERROR_USER_NOT_FOUND);
                return;
            }

            // Datos para la vista
            $data = [
                'user' => $fullUserData,
                'title' => AppConstants::UI_MY_PROFILE,
                'subtitle' => 'Información de tu cuenta'
            ];

            require_once __DIR__ . '/../Views/perfil/view.php';
        } catch (Exception $e) {
            Logger::error("PerfilController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Editar perfil del usuario actual
     */
    public function edit()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para editar perfil
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_perfil')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_EDIT_PERMISSIONS);
                return;
            }

            // Obtener datos completos del usuario
            $fullUserData = $this->userModel->findComplete($currentUser['id']);
            if (!$fullUserData) {
                http_response_code(404);
                echo $this->renderError(AppConstants::ERROR_USER_NOT_FOUND);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updateProfile($fullUserData);
                return;
            }

            // Datos para la vista
            $data = [
                'user' => $fullUserData,
                'title' => AppConstants::UI_TITLE_VIEW_PERFIL_EDIT,
                'subtitle' => AppConstants::UI_SUBTITLE_VIEW_PERFIL_EDIT
            ];

            require_once __DIR__ . '/../Views/perfil/edit.php';
        } catch (Exception $e) {
            Logger::error("PerfilController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Actualizar perfil del usuario
     */
    private function updateProfile($fullUserData)
    {
        try {
            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            // Validar datos requeridos
            $errors = $this->validateProfileData($_POST);
            if (!empty($errors)) {
                http_response_code(400);
                echo $this->renderError(implode(', ', $errors));
                return;
            }

            // Preparar datos para actualización
            $data = [
                'nombre' => Security::sanitizeInput($_POST['nombre']),
                'telefono' => Security::sanitizeInput($_POST['telefono'] ?? ''),
                'direccion' => Security::sanitizeInput($_POST['direccion'] ?? ''),
                'email' => Security::sanitizeInput($_POST['email'])
            ];

            // Actualizar perfil usando el modelo
            if ($this->userModel->updateProfile($fullUserData['id'], $data)) {
                // Redirigir con mensaje de éxito
                $_SESSION['success_message'] = 'Perfil actualizado correctamente';
                header('Location: ' . AppConstants::ROUTE_PERFIL);
                exit;
            } else {
                throw new Exception('Error al actualizar el perfil');
            }
        } catch (Exception $e) {
            Logger::error("PerfilController::updateProfile: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Validar datos del perfil
     */
    private function validateProfileData($data): array
    {
        $errors = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es requerido';
        } elseif (strlen(trim($data['nombre'])) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors[] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido';
        }

        // Validar teléfono (opcional pero si se proporciona debe ser válido)
        if (!empty($data['telefono'])) {
            if (!preg_match('/^[0-9+\-\s()]{7,15}$/', $data['telefono'])) {
                $errors[] = 'El teléfono no es válido';
            }
        }

        return $errors;
    }

    /**
     * Cambiar contraseña del usuario
     */
    public function changePassword()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para cambiar contraseña
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_perfil')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_EDIT_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->processPasswordChange($currentUser);
                return;
            }

            // Mostrar formulario de cambio de contraseña
            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_CHANGE_PASSWORD,
                'subtitle' => 'Actualizar tu contraseña de acceso'
            ];

            require_once __DIR__ . '/../Views/perfil/change_password.php';
        } catch (Exception $e) {
            Logger::error("PerfilController::changePassword: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Procesar cambio de contraseña
     */
    private function processPasswordChange($currentUser)
    {
        try {
            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            // Validar contraseña actual
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $errors = [];

            if (empty($currentPassword)) {
                $errors[] = 'La contraseña actual es requerida';
            }

            if (empty($newPassword)) {
                $errors[] = 'La nueva contraseña es requerida';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres';
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Las contraseñas no coinciden';
            }

            if (!empty($errors)) {
                http_response_code(400);
                echo $this->renderError(implode(', ', $errors));
                return;
            }

            // Verificar contraseña actual
            if (!$this->userModel->verifyPassword($currentUser['id'], $currentPassword)) {
                http_response_code(400);
                echo $this->renderError('La contraseña actual es incorrecta');
                return;
            }

            // Actualizar contraseña
            if ($this->userModel->updatePassword($currentUser['id'], $newPassword)) {
                $_SESSION['success_message'] = 'Contraseña actualizada correctamente';
                header('Location: ' . AppConstants::ROUTE_PERFIL);
                exit;
            } else {
                throw new Exception('Error al actualizar la contraseña');
            }
        } catch (Exception $e) {
            Logger::error("PerfilController::processPasswordChange: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }
}
