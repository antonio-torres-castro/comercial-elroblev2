<?php

namespace App\Controllers;

use App\Services\PermissionService;
use App\Services\CommonDataService;
use App\Core\ViewRenderer;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Constants\AppConstants;
use App\Config\Database;
use PDO;
use Exception;

/**
 * AbstractBaseController - Centraliza funcionalidades comunes
 * Elimina código duplicado de todos los controladores
 */
abstract class AbstractBaseController extends BaseController
{
    protected $permissionService;
    protected $viewRenderer;
    protected $db;
    protected $currentUser;
    protected $commonDataService;

    public function __construct()
    {
        // Verificar autenticación automáticamente (excepto para controladores específicos)
        if (!$this->isAuthExempt()) {
            (new AuthMiddleware())->handle();
            $this->currentUser = $this->getCurrentUser();
        }

        // Inicializar servicios comunes
        $this->permissionService = new PermissionService();
        $this->viewRenderer = new ViewRenderer();
        $this->db = Database::getInstance();
        $this->commonDataService = new CommonDataService();

        // Permitir inicialización específica del controlador
        $this->initializeController();
    }

    /**
     * Determina si el controlador está exento de autenticación
     * Sobrescribir en controladores específicos si es necesario
     */
    protected function isAuthExempt(): bool
    {
        return false;
    }

    /**
     * Hook para inicialización específica del controlador
     * Sobrescribir en controladores que necesiten inicialización adicional
     */
    protected function initializeController(): void
    {
        // Por defecto no hace nada, se sobrescribe según necesidad
    }

    /**
     * Verifica autenticación y permisos de menú en una sola llamada
     * Elimina código duplicado de verificaciones
     */
    protected function requireAuthAndPermission(string $menuKey): bool
    {
        // Verificar autenticación
        if (!$this->currentUser) {
            $this->redirectToLogin();
            return false;
        }

        // Verificar permisos de menú
        if (!$this->permissionService->hasMenuAccess($this->currentUser['id'], $menuKey)) {
            http_response_code(403);
            echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
            return false;
        }

        return true;
    }

    /**
     * Validación completa para operaciones POST
     * Centraliza validaciones HTTP y CSRF
     */
    protected function validatePostRequest(): array
    {
        $errors = [];

        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = AppConstants::ERROR_METHOD_NOT_ALLOWED;
            http_response_code(405);
            return $errors;
        }

        // Verificar token CSRF
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Token de seguridad inválido';
            http_response_code(403);
            return $errors;
        }

        return $errors;
    }

    /**
     * Wrapper para manejo estándar de errores
     * Centraliza el try-catch y logging
     */
    protected function executeWithErrorHandling(callable $operation, string $context = ''): mixed
    {
        try {
            return $operation();
        } catch (Exception $e) {
            $controllerName = get_class($this);
            error_log("Error en {$controllerName}::{$context}: " . $e->getMessage());

            // Si es petición AJAX, devolver JSON
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => AppConstants::ERROR_INTERNAL_SERVER], 500);
                return null;
            }

            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
            return null;
        }
    }

    /**
     * Detecta si es una petición AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Validación estándar de ID
     * Elimina código duplicado de validación de IDs
     */
    protected function validateId(?string $id, string $redirectRoute, string $errorConstant): int
    {
        $numericId = (int)($id ?? 0);
        if ($numericId <= 0) {
            $this->redirectWithError($redirectRoute, $errorConstant);
            exit;
        }
        return $numericId;
    }

    /**
     * Renderización unificada de vistas
     * Centraliza y estandariza el renderizado
     */
    protected function render(string $view, array $data = []): void
    {
        // Siempre incluir usuario actual en los datos
        $data['currentUser'] = $this->currentUser;

        echo $this->viewRenderer->render($view, $data);
    }



    /**
     * Métodos de datos comunes centralizados
     * Elimina duplicación de consultas básicas
     */

    protected function getUserTypes(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM usuario_tipos
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo tipos de usuario: " . $e->getMessage());
            return [];
        }
    }

    protected function getEstadosTipo(array $includeIds = [1, 2, 3, 4]): array
    {
        try {
            $placeholders = str_repeat('?,', count($includeIds) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM estado_tipos
                WHERE id IN ($placeholders)
                ORDER BY id
            ");
            $stmt->execute($includeIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo estados: " . $e->getMessage());
            return [];
        }
    }

    protected function getStatusTypes(): array
    {
        return $this->getEstadosTipo();
    }

    /**
     * Filtros estandarizados
     * Centraliza el manejo de filtros de búsqueda
     */
    protected function extractFilters(array $allowedFilters): array
    {
        $filters = [];
        foreach ($allowedFilters as $filter) {
            if (!empty($_GET[$filter])) {
                $filters[$filter] = $_GET[$filter];
            }
        }
        return $filters;
    }

    /**
     * Paginación estándar
     */
    protected function getPaginationParams(): array
    {
        return [
            'page' => max(1, (int)($_GET['page'] ?? 1)),
            'limit' => max(10, min(100, (int)($_GET['limit'] ?? 25)))
        ];
    }
}
