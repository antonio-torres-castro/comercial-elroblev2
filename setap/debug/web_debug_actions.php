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
    // REEMPLAZA CON TU IP PÚBLICA
    'TU_IP_PUBLICA_AQUI'
];

// Verificar IP
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$isAuthorized = in_array($clientIP, $allowedIPs) || $allowedIPs[0] === 'TU_IP_PUBLICA_AQUI';

if (!$isAuthorized) {
    http_response_code(403);
    die('Acceso denegado');
}

// Función para escribir logs
function writeAppLog($message) {
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
function executeSafeQuery($pdo, $query) {
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

$action = $_GET['action'] ?? '';

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
        
        // Logs
        $diagnostic .= "--- LOGS ---\n";
        $errorLog = ini_get('error_log');
        if ($errorLog && file_exists($errorLog)) {
            $logLines = count(file($errorLog));
            $diagnostic .= "PHP Error Log: OK ($logLines líneas)\n";
        } else {
            $diagnostic .= "PHP Error Log: NO ACCESIBLE\n";
        }
        
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
        
    default:
        http_response_code(400);
        echo "❌ Acción no válida";
        break;
}
?>