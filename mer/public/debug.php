<?php
// Página de depuración del sistema - ACCESO RESTRINGIDO
// Llamar: http://localhost:8080/mer/debug.php

// Verificar que está en modo debug
if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
    die('Depuración deshabilitada');
}

require_once __DIR__ . '/../src/auth_functions.php';

init_secure_session();
require_once __DIR__ . '/../src/functions.php';

echo "<h1>🔍 Depuración del Sistema Mall Virtual</h1>";
echo "<hr>";

// 1. Estado de la base de datos
echo "<h2>1. Conexión a Base de Datos</h2>";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    echo "✅ <strong>Base de datos conectada</strong><br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "BD: " . DB_NAME . "<br>";
    echo "Usuario: " . DB_USER . "<br>";
} catch (Exception $e) {
    echo "❌ <strong>Error de conexión:</strong> " . $e->getMessage() . "<br>";
    exit;
}

// 2. Verificar tablas principales
echo "<h2>2. Verificación de Tablas</h2>";
$requiredTables = ['stores', 'products', 'orders', 'customers'];
foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ $table: $count registros<br>";
    } catch (Exception $e) {
        echo "❌ $table: Error - " . $e->getMessage() . "<br>";
    }
}

// 3. Log de errores
echo "<h2>3. Log de Errores (últimas 10 líneas)</h2>";
$logFile = '/workspace/mer_debug.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -10);
    foreach ($recentLines as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
} else {
    echo "No se encontró archivo de log<br>";
}

// 4. Estado de la sesión
echo "<h2>4. Estado de la Sesión</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Sesión iniciada: " . (session_status() === PHP_SESSION_ACTIVE ? 'Sí' : 'No') . "<br>";
echo "Datos del carrito: " . (isset($_SESSION['cart']) ? count($_SESSION['cart']) . ' items' : 'Vacío') . "<br>";

// 5. Últimas consultas (simulado)
echo "<h2>5. Información del Sistema</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Memoria utilizada: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB<br>";
echo "Tiempo de ejecución: " . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . " segundos<br>";

// 6. Enlaces útiles
echo "<h2>6. Enlaces Útiles</h2>";
echo "<a href='index.php'>🏠 Portal Principal</a><br>";
echo "<a href='cart.php'>🛒 Carrito</a><br>";
echo "<a href='checkout.php'>💳 Checkout</a><br>";
echo "<a href='stores/tienda-a/'>☕ Tienda-A (Café Brew)</a><br>";
echo "<a href='stores/tienda-b/'>🛍️ Tienda-B</a><br>";

echo "<hr>";
echo "<small>Modo depuración activado - NO usar en producción</small>";
?>