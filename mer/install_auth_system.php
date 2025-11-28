<?php
/**
 * Script de Instalación del Sistema de Autenticación
 * Mall Virtual - Autenticación y Autorización
 */

echo "=== INSTALACIÓN DEL SISTEMA DE AUTENTICACIÓN ===\n\n";

// Cargar configuración de base de datos
require_once __DIR__ . '/src/functions.php';

try {
    echo "🔗 Conectando a la base de datos...\n";
    
    // Conexión a la base de datos
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "✅ Conexión exitosa a la base de datos\n\n";
    
    // Leer el archivo SQL
    $sql_file = __DIR__ . '/database/auth_system.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("❌ Archivo SQL no encontrado: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    echo "📖 Leyendo archivo de migración...\n";
    
    // Dividir en comandos individuales
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "🔄 Ejecutando " . count($commands) . " comandos SQL...\n\n";
    
    $executed = 0;
    $errors = 0;
    
    foreach ($commands as $command) {
        if (empty($command) || strpos($command, '--') === 0 || strpos($command, '/*') === 0) {
            continue; // Skip comments and empty lines
        }
        
        try {
            $pdo->exec($command);
            $executed++;
            echo "✅ Comando ejecutado exitosamente\n";
        } catch (PDOException $e) {
            $errors++;
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "   Comando: " . substr($command, 0, 100) . "...\n\n";
        }
    }
    
    echo "\n📊 RESUMEN DE INSTALACIÓN:\n";
    echo "   ✅ Comandos ejecutados: $executed\n";
    echo "   ❌ Errores: $errors\n";
    
    if ($errors === 0) {
        echo "\n🎉 ¡INSTALACIÓN COMPLETADA EXITOSAMENTE!\n\n";
        echo "📋 INFORMACIÓN DE ACCESO:\n";
        echo "   🌐 Portal Principal: http://localhost:8080/mer/public/\n";
        echo "   🔑 Admin Login: http://localhost:8080/mer/public/auth/login.php\n";
        echo "   📧 Email Admin: admin@mallvirtual.com\n";
        echo "   🔒 Password Admin: admin123\n\n";
        
        echo "📁 ARCHIVOS IMPORTANTES CREADOS:\n";
        echo "   • Sistema de autenticación completo\n";
        echo "   • Páginas de login y registro\n";
        echo "   • Dashboard de usuario\n";
        echo "   • Gestión de direcciones\n";
        echo "   • Protección de rutas admin\n";
        echo "   • Menú de usuario integrado\n\n";
        
        echo "🚀 PRÓXIMOS PASOS:\n";
        echo "   1. Acceder al portal principal\n";
        echo "   2. Crear tu cuenta o usar admin\n";
        echo "   3. Explorar el sistema de autenticación\n";
        echo "   4. Configurar verificación por email (opcional)\n\n";
        
    } else {
        echo "\n⚠️  INSTALACIÓN COMPLETADA CON ERRORES\n";
        echo "   Revisa los errores arriba y vuelve a ejecutar si es necesario\n\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR FATAL: " . $e->getMessage() . "\n\n";
    echo "🔧 SOLUCIÓN:\n";
    echo "   1. Verificar configuración de base de datos en src/functions.php\n";
    echo "   2. Asegurarse de que MySQL esté ejecutándose\n";
    echo "   3. Verificar credenciales de acceso\n";
    echo "   4. Ejecutar manualmente el archivo: database/auth_system.sql\n\n";
}

echo "=== FIN DE LA INSTALACIÓN ===\n";
?>