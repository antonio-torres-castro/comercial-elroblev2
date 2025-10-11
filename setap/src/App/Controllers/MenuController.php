<?php

namespace App\Controllers;

use App\Models\Menu;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Constants\AppConstants;
use app\Config\Database;
use Exception;

class MenuController extends BaseController
{
    private $menuModel;
    private $permissionService;
    private $db;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        $this->menuModel = new Menu();
        $this->permissionService = new PermissionService();
        $this->db = Database::getInstance();
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
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
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
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar/editar menú individual (singular) - SIMPLIFICADO
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
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            // Si hay ID, delegar al método edit() que ya está estandarizado
            if ($id) {
                $this->edit($id);
                return;
            }

            // Obtener estados disponibles usando el modelo
            $statusTypes = $this->menuModel->getStatusTypes();
            
            // Obtener grupos de menú para consistencia
            $menuGroups = $this->menuModel->getMenuGroups();

            // Datos para nuevo menú
            $data = [
                'user' => $currentUser,
                'title' => AppConstants::UI_TITLE_VIEW_MENU,
                'subtitle' => 'Nuevo menú',
                'menu_id' => null,
                'menu' => null,
                'menuGroups' => $menuGroups,
                'statusTypes' => $statusTypes,
                'action' => 'create',
                'next_order' => $this->menuModel->getNextOrder()
            ];

            require_once __DIR__ . '/../Views/menus/create.php';
        } catch (Exception $e) {
            error_log("Error en MenuController::show: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar formulario de creación de menú
     */
    public function create()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú de gestión individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menu')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            // Obtener tipos de estado disponibles
            $statusTypes = $this->menuModel->getStatusTypes();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Crear Menú',
                'subtitle' => 'Agregar nuevo menú al sistema',
                'menu' => null, // Para nuevo menú
                'statusTypes' => $statusTypes
            ];

            require_once __DIR__ . '/../Views/menus/create.php';

        } catch (Exception $e) {
            error_log("Error en MenuController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
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

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menus')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectTo(AppConstants::ROUTE_MENUS);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError('/menus/create', 'Token CSRF inválido');
                return;
            }

            // Validar datos del menú con validaciones mejoradas - FASE 3
            $postData = $this->validatePostData([
                'nombre' => '',
                'descripcion' => '',
                'display' => '',
                'icono' => '',
                'url' => '',
                'menu_grupo_id' => 0,
                'orden' => 0
            ]);

            $errors = $this->validateRequiredFields($postData, ['nombre', 'display']);
            // Añadir validaciones adicionales
            $errors = array_merge($errors, $this->validateLength($postData['nombre'], 'nombre', 3, 100));
            $errors = array_merge($errors, $this->validateLength($postData['display'], 'display', 3, 100));
            
            // Manejo estandarizado de errores - FASE 3.3
            $this->handleValidationErrors($errors, $_POST, '/menus/create');

            // Crear el menú con datos validados
            $menuData = [
                'nombre' => $postData['nombre'],
                'descripcion' => $postData['descripcion'],
                'display' => $postData['display'],
                'icono' => $postData['icono'],
                'url' => $postData['url'],
                'menu_grupo_id' => (int)$postData['menu_grupo_id'],
                'orden' => (int)$postData['orden'],
                'estado_tipo_id' => 2 // Activo por defecto
            ];

            if ($this->menuModel->create($menuData)) {
                $this->redirectWithSuccess(AppConstants::ROUTE_MENUS, 'Menú creado correctamente');
            } else {
                throw new Exception('Error al crear el menú');
            }

        } catch (Exception $e) {
            error_log("Error en MenuController::store: " . $e->getMessage());
            $_SESSION['errors'] = ['Error interno del servidor'];
            $_SESSION['old_input'] = $_POST;
            $this->redirectTo('/menus/create');
        }
    }

    /**
     * Editar menú
     */
    public function edit($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menus')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $id = $id ?: (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_MENUS, 'ID de menú inválido');
                return;
            }

            // Obtener el menú a editar
            $menu = $this->menuModel->find($id);
            if (!$menu) {
                $this->redirectWithError(AppConstants::ROUTE_MENUS, 'Menú no encontrado');
                return;
            }

            // Obtener grupos de menú
            $menuGroups = $this->menuModel->getMenuGroups();
            
            // Obtener tipos de estado disponibles
            $statusTypes = $this->menuModel->getStatusTypes();

            // Datos para la vista - ESTANDARIZADO con show()
            $data = [
                'user' => $currentUser,
                'title' => 'Editar Menú',
                'subtitle' => "Editando: {$menu['nombre']}",
                'menu_id' => $id,
                'menu' => $menu,
                'menuGroups' => $menuGroups,
                'statusTypes' => $statusTypes,
                'action' => 'edit'
            ];

            require_once __DIR__ . '/../Views/menus/edit.php';

        } catch (Exception $e) {
            error_log("Error en MenuController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Actualizar menú
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
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menus')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectTo(AppConstants::ROUTE_MENUS);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $this->redirectWithError(AppConstants::ROUTE_MENUS, 'ID de menú inválido');
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError("/menus/edit?id=$id", 'Token CSRF inválido');
                return;
            }

            // Validar datos del menú con validaciones mejoradas - FASE 3
            $postData = $this->validatePostData([
                'nombre' => '',
                'descripcion' => '',
                'display' => '',
                'icono' => '',
                'url' => '',
                'menu_grupo_id' => 0,
                'orden' => 0,
                'estado_tipo_id' => 2
            ]);

            $errors = $this->validateRequiredFields($postData, ['nombre', 'display']);
            // Añadir validaciones adicionales
            $errors = array_merge($errors, $this->validateLength($postData['nombre'], 'nombre', 3, 100));
            $errors = array_merge($errors, $this->validateLength($postData['display'], 'display', 3, 100));
            
            // Manejo estandarizado de errores - FASE 3.3
            $this->handleValidationErrors($errors, $_POST, "/menus/edit?id=$id");

            // Actualizar el menú con datos validados
            $menuData = [
                'nombre' => $postData['nombre'],
                'descripcion' => $postData['descripcion'],
                'display' => $postData['display'],
                'icono' => $postData['icono'],
                'url' => $postData['url'],
                'menu_grupo_id' => (int)$postData['menu_grupo_id'],
                'orden' => (int)$postData['orden'],
                'estado_tipo_id' => (int)$postData['estado_tipo_id']
            ];

            if ($this->menuModel->update($id, $menuData)) {
                $this->redirectWithSuccess(AppConstants::ROUTE_MENUS, 'Menú actualizado correctamente');
            } else {
                throw new Exception('Error al actualizar el menú');
            }

        } catch (Exception $e) {
            error_log("Error en MenuController::update: " . $e->getMessage());
            $id = (int)($_POST['id'] ?? 0);
            $_SESSION['errors'] = ['Error interno del servidor'];
            $_SESSION['old_input'] = $_POST;
            $this->redirectTo("/menus/edit?id=$id");
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
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menus')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => AppConstants::ERROR_NO_PERMISSIONS]);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de menú inválido']);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                return;
            }

            if ($this->menuModel->delete($id)) {
                echo json_encode(['success' => true, 'message' => 'Menú eliminado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el menú']);
            }

        } catch (Exception $e) {
            error_log("Error en MenuController::delete: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    /**
     * Cambiar estado de menú (activar/desactivar)
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

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menus')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $menuId = (int)($_POST['menu_id'] ?? 0);
            $newStatus = (int)($_POST['new_status'] ?? 0);

            if (!$menuId || !in_array($newStatus, [1, 2])) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
                return;
            }

            // Cambiar estado usando el modelo
            $success = $this->menuModel->toggleStatus($menuId);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Estado del menú actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del menú']);
            }
        } catch (Exception $e) {
            error_log("Error en MenuController::toggleStatus: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER]);
        }
    }

    /**
     * Validar datos del menú
     */
    private function validateMenuData(array $data, int $excludeId = 0): array
    {
        $errors = [];

        // Validar nombre
        if (empty(trim($data['nombre'] ?? ''))) {
            $errors[] = 'El nombre es requerido';
        } elseif (strlen(trim($data['nombre'])) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
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

        // Validar grupo de menú
        if (empty($data['menu_grupo_id'])) {
            $errors[] = 'Debe seleccionar un grupo de menú';
        }

        return $errors;
    }

    /**
     * Obtener grupos de menú disponibles
     */
    private function getMenuGroups(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM menu_grupo
                WHERE estado_tipo_id = 2
                ORDER BY orden, nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo grupos de menú: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Redirigir simple
     */
    private function redirectTo(string $url): void
    {
        header("Location: $url");
        exit;
    }

}
