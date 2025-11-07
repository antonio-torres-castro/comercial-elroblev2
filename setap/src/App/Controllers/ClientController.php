<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\Persona;
use App\Services\PermissionService;
use App\Services\ClientValidationService;
use App\Services\CounterpartieService;
use App\Core\ViewRenderer;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;
use Exception;

class ClientController extends BaseController
{
    private $clientModel;
    private $personaModel;
    private $permissionService;
    private $clientValidationService;
    private $counterpartieService;
    private $viewRenderer;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        $this->clientModel = new Client();
        $this->personaModel = new Persona();
        $this->permissionService = new PermissionService();
        $this->clientValidationService = new ClientValidationService();
        $this->counterpartieService = new CounterpartieService();
        $this->viewRenderer = new ViewRenderer();
    }

    /**
     * Lista de clientes
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de clientes
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_clients')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Obtener filtros de búsqueda
            $filters = [
                'rut' => $_GET['rut'] ?? '',
                'razon_social' => $_GET['razon_social'] ?? '',
                'estado_tipo_id' => $_GET['estado_tipo_id'] ?? ''
            ];

            // Obtener clientes
            $clients = $this->clientModel->getAll($filters);
            $statusTypes = $this->clientModel->getStatusTypes();

            // Usar ViewRenderer para renderizar la vista
            echo $this->viewRenderer->render('clients/list', [
                'user' => $currentUser,
                'title' => AppConstants::UI_CLIENT_MANAGEMENT,
                'subtitle' => 'Lista de todos los clientes',
                'clients' => $clients,
                'statusTypes' => $statusTypes,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::error("ClientController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar formulario para crear cliente
     */
    public function create()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $statusTypes = $this->clientModel->getStatusTypes();

            // Usar ViewRenderer para renderizar la vista
            echo $this->viewRenderer->render('clients/create', [
                'user' => $currentUser,
                'title' => AppConstants::UI_NEW_CLIENT,
                'subtitle' => 'Crear nuevo cliente',
                'client' => null,
                'statusTypes' => $statusTypes,
                'action' => 'create'
            ]);
        } catch (Exception $e) {
            Logger::error("ClientController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Guardar nuevo cliente
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
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            // Validar datos usando el servicio de validación
            $errors = $this->clientValidationService->validateClientData($_POST);
            if (!empty($errors)) {
                $statusTypes = $this->clientModel->getStatusTypes();

                // Usar ViewRenderer para renderizar la vista con errores
                echo $this->viewRenderer->render('clients/create', [
                    'user' => $currentUser,
                    'title' => AppConstants::UI_NEW_CLIENT,
                    'subtitle' => 'Crear nuevo cliente',
                    'client' => $_POST,
                    'statusTypes' => $statusTypes,
                    'action' => 'create',
                    'errors' => $errors
                ]);
                return;
            }

            // Crear cliente
            $clientId = $this->clientModel->create($_POST);

            // Redireccionar con mensaje de éxito
            $this->redirectWithSuccess(AppConstants::ROUTE_CLIENTS, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ClientController::store: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_SAVE_CLIENT . ': ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para editar cliente
     */
    public function edit($id)
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            // Obtener cliente
            $client = $this->clientModel->find((int)$id);
            if (!$client) {
                http_response_code(404);
                echo $this->renderError(AppConstants::ERROR_CLIENT_NOT_FOUND);
                return;
            }

            $statusTypes = $this->clientModel->getStatusTypes();
            $counterparties = $this->clientModel->getCounterparties((int)$id);

            // Usar ViewRenderer para renderizar la vista
            echo $this->viewRenderer->render('clients/edit', [
                'user' => $currentUser,
                'title' => AppConstants::UI_EDIT_CLIENT,
                'subtitle' => 'Editando: ' . $client['razon_social'],
                'client_id' => $id,  // Añadir para consistencia
                'client' => $client,
                'statusTypes' => $statusTypes,
                'counterparties' => $counterparties,
                'action' => 'edit'
            ]);
        } catch (Exception $e) {
            Logger::error("ClientController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Actualizar cliente
     */
    public function update()
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
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de cliente requerido');
                return;
            }

            // Validar datos usando el servicio de validación
            $errors = $this->clientValidationService->validateClientData($_POST, $id);
            if (!empty($errors)) {
                $client = $this->clientModel->find($id);
                $statusTypes = $this->clientModel->getStatusTypes();
                $counterparties = $this->clientModel->getCounterparties($id);

                // Usar ViewRenderer para renderizar la vista con errores
                echo $this->viewRenderer->render('clients/edit', [
                    'user' => $currentUser,
                    'title' => AppConstants::UI_EDIT_CLIENT,
                    'subtitle' => 'Editando: ' . $client['razon_social'],
                    'client' => array_merge($client, $_POST),
                    'statusTypes' => $statusTypes,
                    'counterparties' => $counterparties,
                    'action' => 'edit',
                    'errors' => $errors
                ]);
                return;
            }

            // Actualizar cliente
            $success = $this->clientModel->update($id, $_POST);
            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_CLIENTS, AppConstants::SUCCESS_UPDATED);
            } else {
                throw new Exception('No se pudo actualizar el cliente');
            }
        } catch (Exception $e) {
            Logger::error("ClientController::update: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_UPDATE_CLIENT . ': ' . $e->getMessage());
        }
    }

    /**
     * Eliminar cliente
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
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de cliente requerido');
                return;
            }

            // Eliminar cliente
            $success = $this->clientModel->delete($id);
            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_CLIENTS, AppConstants::SUCCESS_DELETED);
            } else {
                throw new Exception('No se pudo eliminar el cliente');
            }
        } catch (Exception $e) {
            Logger::error("ClientController::delete: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_CLIENTS, $e->getMessage());
        }
    }

    /**
     * Mostrar/editar cliente individual (compatibilidad con rutas existentes)
     */
    public function show($id = null)
    {
        if ($id) {
            $this->edit($id);
        } else {
            $this->create();
        }
    }

    /**
     * Lista de contrapartes de clientes
     */
    public function counterparties()
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de contrapartes
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client_counterparties')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $uti = $currentUser['usuario_tipo_id'];

            // Obtener filtros de búsqueda
            $filters = [
                'cliente_id' => $_GET['cliente_id'] ?? '',
                'persona_nombre' => $_GET['persona_nombre'] ?? '',
                'cargo' => $_GET['cargo'] ?? '',
                'estado_tipo_id' => $_GET['estado_tipo_id'] ?? ''
            ];

            if ($uti == 1 || $uti == 2) {
                $_GET['show_btn_nuevo'] = true;
                $_GET['show_col_acciones'] = true;
            } else {
                $_GET['show_btn_nuevo'] = false;
                $_GET['show_col_acciones'] = false;
            }

            // Obtener contrapartes usando el servicio y datos necesarios para filtros
            $counterparties = $this->counterpartieService->getAllCounterparties($filters);
            $statusTypes = $this->clientModel->getStatusTypes();
            $clients = $this->clientModel->getAll(); // Para el filtro de clientes

            // Usar ViewRenderer para renderizar la vista
            echo $this->viewRenderer->render('client-counterparties/list', [
                'user' => $currentUser,
                'title' => AppConstants::UI_CLIENT_COUNTERPARTIES,
                'subtitle' => 'Lista de todas las contrapartes',
                'counterparties' => $counterparties,
                'statusTypes' => $statusTypes,
                'clients' => $clients,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::error("ClientController::counterparties: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Mostrar/editar contraparte individual
     */
    public function counterpartie($id = null)
    {
        try {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar permisos para gestión de contraparte individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client_counterpartie')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $counterpartie = null;
            $action = 'create';

            // Si hay ID, estamos editando
            if ($id) {
                $counterpartie = $this->counterpartieService->findCounterpartie((int)$id);
                if (!$counterpartie) {
                    http_response_code(404);
                    echo $this->renderError('Contraparte no encontrada');
                    return;
                }
                $action = 'edit';
            }

            // Obtener datos necesarios para el formulario usando el servicio
            $formData = $this->counterpartieService->getFormData();

            // Usar ViewRenderer para renderizar la vista
            echo $this->viewRenderer->render('client-counterparties/form', [
                'user' => $currentUser,
                'title' => $id ? AppConstants::UI_EDIT_COUNTERPARTY : AppConstants::UI_NEW_COUNTERPARTY,
                'subtitle' => $id ? AppConstants::UI_EDITING_COUNTERPARTY . " #$id" : 'Crear nueva contraparte',
                'counterpartie' => $counterpartie,
                'counterpartie_id' => $id,
                'clients' => $formData['clients'],
                'personas' => $formData['personas'],
                'statusTypes' => $formData['statusTypes'],
                'action' => $action
            ]);
        } catch (Exception $e) {
            Logger::error("ClientController::counterpartie: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    /**
     * Guardar nueva contraparte
     */
    public function storeCounterpartie()
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
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            // Validar datos de contraparte usando el servicio de validación
            $errors = $this->clientValidationService->validateCounterpartieData($_POST);
            if (!empty($errors)) {
                // Obtener datos necesarios para el formulario usando el servicio
                $formData = $this->counterpartieService->getFormData();

                // Usar ViewRenderer para renderizar la vista con errores
                echo $this->viewRenderer->render('client-counterparties/form', [
                    'user' => $currentUser,
                    'title' => 'Nueva Contraparte',
                    'subtitle' => 'Crear nueva contraparte',
                    'counterpartie' => null,
                    'counterpartie_id' => null,
                    'clients' => $formData['clients'],
                    'personas' => $formData['personas'],
                    'statusTypes' => $formData['statusTypes'],
                    'action' => 'create',
                    'errors' => $errors
                ]);
                return;
            }

            // Crear contraparte usando el servicio
            $counterpartieId = $this->counterpartieService->createCounterpartie($_POST);

            // Redireccionar con mensaje de éxito
            $this->redirectWithSuccess(AppConstants::ROUTE_CLIENT_COUNTERPARTIES, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ClientController::storeCounterpartie: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error al guardar la contraparte: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar contraparte
     */
    public function updateCounterpartie()
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
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de contraparte requerido');
                return;
            }

            // Validar datos de contraparte usando el servicio de validación
            $errors = $this->clientValidationService->validateCounterpartieData($_POST, $id);
            if (!empty($errors)) {
                // Obtener datos necesarios para el formulario usando los servicios
                $counterpartie = $this->counterpartieService->findCounterpartie($id);
                $formData = $this->counterpartieService->getFormData();

                // Usar ViewRenderer para renderizar la vista con errores
                echo $this->viewRenderer->render('client-counterparties/form', [
                    'user' => $currentUser,
                    'title' => 'Editar Contraparte',
                    'subtitle' => 'Editando contraparte #' . $id,
                    'counterpartie' => array_merge($counterpartie, $_POST),
                    'counterpartie_id' => $id,
                    'clients' => $formData['clients'],
                    'personas' => $formData['personas'],
                    'statusTypes' => $formData['statusTypes'],
                    'action' => 'edit',
                    'errors' => $errors
                ]);
                return;
            }

            // Actualizar contraparte usando el servicio
            $success = $this->counterpartieService->updateCounterpartie($id, $_POST);
            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_CLIENT_COUNTERPARTIES, AppConstants::SUCCESS_UPDATED);
            }
        } catch (Exception $e) {
            Logger::error("ClientController::updateCounterpartie: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error al actualizar la contraparte: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar contraparte
     */
    public function deleteCounterpartie()
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
                echo $this->renderError(AppConstants::ERROR_METHOD_NOT_ALLOWED);
                return;
            }

            // Verificar token CSRF
            if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
                return;
            }

            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de contraparte requerido');
                return;
            }

            // Eliminar contraparte usando el servicio
            $success = $this->counterpartieService->deleteCounterpartie($id);
            if ($success) {
                $this->redirectWithSuccess(AppConstants::ROUTE_CLIENT_COUNTERPARTIES, AppConstants::SUCCESS_DELETED);
            }
        } catch (Exception $e) {
            Logger::error("ClientController::deleteCounterpartie: " . $e->getMessage());
            $this->redirectWithError(AppConstants::ROUTE_CLIENT_COUNTERPARTIES, $e->getMessage());
        }
    }
}
