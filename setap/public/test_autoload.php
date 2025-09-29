<?php
require __DIR__ . '/../vendor/autoload.php';

try {
    // Test de controladores
    $authController = new App\Controllers\AuthController();
    echo "✅ AuthController cargado<br>";

    // Test de middlewares
    $authMiddleware = new App\Middlewares\AuthMiddleware();
    echo "✅ AuthMiddleware cargado<br>";

    $permissionMiddleware = new App\Middlewares\PermissionMiddleware([1]);
    echo "✅ PermissionMiddleware cargado<br>";

    // Test de helpers
    $security = new App\Helpers\Security();
    echo "✅ Security cargado<br>";

    echo "🎉 ¡Todo el autoload funciona correctamente!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
    echo "<br>¿Estás seguro de que los namespaces están correctos?";
}
