<?php

namespace App\Controllers;

use App\Models\GrupoTipo;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;
use App\Core\ViewRenderer;
use Exception;

class GrupoTipoController extends BaseController
{
    private $model;
    private $permissionService;
    private $viewRenderer;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->model = new GrupoTipo();
        $this->permissionService = new PermissionService();
        $this->viewRenderer = new ViewRenderer();
    }

    public function index(): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_grupo_tipos')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            $items = $this->model->getAll();
            echo $this->viewRenderer->render('grupo_tipos/list', [
                'user' => $currentUser,
                'title' => 'Grupo Tipos',
                'items' => $items,
                'success' => $_GET['success'] ?? '',
                'error' => $_GET['error'] ?? ''
            ]);
        } catch (Exception $e) {
            Logger::error('GrupoTipoController::index: ' . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function create(): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_grupo_tipos')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            echo $this->viewRenderer->render('grupo_tipos/form', [
                'title' => 'Grupo Tipos',
                'item' => null,
                'action' => AppConstants::ROUTE_GRUPO_TIPOS_CREATE
            ]);
        } catch (Exception $e) {
            Logger::error('GrupoTipoController::create: ' . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function store(): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_grupo_tipos')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS_CREATE, AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            if ($nombre === '') {
                $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS, 'Nombre requerido');
                return;
            }
            $ok = $this->model->create(['nombre' => $nombre, 'descripcion' => $descripcion]);
            if ($ok) {
                $this->redirectWithSuccess(AppConstants::ROUTE_GRUPO_TIPOS, 'Creado correctamente');
            } else {
                $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS, 'El nombre ya existe o error al crear');
            }
        } catch (Exception $e) {
            Logger::error('GrupoTipoController::store: ' . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS, AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function edit(?int $id = null): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_grupo_tipos')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            $id = $id ?? (isset($_GET['id']) ? (int)$_GET['id'] : null);
            if (!$id) {
                $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS, AppConstants::ERROR_INVALID_DATA);
                return;
            }
            $item = $this->model->find($id);
            if (!$item) {
                $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS, 'Registro no encontrado');
                return;
            }
            echo $this->viewRenderer->render('grupo_tipos/form', [
                'title' => 'Grupo Tipos',
                'item' => $item,
                'action' => AppConstants::ROUTE_GRUPO_TIPOS_UPDATE . '?id=' . $id
            ]);
        } catch (Exception $e) {
            Logger::error('GrupoTipoController::edit: ' . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function update(): void
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_grupo_tipos')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Validar CSRF token
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS_EDIT, AppConstants::ERROR_INVALID_CSRF_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            if (!$id || $nombre === '') {
                $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS, AppConstants::ERROR_INVALID_DATA);
                return;
            }
            $ok = $this->model->update($id, ['nombre' => $nombre, 'descripcion' => $descripcion]);
            if ($ok) {
                $this->redirectWithSuccess(AppConstants::ROUTE_GRUPO_TIPOS, 'Actualizado correctamente');
            } else {
                $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS, 'El nombre ya existe o error al actualizar');
            }
        } catch (Exception $e) {
            Logger::error('GrupoTipoController::update: ' . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_GRUPO_TIPOS, AppConstants::ERROR_INTERNAL_SERVER);
        }
    }
}
