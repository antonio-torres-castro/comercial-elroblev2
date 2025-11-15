<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/App/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Controllers\ProjectController;
use App\Controllers\MenuController;
use App\Controllers\PersonaController;
use App\Controllers\PerfilController;
use App\Controllers\ClientController;
use App\Controllers\TaskController;
use App\Controllers\ReportController;
use App\Controllers\GrupoTipoController;
use App\Controllers\ProyectoFeriadoController;
use App\Controllers\AccessController;
use App\Controllers\PermissionsController;
use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;

// Configurar headers de seguridad básicos
Security::setSecurityHeaders();

// Manejar CORS para desarrollo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obtener la ruta de la solicitud
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Eliminar slash inicial y cualquier segmento no deseado
$route = ltrim($requestUri, '/');

// Si la ruta comienza con 'setap/', eliminarlo
if (strpos($route, 'setap/') === 0) {
    $route = substr($route, strlen('setap/'));
}

// Dividir la ruta para obtener el controlador y la acción
$parts = explode('/', $route);
$controllerName = $parts[0] ?: 'login';
$action = $parts[1] ?? '';
$id = $parts[2] ?? null;

// Rate limiting básico para login
if ($controllerName === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::checkRateLimit('login', 5, 300)) {
        http_response_code(429);
        echo json_encode(['error' => 'Demasiados intentos. Intente más tarde.']);
        exit;
    }
}

// Enrutamiento simplificado
try {
    switch ($controllerName) {
        case 'login':
            $controller = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->login();
            } else {
                $controller->showLoginForm();
            }
            break;

        case 'logout':
            $controller = new AuthController();
            $controller->logout();
            break;

        case 'home':
            // Redirigir a home si está autenticado, sino a login
            if (Security::isAuthenticated()) {
                $controller = new HomeController();
                $controller->index();
            } else {
                Security::redirect(AppConstants::ROUTE_LOGIN);
            }
            break;

        case 'dashboard':
            // Redirigir a la nueva ruta /home para compatibilidad
            header('Location: ' . AppConstants::ROUTE_HOME, true, 301);
            exit;

        case 'users':
            $controller = new UserController();

            switch ($action) {
                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        $controller->create();
                    }
                    break;

                case 'edit':
                    $controller->edit($id);
                    break;

                case 'update':
                    $controller->update();
                    break;

                case 'delete':
                    $controller->delete();
                    break;

                case 'toggle-status':
                    $controller->toggleStatus();
                    break;

                case 'change-password':
                    $controller->changePassword();
                    break;

                case 'search-personas':
                    $controller->searchPersonas();
                    break;

                case 'seek_personas':
                    $controller->seekPersonas();
                    break;

                case 'validate-field':
                    $controller->validateField();
                    break;

                case 'store':
                    $controller->store();
                    break;

                case 'permissions':
                    $controller->permissions();
                    break;

                case '':
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'projects':
            $controller = new ProjectController();

            switch ($action) {
                case 'show':
                    if ($id) {
                        $controller->show((int)$id);
                    } else {
                        $controller->index();
                    }
                    break;

                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        $controller->create();
                    }
                    break;

                case 'edit':
                    $controller->edit();
                    break;

                case 'update':
                    $controller->update();
                    break;

                case 'delete':
                    $controller->delete();
                    break;

                case 'change-status':
                    $controller->changeStatus();
                    break;

                case 'reports':
                    $controller->index();
                    break;

                case '':
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'project':
            $controller = new ProjectController();

            switch ($action) {
                case 'show':
                    if ($id) {
                        $controller->show((int)$id);
                    } else {
                        $controller->show();
                    }
                    break;

                case 'refreshCardTasks':
                    $controller->refreshCardTasks();
                    break;

                // AJAX mantenedor proyecto-usuarios-grupo
                case 'usuarios-grupo-list':
                    $controller->usuariosGrupoList();
                    break;
                case 'usuarios-grupo-add':
                    $controller->usuariosGrupoAdd();
                    break;
                case 'usuarios-grupo-update':
                    $controller->usuariosGrupoUpdate();
                    break;
                case 'usuarios-grupo-delete':
                    $controller->usuariosGrupoDelete();
                    break;

                case 'report':
                    $controller->report();
                    break;

                case '':
                    $controller->create();
                    break;
            }
            break;

        case 'grupo-tipos':
            $controller = new GrupoTipoController();

            switch ($action) {
                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        $controller->create();
                    }
                    break;

                case 'edit':
                    if ($id && is_numeric($id)) {
                        $controller->edit((int)$id);
                    } else {
                        $controller->edit();
                    }
                    break;

                case 'update':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->update();
                    } else {
                        Security::redirect(AppConstants::ROUTE_GRUPO_TIPOS);
                    }
                    break;

                case 'store':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        Security::redirect(AppConstants::ROUTE_GRUPO_TIPOS);
                    }
                    break;

                case '':
                case null:
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'menus':
            $controller = new MenuController();

            switch ($action) {
                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        $controller->create();
                    }
                    break;

                case 'edit':
                    $controller->edit($id);
                    break;

                case 'update':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->update($id);
                    } else {
                        Security::redirect(AppConstants::ROUTE_MENUS);
                    }
                    break;

                case 'store':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        Security::redirect(AppConstants::ROUTE_MENUS);
                    }
                    break;

                case 'delete':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->delete();
                    } else {
                        Security::redirect(AppConstants::ROUTE_MENUS);
                    }
                    break;

                case 'toggle-status':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->toggleStatus();
                    } else {
                        Security::redirect(AppConstants::ROUTE_MENUS);
                    }
                    break;

                case '':
                case null:
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'menu':
            $controller = new MenuController();

            switch ($action) {
                case 'create':
                    $controller->create();
                    break;

                default:
                    if ($action) {
                        $controller->edit($action);
                    } else {
                        $controller->create();
                    }
                    break;
            }
            break;

        case 'personas':
            $controller = new PersonaController();

            switch ($action) {
                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        $controller->create();
                    }
                    break;

                case 'edit':
                    $controller->edit();
                    break;

                case 'update':
                    $controller->update();
                    break;

                case 'delete':
                    $controller->delete();
                    break;

                case 'store':
                    $controller->store();
                    break;

                case '':
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'persona':
            $controller = new PersonaController();

            if ($action) {
                $controller->show((int)$action);
            } else {
                $controller->show();
            }
            break;

        case 'perfil':
            $controller = new PerfilController();

            if ($action === 'edit') {
                $controller->edit();
            } else {
                $controller->index();
            }
            break;

        case 'clients':
            $controller = new ClientController();

            switch ($action) {
                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        $controller->create();
                    }
                    break;

                case 'edit':
                    if ($id) {
                        $controller->edit($id);
                    } else {
                        $controller->index();
                    }
                    break;

                case 'store':
                    $controller->store();
                    break;

                case 'update':
                    $controller->update();
                    break;

                case 'delete':
                    $controller->delete();
                    break;

                default:
                    $controller->index();
                    break;
            }
            break;

        case 'client':
            $controller = new ClientController();

            switch ($action) {
                case 'create':
                    $controller->create();
                    break;

                default:
                    if ($action) {
                        $controller->edit($action);
                    } else {
                        $controller->create();
                    }
                    break;
            }
            break;

        case 'client-counterparties':
            $controller = new ClientController();
            $controller->counterparties();
            break;

        case 'client-counterpartie':
            $controller = new ClientController();

            switch ($action) {
                case 'create':
                    $controller->counterpartie();
                    break;

                case 'edit':
                    if ($id) {
                        $controller->counterpartie((int)$id);
                    } else {
                        $controller->counterpartie();
                    }
                    break;

                case 'store':
                    $controller->storeCounterpartie();
                    break;

                case 'update':
                    $controller->updateCounterpartie();
                    break;

                case 'delete':
                    $controller->deleteCounterpartie();
                    break;

                default:
                    if ($action) {
                        $controller->counterpartie((int)$action);
                    } else {
                        $controller->counterpartie();
                    }
                    break;
            }
            break;

        case 'tasks':
            $controller = new TaskController();

            switch ($action) {
                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        $controller->create();
                    }
                    break;

                case 'edit':
                    $controller->edit();
                    break;

                case 'update':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->update();
                    } else {
                        Security::redirect(AppConstants::ROUTE_TASKS);
                    }
                    break;

                case 'updatet':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->updateT();
                    } else {
                        Security::redirect(AppConstants::ROUTE_TASKS);
                    }
                    break;

                case 'store':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        Security::redirect(AppConstants::ROUTE_TASKS);
                    }
                    break;

                case 'storet':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->storeT();
                    } else {
                        Security::redirect(AppConstants::ROUTE_TASKS);
                    }
                    break;

                case 'delete':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->delete();
                    } else {
                        Security::redirect(AppConstants::ROUTE_TASKS);
                    }
                    break;

                case 'deleteT':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->deleteT();
                    } else {
                        Security::redirect(AppConstants::ROUTE_TASKS);
                    }
                    break;

                case 'refreshTasksTable':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->refreshTasksTable();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'show':
                    if ($id) {
                        $controller->show((int)$id);
                    } else {
                        Security::redirect(AppConstants::ROUTE_TASKS);
                    }
                    break;

                case 'change-state':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->changeState();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'change-stateFSR':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->changeStateFSR();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'check-executable':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->checkExecutable();
                    } else {
                        http_response_code(405);
                        echo json_encode(['valid' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'valid-transitions':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->getValidTransitions();
                    } else {
                        http_response_code(405);
                        echo json_encode(['transitions' => [], 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'my':
                    $controller->myIndex();

                    break;

                case '':
                case null:
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'task':
            $controller = new TaskController();

            if ($action && is_numeric($action)) {
                $controller->show((int)$action);
            } else {
                $controller->newTask();
            }
            break;

        case 'proyecto-feriados':
            $controller = new ProyectoFeriadoController();

            switch ($action) {
                case 'create-masivo':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->createMasivo();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'create-especifico':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->createEspecifico();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'create-rango':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->createRango();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'list':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->list();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'refreshHolidaysTable':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->refreshHolidaysTable();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'update':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->update();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'delete':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->delete();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'check-conflicts':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->checkConflicts();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'move-tasks':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->moveTasks();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case 'working-days':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->getWorkingDays();
                    } else {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => AppConstants::ERROR_METHOD_NOT_ALLOWED]);
                    }
                    break;

                case '':
                case null:
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'user':
            // Redireccionar las rutas /user/{id} a las rutas estándar
            if ($action) {
                // Editar usuario existente
                Security::redirect(AppConstants::ROUTE_USERS . "/edit?id={$action}");
            } else {
                // Nuevo usuario
                Security::redirect(AppConstants::ROUTE_USERS . "/create");
            }
            break;

        case 'api':
            // Rutas API
            if ($action === 'users') {
                $controller = new UserController();
                if (isset($parts[2])) {
                    switch ($parts[2]) {
                        case 'validate':
                            $controller->validateField();
                            break;
                        case 'details':
                            $controller->getUserDetails();
                            break;
                        default:
                            http_response_code(404);
                            echo json_encode(['error' => 'API endpoint not found']);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'API endpoint not found']);
                }
            } elseif ($action === 'user-check') {
                // Ruta para validaciones de usuario (usada en create.php)
                $controller = new UserController();
                $controller->validateUserCheck();
            } elseif ($action === 'personas') {
                // Rutas API para personas
                $controller = new UserController();
                if (isset($parts[2])) {
                    switch ($parts[2]) {
                        case 'available-for-user':
                            $controller->getAvailablePersonas();
                            break;
                        default:
                            http_response_code(404);
                            echo json_encode(['error' => 'API endpoint not found']);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'API endpoint not found']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
            }
            break;

        case 'accesos':
            $controller = new AccessController();

            switch ($action) {
                case 'update':
                    $controller->update();
                    break;

                case '':
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'permisos':
            $controller = new PermissionsController();

            switch ($action) {
                case 'update':
                    $controller->update();
                    break;

                case '':
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'reports':
            $controller = new ReportController();

            switch ($action) {
                case 'create':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->generate();
                    } else {
                        $controller->create();
                    }
                    break;

                case 'generate':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->generate();
                    } else {
                        Security::redirect(AppConstants::ROUTE_REPORTS);
                    }
                    break;

                case 'download':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->download();
                    } else {
                        Security::redirect(AppConstants::ROUTE_REPORTS);
                    }
                    break;

                case 'users-report':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->usersReport();
                    } else {
                        Security::redirect(AppConstants::ROUTE_REPORTS);
                    }
                    break;

                case 'projects-report':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->projectsReport();
                    } else {
                        Security::redirect(AppConstants::ROUTE_REPORTS);
                    }
                    break;

                case '':
                case null:
                default:
                    $controller->index();
                    break;
            }
            break;

        case '':


        default:
            // Página no encontrada
            http_response_code(404);
            echo '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Página No Encontrada - SETAP</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body>
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="mb-0">Página No Encontrada</h4>
                                </div>
                                <div class="card-body text-center">
                                    <p class="mb-3">La página solicitada no existe.</p>
                                    <a href="/setap/home" class="btn btn-primary">Volver al Home</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>';
            break;
    }
} catch (Throwable $e) {
    // Manejo de errores global
    Logger::error("router: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());

    http_response_code(500);

    // Mostrar error detallado solo en desarrollo
    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
        echo "<h1>Error Internal del Servidor</h1>";
        echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
    } else {
        echo '<!DOCTYPE html>
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
                                <h4 class="mb-0">Error Interno del Servidor</h4>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">Ha ocurrido un error interno. Por favor, intente más tarde.</p>
                                <a href="/setap/home" class="btn btn-primary">Volver al Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
}
