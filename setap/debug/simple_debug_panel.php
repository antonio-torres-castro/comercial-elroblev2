<?php
/**
 * Panel web de debugging simple para acceso directo desde navegador
 * Uso: http://tudominio.com/debug/simple_debug.php
 * 
 * IMPORTANTE: Restringe el acceso por IP para seguridad
 */

// Configuración de seguridad - Solo IPs autorizadas
$allowedIPs = [
    '127.0.0.1',
    'localhost',
    // Agrega tu IP pública aquí:
    // 'TU_IP_PUBLICA_AQUI'
];

// Verificar IP
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    die('<h1>403 - Acceso Denegado</h1><p>Esta herramienta de debugging solo está disponible para IPs autorizadas.</p>');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Debug - Comercial El Roble</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: #f5f5f5; 
            padding: 20px; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header { 
            background: #2c3e50; 
            color: white; 
            padding: 20px; 
            border-radius: 8px 8px 0 0;
        }
        .section { 
            padding: 20px; 
            border-bottom: 1px solid #eee; 
        }
        .section:last-child { border-bottom: none; }
        .status-ok { 
            color: #27ae60; 
            font-weight: bold; 
        }
        .status-error { 
            color: #e74c3c; 
            font-weight: bold; 
        }
        .status-warning { 
            color: #f39c12; 
            font-weight: bold; 
        }
        .log-content { 
            background: #2c3e50; 
            color: #ecf0f1; 
            padding: 15px; 
            border-radius: 4px; 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            max-height: 300px; 
            overflow-y: auto; 
            white-space: pre-wrap;
        }
        .button { 
            background: #3498db; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 5px; 
        }
        .button:hover { background: #2980b9; }
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        .card { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 4px; 
            border: 1px solid #dee2e6;
        }
        .metric { 
            font-size: 24px; 
            font-weight: bold; 
            margin: 10px 0; 
        }
        .refresh-btn { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            background: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Panel de Debug - Comercial El Roble</h1>
            <p>Tu IP: <?= $clientIP ?> | Última actualización: <?= date('Y-m-d H:i:s') ?></p>
            <button class="button refresh-btn" onclick="location.reload()">🔄 Actualizar</button>
        </div>

        <div class="section">
            <h2>📊 Estado General del Sistema</h2>
            <div class="grid">
                <?php
                // Información básica del sistema
                $memoryUsage = memory_get_usage(true);
                $memoryLimit = ini_get('memory_limit');
                $memoryPercent = str_replace('M', '', $memoryLimit) * 1024 * 1024;
                $usagePercent = round(($memoryUsage / $memoryPercent) * 100, 1);
                
                $memoryStatus = $usagePercent < 70 ? 'status-ok' : ($usagePercent < 85 ? 'status-warning' : 'status-error');
                ?>
                
                <div class="card">
                    <h3>💾 Memoria</h3>
                    <div class="metric <?= $memoryStatus ?>">
                        <?= round($memoryUsage / 1024 / 1024, 1) ?> MB
                    </div>
                    <p>Límite: <?= $memoryLimit ?> (<?= $usagePercent ?>%)</p>
                </div>

                <?php
                // Tiempo de ejecución
                $execTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
                $timeStatus = $execTime < 2 ? 'status-ok' : ($execTime < 5 ? 'status-warning' : 'status-error');
                ?>
                
                <div class="card">
                    <h3>⚡ Tiempo de Respuesta</h3>
                    <div class="metric <?= $timeStatus ?>">
                        <?= round($execTime * 1000, 1) ?> ms
                    </div>
                    <p>Tiempo de carga de esta página</p>
                </div>

                <?php
                // PHP Version
                $phpVersion = PHP_VERSION;
                $phpStatus = version_compare($phpVersion, '7.4.0', '>=') ? 'status-ok' : 'status-warning';
                ?>
                
                <div class="card">
                    <h3>🐘 PHP</h3>
                    <div class="metric <?= $phpStatus ?>">
                        <?= $phpVersion ?>
                    </div>
                    <p><?= version_compare($phpVersion, '7.4.0', '>=') ? 'Versión actualizada' : 'Considerar actualizar PHP' ?></p>
                </div>

                <?php
                // Estado de la base de datos
                try {
                    include_once '../config/database.php';
                    if (defined('DB_HOST')) {
                        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
                        $userCount = $stmt->fetchColumn();
                        $dbStatus = 'status-ok';
                        $dbText = "✅ Conectado ($userCount usuarios)";
                    } else {
                        $dbStatus = 'status-error';
                        $dbText = "❌ Configuración DB incompleta";
                    }
                } catch (Exception $e) {
                    $dbStatus = 'status-error';
                    $dbText = "❌ Error de conexión";
                }
                ?>
                
                <div class="card">
                    <h3>🗄️ Base de Datos</h3>
                    <div class="metric <?= $dbStatus ?>">
                        <?= $dbText ?>
                    </div>
                    <p>Estado de la conexión</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>📝 Logs Recientes</h2>
            
            <h3>Errores PHP</h3>
            <div class="log-content">
                <?php
                $errorLog = ini_get('error_log');
                if ($errorLog && file_exists($errorLog)) {
                    $lines = file($errorLog);
                    $recentErrors = array_slice($lines, -20);
                    echo empty($recentErrors) ? "✅ No hay errores recientes" : implode('', $recentErrors);
                } else {
                    echo "❌ No se encontró archivo de errores PHP";
                }
                ?>
            </div>

            <h3>Logs de Apache</h3>
            <div class="log-content">
                <?php
                $apacheErrorLog = '/var/log/apache2/error.log';
                if (file_exists($apacheErrorLog)) {
                    $lines = file($apacheErrorLog);
                    $recentErrors = array_slice($lines, -15);
                    echo empty($recentErrors) ? "✅ No hay errores recientes" : implode('', $recentErrors);
                } else {
                    echo "❌ No se encontró log de errores de Apache";
                }
                ?>
            </div>

            <h3>Logs de la Aplicación</h3>
            <div class="log-content">
                <?php
                $appLog = __DIR__ . '/../logs/app.log';
                if (file_exists($appLog)) {
                    $lines = file($appLog);
                    $recentLogs = array_slice($lines, -20);
                    echo empty($recentLogs) ? "✅ No hay logs recientes" : implode('', $recentLogs);
                } else {
                    echo "ℹ️ No se encontró log de aplicación (aún no configurado)";
                }
                ?>
            </div>
        </div>

        <div class="section">
            <h2>🔧 Configuración de PHP</h2>
            <div class="grid">
                <div class="card">
                    <h4>Configuración de Errores</h4>
                    <p><strong>Display Errors:</strong> <?= ini_get('display_errors') ? '<span class="status-warning">ON ⚠️</span>' : '<span class="status-ok">OFF ✅</span>' ?></p>
                    <p><strong>Log Errors:</strong> <?= ini_get('log_errors') ? '<span class="status-ok">ON ✅</span>' : '<span class="status-error">OFF ❌</span>' ?></p>
                    <p><strong>Error Reporting:</strong> <?= ini_get('error_reporting') ?></p>
                </div>

                <div class="card">
                    <h4>Límites del Sistema</h4>
                    <p><strong>Memory Limit:</strong> <?= ini_get('memory_limit') ?></p>
                    <p><strong>Max Execution Time:</strong> <?= ini_get('max_execution_time') ?>s</p>
                    <p><strong>Upload Max Size:</strong> <?= ini_get('upload_max_filesize') ?></p>
                </div>

                <div class="card">
                    <h4>Extensiones Críticas</h4>
                    <?php
                    $criticalExts = ['pdo', 'pdo_mysql', 'curl', 'openssl', 'mbstring'];
                    foreach ($criticalExts as $ext) {
                        $status = extension_loaded($ext) ? '✅' : '❌';
                        echo "<p><strong>$ext:</strong> $status</p>";
                    }
                    ?>
                </div>

                <div class="card">
                    <h4>Estado de Archivos</h4>
                    <?php
                    $criticalFiles = [
                        '../public/index.php' => 'Index',
                        '../vendor/autoload.php' => 'Autoload',
                        '../config/database.php' => 'DB Config'
                    ];
                    
                    foreach ($criticalFiles as $file => $name) {
                        $status = file_exists($file) ? '✅' : '❌';
                        echo "<p><strong>$name:</strong> $status</p>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>🚀 Herramientas Adicionales</h2>
            <button class="button" onclick="runDiagnostic()">🔍 Ejecutar Diagnóstico Completo</button>
            <button class="button" onclick="downloadReport()">📥 Descargar Reporte</button>
            <button class="button" onclick="clearLogs()">🗑️ Limpiar Logs</button>
            
            <div id="diagnostic-result" style="margin-top: 20px;"></div>
        </div>
    </div>

    <script>
        function runDiagnostic() {
            document.getElementById('diagnostic-result').innerHTML = 'Ejecutando diagnóstico...';
            
            fetch('./production_debug_tool.php?ajax=1')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('diagnostic-result').innerHTML = 
                        '<pre style="background:#f5f5f5;padding:15px;border-radius:4px;">' + data + '</pre>';
                })
                .catch(error => {
                    document.getElementById('diagnostic-result').innerHTML = 
                        'Error: ' + error;
                });
        }

        function downloadReport() {
            window.open('./production_debug_tool.php?report=1', '_blank');
        }

        function clearLogs() {
            if (confirm('¿Estás seguro de que quieres limpiar los logs? Esta acción no se puede deshacer.')) {
                // Implementar limpieza de logs
                alert('Función de limpieza de logs no implementada aún.');
            }
        }

        // Auto-refresh cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>