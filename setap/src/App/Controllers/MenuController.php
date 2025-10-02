<?php

namespace App\Controllers;

use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Config\Database;
use Exception;
use PDO;

class MenuController
{
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();

        $this->permissionService = new PermissionService();
    }

    /**
     * Lista de menús (plural) - Solo para administradores
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar acceso al menú primero
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menus')) {
                http_response_code(403);
                echo $this->renderError('No tienes acceso a esta sección.');
                return;
            }

            // Obtener todos los menús de la base de datos
            $menus = $this->getAllMenus();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Menús',
                'subtitle' => 'Lista de todos los menús del sistema',
                'menus' => $menus
            ];

            require_once __DIR__ . '/../Views/menus/list.php';
        } catch (Exception $e) {
            error_log("Error en MenuController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    /**
     * Mostrar/editar menú individual (singular)
     */
    public function show($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar acceso al menú primero
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menu')) {
                http_response_code(403);
                echo $this->renderError('No tienes acceso a esta sección.');
                return;
            }

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Menú',
                'subtitle' => $id ? "Editando menú #$id" : 'Nuevo menú',
                'menu_id' => $id
            ];

            require_once __DIR__ . '/../Views/menus/form.php';
        } catch (Exception $e) {
            error_log("Error en MenuController::show: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    /**
     * Obtiene todos los menús de la base de datos
     */
    private function getAllMenus(): array
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT 
                        id,
                        nombre,
                        url,
                        icono,
                        orden,
                        estado_tipo_id,
                        fecha_creacion,
                        fecha_modificacion,
                        display
                    FROM menu 
                    ORDER BY orden ASC, nombre ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener menús: " . $e->getMessage());
            return [];
        }
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
                                <a href="/home" class="btn btn-primary">Volver al Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
}
