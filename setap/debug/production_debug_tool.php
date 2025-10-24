<?php
/**
 * Herramienta completa de debugging para producción
 * Ubicación: /ruta/a/tu/proyecto/debug/production_debug_tool.php
 */

class ProductionDebugTool
{
    private $logDir;
    private $errorLog;
    
    public function __construct()
    {
        $this->logDir = __DIR__ . '/../logs';
        $this->errorLog = ini_get('error_log') ?: $this->logDir . '/php_errors.log';
        
        // Crear directorio de logs si no existe
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Ejecutar diagnóstico completo del sistema
     */
    public function runFullDiagnostic()
    {
        echo "=== DIAGNÓSTICO COMPLETO - " . date('Y-m-d H:i:s') . " ===\n\n";
        
        $this->checkSystemInfo();
        $this->checkPHPConfiguration();
        $this->checkFilePermissions();
        $this->checkDatabaseConnection();
        $this->checkApacheStatus();
        $this->analyzeRecentErrors();
        $this->checkApplicationHealth();
        $this->generateRecommendations();
    }
    
    /**
     * Información básica del sistema
     */
    private function checkSystemInfo()
    {
        echo "=== INFORMACIÓN DEL SISTEMA ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "OS: " . PHP_OS . "\n";
        echo "Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
        echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
        echo "Memoria disponible: " . ini_get('memory_limit') . "\n";
        echo "Tiempo máx ejecución: " . ini_get('max_execution_time') . "s\n\n";
    }
    
    /**
     * Verificar configuración PHP
     */
    private function checkPHPConfiguration()
    {
        echo "=== CONFIGURACIÓN PHP ===\n";
        echo "Display Errors: " . (ini_get('display_errors') ? 'ON ❌ (Peligroso en producción)' : 'OFF ✅') . "\n";
        echo "Log Errors: " . (ini_get('log_errors') ? 'ON ✅' : 'OFF ❌') . "\n";
        echo "Error Reporting: " . ini_get('error_reporting') . "\n";
        echo "Error Log: " . $this->errorLog . "\n";
        
        // Extensiones críticas
        echo "\nExtensiones críticas:\n";
        $criticalExtensions = [
            'pdo', 'pdo_mysql', 'curl', 'openssl', 'mbstring', 
            'gd', 'json', 'session', 'filter', 'tokenizer'
        ];
        
        foreach ($criticalExtensions as $ext) {
            $status = extension_loaded($ext) ? "✅" : "❌";
            echo "$status $ext\n";
        }
        echo "\n";
    }
    
    /**
     * Verificar permisos de archivos
     */
    private function checkFilePermissions()
    {
        echo "=== PERMISOS DE ARCHIVOS ===\n";
        
        $paths = [
            '../logs' => 'Directorio de logs',
            '../storage' => 'Directorio de storage',
            '../public' => 'Directorio público',
            '../vendor' => 'Vendor directory'
        ];
        
        foreach ($paths as $path => $description) {
            if (is_dir($path)) {
                $writable = is_writable($path) ? "✅" : "❌";
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                echo "$writable $description ($path) - Permisos: $perms\n";
            } else {
                echo "❌ $description ($path) - NO EXISTE\n";
            }
        }
        echo "\n";
    }
    
    /**
     * Verificar conexión a base de datos
     */
    private function checkDatabaseConnection()
    {
        echo "=== CONEXIÓN A BASE DE DATOS ===\n";
        
        try {
            $configFile = '../config/database.php';
            if (file_exists($configFile)) {
                include_once $configFile;
                
                if (defined('DB_HOST')) {
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    echo "✅ Conexión exitosa a " . DB_NAME . "\n";
                    
                    // Verificar tablas críticas
                    $criticalTables = ['usuarios', 'clientes', 'proyectos', 'tareas'];
                    foreach ($criticalTables as $table) {
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                            $count = $stmt->fetchColumn();
                            echo "  ✅ Tabla $table: $count registros\n";
                        } catch (Exception $e) {
                            echo "  ❌ Tabla $table: Error - " . $e->getMessage() . "\n";
                        }
                    }
                } else {
                    echo "❌ Configuración de DB incompleta\n";
                }
            } else {
                echo "❌ Archivo de configuración de DB no encontrado\n";
            }
        } catch (Exception $e) {
            echo "❌ Error de conexión: " . $e->getMessage() . "\n";
            echo "Verifica:\n";
            echo "  - Credenciales en config/database.php\n";
            echo "  - Servicio MySQL ejecutándose\n";
            echo "  - Permisos de usuario DB\n";
        }
        echo "\n";
    }
    
    /**
     * Verificar estado de Apache
     */
    private function checkApacheStatus()
    {
        echo "=== ESTADO DE APACHE ===\n";
        
        // Verificar si Apache está ejecutándose
        $apacheStatus = shell_exec("systemctl is-active apache2 2>/dev/null");
        if (strpos($apacheStatus, 'active') !== false) {
            echo "✅ Apache2 está ejecutándose\n";
        } else {
            echo "❌ Apache2 no está ejecutándose\n";
        }
        
        // Verificar módulos importantes
        $modules = shell_exec("apache2ctl -M 2>/dev/null");
        $requiredModules = ['rewrite', 'ssl', 'deflate'];
        
        foreach ($requiredModules as $module) {
            $status = strpos($modules, $module) !== false ? "✅" : "❌";
            echo "$status Módulo $module\n";
        }
        echo "\n";
    }
    
    /**
     * Analizar errores recientes
     */
    private function analyzeRecentErrors()
    {
        echo "=== ERRORES RECIENTES ===\n";
        
        if (!file_exists($this->errorLog)) {
            echo "❌ Archivo de errores no encontrado: $this->errorLog\n\n";
            return;
        }
        
        $lines = file($this->errorLog);
        $recentErrors = array_slice($lines, -20);
        
        if (empty($recentErrors)) {
            echo "✅ No hay errores recientes\n\n";
            return;
        }
        
        echo "Últimos 20 errores:\n";
        foreach ($recentErrors as $line) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'Fatal error') !== false) {
                echo "❌ " . $line;
            }
        }
        echo "\n";
    }
    
    /**
     * Verificar salud de la aplicación
     */
    private function checkApplicationHealth()
    {
        echo "=== SALUD DE LA APLICACIÓN ===\n";
        
        // Verificar archivos críticos
        $criticalFiles = [
            '../public/index.php' => 'Punto de entrada',
            '../vendor/autoload.php' => 'Composer autoload',
            '../src/App/bootstrap.php' => 'Bootstrap de la aplicación'
        ];
        
        foreach ($criticalFiles as $file => $description) {
            $exists = file_exists($file) ? "✅" : "❌";
            echo "$exists $description\n";
        }
        
        // Verificar si la aplicación puede cargar
        try {
            require_once '../vendor/autoload.php';
            echo "✅ Composer autoload funciona\n";
        } catch (Exception $e) {
            echo "❌ Error en autoload: " . $e->getMessage() . "\n";
        }
        
        // Verificar uso de memoria
        $memoryUsage = round(memory_get_usage() / 1024 / 1024, 2);
        $memoryLimit = ini_get('memory_limit');
        echo "💾 Uso actual de memoria: $memoryUsage MB / $memoryLimit\n\n";
    }
    
    /**
     * Generar recomendaciones
     */
    private function generateRecommendations()
    {
        echo "=== RECOMENDACIONES ===\n";
        
        echo "1. 📝 Logging:\n";
        echo "   - Activar logs detallados: error_reporting = E_ALL\n";
        echo "   - Configurar logrotate para archivos de log\n";
        echo "   - Implementar monitoreo de logs\n\n";
        
        echo "2. 🔒 Seguridad:\n";
        echo "   - Deshabilitar display_errors en producción\n";
        echo "   - Verificar permisos de archivos (644 para archivos, 755 para directorios)\n";
        echo "   - Configurar firewall para Apache\n\n";
        
        echo "3. ⚡ Rendimiento:\n";
        echo "   - Habilitar OPcache: opcache.enable=1\n";
        echo "   - Configurar compresión: mod_deflate\n";
        echo "   - Optimizar consultas de base de datos\n\n";
        
        echo "4. 🗄️ Base de Datos:\n";
        echo "   - Verificar índices en tablas críticas\n";
        echo "   - Configurar conexión persistente\n";
        echo "   - Implementar backup automático\n\n";
        
        echo "5. 🔧 Monitoreo:\n";
        echo "   - Implementar health checks automáticos\n";
        echo "   - Configurar alertas por email\n";
        echo "   - Usar herramientas como Nagios o Zabbix\n\n";
    }
    
    /**
     * Generar reporte técnico detallado
     */
    public function generateTechnicalReport()
    {
        $report = [];
        $report['timestamp'] = date('Y-m-d H:i:s');
        $report['system_info'] = [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];
        $report['php_config'] = [
            'display_errors' => ini_get('display_errors'),
            'log_errors' => ini_get('log_errors'),
            'error_log' => $this->errorLog,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
        $report['extensions'] = get_loaded_extensions();
        $report['memory_usage'] = [
            'current' => memory_get_usage(),
            'peak' => memory_get_peak_usage(),
            'limit' => ini_get('memory_limit')
        ];
        
        // Guardar reporte
        $reportFile = $this->logDir . '/diagnostic_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        
        return $reportFile;
    }
}

// Función principal para ejecutar desde línea de comandos
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'production_debug_tool.php') {
    $debugTool = new ProductionDebugTool();
    
    if (isset($argv[1]) && $argv[1] === '--report') {
        $reportFile = $debugTool->generateTechnicalReport();
        echo "Reporte técnico generado: $reportFile\n";
    } else {
        $debugTool->runFullDiagnostic();
    }
}
?>