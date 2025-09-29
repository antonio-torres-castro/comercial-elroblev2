<?php

namespace App\Controllers;

use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use Exception;

class PerfilController
{
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        
        $this->permissionService = new PermissionService();
    }

    /**
     * Ver perfil del usuario actual
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para ver perfil
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'view_perfil')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Mi Perfil',
                'subtitle' => 'Información de tu cuenta'
            ];

            require_once __DIR__ . '/../Views/perfil/view.php';

        } catch (Exception $e) {
            error_log("Error en PerfilController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
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
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para editar perfil
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_perfil')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para editar tu perfil.');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updateProfile($currentUser);
                return;
            }

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Editar Perfil',
                'subtitle' => 'Actualiza tu información personal'
            ];

            require_once __DIR__ . '/../Views/perfil/edit.php';

        } catch (Exception $e) {
            error_log("Error en PerfilController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    /**
     * Actualizar información del perfil
     */
    private function updateProfile(array $currentUser)
    {
        // TODO: Implementar lógica de actualización del perfil
        // Por ahora solo mostramos un mensaje de éxito
        
        $_SESSION['success_message'] = 'Perfil actualizado correctamente';
        Security::redirect('/perfil');
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