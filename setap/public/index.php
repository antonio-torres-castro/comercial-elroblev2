<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Inicializar Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configuración de errores según APP_ENV
if ($_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

echo "<h1>SETAP WebApp funcionando 🚀</h1>";
echo "<p>Ambiente actual: " . $_ENV['APP_ENV'] . "</p>";
echo "<p>Base de datos configurada: " . $_ENV['DB_DATABASE'] . "</p>";
