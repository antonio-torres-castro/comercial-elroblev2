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

            // Generar token CSRF si no existe
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

            // Generar token CSRF si no existe
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            $menu = null;
            if ($id) {
                $menu = $this->getMenuById($id);
                if (!$menu) {
                    http_response_code(404);
                    echo $this->renderError('Menú no encontrado.');
                    return;
                }
            }

            // Obtener estados disponibles
            $estados = $this->getEstados();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Menú',
                'subtitle' => $id ? "Editando menú #$id" : 'Nuevo menú',
                'menu_id' => $id,
                'menu' => $menu,
                'estados' => $estados
            ];

            require_once __DIR__ . '/../Views/menus/form.php';
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
                Security::redirect('/login');
                return;
            }

            // Verificar acceso
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menu')) {
                http_response_code(403);
                echo $this->renderError('No tienes acceso a esta sección.');
                return;
            }

            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError('Token de seguridad inválido.');
                return;
            }

            // Validar datos
            $errors = $this->validateMenuData($_POST);
            
            if (!empty($errors)) {
                $estados = $this->getEstados();
                $data = [
                    'user' => $currentUser,
                    'title' => 'Gestión de Menú',
                    'subtitle' => 'Nuevo menú',
                    'menu_id' => null,
                    'menu' => $_POST,
                    'estados' => $estados,
                    'errors' => $errors
                ];
                require_once __DIR__ . '/../Views/menus/form.php';
                return;
            }

            // Crear menú
            $menuId = $this->createMenu($_POST);

            if ($menuId) {
                $_SESSION['success_message'] = 'Menú creado exitosamente.';
                Security::redirect('/menus');
            } else {
                throw new Exception('Error al crear el menú');
            }

        } catch (Exception $e) {
            error_log("Error en MenuController::store: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
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
                Security::redirect('/login');
                return;
            }

            // Verificar acceso
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menu')) {
                http_response_code(403);
                echo $this->renderError('No tienes acceso a esta sección.');
                return;
            }

            // Obtener ID del menú
            $menuId = $id ?? $_POST['id'] ?? null;
            
            if (!$menuId) {
                http_response_code(400);
                echo $this->renderError('ID de menú requerido.');
                return;
            }

            // Verificar que el menú existe
            $menu = $this->getMenuById($menuId);
            if (!$menu) {
                http_response_code(404);
                echo $this->renderError('Menú no encontrado.');
                return;
            }

            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError('Token de seguridad inválido.');
                return;
            }

            // Validar datos
            $errors = $this->validateMenuData($_POST, $menuId);
            
            if (!empty($errors)) {
                $estados = $this->getEstados();
                $data = [
                    'user' => $currentUser,
                    'title' => 'Gestión de Menú',
                    'subtitle' => "Editando menú #$menuId",
                    'menu_id' => $menuId,
                    'menu' => array_merge($menu, $_POST),
                    'estados' => $estados,
                    'errors' => $errors
                ];
                require_once __DIR__ . '/../Views/menus/form.php';
                return;
            }

            // Actualizar menú
            $result = $this->updateMenu($menuId, $_POST);

            if ($result) {
                $_SESSION['success_message'] = 'Menú actualizado exitosamente.';
                Security::redirect('/menus');
            } else {
                throw new Exception('Error al actualizar el menú');
            }

        } catch (Exception $e) {
            error_log("Error en MenuController::update: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
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
                echo json_encode(['success' => false, 'message' => 'No autenticado']);
                return;
            }

            // Verificar acceso
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menu')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $menuId = $_POST['id'] ?? null;
            
            if (!$menuId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                return;
            }

            // Verificar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            // Verificar que el menú existe
            $menu = $this->getMenuById($menuId);
            if (!$menu) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Menú no encontrado']);
                return;
            }

            // Eliminar (cambiar estado a eliminado)
            $result = $this->deleteMenu($menuId);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Menú eliminado exitosamente' : 'Error al eliminar el menú'
            ]);

        } catch (Exception $e) {
            error_log("Error en MenuController::delete: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
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
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autenticado']);
                return;
            }

            // Verificar acceso
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_menu')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permisos']);
                return;
            }

            $menuId = $_POST['id'] ?? null;
            $newStatus = $_POST['status'] ?? null;
            
            if (!$menuId || !$newStatus) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos requeridos']);
                return;
            }

            // Verificar que el menú existe
            $menu = $this->getMenuById($menuId);
            if (!$menu) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Menú no encontrado']);
                return;
            }

            // Cambiar estado
            $result = $this->changeMenuStatus($menuId, $newStatus);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Estado actualizado exitosamente' : 'Error al actualizar el estado'
            ]);

        } catch (Exception $e) {
            error_log("Error en MenuController::toggleStatus: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
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
                        m.id,
                        m.nombre,
                        m.descripcion,
                        m.url,
                        m.icono,
                        m.orden,
                        m.estado_tipo_id,
                        m.fecha_creacion,
                        m.fecha_modificacion,
                        m.display,
                        et.nombre as estado_nombre
                    FROM menu m
                    LEFT JOIN estado_tipos et ON m.estado_tipo_id = et.id
                    WHERE m.estado_tipo_id != 4
                    ORDER BY m.orden ASC, m.nombre ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener menús: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un menú por ID
     */
    private function getMenuById($id): ?array
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT 
                        id,
                        nombre,
                        descripcion,
                        url,
                        icono,
                        orden,
                        estado_tipo_id,
                        fecha_creacion,
                        fecha_modificacion,
                        display
                    FROM menu 
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error al obtener menú por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene los estados disponibles
     */
    private function getEstados(): array
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT id, nombre 
                    FROM tipo 
                    WHERE tabla_nombre = 'menu' 
                    AND campo_nombre = 'estado' 
                    ORDER BY id ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si no hay estados específicos, usar estados generales
            if (empty($estados)) {
                $estados = [
                    ['id' => 1, 'nombre' => 'Creado'],
                    ['id' => 2, 'nombre' => 'Activo'], 
                    ['id' => 3, 'nombre' => 'Inactivo'],
                    ['id' => 4, 'nombre' => 'Eliminado']
                ];
            }

            return $estados;
        } catch (Exception $e) {
            error_log("Error al obtener estados: " . $e->getMessage());
            return [
                ['id' => 1, 'nombre' => 'Creado'],
                ['id' => 2, 'nombre' => 'Activo'], 
                ['id' => 3, 'nombre' => 'Inactivo'],
                ['id' => 4, 'nombre' => 'Eliminado']
            ];
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
        }

        // Display requerido
        if (empty(trim($data['display'] ?? ''))) {
            $errors[] = 'El título de visualización es requerido';
        } elseif (strlen($data['display']) > 150) {
            $errors[] = 'El título de visualización no puede exceder 150 caracteres';
        }

        // URL requerida
        if (empty(trim($data['url'] ?? ''))) {
            $errors[] = 'La URL es requerida';
        } elseif (strlen($data['url']) > 100) {
            $errors[] = 'La URL no puede exceder 100 caracteres';
        } elseif (!str_starts_with($data['url'], '/')) {
            $errors[] = 'La URL debe comenzar con "/"';
        }

        // Verificar URL única
        if (!empty($data['url']) && $this->isUrlDuplicate($data['url'], $excludeId)) {
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

        return $errors;
    }

    /**
     * Verifica si la URL está duplicada
     */
    private function isUrlDuplicate(string $url, $excludeId = null): bool
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT id FROM menu WHERE url = :url";
            $params = [':url' => $url];

            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error al verificar URL duplicada: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuevo menú
     */
    private function createMenu(array $data): ?int
    {
        try {
            $db = Database::getInstance();

            $sql = "INSERT INTO menu (nombre, descripcion, url, icono, orden, estado_tipo_id, display, fecha_creacion) 
                    VALUES (:nombre, :descripcion, :url, :icono, :orden, :estado_tipo_id, :display, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nombre' => trim($data['nombre']),
                ':descripcion' => trim($data['descripcion'] ?? ''),
                ':url' => trim($data['url']),
                ':icono' => trim($data['icono'] ?? ''),
                ':orden' => (int)$data['orden'],
                ':estado_tipo_id' => (int)($data['estado_tipo_id'] ?? 2),
                ':display' => trim($data['display'])
            ]);

            return $db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error al crear menú: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza un menú existente
     */
    private function updateMenu(int $id, array $data): bool
    {
        try {
            $db = Database::getInstance();

            $sql = "UPDATE menu 
                    SET nombre = :nombre, 
                        descripcion = :descripcion,
                        url = :url, 
                        icono = :icono, 
                        orden = :orden, 
                        estado_tipo_id = :estado_tipo_id, 
                        display = :display, 
                        fecha_modificacion = NOW() 
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':nombre' => trim($data['nombre']),
                ':descripcion' => trim($data['descripcion'] ?? ''),
                ':url' => trim($data['url']),
                ':icono' => trim($data['icono'] ?? ''),
                ':orden' => (int)$data['orden'],
                ':estado_tipo_id' => (int)($data['estado_tipo_id'] ?? 2),
                ':display' => trim($data['display']),
                ':id' => $id
            ]);
        } catch (Exception $e) {
            error_log("Error al actualizar menú: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un menú (cambia estado a eliminado)
     */
    private function deleteMenu(int $id): bool
    {
        try {
            $db = Database::getInstance();

            $sql = "UPDATE menu 
                    SET estado_tipo_id = 4, 
                        fecha_modificacion = NOW() 
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            error_log("Error al eliminar menú: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambia el estado de un menú
     */
    private function changeMenuStatus(int $id, int $newStatus): bool
    {
        try {
            $db = Database::getInstance();

            $sql = "UPDATE menu 
                    SET estado_tipo_id = :status, 
                        fecha_modificacion = NOW() 
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':status' => $newStatus,
                ':id' => $id
            ]);
        } catch (Exception $e) {
            error_log("Error al cambiar estado del menú: " . $e->getMessage());
            return false;
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
