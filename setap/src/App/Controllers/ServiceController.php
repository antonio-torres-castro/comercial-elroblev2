<?php

namespace App\Controllers;

use App\Core\ViewRenderer;
use App\Helpers\Logger;
use App\Helpers\Security;
use App\Middlewares\AuthMiddleware;
use App\Models\Process;
use App\Models\Service;
use App\Models\Suppliers;
use App\Services\PermissionService;
use App\Constants\AppConstants;
use Exception;

class ServiceController extends BaseController
{
    private Service $serviceModel;
    private Suppliers $supplierModel;
    private Process $processModel;
    private PermissionService $permissionService;
    private ViewRenderer $viewRenderer;

    public function __construct()
    {
        (new AuthMiddleware())->handle();
        $this->serviceModel = new Service();
        $this->supplierModel = new Suppliers();
        $this->processModel = new Process();
        $this->permissionService = new PermissionService();
        $this->viewRenderer = new ViewRenderer();
    }

    public function index(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canView($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $filters = [
                'proveedor_id' => $_GET['proveedor_id'] ?? ($user['id'] == 1 ? '' : ($user['proveedor_id'] ?? '')),
                'cliente_id' => $_GET['cliente_id'] ?? '',
                'estado_operacional_id' => $_GET['estado_operacional_id'] ?? ''
            ];

            echo $this->viewRenderer->render('services/list', [
                'user' => $user,
                'title' => 'Servicios Planificados',
                'services' => $this->serviceModel->getPlannedServices($filters, $user),
                'suppliers' => $this->getSuppliersForUser($user),
                'clients' => $this->serviceModel->getClientsForService($user['id'] == 1 ? null : (int)($user['proveedor_id'] ?? 0)),
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::error("ServiceController::index: " . $e->getMessage());
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function catalog(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canAdmin($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $filters = [
                'proveedor_id' => $_GET['proveedor_id'] ?? ($user['id'] == 1 ? '' : ($user['proveedor_id'] ?? '')),
                'nombre' => $_GET['nombre'] ?? '',
                'activo' => $_GET['activo'] ?? ''
            ];

            echo $this->viewRenderer->render('services/catalog', [
                'user' => $user,
                'title' => 'Catalogo de Servicios',
                'services' => $this->serviceModel->getCatalog($filters),
                'suppliers' => $this->getSuppliersForUser($user),
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            Logger::error("ServiceController::catalog: " . $e->getMessage());
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function create(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canAdmin($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            $providerId = $user['id'] == 1 ? null : (int)($user['proveedor_id'] ?? 0);
            echo $this->viewRenderer->render('services/create', $this->formData($user, null, $providerId));
        } catch (Exception $e) {
            Logger::error("ServiceController::create: " . $e->getMessage());
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function store(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canAdmin($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            $this->ensurePost();
            $this->ensureCsrf();

            $errors = $this->validateCatalogData($_POST);
            if ($errors) {
                echo $this->viewRenderer->render('services/create', array_merge($this->formData($user, $_POST), ['errors' => $errors]));
                return;
            }

            $this->serviceModel->createServiceWithVersion($_POST);
            $this->redirectWithSuccess(AppConstants::ROUTE_SERVICES_CATALOG, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ServiceController::store: " . $e->getMessage());
            echo $this->renderError($e->getMessage());
        }
    }

    public function version(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canAdmin($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            $this->ensurePost();
            $this->ensureCsrf();

            $serviceId = (int)($_POST['servicio_id'] ?? 0);
            if (!$serviceId) {
                throw new Exception('Servicio requerido');
            }
            $this->serviceModel->createNewVersion($serviceId, $_POST);
            $this->redirectWithSuccess(AppConstants::ROUTE_SERVICES_CATALOG, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ServiceController::version: " . $e->getMessage());
            echo $this->renderError($e->getMessage());
        }
    }

    public function plan(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canAdmin($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }

            $providerId = $user['id'] == 1 ? ($_GET['proveedor_id'] ?? null) : ($user['proveedor_id'] ?? null);
            echo $this->viewRenderer->render('services/plan', [
                'user' => $user,
                'title' => 'Planificar Servicio',
                'suppliers' => $this->getSuppliersForUser($user),
                'versions' => $this->serviceModel->getServiceVersionsForSelect($providerId ? (int)$providerId : null),
                'clients' => $this->serviceModel->getClientsForService($providerId ? (int)$providerId : null),
                'projects' => $providerId ? $this->serviceModel->getOperationalProjects((int)$providerId) : []
            ]);
        } catch (Exception $e) {
            Logger::error("ServiceController::plan: " . $e->getMessage());
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function generate(): void
    {
        try {
            $user = $this->requireUser();
            $this->ensurePost();
            $this->ensureCsrf();

            $errors = $this->validatePlanData($_POST);
            if ($errors) {
                echo $this->renderError(implode('<br>', $errors));
                return;
            }

            $plannedId = $this->serviceModel->planService($_POST, $user);
            $this->redirectWithSuccess(AppConstants::ROUTE_SERVICES . '/show/' . $plannedId, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ServiceController::generate: " . $e->getMessage());
            echo $this->renderError($e->getMessage());
        }
    }

    public function show(?int $id = null): void
    {
        try {
            $user = $this->requireUser();
            if (!$id) {
                $this->redirectToRoute(AppConstants::ROUTE_SERVICES);
                return;
            }

            $service = $this->serviceModel->findPlannedService($id, $user);
            if (!$service) {
                echo $this->renderError('Servicio planificado no encontrado');
                return;
            }

            echo $this->viewRenderer->render('services/show', [
                'user' => $user,
                'title' => 'Tracking de Servicio',
                'service' => $service,
                'tracking' => $this->serviceModel->getTrackingDetail($id),
                'canAdmin' => $this->canAdmin($user)
            ]);
        } catch (Exception $e) {
            Logger::error("ServiceController::show: " . $e->getMessage());
            echo $this->renderError(AppConstants::ERROR_INTERNAL_SERVER);
        }
    }

    public function suspend(): void
    {
        $this->changeState(3, 'suspension');
    }

    public function finishEarly(): void
    {
        $this->changeState(4, 'termino_anticipado');
    }

    public function replan(): void
    {
        try {
            $user = $this->requireUser();
            $this->ensurePost();
            $this->ensureCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $fromDate = $_POST['desde_fecha'] ?? '';
            $days = (int)($_POST['dias_desfase'] ?? 0);
            if (!$id || !$this->isValidDate($fromDate) || $days === 0) {
                throw new Exception('Datos de replanificacion invalidos');
            }
            $this->serviceModel->replan($id, $fromDate, $days, (int)$user['id']);
            $this->redirectWithSuccess(AppConstants::ROUTE_SERVICES . '/show/' . $id, AppConstants::SUCCESS_UPDATED);
        } catch (Exception $e) {
            Logger::error("ServiceController::replan: " . $e->getMessage());
            echo $this->renderError($e->getMessage());
        }
    }

    public function createCategory(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canAdmin($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            $this->ensurePost();
            $this->ensureCsrf();
            if (empty($_POST['nombre'])) {
                throw new Exception('Nombre de categoria requerido');
            }
            $this->serviceModel->createCategory($_POST);
            $this->redirectWithSuccess(AppConstants::ROUTE_SERVICES_CREATE, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ServiceController::createCategory: " . $e->getMessage());
            echo $this->renderError($e->getMessage());
        }
    }

    public function createType(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canAdmin($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            $this->ensurePost();
            $this->ensureCsrf();
            if (empty($_POST['proveedor_id']) || empty($_POST['nombre'])) {
                throw new Exception('Proveedor y nombre del tipo son requeridos');
            }
            $this->serviceModel->createType($_POST);
            $this->redirectWithSuccess(AppConstants::ROUTE_SERVICES_CREATE, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ServiceController::createType: " . $e->getMessage());
            echo $this->renderError($e->getMessage());
        }
    }

    public function createClient(): void
    {
        try {
            $user = $this->requireUser();
            if (!$this->canAdmin($user)) {
                echo $this->renderError(AppConstants::ERROR_NO_PERMISSIONS);
                return;
            }
            $this->ensurePost();
            $this->ensureCsrf();
            if (empty($_POST['proveedor_id']) || empty($_POST['razon_social'])) {
                throw new Exception('Proveedor y razon social son requeridos');
            }
            $this->serviceModel->createServiceClient($_POST);
            $this->redirectWithSuccess(AppConstants::ROUTE_SERVICES_PLAN, AppConstants::SUCCESS_CREATED);
        } catch (Exception $e) {
            Logger::error("ServiceController::createClient: " . $e->getMessage());
            echo $this->renderError($e->getMessage());
        }
    }

    public function providerData(): void
    {
        try {
            $user = $this->requireUser();
            $providerId = (int)($_GET['proveedor_id'] ?? 0);
            if ($user['id'] != 1 && $providerId !== (int)($user['proveedor_id'] ?? 0)) {
                $this->jsonForbidden();
            }
            $this->jsonSuccess('Datos cargados', [
                'data' => [
                    'versions' => $this->serviceModel->getServiceVersionsForSelect($providerId),
                    'clients' => $this->serviceModel->getClientsForService($providerId),
                    'projects' => $this->serviceModel->getOperationalProjects($providerId),
                    'types' => $this->serviceModel->getTypes(['proveedor_id' => $providerId]),
                    'processes' => $this->processModel->getByProvider($providerId)
                ]
            ]);
        } catch (Exception $e) {
            Logger::error("ServiceController::providerData: " . $e->getMessage());
            $this->jsonInternalError($e->getMessage());
        }
    }

    private function changeState(int $state, string $action): void
    {
        try {
            $user = $this->requireUser();
            $this->ensurePost();
            $this->ensureCsrf();
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new Exception('Servicio planificado requerido');
            }
            $this->serviceModel->changePlannedState($id, $state, (int)$user['id'], $action);
            $this->redirectWithSuccess(AppConstants::ROUTE_SERVICES . '/show/' . $id, AppConstants::SUCCESS_UPDATED);
        } catch (Exception $e) {
            Logger::error("ServiceController::changeState: " . $e->getMessage());
            echo $this->renderError($e->getMessage());
        }
    }

    private function formData(array $user, ?array $old = null, ?int $providerId = null): array
    {
        return [
            'user' => $user,
            'title' => 'Nuevo Servicio',
            'service' => $old,
            'suppliers' => $this->getSuppliersForUser($user),
            'categories' => $this->serviceModel->getCategories(),
            'types' => $this->serviceModel->getTypes(['proveedor_id' => $providerId]),
            'processes' => $providerId ? $this->processModel->getByProvider($providerId) : []
        ];
    }

    private function validateCatalogData(array $data): array
    {
        $errors = [];
        foreach (['proveedor_id', 'servicio_tipo_id', 'nombre', 'tiempo_estimado_dias'] as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es requerido";
            }
        }
        return $errors;
    }

    private function validatePlanData(array $data): array
    {
        $errors = [];
        foreach (['cliente_id', 'servicio_version_id', 'fecha_inicio'] as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es requerido";
            }
        }
        if (empty($data['proyecto_ids']) || !is_array($data['proyecto_ids'])) {
            $errors[] = 'Debe seleccionar proyectos operacionales';
        }
        return $errors;
    }

    private function requireUser(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            $this->redirectToLogin();
            exit;
        }
        return $user;
    }

    private function ensurePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception(AppConstants::ERROR_METHOD_NOT_ALLOWED);
        }
    }

    private function ensureCsrf(): void
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception(AppConstants::ERROR_INVALID_SECURITY_TOKEN);
        }
    }

    private function canView(array $user): bool
    {
        return (int)$user['id'] === 1
            || $this->permissionService->hasMenuAccess((int)$user['id'], 'manage_projects')
            || $this->permissionService->hasMenuAccess((int)$user['id'], 'manage_tasks')
            || !empty($user['cliente_id']);
    }

    private function canAdmin(array $user): bool
    {
        return (int)$user['id'] === 1
            || $this->permissionService->hasMenuAccess((int)$user['id'], 'manage_projects')
            || $this->permissionService->hasMenuAccess((int)$user['id'], 'manage_tasks');
    }

    private function getSuppliersForUser(array $user): array
    {
        if ((int)$user['id'] === 1) {
            return $this->supplierModel->getAll(['estado_tipo_id' => 2]);
        }
        $supplierId = (int)($user['proveedor_id'] ?? 0);
        return $supplierId ? $this->supplierModel->getAll(['proveedor_id' => $supplierId, 'estado_tipo_id' => 2]) : [];
    }
}
