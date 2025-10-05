<?php

namespace App\Controllers;

use App\Models\Menu;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Constants\AppConstants;
use Exception;

class MenuController extends BaseController
{
    private $menuModel;
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();

        $this->menuModel = new Menu();
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
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú primero
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menus')) {
                http_response_code(403);
                echo $this->renderError('No tienes acceso a esta sección.');
                return;
            }

            // Obtener filtros de búsqueda
            $filters = [
                'nombre' => $_GET['nombre'] ?? '',
                'estado_tipo_id' => $_GET['estado_tipo_id'] ?? '',
                'display' => $_GET['display'] ?? ''
            ];

            // Obtener menús y estados usando el modelo
            $menus = $this->menuModel->getAll($filters);
            $statusTypes = $this->menuModel->getStatusTypes();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Menús',
                'subtitle' => 'Lista de todos los menús del sistema',
                'menus' => $menus,
                'statusTypes' => $statusTypes,
                'filters' => $filters
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
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú primero
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menu')) {
                http_response_code(403);
                echo $this->renderError('No tienes acceso a esta sección.');
                return;
            }

            $menu = null;
            if ($id) {
                $menu = $this->menuModel->find((int)$id);
                if (!$menu) {
                    http_response_code(404);
                    echo $this->renderError('Menú no encontrado.');
                    return;
                }
            }

            // Obtener estados disponibles usando el modelo
            $statusTypes = $this->menuModel->getStatusTypes();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Menú',
                'subtitle' => $id ? "Editando menú #$id" : 'Nuevo menú',
                'menu_id' => $id,
                'menu' => $menu,
                'statusTypes' => $statusTypes,
                'action' => $id ? 'edit' : 'create',
                'next_order' => $id ? null : $this->menuModel->getNextOrder()
            ];

            // Usar vista específica según la acción
            if ($id) {
                require_once __DIR__ . '/../Views/menus/edit.php';
            } else {
                require_once __DIR__ . '/../Views/menus/create.php';
            }
        } catch (Exception $e) {
            error_log("Error en MenuController::show: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    /**
     * Crear nuevo menú
     */
    public function create()
    {
        $this->show();
    }

    /**
     * Guardar nuevo menú
     */
    public function store()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError('Método no permitido');
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError('Token de seguridad inválido');
                return;
            }

            // Validar datos
            $errors = $this->validateMenuData($_POST);
            
            if (!empty($errors)) {
                $statusTypes = $this->menuModel->getStatusTypes();
                $data = [
                    'user' => $currentUser,
                    'title' => 'Gestión de Menú',
                    'subtitle' => 'Nuevo menú',
                    'menu_id' => null,
                    'menu' => $_POST,
                    'statusTypes' => $statusTypes,
                    'action' => 'create',
                    'next_order' => $this->menuModel->getNextOrder(),
                    'errors' => $errors
                ];
                require_once __DIR__ . '/../Views/menus/create.php';
                return;
            }

            // Crear menú usando el modelo
            $menuId = $this->menuModel->create($_POST);

            // Redireccionar con mensaje de éxito
            $this->redirectWithSuccess(AppConstants::ROUTE_MENUS, AppConstants::SUCCESS_CREATED);

        } catch (Exception $e) {
            error_log("Error en MenuController::store: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error al guardar el menú: ' . $e->getMessage());
        }
    }

    /**
     * Editar menú existente
     */
    public function edit($id)
    {
        $this->show($id);
    }

    /**
     * Actualizar menú existente
     */
    public function update($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError('Método no permitido');
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError('Token de seguridad inválido');
                return;
            }

            // Obtener ID del menú
            $menuId = $id ?? (int)($_POST['id'] ?? 0);
            
            if (!$menuId) {
                http_response_code(400);
                echo $this->renderError('ID de menú requerido');
                return;
            }

            // Verificar que el menú existe usando el modelo
            $menu = $this->menuModel->find($menuId);
            if (!$menu) {
                http_response_code(404);
                echo $this->renderError('Menú no encontrado');
                return;
            }

            // Validar datos
            $errors = $this->validateMenuData($_POST, $menuId);
            
            if (!empty($errors)) {
                $statusTypes = $this->menuModel->getStatusTypes();
                $data = [
                    'user' => $currentUser,
                    'title' => 'Gestión de Menú',
                    'subtitle' => "Editando menú #$menuId",
                    'menu_id' => $menuId,
                    'menu' => array_merge($menu, $_POST),
                    'statusTypes' => $statusTypes,
                    'action' => 'edit',
                    'errors' => $errors
                ];
                require_once __DIR__ . '/../Views/menus/edit.php';
                return;
            }

            // Actualizar menú usando el modelo
            $success = $this->menuModel->update($menuId, $_POST);

            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_MENUS, AppConstants::SUCCESS_UPDATED);
            } else {
                throw new Exception('No se pudo actualizar el menú');
            }

        } catch (Exception $e) {
            error_log("Error en MenuController::update: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error al actualizar el menú: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar menú
     */
    public function delete()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError('Método no permitido');
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError('Token de seguridad inválido');
                return;
            }

            $menuId = (int)($_POST['id'] ?? 0);
            
            if (!$menuId) {
                http_response_code(400);
                echo $this->renderError('ID de menú requerido');
                return;
            }

            // Eliminar menú usando el modelo
            $success = $this->menuModel->delete($menuId);

            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_MENUS, AppConstants::SUCCESS_DELETED);
            } else {
                throw new Exception('No se pudo eliminar el menú');
            }

        } catch (Exception $e) {
            error_log("Error en MenuController::delete: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_MENUS, $e->getMessage());
        }
    }

    /**
     * Cambiar estado del menú
     */
    public function toggleStatus()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar método POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError('Método no permitido');
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError('Token de seguridad inválido');
                return;
            }

            $menuId = (int)($_POST['id'] ?? 0);
            
            if (!$menuId) {
                http_response_code(400);
                echo $this->renderError('ID de menú requerido');
                return;
            }

            // Cambiar estado usando el modelo
            $success = $this->menuModel->toggleStatus($menuId);

            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_MENUS, AppConstants::SUCCESS_STATUS_CHANGED);
            } else {
                throw new Exception('No se pudo cambiar el estado del menú');
            }

        } catch (Exception $e) {
            error_log("Error en MenuController::toggleStatus: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_MENUS, $e->getMessage());
        }
    }

    /**
     * Valida los datos del menú
     */
    private function validateMenuData(array $data, $excludeId = null): array
    {
        $errors = [];

        // Nombre requerido
        if (empty(trim($data['nombre'] ?? ''))) {
            $errors[] = 'El nombre es requerido';
        } elseif (strlen($data['nombre']) > 150) {
            $errors[] = 'El nombre no puede exceder 150 caracteres';
        } elseif ($this->menuModel->nameExists($data['nombre'], $excludeId)) {
            $errors[] = 'Ya existe un menú con este nombre';
        }

        // URL requerida
        if (empty(trim($data['url'] ?? ''))) {
            $errors[] = 'La URL es requerida';
        } elseif (strlen($data['url']) > 100) {
            $errors[] = 'La URL no puede exceder 100 caracteres';
        } elseif (!str_starts_with($data['url'], '/')) {
            $errors[] = 'La URL debe comenzar con "/"';
        } elseif ($this->menuModel->urlExists($data['url'], $excludeId)) {
            $errors[] = 'Ya existe un menú con esta URL';
        }

        // Orden requerido
        if (empty($data['orden']) || !is_numeric($data['orden']) || $data['orden'] < 1) {
            $errors[] = 'El orden debe ser un número mayor a 0';
        }

        // Icono opcional pero validar longitud
        if (!empty($data['icono']) && strlen($data['icono']) > 50) {
            $errors[] = 'El icono no puede exceder 50 caracteres';
        }

        // Estado válido
        if (!empty($data['estado_tipo_id']) && !is_numeric($data['estado_tipo_id'])) {
            $errors[] = 'Estado inválido';
        }

        // Descripción opcional pero validar longitud
        if (!empty($data['descripcion']) && strlen($data['descripcion']) > 300) {
            $errors[] = 'La descripción no puede exceder 300 caracteres';
        }

        // Display requerido (texto para mostrar en la interfaz)
        if (empty(trim($data['display'] ?? ''))) {
            $errors[] = 'El texto a mostrar (display) es requerido';
        } elseif (strlen($data['display']) > 150) {
            $errors[] = 'El texto a mostrar no puede exceder 150 caracteres';
        }

        return $errors;
    }


}
