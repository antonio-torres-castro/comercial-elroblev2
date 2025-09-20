<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/App/bootstrap.php';

use App\Config\AppConfig;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UserController;

// Manejar CORS para desarrollo
if (AppConfig::getEnv() === 'development') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
}

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obtener la ruta de la solicitud
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Eliminar slash inicial y cualquier segmento no deseado (como 'setap' si está presente)
$route = ltrim($requestUri, '/');

// Si la ruta comienza con 'setap/', eliminarlo
if (strpos($route, 'setap/') === 0) {
    $route = substr($route, strlen('setap/'));
}

// Dividir la ruta para obtener el controlador y la acción
$parts = explode('/', $route);
$controllerName = $parts[0] ?: 'login';  // Si está vacío, redirigir a login
$action = $parts[1] ?? '';

// Enrutamiento simple
switch ($controllerName) {
    case 'login':
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->login();
        } else {
            $controller->showLogin();
        }
        break;

    case 'dashboard':
        $controller = new DashboardController();
        $controller->index();
        break;

    case 'logout':
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'users':
        $controller = new UserController();
        $action = $parts[1] ?? 'index';
        $id = $parts[2] ?? null;

        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->create();
        } elseif ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->store();
        } else {
            $controller->index();
        }
        break;

    case 'test_autoload':
        $testFile = __DIR__ . '/test_autoload.php';
        if (file_exists($testFile)) {
            require $testFile;
        } else {
            http_response_code(404);
            echo "Archivo de prueba no encontrado";
        }
        break;

    default:
        http_response_code(404);
        echo "Página no encontrada";
        break;
}
