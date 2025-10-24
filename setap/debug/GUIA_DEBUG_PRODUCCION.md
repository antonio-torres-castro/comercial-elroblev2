# 🔧 Guía Completa para Depurar PHP en Producción

## 📋 Tabla de Contenidos
1. [Preparativos Previos](#preparativos-previos)
2. [Logging y Monitoreo](#logging-y-monitoreo)
3. [Debugging de Errores](#debugging-de-errores)
4. [Análisis de Rendimiento](#análisis-de-rendimiento)
5. [Debugging de Base de Datos](#debugging-de-base-de-datos)
6. [Herramientas de Depuración](#herramientas-de-depuración)
7. [Estrategias de Depuración](#estrategias-de-depuración)
8. [Casos Comunes](#casos-comunes)

---

## 🚀 Preparativos Previos

### ✅ Configuración Recomendada

#### 1. Habilitar Logging en PHP
```apache
# En tu .htaccess o httpd.conf
php_flag display_errors Off
php_flag log_errors On
php_value error_log /var/log/php/error.log
```

#### 2. Configurar Apache para Log Detallado
```apache
# En httpd.conf o virtual host
LogLevel info:error
LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-agent}i\" %D" detailed
CustomLog /var/log/apache2/comercial-elroble.log detailed
```

#### 3. Habilitar Error Logging Personalizado
```php
// En tu bootstrap.php o configuración inicial
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/app_error.log');
ini_set('display_errors', 0); // En producción
```

---

## 📊 Logging y Monitoreo

### 1. Sistema de Logs Personalizado

#### 📝 Crear sistema de logging
```php
<?php
// src/App/Utils/Logger.php
class Logger
{
    private static $logFile;
    
    public static function init($logFile = null)
    {
        self::$logFile = $logFile ?: __DIR__ . '/../../logs/app.log';
        
        // Crear directorio si no existe
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public static function error($message, $context = [])
    {
        self::log('ERROR', $message, $context);
    }
    
    public static function info($message, $context = [])
    {
        self::log('INFO', $message, $context);
    }
    
    public static function debug($message, $context = [])
    {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            self::log('DEBUG', $message, $context);
        }
    }
    
    private static function log($level, $message, $context)
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Inicializar en bootstrap.php
Logger::init(__DIR__ . '/../../logs/app.log');
```

#### 📝 Uso del Logger
```php
// En tus controladores
Logger::info('Usuario iniciando sesión', ['user_id' => $userId]);
Logger::error('Error al procesar solicitud', ['error' => $e->getMessage()]);
Logger::debug('Variables recibidas', $_POST);

// En bootstrap.php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Logger::error("PHP Error: $errstr", [
        'file' => $errfile,
        'line' => $errline,
        'type' => $errno
    ]);
});
```

### 2. Monitoreo de Apache

#### 🔍 Logs de Apache útiles
```bash
# Ver errores de Apache
tail -f /var/log/apache2/error.log

# Ver acceso detallado
tail -f /var/log/apache2/comercial-elroble.log

# Analizar errores PHP
tail -f /var/log/php/error.log

# Verificar logs personalizados
tail -f /ruta/a/tu/proyecto/logs/app.log
```

---

## 🐛 Debugging de Errores

### 1. Análisis de Errores PHP

#### 📋 Script para analizar errores
```php
<?php
// debug/errors_analyzer.php
class ErrorsAnalyzer
{
    public function analyzeErrorLog($logFile)
    {
        if (!file_exists($logFile)) {
            return ['error' => 'Archivo de log no encontrado'];
        }
        
        $errors = [];
        $lines = file($logFile);
        $recentLines = array_slice($lines, -100); // Últimas 100 líneas
        
        foreach ($recentLines as $line) {
            if (preg_match('/\[(.*?)\] \[(.*?)\] (.*)/', $line, $matches)) {
                $errors[] = [
                    'timestamp' => $matches[1],
                    'level' => $matches[2],
                    'message' => $matches[3]
                ];
            }
        }
        
        return $this->generateReport($errors);
    }
    
    private function generateReport($errors)
    {
        $report = "=== REPORTE DE ERRORES ===\n\n";
        
        // Contar por tipo
        $types = array_count_values(array_column($errors, 'level'));
        $report .= "Errores por tipo:\n";
        foreach ($types as $type => $count) {
            $report .= "- $type: $count\n";
        }
        
        // Errores más frecuentes
        $messages = array_column($errors, 'message');
        $messageCounts = array_count_values($messages);
        arsort($messageCounts);
        
        $report .= "\nErrores más frecuentes:\n";
        $topErrors = array_slice($messageCounts, 0, 10, true);
        foreach ($topErrors as $message => $count) {
            $report .= "- ($count veces) $message\n";
        }
        
        return $report;
    }
}

// Uso
$analyzer = new ErrorsAnalyzer();
$report = $analyzer->analyzeErrorLog('/var/log/php/error.log');
echo $report;
```

### 2. Debug de Excepciones

#### 📝 Manejo personalizado de excepciones
```php
<?php
// debug/ExceptionHandler.php
class ExceptionHandler
{
    public static function handle($exception)
    {
        $errorInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'post_data' => $_POST,
            'get_data' => $_GET,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        // Log del error
        Logger::error('Excepción no controlada', $errorInfo);
        
        // Si es AJAX, retornar JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Error interno del servidor']);
            exit;
        }
        
        // Página de error personalizada
        include 'error_template.php';
        exit;
    }
}

// Configurar en bootstrap.php
set_exception_handler(['ExceptionHandler', 'handle']);
```

---

## ⚡ Análisis de Rendimiento

### 1. Profiling Básico

#### 📊 Script de análisis de rendimiento
```php
<?php
// debug/performance_analyzer.php
class PerformanceAnalyzer
{
    private $startTime;
    private $startMemory;
    private $queries = [];
    
    public function start()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }
    
    public function logQuery($query, $time = null)
    {
        $this->queries[] = [
            'query' => $query,
            'time' => $time ?? $this->getElapsedTime(),
            'timestamp' => date('H:i:s')
        ];
    }
    
    public function getElapsedTime()
    {
        return round((microtime(true) - $this->startTime) * 1000, 2);
    }
    
    public function getMemoryUsage()
    {
        return round((memory_get_usage() - $this->startMemory) / 1024 / 1024, 2);
    }
    
    public function getReport()
    {
        return [
            'execution_time' => $this->getElapsedTime() . ' ms',
            'memory_usage' => $this->getMemoryUsage() . ' MB',
            'queries_count' => count($this->queries),
            'queries' => $this->queries
        ];
    }
}

// En bootstrap.php
$GLOBALS['perf_analyzer'] = new PerformanceAnalyzer();
$GLOBALS['perf_analyzer']->start();
```

### 2. Análisis de Slow Queries

#### 🐌 Detectar queries lentas
```php
<?php
// debug/SlowQueryAnalyzer.php
class SlowQueryAnalyzer
{
    public static function analyzeQueries($queries, $threshold = 100)
    {
        $slowQueries = array_filter($queries, function($query) use ($threshold) {
            return $query['time'] > $threshold;
        });
        
        if (!empty($slowQueries)) {
            Logger::warning('Queries lentas detectadas', [
                'slow_queries' => $slowQueries,
                'threshold' => $threshold
            ]);
        }
        
        return $slowQueries;
    }
}
```

---

## 🗄️ Debugging de Base de Datos

### 1. Logging de Consultas SQL

#### 📝 Wrapper para PDO con logging
```php
<?php
// src/App/Database/DebugPDO.php
class DebugPDO extends PDO
{
    private $queryLog = [];
    
    public function query($query)
    {
        $start = microtime(true);
        $result = parent::query($query);
        $time = (microtime(true) - $start) * 1000;
        
        $this->logQuery($query, $time);
        
        return $result;
    }
    
    public function prepare($statement, $driver_options = [])
    {
        $stmt = parent::prepare($statement, $driver_options);
        return new DebugPDOStatement($stmt, $this);
    }
    
    private function logQuery($query, $time)
    {
        $this->queryLog[] = [
            'query' => $query,
            'time' => $time,
            'timestamp' => date('H:i:s')
        ];
        
        // Log si es muy lenta
        if ($time > 100) {
            Logger::warning('Query lenta', ['query' => $query, 'time' => $time . 'ms']);
        }
    }
    
    public function getQueryLog()
    {
        return $this->queryLog;
    }
}

class DebugPDOStatement
{
    private $statement;
    private $pdo;
    
    public function __construct($statement, $pdo)
    {
        $this->statement = $statement;
        $this->pdo = $pdo;
    }
    
    public function execute($params = null)
    {
        $start = microtime(true);
        $result = $this->statement->execute($params);
        $time = (microtime(true) - $start) * 1000;
        
        $this->pdo->logQuery($this->statement->queryString, $time);
        
        return $result;
    }
    
    public function __call($method, $args)
    {
        return call_user_func_array([$this->statement, $method], $args);
    }
}
```

---

## 🛠️ Herramientas de Depuración

### 1. Script de Análisis Completo

#### 🔍 Analizador de estado completo
```php
<?php
// debug/complete_diagnostics.php
class CompleteDiagnostics
{
    public function run()
    {
        $report = "=== DIAGNÓSTICO COMPLETO DEL SISTEMA ===\n\n";
        
        // Información del sistema
        $report .= "=== INFORMACIÓN DEL SISTEMA ===\n";
        $report .= "PHP Version: " . PHP_VERSION . "\n";
        $report .= "OS: " . PHP_OS . "\n";
        $report .= "Memory Limit: " . ini_get('memory_limit') . "\n";
        $report .= "Max Execution Time: " . ini_get('max_execution_time') . "\n";
        $report .= "Upload Max Size: " . ini_get('upload_max_filesize') . "\n\n";
        
        // Extensiones cargadas
        $report .= "=== EXTENSIONES CRÍTICAS ===\n";
        $criticalExtensions = ['pdo', 'pdo_mysql', 'curl', 'openssl', 'mbstring'];
        foreach ($criticalExtensions as $ext) {
            $status = extension_loaded($ext) ? "✅" : "❌";
            $report .= "$status $ext\n";
        }
        $report .= "\n";
        
        // Permisos de archivos
        $report .= "=== PERMISOS DE ARCHIVOS ===\n";
        $paths = [
            __DIR__ . '/../logs' => 'Directorio de logs',
            __DIR__ . '/../storage' => 'Directorio de storage',
            __DIR__ . '/../../vendor' => 'Vendor directory'
        ];
        
        foreach ($paths as $path => $description) {
            if (is_dir($path)) {
                $writable = is_writable($path) ? "✅" : "❌";
                $report .= "$writable $description: $path\n";
            }
        }
        $report .= "\n";
        
        // Conexión a base de datos
        $report .= "=== CONEXIÓN A BASE DE DATOS ===\n";
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=test", "test", "test");
            $report .= "✅ Conexión DB: OK\n";
        } catch (Exception $e) {
            $report .= "❌ Conexión DB: FAILED - " . $e->getMessage() . "\n";
        }
        $report .= "\n";
        
        // Errores recientes
        $report .= "=== ERRORES RECIENTES ===\n";
        $errorLog = ini_get('error_log');
        if (file_exists($errorLog)) {
            $lines = file($errorLog);
            $recentErrors = array_slice($lines, -10);
            foreach ($recentErrors as $line) {
                $report .= $line;
            }
        } else {
            $report .= "No se encontró archivo de errores\n";
        }
        
        return $report;
    }
}

// Ejecutar diagnóstico
$diagnostics = new CompleteDiagnostics();
$report = $diagnostics->run();
file_put_contents(__DIR__ . '/diagnostics_report.txt', $report);
echo $report;
```

### 2. Panel de Monitoreo Simple

#### 📊 Panel web de estado
```php
<?php
// debug/status_panel.php
class StatusPanel
{
    public function render()
    {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Panel de Estado - Comercial El Roble</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .status-ok { color: green; font-weight: bold; }
                .status-error { color: red; font-weight: bold; }
                .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
                pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
            </style>
        </head>
        <body>
            <h1>🔧 Panel de Depuración - Comercial El Roble</h1>
            
            <div class="section">
                <h2>📊 Estado del Sistema</h2>
                <p>PHP Version: <?= PHP_VERSION ?> <?= PHP_VERSION_ID >= 70400 ? '✅' : '⚠️' ?></p>
                <p>Memory Limit: <?= ini_get('memory_limit') ?></p>
                <p>Error Reporting: <?= ini_get('display_errors') ? 'ON' : 'OFF' ?></p>
                <p>Log Errors: <?= ini_get('log_errors') ? 'ON' : 'OFF' ?></p>
            </div>
            
            <div class="section">
                <h2>📝 Últimos Errores</h2>
                <?php
                $errorLog = ini_get('error_log');
                if (file_exists($errorLog)) {
                    $lines = file($errorLog);
                    $lastErrors = array_slice($lines, -10);
                    echo '<pre>' . implode('', $lastErrors) . '</pre>';
                } else {
                    echo '<p class="status-error">No se encontró archivo de errores</p>';
                }
                ?>
            </div>
            
            <div class="section">
                <h2>🗄️ Estado de Base de Datos</h2>
                <?php
                try {
                    $pdo = new PDO("mysql:host=localhost;dbname=comercial_elroble", "root", "");
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
                    $result = $stmt->fetch();
                    echo '<p class="status-ok">✅ Conexión OK - Usuarios: ' . $result['total'] . '</p>';
                } catch (Exception $e) {
                    echo '<p class="status-error">❌ Error DB: ' . $e->getMessage() . '</p>';
                }
                ?>
            </div>
            
            <div class="section">
                <h2>📂 Logs de la Aplicación</h2>
                <?php
                $appLog = __DIR__ . '/../logs/app.log';
                if (file_exists($appLog)) {
                    $lines = file($appLog);
                    $lastLogs = array_slice($lines, -20);
                    echo '<pre>' . implode('', $lastLogs) . '</pre>';
                } else {
                    echo '<p>No se encontró log de aplicación</p>';
                }
                ?>
            </div>
        </body>
        </html>
        <?php
    }
}

// Solo accesible desde IPs específicas
$allowedIPs = ['127.0.0.1', 'TU_IP_PUBLICA'];
if (in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    $panel = new StatusPanel();
    $panel->render();
} else {
    http_response_code(403);
    echo "Acceso denegado";
}
```

---

## 🎯 Estrategias de Depuración

### 1. Debugging en Producción (Sin Xdebug)

#### 🔍 Técnicas seguras para producción
```php
<?php
// debug/production_debug.php
class ProductionDebug
{
    public static function logRequest()
    {
        $requestInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => self::getClientIP(),
            'get_data' => $_GET,
            'post_data' => self::sanitizeData($_POST),
            'session_data' => $_SESSION ?? []
        ];
        
        Logger::info('Request procesado', $requestInfo);
    }
    
    public static function logMemoryUsage()
    {
        $memory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        Logger::info('Uso de memoria', [
            'current' => round($memory / 1024 / 1024, 2) . ' MB',
            'peak' => round($peakMemory / 1024 / 1024, 2) . ' MB'
        ]);
    }
    
    private static function sanitizeData($data)
    {
        // Remover datos sensibles
        $sensitive = ['password', 'pass', 'token', 'secret'];
        foreach ($sensitive as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        return $data;
    }
    
    private static function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
```

### 2. Monitor de Salud de la Aplicación

#### 🏥 Health check automático
```php
<?php
// debug/health_monitor.php
class HealthMonitor
{
    public function checkHealth()
    {
        $health = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'OK',
            'checks' => []
        ];
        
        // Verificar PHP
        $health['checks']['php'] = $this->checkPHP();
        
        // Verificar base de datos
        $health['checks']['database'] = $this->checkDatabase();
        
        // Verificar archivos críticos
        $health['checks']['files'] = $this->checkFiles();
        
        // Verificar permisos
        $health['checks']['permissions'] = $this->checkPermissions();
        
        // Determinar estado general
        $failedChecks = array_filter($health['checks'], function($check) {
            return $check['status'] !== 'OK';
        });
        
        if (!empty($failedChecks)) {
            $health['status'] = 'ERROR';
            Logger::error('Health check fallido', $health);
        }
        
        return $health;
    }
    
    private function checkPHP()
    {
        return [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'status' => 'OK'
        ];
    }
    
    private function checkDatabase()
    {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=comercial_elroble", "root", "");
            $stmt = $pdo->query("SELECT 1");
            return ['status' => 'OK', 'connection' => 'OK'];
        } catch (Exception $e) {
            return ['status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }
    
    private function checkFiles()
    {
        $criticalFiles = [
            '../config/database.php' => 'Configuración DB',
            '../src/App/bootstrap.php' => 'Bootstrap',
            '../vendor/autoload.php' => 'Composer Autoload'
        ];
        
        $results = [];
        foreach ($criticalFiles as $file => $description) {
            if (file_exists($file)) {
                $results[$description] = 'OK';
            } else {
                $results[$description] = 'MISSING';
            }
        }
        
        return $results;
    }
    
    private function checkPermissions()
    {
        $writableDirs = [
            '../logs' => 'Logs directory',
            '../storage' => 'Storage directory'
        ];
        
        $results = [];
        foreach ($writableDirs as $dir => $description) {
            if (is_writable($dir)) {
                $results[$description] = 'OK';
            } else {
                $results[$description] = 'NOT_WRITABLE';
            }
        }
        
        return $results;
    }
}
```

---

## 🐞 Casos Comunes de Debugging

### 1. Error 500 - Internal Server Error

#### 🔍 Pasos para diagnosticar
```bash
# 1. Verificar logs de Apache
tail -f /var/log/apache2/error.log

# 2. Verificar logs de PHP
tail -f /var/log/php/error.log

# 3. Verificar permisos
ls -la /ruta/a/tu/proyecto/
chmod 755 /ruta/a/tu/proyecto/
chmod 644 /ruta/a/tu/proyecto/*.php

# 4. Verificar sintaxis PHP
php -l /ruta/a/tu/proyecto/index.php

# 5. Verificar configuración de Apache
apache2ctl configtest
```

### 2. Problemas de Conexión a Base de Datos

#### 🗄️ Debugging de DB
```php
<?php
// debug/db_debug.php
function debugDatabaseConnection()
{
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=comercial_elroble", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Conexión exitosa\n";
        
        // Verificar tablas críticas
        $tables = ['usuarios', 'clientes', 'proyectos', 'tareas'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ Tabla $table: $count registros\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Error de conexión: " . $e->getMessage() . "\n";
        
        // Verificar credenciales
        echo "Host: localhost\n";
        echo "Usuario: root\n";
        echo "Base de datos: comercial_elroble\n";
    }
}
```

### 3. Problemas de Memoria

#### 💾 Analizar uso de memoria
```php
<?php
// debug/memory_debug.php
function debugMemory()
{
    echo "=== ANÁLISIS DE MEMORIA ===\n";
    echo "Memoria actual: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    echo "Memoria pico: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
    echo "Límite: " . ini_get('memory_limit') . "\n";
    
    if (memory_get_usage() / 1024 / 1024 > 100) {
        echo "⚠️ Uso de memoria alto!\n";
        
        // Generar reporte de objetos
        $objects = get_declared_classes();
        echo "Clases cargadas: " . count($objects) . "\n";
    }
}
```

---

## 📱 Comandos Útiles para Production Debugging

### 🖥️ Comandos de Linux para debugging
```bash
# Monitoreo en tiempo real
tail -f /var/log/apache2/error.log
tail -f /var/log/php/error.log
htop

# Verificar estado de Apache
systemctl status apache2
apache2ctl -M  # Ver módulos cargados
apache2ctl -t  # Test de configuración

# Verificar PHP
php -v
php -m  # Ver módulos PHP
php --ini  # Ver archivos de configuración

# Monitorear recursos
free -h
df -h
iotop

# Ver procesos PHP
ps aux | grep apache2
ps aux | grep php

# Analizar logs
grep "ERROR" /var/log/php/error.log | tail -20
awk '$4 >= 500' /var/log/apache2/access.log | tail -10
```

---

## 🚀 Próximos Pasos Recomendados

1. **Implementar el sistema de logging**
2. **Configurar el panel de monitoreo**
3. **Establecer alertas automáticas**
4. **Documentar tus propios casos específicos**
5. **Crear backups automáticos de logs**

¡Con estas herramientas podrás identificar y resolver la mayoría de problemas en producción de manera eficiente!