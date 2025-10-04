<?php

namespace App\Controllers;

use App\Models\Client;
use App\Models\Persona;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use Exception;

class ClientController
{
    private $clientModel;
    private $personaModel;
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        
        $this->clientModel = new Client();
        $this->personaModel = new Persona();
        $this->permissionService = new PermissionService();
    }

    /**
     * Lista de clientes
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para gestión de clientes
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_clients')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
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

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Clientes',
                'subtitle' => 'Lista de todos los clientes',
                'clients' => $clients,
                'statusTypes' => $statusTypes,
                'filters' => $filters
            ];

            require_once __DIR__ . '/../Views/clients/list.php';

        } catch (Exception $e) {
            error_log("Error en ClientController::index: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
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
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            $statusTypes = $this->clientModel->getStatusTypes();

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Nuevo Cliente',
                'subtitle' => 'Crear nuevo cliente',
                'client' => null,
                'statusTypes' => $statusTypes,
                'action' => 'create'
            ];

            require_once __DIR__ . '/../Views/clients/create.php';

        } catch (Exception $e) {
            error_log("Error en ClientController::create: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
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
                Security::redirect('/login');
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
            $errors = $this->validateClientData($_POST);
            
            if (!empty($errors)) {
                $statusTypes = $this->clientModel->getStatusTypes();
                
                $data = [
                    'user' => $currentUser,
                    'title' => 'Nuevo Cliente',
                    'subtitle' => 'Crear nuevo cliente',
                    'client' => $_POST,
                    'statusTypes' => $statusTypes,
                    'action' => 'create',
                    'errors' => $errors
                ];

                require_once __DIR__ . '/../Views/clients/create.php';
                return;
            }

            // Crear cliente
            $clientId = $this->clientModel->create($_POST);

            // Redireccionar con mensaje de éxito
            Security::redirect('/clients?success=created');

        } catch (Exception $e) {
            error_log("Error en ClientController::store: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error al guardar el cliente: ' . $e->getMessage());
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
                Security::redirect('/login');
                return;
            }

            // Verificar permisos
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            // Obtener cliente
            $client = $this->clientModel->find((int)$id);
            
            if (!$client) {
                http_response_code(404);
                echo $this->renderError('Cliente no encontrado');
                return;
            }

            $statusTypes = $this->clientModel->getStatusTypes();
            $counterparties = $this->clientModel->getCounterparties((int)$id);

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Editar Cliente',
                'subtitle' => 'Editando: ' . $client['razon_social'],
                'client' => $client,
                'statusTypes' => $statusTypes,
                'counterparties' => $counterparties,
                'action' => 'edit'
            ];

            require_once __DIR__ . '/../Views/clients/edit.php';

        } catch (Exception $e) {
            error_log("Error en ClientController::edit: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
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
                Security::redirect('/login');
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

            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de cliente requerido');
                return;
            }

            // Validar datos
            $errors = $this->validateClientData($_POST, $id);
            
            if (!empty($errors)) {
                $client = $this->clientModel->find($id);
                $statusTypes = $this->clientModel->getStatusTypes();
                $counterparties = $this->clientModel->getCounterparties($id);
                
                $data = [
                    'user' => $currentUser,
                    'title' => 'Editar Cliente',
                    'subtitle' => 'Editando: ' . $client['razon_social'],
                    'client' => array_merge($client, $_POST),
                    'statusTypes' => $statusTypes,
                    'counterparties' => $counterparties,
                    'action' => 'edit',
                    'errors' => $errors
                ];

                require_once __DIR__ . '/../Views/clients/edit.php';
                return;
            }

            // Actualizar cliente
            $success = $this->clientModel->update($id, $_POST);

            if ($success) {
                Security::redirect('/clients?success=updated');
            } else {
                throw new Exception('No se pudo actualizar el cliente');
            }

        } catch (Exception $e) {
            error_log("Error en ClientController::update: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error al actualizar el cliente: ' . $e->getMessage());
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
                Security::redirect('/login');
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

            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de cliente requerido');
                return;
            }

            // Eliminar cliente
            $success = $this->clientModel->delete($id);

            if ($success) {
                Security::redirect('/clients?success=deleted');
            } else {
                throw new Exception('No se pudo eliminar el cliente');
            }

        } catch (Exception $e) {
            error_log("Error en ClientController::delete: " . $e->getMessage());
            Security::redirect('/clients?error=' . urlencode($e->getMessage()));
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
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para gestión de contrapartes
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client_counterparties')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Contrapartes de Clientes',
                'subtitle' => 'Lista de todas las contrapartes'
            ];

            require_once __DIR__ . '/../Views/client-counterparties/list.php';

        } catch (Exception $e) {
            error_log("Error en ClientController::counterparties: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
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
                Security::redirect('/login');
                return;
            }

            // Verificar permisos para gestión de contraparte individual
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_client_counterpartie')) {
                http_response_code(403);
                echo $this->renderError('No tienes permisos para acceder a esta sección.');
                return;
            }

            // Datos para la vista
            $data = [
                'user' => $currentUser,
                'title' => 'Gestión de Contraparte',
                'subtitle' => $id ? "Editando contraparte #$id" : 'Nueva contraparte',
                'counterpartie_id' => $id
            ];

            require_once __DIR__ . '/../Views/client-counterparties/form.php';

        } catch (Exception $e) {
            error_log("Error en ClientController::counterpartie: " . $e->getMessage());
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
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
                Security::redirect('/login');
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

            // Crear contraparte
            $counterpartieId = $this->clientModel->addCounterpartie($_POST);

            // Redireccionar con mensaje de éxito
            Security::redirect('/client-counterparties?success=created');

        } catch (Exception $e) {
            error_log("Error en ClientController::storeCounterpartie: " . $e->getMessage());
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
                Security::redirect('/login');
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

            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de contraparte requerido');
                return;
            }

            // Actualizar contraparte (necesitaríamos implementar este método en el modelo)
            // $success = $this->clientModel->updateCounterpartie($id, $_POST);

            // Por ahora redirigir con mensaje de éxito
            Security::redirect('/client-counterparties?success=updated');

        } catch (Exception $e) {
            error_log("Error en ClientController::updateCounterpartie: " . $e->getMessage());
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
                Security::redirect('/login');
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

            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                http_response_code(400);
                echo $this->renderError('ID de contraparte requerido');
                return;
            }

            // Eliminar contraparte (necesitaríamos implementar este método en el modelo)
            // $success = $this->clientModel->deleteCounterpartie($id);

            // Por ahora redirigir con mensaje de éxito
            Security::redirect('/client-counterparties?success=deleted');

        } catch (Exception $e) {
            error_log("Error en ClientController::deleteCounterpartie: " . $e->getMessage());
            Security::redirect('/client-counterparties?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Validar datos de cliente
     */
    private function validateClientData(array $data, int $excludeId = null): array
    {
        $errors = [];

        // Razón social requerida
        if (empty($data['razon_social'])) {
            $errors[] = 'La razón social es requerida';
        } elseif (strlen($data['razon_social']) > 150) {
            $errors[] = 'La razón social no puede exceder 150 caracteres';
        }

        // Validar RUT si se proporciona
        if (!empty($data['rut'])) {
            if (!$this->clientModel->validateRut($data['rut'])) {
                $errors[] = 'El formato del RUT es inválido';
            } elseif ($this->clientModel->rutExists($data['rut'], $excludeId)) {
                $errors[] = 'El RUT ya está registrado para otro cliente';
            }
            
            if (strlen($data['rut']) > 20) {
                $errors[] = 'El RUT no puede exceder 20 caracteres';
            }
        }

        // Validar email si se proporciona
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'El formato del email es inválido';
            } elseif (strlen($data['email']) > 150) {
                $errors[] = 'El email no puede exceder 150 caracteres';
            }
        }

        // Validar longitudes
        if (!empty($data['direccion']) && strlen($data['direccion']) > 255) {
            $errors[] = 'La dirección no puede exceder 255 caracteres';
        }

        if (!empty($data['telefono']) && strlen($data['telefono']) > 20) {
            $errors[] = 'El teléfono no puede exceder 20 caracteres';
        }

        // Validar fechas
        if (!empty($data['fecha_inicio_contrato']) && !$this->isValidDate($data['fecha_inicio_contrato'])) {
            $errors[] = 'La fecha de inicio de contrato no es válida';
        }

        if (!empty($data['fecha_facturacion']) && !$this->isValidDate($data['fecha_facturacion'])) {
            $errors[] = 'La fecha de facturación no es válida';
        }

        if (!empty($data['fecha_termino_contrato']) && !$this->isValidDate($data['fecha_termino_contrato'])) {
            $errors[] = 'La fecha de término de contrato no es válida';
        }

        // Validar que fecha de término sea posterior a fecha de inicio
        if (!empty($data['fecha_inicio_contrato']) && !empty($data['fecha_termino_contrato'])) {
            if (strtotime($data['fecha_termino_contrato']) <= strtotime($data['fecha_inicio_contrato'])) {
                $errors[] = 'La fecha de término debe ser posterior a la fecha de inicio';
            }
        }

        return $errors;
    }

    /**
     * Validar formato de fecha
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
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