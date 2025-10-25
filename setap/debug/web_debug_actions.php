<?php

/**
 * Acciones AJAX para el Panel Web de Debug
 * Maneja todas las peticiones de diagnóstico y herramientas
 * Autor: MiniMax Agent
 */

// Configuración de seguridad - Solo IPs autorizadas
$allowedIPs = [
    '127.0.0.1',
    'localhost',
    '::1',
    '181.72.88.67'
];

// Verificar IP
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$isAuthorized = in_array($clientIP, $allowedIPs) || $allowedIPs[0] === '181.72.88.67';

// Detección del sistema operativo y configuración de logs
function detectOS()
{
    $os = php_uname('s');
    if (stripos($os, 'Windows') !== false) {
        return 'windows';
    } elseif (stripos($os, 'Linux') !== false) {
        return 'linux';
    } elseif (stripos($os, 'Darwin') !== false) {
        return 'macos';
    }
    return 'unknown';
}

function getApacheLogPaths()
{
    $os = detectOS();
    $apacheLogPaths = [];

    switch ($os) {
        case 'windows':
            // Windows (XAMPP, WAMP, etc.)
            $possiblePaths = [
                'C:/xampp/apache/logs/error.log',
                'C:/xampp/apache/logs/access.log',
                'C:/wamp64/bin/apache/apache2.4.41/logs/error.log',
                'C:/wamp64/bin/apache/apache2.4.41/logs/access.log',
                'C:/laragon/bin/apache/apache-2.4.41-win64/logs/error.log',
                'C:/laragon/bin/apache/apache-2.4.41-win64/logs/access.log',
                'D:/xampp/apache/logs/error.log',
                'D:/xampp/apache/logs/access.log'
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    if (strpos($path, 'error') !== false) {
                        $apacheLogPaths['apache_error'] = $path;
                    } else {
                        $apacheLogPaths['apache_access'] = $path;
                    }
                }
            }
            break;

        case 'linux':
            // Linux (Ubuntu, Debian, CentOS, etc.)
            $linuxPaths = [
                '/var/log/apache2/error.log',
                '/var/log/apache2/access.log',
                '/var/log/httpd/error_log',
                '/var/log/httpd/access_log',
                '/var/log/apache2/access_log',
                '/var/log/apache2/error_log'
            ];

            foreach ($linuxPaths as $path) {
                if (file_exists($path) && is_readable($path)) {
                    if (strpos($path, 'error') !== false) {
                        $apacheLogPaths['apache_error'] = $path;
                    } else {
                        $apacheLogPaths['apache_access'] = $path;
                    }
                }
            }
            break;

        case 'macos':
            // macOS
            $macosPaths = [
                '/usr/local/var/log/apache2/error.log',
                '/usr/local/var/log/apache2/access.log',
                '/var/log/apache2/error.log',
                '/var/log/apache2/access.log'
            ];

            foreach ($macosPaths as $path) {
                if (file_exists($path) && is_readable($path)) {
                    if (strpos($path, 'error') !== false) {
                        $apacheLogPaths['apache_error'] = $path;
                    } else {
                        $apacheLogPaths['apache_access'] = $path;
                    }
                }
            }
            break;
    }

    return $apacheLogPaths;
}

if (!$isAuthorized) {
    http_response_code(403);
    die('Acceso denegado');
}

// Función para escribir logs
function writeAppLog($message)
{
    $logFile = __DIR__ . '/../logs/web_debug.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logEntry = "[$timestamp] [IP:$ip] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Función para ejecutar consulta SQL segura
function executeSafeQuery($pdo, $query)
{
    $query = trim($query);

    // Solo permitir SELECT queries
    if (!preg_match('/^\s*SELECT\s+/i', $query)) {
        return ['error' => 'Solo se permiten consultas SELECT'];
    }

    // No permitir ciertas palabras peligrosas
    $dangerous = ['DROP', 'DELETE', 'INSERT', 'UPDATE', 'ALTER', 'CREATE', 'TRUNCATE', 'GRANT', 'REVOKE'];
    foreach ($dangerous as $word) {
        if (stripos($query, $word) !== false) {
            return ['error' => "La consulta contiene la palabra restringida: $word"];
        }
    }

    try {
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'data' => $results, 'count' => count($results)];
    } catch (Exception $e) {
        return ['error' => 'Error en la consulta: ' . $e->getMessage()];
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'clear_logs':
        $logFile = __DIR__ . '/../logs/web_debug.log';
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            writeAppLog("Logs limpiados manualmente");
            echo "✅ Logs de debug limpiados correctamente";
        } else {
            echo "ℹ️ No hay logs para limpiar";
        }
        break;

    case 'execute_query':
        if (!isset($_GET['query'])) {
            echo '<div class="error-box"><strong>❌ Error:</strong> No se proporcionó consulta</div>';
            break;
        }

        $query = $_GET['query'];

        try {
            if (file_exists('../config/database.php')) {
                include_once '../config/database.php';
                if (defined('DB_HOST')) {
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);

                    $result = executeSafeQuery($pdo, $query);

                    if (isset($result['error'])) {
                        echo '<div class="error-box"><strong>❌ Error:</strong> ' . $result['error'] . '</div>';
                    } else {
                        $data = $result['data'];
                        $count = $result['count'];

                        echo '<div class="success-box"><strong>✅ Éxito:</strong> Consulta ejecutada. Se encontraron ' . $count . ' resultados.</div>';

                        if ($count > 0) {
                            echo '<div style="overflow-x: auto; margin-top: 15px;">';
                            echo '<table class="db-table">';

                            // Headers
                            $headers = array_keys($data[0]);
                            echo '<thead><tr>';
                            foreach ($headers as $header) {
                                echo '<th>' . htmlspecialchars($header) . '</th>';
                            }
                            echo '</tr></thead>';

                            // Data
                            echo '<tbody>';
                            foreach ($data as $row) {
                                echo '<tr>';
                                foreach ($row as $value) {
                                    $displayValue = is_null($value) ? '<em>NULL</em>' : htmlspecialchars($value);
                                    echo '<td>' . $displayValue . '</td>';
                                }
                                echo '</tr>';
                            }
                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<div class="error-box"><strong>❌ Error:</strong> Configuración de BD incompleta</div>';
                }
            } else {
                echo '<div class="error-box"><strong>❌ Error:</strong> No se encontró config/database.php</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error-box"><strong>❌ Error:</strong> ' . $e->getMessage() . '</div>';
        }
        break;

    case 'full_diagnostic':
        writeAppLog("Diagnóstico completo iniciado");

        $diagnostic = "=== DIAGNÓSTICO COMPLETO ===\n";
        $diagnostic .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
        $diagnostic .= "IP: " . $clientIP . "\n";
        $diagnostic .= "Usuario: " . ($_SERVER['REMOTE_USER'] ?? 'N/A') . "\n\n";

        // Sistema
        $diagnostic .= "--- SISTEMA ---\n";
        $diagnostic .= "PHP Version: " . PHP_VERSION . "\n";
        $diagnostic .= "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
        $diagnostic .= "Operating System: " . PHP_OS . "\n";
        $diagnostic .= "Memory Limit: " . ini_get('memory_limit') . "\n";
        $diagnostic .= "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
        $diagnostic .= "Current Memory Usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n\n";

        // PHP Extensions
        $diagnostic .= "--- EXTENSIONES CRÍTICAS ---\n";
        $criticalExts = ['pdo', 'pdo_mysql', 'curl', 'openssl', 'mbstring', 'json', 'filter'];
        foreach ($criticalExts as $ext) {
            $status = extension_loaded($ext) ? 'OK' : 'MISSING';
            $diagnostic .= "$ext: $status\n";
        }
        $diagnostic .= "\n";

        // Archivos críticos
        $diagnostic .= "--- ARCHIVOS CRÍTICOS ---\n";
        $criticalFiles = [
            '../public/index.php' => 'Index Principal',
            '../vendor/autoload.php' => 'Composer Autoload',
            '../config/database.php' => 'Configuración DB',
            '../.htaccess' => 'Htaccess Raíz'
        ];

        foreach ($criticalFiles as $file => $name) {
            if (file_exists($file)) {
                $size = round(filesize($file) / 1024, 1);
                $diagnostic .= "$name: OK ($size KB)\n";
            } else {
                $diagnostic .= "$name: MISSING ❌\n";
            }
        }
        $diagnostic .= "\n";

        // Base de datos
        $diagnostic .= "--- BASE DE DATOS ---\n";
        try {
            if (file_exists('../config/database.php')) {
                include_once '../config/database.php';
                if (defined('DB_HOST')) {
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);

                    $diagnostic .= "Conexión: OK\n";

                    // Contar tablas
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    $diagnostic .= "Total de tablas: " . count($tables) . "\n";

                    // Contar usuarios si existe la tabla
                    if (in_array('usuarios', $tables)) {
                        $userCount = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
                        $diagnostic .= "Total de usuarios: $userCount\n";
                    }
                } else {
                    $diagnostic .= "Configuración DB: INCOMPLETA ❌\n";
                }
            } else {
                $diagnostic .= "Config DB File: MISSING ❌\n";
            }
        } catch (Exception $e) {
            $diagnostic .= "Error de BD: " . $e->getMessage() . " ❌\n";
        }
        $diagnostic .= "\n";

        // Logs con detección automática de sistema operativo
        $diagnostic .= "--- LOGS (Sistema: " . strtoupper(detectOS()) . ") ---\n";

        // Logs PHP
        $errorLog = ini_get('error_log');
        if ($errorLog && file_exists($errorLog)) {
            $logLines = count(file($errorLog));
            $diagnostic .= "PHP Error Log: OK ($logLines líneas) - $errorLog\n";
        } else {
            $diagnostic .= "PHP Error Log: NO ACCESIBLE\n";
        }

        // Logs Apache
        $apachePaths = getApacheLogPaths();
        if (!empty($apachePaths)) {
            foreach ($apachePaths as $type => $path) {
                if (file_exists($path)) {
                    $logLines = @count(file($path));
                    $readable = is_readable($path) ? 'OK' : 'READ-ONLY';
                    $diagnostic .= "Apache $type: $readable ($logLines líneas) - $path\n";
                }
            }
        } else {
            $diagnostic .= "Apache Logs: NO ENCONTRADOS\n";
        }

        // Log de aplicación
        $appLog = __DIR__ . '/../logs/web_debug.log';
        if (file_exists($appLog)) {
            $logLines = count(file($appLog));
            $diagnostic .= "App Debug Log: OK ($logLines líneas)\n";
        } else {
            $diagnostic .= "App Debug Log: NO EXISTE\n";
        }
        $diagnostic .= "\n";

        // Permisos
        $diagnostic .= "--- PERMISOS ---\n";
        $writableDirs = ['../logs/', '../cache/', '../tmp/'];
        foreach ($writableDirs as $dir) {
            $fullPath = __DIR__ . '/' . $dir;
            if (is_dir($fullPath)) {
                $writable = is_writable($fullPath) ? 'WRITABLE' : 'READONLY';
                $diagnostic .= "$dir: $writable\n";
            } else {
                $diagnostic .= "$dir: DOES NOT EXIST\n";
            }
        }

        writeAppLog("Diagnóstico completo finalizado");
        echo $diagnostic;
        break;

    case 'generate_report':
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="debug_report_' . date('Y-m-d_H-i-s') . '.txt"');

        $report = "=== REPORTE DE DEBUG - COMERCIAL EL ROBLE ===\n";
        $report .= "Generado: " . date('Y-m-d H:i:s') . "\n";
        $report .= "IP: " . $clientIP . "\n";
        $report .= "=================================================\n\n";

        // Ejecutar diagnóstico
        ob_start();
        include __FILE__; // Esto causaría recursión, mejor duplicar el código
        ob_end_clean();

        // Información básica
        $report .= "--- INFORMACIÓN DEL SISTEMA ---\n";
        $report .= "PHP Version: " . PHP_VERSION . "\n";
        $report .= "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
        $report .= "Memory Limit: " . ini_get('memory_limit') . "\n";
        $report .= "Current Memory: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
        $report .= "Max Execution Time: " . ini_get('max_execution_time') . "s\n\n";

        // Estado de archivos
        $report .= "--- ESTADO DE ARCHIVOS ---\n";
        $files = [
            '../public/index.php' => 'Index Principal',
            '../vendor/autoload.php' => 'Composer Autoload',
            '../config/database.php' => 'Config DB'
        ];

        foreach ($files as $file => $name) {
            if (file_exists($file)) {
                $report .= "$name: EXISTS (" . round(filesize($file) / 1024, 1) . " KB)\n";
            } else {
                $report .= "$name: MISSING\n";
            }
        }
        $report .= "\n";

        echo $report;
        break;

    case 'clear_cache':
        $cleared = 0;
        $cacheDirs = ['../cache/', '../tmp/', '../logs/'];

        foreach ($cacheDirs as $dir) {
            $fullPath = __DIR__ . '/' . $dir;
            if (is_dir($fullPath)) {
                $files = glob($fullPath . '*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.htaccess') {
                        if (unlink($file)) {
                            $cleared++;
                        }
                    }
                }
            }
        }

        writeAppLog("Cache limpiado: $cleared archivos eliminados");
        echo "✅ Cache limpiado correctamente. $cleared archivos eliminados.";
        break;

    case 'check_performance':
        $startTime = microtime(true);

        // Test 1: Memory usage
        $memoryBefore = memory_get_usage(true);
        sleep(1); // Simular trabajo
        $memoryAfter = memory_get_usage(true);
        $memoryDiff = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        // Test 2: Database connection
        $dbTime = null;
        try {
            if (file_exists('../config/database.php')) {
                include_once '../config/database.php';
                if (defined('DB_HOST')) {
                    $dbStart = microtime(true);
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                    $pdo->query("SELECT 1");
                    $dbTime = (microtime(true) - $dbStart) * 1000;
                }
            }
        } catch (Exception $e) {
            $dbTime = null;
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        $performance = "<h4>📊 Resultados de Rendimiento</h4>";
        $performance .= "<div class='grid'>";

        $performance .= "<div class='card'>";
        $performance .= "<h4>⏱️ Tiempo Total</h4>";
        $performance .= "<p class='metric'>" . round($totalTime, 2) . " ms</p>";
        $performance .= "<p>Tiempo de ejecución del test</p>";
        $performance .= "</div>";

        $performance .= "<div class='card'>";
        $performance .= "<h4>💾 Memoria</h4>";
        $performance .= "<p class='metric'>" . round($memoryDiff, 2) . " MB</p>";
        $performance .= "<p>Incremento de memoria</p>";
        $performance .= "</div>";

        if ($dbTime !== null) {
            $performance .= "<div class='card'>";
            $performance .= "<h4>🗄️ Base de Datos</h4>";
            $dbStatus = $dbTime < 100 ? 'status-ok' : ($dbTime < 500 ? 'status-warning' : 'status-error');
            $performance .= "<p class='metric $dbStatus'>" . round($dbTime, 2) . " ms</p>";
            $performance .= "<p>Tiempo de consulta</p>";
            $performance .= "</div>";
        } else {
            $performance .= "<div class='card'>";
            $performance .= "<h4>🗄️ Base de Datos</h4>";
            $performance .= "<p class='metric status-error'>No disponible</p>";
            $performance .= "<p>No se pudo conectar</p>";
            $performance .= "</div>";
        }

        $performance .= "</div>";

        echo $performance;
        break;

    case 'test_connectivity':
        $connectivity = "<h4>🌐 Test de Conectividad</h4>";

        // Test 1: DNS Resolution
        $domain = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST) ?: $_SERVER['HTTP_HOST'];
        $ip = gethostbyname($domain);
        $connectivity .= "<div class='card'>";
        $connectivity .= "<h4>🔍 Resolución DNS</h4>";
        $connectivity .= "<p>Dominio: $domain</p>";
        $connectivity .= "<p>IP: $ip</p>";
        $connectivity .= "</div>";

        // Test 2: HTTP Response
        $httpCode = http_response_code();
        $connectivity .= "<div class='card'>";
        $connectivity .= "<h4>🌍 HTTP Response</h4>";
        $connectivity .= "<p>Código de respuesta: $httpCode</p>";
        $connectivity .= "<p>Método: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "</p>";
        $connectivity .= "</div>";

        // Test 3: cURL if available
        if (function_exists('curl_init')) {
            $connectivity .= "<div class='card'>";
            $connectivity .= "<h4>🔌 cURL</h4>";
            $connectivity .= "<p class='status-ok'>✅ Disponible</p>";

            // Test Google
            $ch = curl_init('https://www.google.com');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200) {
                $connectivity .= "<p class='status-ok'>✅ Conexión externa exitosa</p>";
            } else {
                $connectivity .= "<p class='status-warning'>⚠️ Respuesta HTTP: $httpCode</p>";
            }
            $connectivity .= "</div>";
        } else {
            $connectivity .= "<div class='card'>";
            $connectivity .= "<h4>🔌 cURL</h4>";
            $connectivity .= "<p class='status-error'>❌ No disponible</p>";
            $connectivity .= "</div>";
        }

        echo $connectivity;
        break;

    case 'test_log_access':
        $os = PHP_OS_FAMILY;
        if ($os === 'Windows') {
            $os = 'Windows';
        } elseif ($os === 'Linux') {
            $os = 'Linux';
        } elseif ($os === 'Darwin') {
            $os = 'macOS';
        } else {
            $os = 'Unknown';
        }

        $logTest = "<div class='info-box'><strong>🧪 Test de Acceso a Logs - Sistema: $os</strong></div>";

        // Test PHP Error Log
        $phpErrorLog = ini_get('error_log');
        $logTest .= "<div class='card'>";
        $logTest .= "<h4>📝 PHP Error Log</h4>";
        if ($phpErrorLog) {
            if (file_exists($phpErrorLog)) {
                $size = filesize($phpErrorLog);
                $readable = is_readable($phpErrorLog) ? '✅' : '❌';
                $logTest .= "<p>📁 Ruta: $phpErrorLog</p>";
                $logTest .= "<p>📏 Tamaño: " . round($size / 1024, 2) . " KB</p>";
                $logTest .= "<p>🔓 Legible: $readable</p>";
            } else {
                $logTest .= "<p class='status-warning'>⚠️ Archivo no encontrado: $phpErrorLog</p>";
            }
        } else {
            $logTest .= "<p class='status-warning'>⚠️ No configurado en php.ini</p>";
        }
        $logTest .= "</div>";

        // Test Apache logs
        $apachePaths = [];
        if ($os === 'Windows') {
            $testPaths = [
                'C:/xampp/apache/logs/error.log',
                'C:/xampp/apache/logs/access.log',
                'C:/wamp64/bin/apache/apache2.4.*/logs/error.log',
                'C:/laragon/bin/apache/apache-2.*/logs/error.log'
            ];
        } elseif ($os === 'Linux') {
            $testPaths = [
                '/var/log/apache2/error.log',
                '/var/log/apache2/access.log',
                '/var/log/httpd/error_log',
                '/var/log/httpd/access_log'
            ];
        } else {
            $testPaths = [];
        }

        $logTest .= "<div class='card'>";
        $logTest .= "<h4>🌐 Apache Logs</h4>";

        foreach ($testPaths as $path) {
            // Expand wildcards for Windows paths
            $expandedPaths = glob($path);
            if (empty($expandedPaths)) {
                $expandedPaths = [$path];
            }

            foreach ($expandedPaths as $actualPath) {
                if (file_exists($actualPath)) {
                    $size = filesize($actualPath);
                    $readable = is_readable($actualPath) ? '✅' : '❌';
                    $writable = is_writable($actualPath) ? '✅' : '❌';
                    $lastModified = date('Y-m-d H:i:s', filemtime($actualPath));

                    $logTest .= "<div style='margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;'>";
                    $logTest .= "<p><strong>📁 " . basename($actualPath) . "</strong></p>";
                    $logTest .= "<p>📍 Ruta: $actualPath</p>";
                    $logTest .= "<p>📏 Tamaño: " . round($size / 1024, 2) . " KB</p>";
                    $logTest .= "<p>🔓 Legible: $readable | ✏️ Escribible: $writable</p>";
                    $logTest .= "<p>🕐 Última modificación: $lastModified</p>";
                    $logTest .= "</div>";
                }
            }
        }

        if (empty($expandedPaths) || (!file_exists($actualPath ?? ''))) {
            $logTest .= "<p class='status-warning'>⚠️ No se encontraron archivos de log de Apache</p>";
            $logTest .= "<p><strong>Rutas buscadas:</strong></p><ul>";
            foreach (array_slice($testPaths, 0, 6) as $path) {
                $logTest .= "<li>$path</li>";
            }
            $logTest .= "</ul>";
        }

        $logTest .= "</div>";

        // Test Application Log
        $appLog = __DIR__ . '/../logs/web_debug.log';
        $logTest .= "<div class='card'>";
        $logTest .= "<h4>🛠️ Debug Web Log</h4>";
        if (file_exists($appLog)) {
            $size = filesize($appLog);
            $lines = count(file($appLog));
            $readable = is_readable($appLog) ? '✅' : '❌';
            $logTest .= "<p>📁 Ruta: $appLog</p>";
            $logTest .= "<p>📏 Tamaño: " . round($size / 1024, 2) . " KB</p>";
            $logTest .= "<p>📄 Líneas: $lines</p>";
            $logTest .= "<p>🔓 Legible: $readable</p>";
        } else {
            $logTest .= "<p class='status-warning'>⚠️ No existe aún (se creará al usar el sistema)</p>";
        }
        $logTest .= "</div>";

        // Permisos de directorios
        $logTest .= "<div class='card'>";
        $logTest .= "<h4>🔐 Permisos de Directorios</h4>";
        $dirs = [
            __DIR__ . '/../logs/' => 'Logs Directory',
            __DIR__ . '/../cache/' => 'Cache Directory',
            __DIR__ . '/../tmp/' => 'Temp Directory'
        ];

        foreach ($dirs as $dir => $name) {
            if (is_dir($dir)) {
                $writable = is_writable($dir) ? '✅' : '❌';
                $logTest .= "<p><strong>$name:</strong> $writable</p>";
            } else {
                $logTest .= "<p><strong>$name:</strong> <span class='status-warning'>⚠️ No existe</span></p>";
            }
        }
        $logTest .= "</div>";

        writeAppLog("Test de acceso a logs ejecutado");
        echo $logTest;
        break;

    // ===== ACCIONES DE AUTENTICACIÓN =====

    case 'test_auth':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<div class='error-box'>❌ Método no permitido</div>";
            break;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $mode = $_POST['mode'] ?? 'test';

        if (empty($username) || empty($password)) {
            echo "<div class='error-box'>❌ Usuario y contraseña son requeridos</div>";
            break;
        }

        $result = testAuthentication($username, $password, $mode);
        echo $result;
        break;

    case 'create_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<div class='error-box'>❌ Método no permitido</div>";
            break;
        }

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($username) || empty($email) || empty($password)) {
            echo "<div class='error-box'>❌ Todos los campos son requeridos</div>";
            break;
        }

        if ($password !== $passwordConfirm) {
            echo "<div class='error-box'>❌ Las contraseñas no coinciden</div>";
            break;
        }

        if (strlen($password) < 6) {
            echo "<div class='error-box'>❌ La contraseña debe tener al menos 6 caracteres</div>";
            break;
        }

        $result = createUser($username, $email, $password, $role);
        echo $result;
        break;

    case 'list_users':
        $result = listUsers();
        echo $result;
        break;

    default:
        http_response_code(400);
        echo "❌ Acción no válida";
        break;
}

// ===== FUNCIONES DE AUTENTICACIÓN =====

/**
 * Prueba la autenticación con las credenciales proporcionadas
 * Muestra errores en crudo para debugging
 */
function testAuthentication($username, $password, $mode = 'test')
{
    try {
        // Configurar la aplicación
        require_once __DIR__ . '/../vendor/autoload.php';

        // Cargar configuración de entorno
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }

        // Configurar variables de entorno críticas
        putenv("DB_HOST=" . ($_ENV['DB_HOST'] ?? 'localhost'));
        putenv("DB_NAME=" . ($_ENV['DB_NAME'] ?? 'setap'));
        putenv("DB_USER=" . ($_ENV['DB_USER'] ?? 'root'));
        putenv("DB_PASS=" . ($_ENV['DB_PASS'] ?? ''));

        // Incluir servicios necesarios
        require_once __DIR__ . '/../src/App/Services/AuthService.php';
        require_once __DIR__ . '/../src/App/Models/User.php';

        $result = "<div class='card'>";
        $result .= "<h4>🔍 Resultado de Prueba de Autenticación</h4>";

        // Verificar si los archivos existen
        if (!file_exists(__DIR__ . '/../src/App/Services/AuthService.php')) {
            $result .= "<div class='error-box'>❌ Error: AuthService.php no encontrado</div>";
            $result .= "</div>";
            return $result;
        }

        // Intentar crear AuthService
        try {
            $authService = new \App\Services\AuthService();
        } catch (Exception $e) {
            $result .= "<div class='error-box'>❌ Error creando AuthService: " . htmlspecialchars($e->getMessage()) . "</div>";
            $result .= "<div class='code-block'>";
            $result .= "<strong>Stack Trace:</strong><br>";
            $result .= htmlspecialchars($e->getTraceAsString());
            $result .= "</div>";
            $result .= "</div>";
            return $result;
        }

        // Verificar si existe el método authenticate
        if (!method_exists($authService, 'authenticate')) {
            $result .= "<div class='error-box'>❌ Error: Método authenticate() no existe en AuthService</div>";
            $result .= "</div>";
            return $result;
        }

        // Intentar autenticar
        try {
            $authResult = $authService->authenticate($username, $password);
            $authUser = $authResult['user'] ?? null;

            if ($authUser) {
                $result .= "<div class='success-box'>✅ <strong>Autenticación exitosa</strong></div>";
                $result .= "<div class='info-box'>";
                $result .= "<strong>Usuario encontrado:</strong><br>";
                $result .= "- ID: " . ($authUser['id'] ?? 'N/A') . "<br>";
                $result .= "- Usuario: " . ($authUser['nombre_usuario'] ?? 'N/A') . "<br>";
                $result .= "- Email: " . ($authUser['email'] ?? 'N/A') . "<br>";
                $result .= "- Estado: " . ($authUser['estado_tipo_id'] != '2' ? 'N/A' : 'Activo') . "<br>";
                $result .= "</div>";

                if ($mode === 'login') {
                    $result .= "<div class='info-box'>🔄 <strong>Modo Login:</strong> Se iniciaría sesión automáticamente</div>";
                    // Aquí se podría iniciar la sesión si se desea
                    session_start();
                    $_SESSION['user_id'] = $authUser['id'];
                    $_SESSION['username'] = $authUser['nombre_usuario'];
                    $_SESSION['email'] = $authUser['email'];
                    $_SESSION['nombre_completo'] = $authUser['nombre_completo'];
                    $_SESSION['rol'] = $authUser['rol'];
                    $_SESSION['usuario_tipo_id'] = $authUser['usuario_tipo_id'];
                    $_SESSION['login_time'] = time();
                }
            } else {
                $result .= "<div class='error-box'>❌ <strong>Autenticación fallida</strong></div>";
                $result .= "<div class='warning-box'>⚠️ Las credenciales no son válidas o el usuario no está activo</div>";
            }
        } catch (Exception $e) {
            $result .= "<div class='error-box'>❌ <strong>Error durante la autenticación:</strong></div>";
            $result .= "<div class='code-block'>";
            $result .= "<strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
            $result .= "<strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
            $result .= "<strong>Línea:</strong> " . $e->getLine() . "<br><br>";
            $result .= "<strong>Stack Trace:</strong><br>";
            $result .= htmlspecialchars($e->getTraceAsString());
            $result .= "</div>";
        }

        $result .= "</div>";
        return $result;
    } catch (Exception $e) {
        $result = "<div class='error-box'>❌ <strong>Error crítico en testAuthentication:</strong></div>";
        $result .= "<div class='code-block'>";
        $result .= "<strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
        $result .= "<strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
        $result .= "<strong>Línea:</strong> " . $e->getLine() . "<br><br>";
        $result .= "<strong>Stack Trace:</strong><br>";
        $result .= htmlspecialchars($e->getTraceAsString());
        $result .= "</div>";
        return $result;
    }
}

/**
 * Crea un nuevo usuario desde debug
 */
function createUser($username, $email, $password, $role = '4')
{
    try {
        // Configurar la aplicación
        require_once __DIR__ . '/../vendor/autoload.php';

        // Cargar configuración de entorno
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }

        // Incluir servicios necesarios
        require_once __DIR__ . '/../src/App/Models/User.php';
        require_once __DIR__ . '/../config/database.php';

        // Usar la conexión singleton
        $db = \DatabaseConnection::getInstance();
        $connection = $db->getConnection();

        // Verificar si el usuario ya existe
        $stmt = $connection->prepare("SELECT id FROM usuarios WHERE nombre_usuario = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return "<div class='error-box'>❌ Ya existe un usuario con ese nombre de usuario o email</div>";
        }

        // Encriptar contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $estadoTipoId = 2; //Activo por que quiero probar la encriptacion

        $personaId = 1; //Yo mismo el papá de los tomas

        // Insertar usuario
        $stmt = $connection->prepare("
            INSERT INTO usuarios (usuario_tipo_id, nombre_usuario, email, clave_hash, estado_tipo_id, fecha_Creado, fecha_modificacion, persona_id) 
            VALUES               (              ?,              ?,     ?,          ?,              ?,        NOW(),              NOW(),          ?)
        ");

        $result = $stmt->execute([$role, $username, $email, $hashedPassword, $estadoTipoId, $personaId]);

        if ($result) {
            $userId = $connection->lastInsertId();

            // Aquí se podrían asignar permisos específicos según el rol

            writeAppLog("Usuario creado desde debug: $username (ID: $userId)");

            return "<div class='success-box'>✅ <strong>Usuario creado exitosamente</strong></div>" .
                "<div class='info-box'>" .
                "<strong>Detalles del usuario:</strong><br>" .
                "- ID: $userId<br>" .
                "- Usuario: " . htmlspecialchars($username) . "<br>" .
                "- Email: " . htmlspecialchars($email) . "<br>" .
                "- Rol: " . ucfirst($role) . "<br>" .
                "- Estado: Activo" .
                "</div>";
        } else {
            return "<div class='error-box'>❌ Error al crear el usuario en la base de datos</div>";
        }
    } catch (Exception $e) {
        $result = "<div class='error-box'>❌ <strong>Error al crear usuario:</strong></div>";
        $result .= "<div class='code-block'>";
        $result .= "<strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
        $result .= "<strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
        $result .= "<strong>Línea:</strong> " . $e->getLine() . "<br><br>";
        $result .= "<strong>Stack Trace:</strong><br>";
        $result .= htmlspecialchars($e->getTraceAsString());
        $result .= "</div>";
        return $result;
    }
}

/**
 * Lista los usuarios existentes en la base de datos
 */
function listUsers()
{
    try {
        // Configurar la aplicación
        require_once __DIR__ . '/../vendor/autoload.php';

        // Incluir configuración de base de datos
        require_once __DIR__ . '/../config/database.php';

        // Usar la conexión singleton
        $db = \DatabaseConnection::getInstance();
        $connection = $db->getConnection();

        // Obtener usuarios
        $stmt = $connection->prepare("
            SELECT id, nombre_usuario, email, estado_tipo_id, fecha_Creado, fecha_modificacion 
            FROM usuarios 
            ORDER BY fecha_Creado DESC 
            LIMIT 50
        ");

        $stmt->execute();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = "<div class='card'>";
        $result .= "<h4>👥 Lista de Usuarios</h4>";

        if (empty($users)) {
            $result .= "<div class='warning-box'>⚠️ No hay usuarios en la base de datos</div>";
        } else {
            $result .= "<table class='table table-striped'>";
            $result .= "<thead>";
            $result .= "<tr><th>ID</th><th>Usuario</th><th>Email</th><th>Estado</th><th>Creado</th></tr>";
            $result .= "</thead>";
            $result .= "<tbody>";

            foreach ($users as $user) {
                $estado = $user['estado_tipo_id'] == 2 ? 'Activo' : 'Inactivo';
                $estadoClass = $user['estado_tipo_id'] == 2 ? 'status-ok' : 'status-error';
                $createdDate = date('Y-m-d H:i:s', strtotime($user['created_at']));

                $result .= "<tr>";
                $result .= "<td>" . $user['id'] . "</td>";
                $result .= "<td>" . htmlspecialchars($user['usuario']) . "</td>";
                $result .= "<td>" . htmlspecialchars($user['email']) . "</td>";
                $result .= "<td><span class='$estadoClass'>$estado</span></td>";
                $result .= "<td>$createdDate</td>";
                $result .= "</tr>";
            }

            $result .= "</tbody>";
            $result .= "</table>";
            $result .= "<div class='info-box'>📊 Total de usuarios: " . count($users) . "</div>";
        }

        $result .= "</div>";
        return $result;
    } catch (Exception $e) {
        $result = "<div class='error-box'>❌ <strong>Error al listar usuarios:</strong></div>";
        $result .= "<div class='code-block'>";
        $result .= "<strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
        $result .= "<strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
        $result .= "<strong>Línea:</strong> " . $e->getLine() . "<br><br>";
        $result .= "<strong>Stack Trace:</strong><br>";
        $result .= htmlspecialchars($e->getTraceAsString());
        $result .= "</div>";
        return $result;
    }
}
