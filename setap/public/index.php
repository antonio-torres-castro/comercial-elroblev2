<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/App/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Controllers\ProjectController;
use App\Controllers\MenuController;
use App\Controllers\PersonaController;
use App\Controllers\PerfilController;
use App\Controllers\ClientController;
use App\Controllers\TaskController;
use App\Helpers\Security;

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

        case 'dashboard':
            $controller = new DashboardController();
            $controller->index();
            break;

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
                    
                case 'search':
                    $controller->search();
                    break;
                    
                case '':
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'project':
            $controller = new ProjectController();
            
            if ($action) {
                $controller->show((int)$action);
            } else {
                // Redirigir a nuevo proyecto
                $controller->show();
            }
            break;

        case 'menus':
            $controller = new MenuController();
            $controller->index();
            break;

        case 'menu':
            $controller = new MenuController();
            
            if ($action) {
                $controller->show((int)$action);
            } else {
                $controller->show();
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
            
            if ($action) {
                $controller->counterpartie((int)$action);
            } else {
                $controller->counterpartie();
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
                    $controller->update();
                    break;
                    
                case 'delete':
                    $controller->delete();
                    break;
                    
                case '':
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'task':
            $controller = new TaskController();
            
            if ($action) {
                $controller->show((int)$action);
            } else {
                $controller->show();
            }
            break;

        case 'user':
            $controller = new UserController();
            
            if ($action) {
                $controller->show((int)$action);
            } else {
                // Nuevo usuario
                $controller->create();
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
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
            }
            break;

        case '':
        case 'home':
            // Redirigir a dashboard si está autenticado, sino a login
            if (Security::isAuthenticated()) {
                Security::redirect('/dashboard');
            } else {
                Security::redirect('/login');
            }
            break;

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
                                    <a href="/dashboard" class="btn btn-primary">Volver al Dashboard</a>
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
    error_log("Error en router: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    
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
                                <a href="/dashboard" class="btn btn-primary">Volver al Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
}