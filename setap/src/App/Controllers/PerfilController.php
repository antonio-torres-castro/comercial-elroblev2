<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
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
                'title' => 'Mi Perfil',
                'subtitle' => 'Información de tu cuenta'
            ];

            require_once __DIR__ . '/../Views/perfil/view.php';
        } catch (Exception $e) {
            error_log("Error en PerfilController::index: " . $e->getMessage());
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
            error_log("Error en PerfilController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Actualizar información del perfil
     */
    private function updateProfile(array $currentUser)
    {
        try {
            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError('Token de seguridad inválido');
                return;
            }

            // Validar datos
            $errors = $this->validateProfileData($_POST);

            if (!empty($errors)) {
                // Mostrar formulario con errores
                $data = [
                    'user' => array_merge($currentUser, $_POST),
                    'title' => AppConstants::UI_TITLE_VIEW_PERFIL_EDIT ,
                    'subtitle' => AppConstants::UI_SUBTITLE_VIEW_PERFIL_EDIT ,
                    'errors' => $errors
                ];
                require_once __DIR__ . '/../Views/perfil/edit.php';
                return;
            }

            // Preparar datos para actualización
            $updateData = [
                'nombre' => trim($_POST['nombre']),
                'email' => trim($_POST['email']),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'direccion' => trim($_POST['direccion'] ?? '')
            ];

            // Actualizar perfil usando el modelo
            $success = $this->userModel->updateProfile($currentUser['id'], $updateData);

            if ($success) {
                // Actualizar datos en sesión
                $_SESSION['nombre_completo'] = $updateData['nombre'];
                $_SESSION['email'] = $updateData['email'];

                $_SESSION['success_message'] = 'Perfil actualizado correctamente';
                $this->redirectToRoute(AppConstants::ROUTE_PERFIL);
            } else {
                throw new Exception('No se pudo actualizar el perfil');
            }
        } catch (Exception $e) {
            error_log("Error en PerfilController::updateProfile: " . $e->getMessage());

            // Mostrar formulario con error
            $data = [
                'user' => array_merge($currentUser, $_POST ?? []),
                'title' => AppConstants::UI_TITLE_VIEW_PERFIL_EDIT ,
                'subtitle' => AppConstants::UI_SUBTITLE_VIEW_PERFIL_EDIT ,
                'errors' => ['Error al actualizar el perfil: ' . $e->getMessage()]
            ];
            require_once __DIR__ . '/../Views/perfil/edit.php';
        }
    }

    /**
     * Validar datos del perfil
     */
    private function validateProfileData(array $data): array
    {
        $errors = [];

        // Nombre requerido
        if (empty(trim($data['nombre'] ?? ''))) {
            $errors[] = 'El nombre completo es requerido';
        } elseif (strlen($data['nombre']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['nombre']) > 100) {
            $errors[] = 'El nombre no puede exceder 100 caracteres';
        }

        // Email requerido y válido
        if (empty(trim($data['email'] ?? ''))) {
            $errors[] = 'El email es requerido';
        } elseif (!Security::validateEmail($data['email'])) {
            $errors[] = 'El email no tiene un formato válido';
        }

        // Teléfono opcional pero validar formato si se proporciona
        if (!empty($data['telefono'])) {
            $telefono = preg_replace('/[^0-9+\-\s]/', '', $data['telefono']);
            if (strlen($telefono) < 8) {
                $errors[] = 'El teléfono debe tener al menos 8 dígitos';
            } elseif (strlen($telefono) > 20) {
                $errors[] = 'El teléfono no puede exceder 20 caracteres';
            }
        }

        // Dirección opcional pero validar longitud
        if (!empty($data['direccion']) && strlen($data['direccion']) > 200) {
            $errors[] = 'La dirección no puede exceder 200 caracteres';
        }

        return $errors;
    }
}
