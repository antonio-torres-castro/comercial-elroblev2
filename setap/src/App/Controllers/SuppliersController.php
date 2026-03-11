<?php

namespace App\Controllers;

use App\Models\Suppliers;
use App\Services\PermissionService;
use App\Core\ViewRenderer;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;
use Exception;

class SuppliersController extends BaseController
{
    private $supplierModel;
    private $permissionService;
    private $viewRenderer;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->supplierModel = new Suppliers();
        $this->permissionService = new PermissionService();
        $this->viewRenderer = new ViewRenderer();
    }

    /**
     * Lista de proveedores
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_clients')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $filters = [
                'rut' => $_GET['rut'] ?? '',
                'razon_social' => $_GET['razon_social'] ?? '',
                'estado_tipo_id' => $_GET['estado_tipo_id'] ?? ''
            ];

            $suppliers = $this->supplierModel->getAll($filters);
            $statusTypes = $this->supplierModel->getStatusTypes();

            echo $this->viewRenderer->render('suppliers/list', [
                'user' => $currentUser,
                'title' => AppConstants::UI_SUPPLIER_MANAGEMENT,
                'subtitle' => 'Lista de todos los proveedores',
                'suppliers' => $suppliers,
                'statusTypes' => $statusTypes,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::error("SuppliersController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar formulario para crear proveedor
     */
    public function create()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $statusTypes = $this->supplierModel->getStatusTypes();

            echo $this->viewRenderer->render('suppliers/create', [
                'user' => $currentUser,
                'title' => AppConstants::UI_NEW_SUPPLIER,
                'subtitle' => 'Crear nuevo proveedor',
                'supplier' => null,
                'statusTypes' => $statusTypes,
                'action' => 'create'
            ]);
        } catch (Exception $e) {
            Logger::error("SuppliersController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Guardar nuevo proveedor
     */
    public function store()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $errors = $this->validateSupplierData($_POST);
            if (!empty($errors)) {
                $statusTypes = $this->supplierModel->getStatusTypes();

                echo $this->viewRenderer->render('suppliers/create', [
                    'user' => $currentUser,
                    'title' => AppConstants::UI_NEW_SUPPLIER,
                    'subtitle' => 'Crear nuevo proveedor',
                    'supplier' => $_POST,
                    'statusTypes' => $statusTypes,
                    'action' => 'create',
                    'errors' => $errors
                ]);
                return;
            }

            $supplierId = $this->supplierModel->create($_POST);

            $this->redirectWithSuccess(AppConstants::ROUTE_SUPPLIERS, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("SuppliersController::store: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_SAVE_SUPPLIER . ': ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para editar proveedor
     */
    public function edit($id)
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $supplier = $this->supplierModel->find((int)$id);
            if (!$supplier) {
                http_response_code(404);
                echo $this->renderError(AppConstants::ERROR_SUPPLIER_NOT_FOUND);
                return;
            }

            $statusTypes = $this->supplierModel->getStatusTypes();

            echo $this->viewRenderer->render('suppliers/edit', [
                'user' => $currentUser,
                'title' => AppConstants::UI_EDIT_SUPPLIER,
                'subtitle' => 'Editando: ' . $supplier['razon_social'],
                'supplier_id' => $id,
                'supplier' => $supplier,
                'statusTypes' => $statusTypes,
                'action' => 'edit'
            ]);
        } catch (Exception $e) {
            Logger::error("SuppliersController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Actualizar proveedor
     */
    public function update()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo $this->renderError(AppConstants::ERROR_SUPPLIER_ID_REQUIRED);
                return;
            }

            $errors = $this->validateSupplierData($_POST, $id);
            if (!empty($errors)) {
                $supplier = $this->supplierModel->find($id);
                $statusTypes = $this->supplierModel->getStatusTypes();

                echo $this->viewRenderer->render('suppliers/edit', [
                    'user' => $currentUser,
                    'title' => AppConstants::UI_EDIT_SUPPLIER,
                    'subtitle' => 'Editando: ' . ($supplier['razon_social'] ?? ''),
                    'supplier' => array_merge($supplier ?? [], $_POST),
                    'statusTypes' => $statusTypes,
                    'action' => 'edit',
                    'errors' => $errors
                ]);
                return;
            }

            $success = $this->supplierModel->update($id, $_POST);
            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_SUPPLIERS, AppConstants::SUCCESS_UPDATED);
            } else {
                throw new Exception('No se pudo actualizar el proveedor');
            }
        } catch (Exception $e) {
            Logger::error("SuppliersController::update: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_UPDATE_SUPPLIER . ': ' . $e->getMessage());
        }
    }

    /**
     * Eliminar proveedor
     */
    public function delete()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo $this->renderError(AppConstants::ERROR_SUPPLIER_ID_REQUIRED);
                return;
            }

            $success = $this->supplierModel->delete($id);
            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_SUPPLIERS, AppConstants::SUCCESS_DELETED);
            } else {
                throw new Exception('No se pudo eliminar el proveedor');
            }
        } catch (Exception $e) {
            Logger::error("SuppliersController::delete: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_SUPPLIERS, $e->getMessage());
        }
    }

    /**
     * Mostrar/editar proveedor individual
     */
    public function show($id = null)
    {
        if ($id) {
            $this->edit($id);
        } else {
            $this->create();
        }
    }

    private function validateSupplierData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty($data['razon_social'])) {
            $errors[] = 'La razon social es requerida';
        } elseif (strlen($data['razon_social']) > 150) {
            $errors[] = 'La razon social no puede exceder 150 caracteres';
        }

        if (!empty($data['rut'])) {
            if (!$this->supplierModel->validateRut($data['rut'])) {
                $errors[] = 'El formato del RUT es invalido';
            } elseif ($this->supplierModel->rutExists($data['rut'], $excludeId)) {
                $errors[] = 'El RUT ya esta registrado para otro proveedor';
            }

            if (strlen($data['rut']) > 20) {
                $errors[] = 'El RUT no puede exceder 20 caracteres';
            }
        }

        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'El formato del email es invalido';
            } elseif (strlen($data['email']) > 150) {
                $errors[] = 'El email no puede exceder 150 caracteres';
            }
        }

        if (!empty($data['direccion']) && strlen($data['direccion']) > 255) {
            $errors[] = 'La direccion no puede exceder 255 caracteres';
        }

        if (!empty($data['telefono']) && strlen($data['telefono']) > 20) {
            $errors[] = 'El telefono no puede exceder 20 caracteres';
        }

        if (!empty($data['fecha_inicio_contrato']) && !$this->isValidDate($data['fecha_inicio_contrato'])) {
            $errors[] = 'La fecha de inicio de contrato no es valida';
        }

        if (!empty($data['fecha_facturacion']) && !$this->isValidDate($data['fecha_facturacion'])) {
            $errors[] = 'La fecha de facturacion no es valida';
        }

        if (!empty($data['fecha_termino_contrato']) && !$this->isValidDate($data['fecha_termino_contrato'])) {
            $errors[] = 'La fecha de termino de contrato no es valida';
        }

        if (!empty($data['fecha_inicio_contrato']) && !empty($data['fecha_termino_contrato'])) {
            if (strtotime($data['fecha_termino_contrato']) <= strtotime($data['fecha_inicio_contrato'])) {
                $errors[] = 'La fecha de termino debe ser posterior a la fecha de inicio';
            }
        }

        return $errors;
    }
}

