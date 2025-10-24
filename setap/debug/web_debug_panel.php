<?php
/**
 * Panel de Debug Web-Only para Producción
 * Diseñado para entornos sin acceso a consola
 * Autor: MiniMax Agent
 */

// Configuración de seguridad - Solo IPs autorizadas
$allowedIPs = [
    '127.0.0.1',
    'localhost',
    // Agrega tu IP pública aquí - REEMPLAZA ESTA IP
    'TU_IP_PUBLICA_AQUI'
];

// Verificar IP
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$isAuthorized = in_array($clientIP, $allowedIPs) || $allowedIPs[0] === 'TU_IP_PUBLICA_AQUI';

// Función para escribir logs personalizados
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

// Log de acceso
writeAppLog("Acceso al panel de debug - IP: $clientIP");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Panel Web de Debug - Solo para Producción</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px; 
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white; 
            padding: 30px; 
            position: relative;
        }
        .header h1 { margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .security-warning {
            background: #e74c3c;
            padding: 15px;
            text-align: center;
            color: white;
        }
        .section { 
            padding: 25px; 
            border-bottom: 1px solid #eee; 
        }
        .section:last-child { border-bottom: none; }
        .section h2 { 
            color: #2c3e50; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
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
            padding: 20px; 
            border-radius: 8px; 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            max-height: 400px; 
            overflow-y: auto; 
            white-space: pre-wrap;
            margin: 10px 0;
        }
        .button { 
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            margin: 5px; 
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        .button.danger { 
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        .button.success { 
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 20px; 
            margin: 20px 0;
        }
        .card { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            border: 1px solid #dee2e6;
            transition: transform 0.2s ease;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .card h3 { 
            color: #2c3e50; 
            margin-bottom: 15px; 
            font-size: 18px;
        }
        .metric { 
            font-size: 28px; 
            font-weight: bold; 
            margin: 10px 0; 
        }
        .progress-bar { 
            width: 100%; 
            height: 8px; 
            background: #ecf0f1; 
            border-radius: 4px; 
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill { 
            height: 100%; 
            transition: width 0.3s ease;
        }
        .progress-ok { background: linear-gradient(90deg, #27ae60, #2ecc71); }
        .progress-warning { background: linear-gradient(90deg, #f39c12, #f1c40f); }
        .progress-error { background: linear-gradient(90deg, #e74c3c, #c0392b); }
        .tab-container { 
            margin: 20px 0; 
        }
        .tabs { 
            display: flex; 
            border-bottom: 2px solid #dee2e6; 
        }
        .tab { 
            padding: 15px 25px; 
            cursor: pointer; 
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .tab.active { 
            border-bottom-color: #3498db; 
            background: #f8f9fa;
        }
        .tab-content { 
            display: none; 
            padding: 20px 0; 
        }
        .tab-content.active { 
            display: block; 
        }
        .db-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
            font-size: 14px;
        }
        .db-table th, .db-table td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #dee2e6; 
        }
        .db-table th { 
            background: #f8f9fa; 
            font-weight: 600; 
            color: #2c3e50;
        }
        .db-table tr:hover { background: #f1f3f4; }
        .refresh-btn { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            z-index: 1000;
        }
        .auto-refresh { 
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        .code-block { 
            background: #2c3e50; 
            color: #ecf0f1; 
            padding: 15px; 
            border-radius: 6px; 
            font-family: 'Courier New', monospace; 
            margin: 10px 0;
            overflow-x: auto;
        }
        .info-box { 
            background: #e8f4f8; 
            border-left: 4px solid #3498db; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 0 6px 6px 0;
        }
        .warning-box { 
            background: #fef9e7; 
            border-left: 4px solid #f39c12; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 0 6px 6px 0;
        }
        .error-box { 
            background: #fdf2f2; 
            border-left: 4px solid #e74c3c; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 0 6px 6px 0;
        }
    </style>
</head>
<body>
    <?php if (!$isAuthorized): ?>
    <div class="container">
        <div class="security-warning">
            <h2>🚫 Acceso Denegado</h2>
            <p>Esta herramienta de debugging solo está disponible para IPs autorizadas.</p>
            <p>Tu IP: <strong><?= $clientIP ?></strong></p>
            <p>Para activar acceso, edita el archivo y agrega tu IP en la lista de IPs permitidas.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="container">
        <div class="header">
            <h1>🔧 Panel Web de Debug - Comercial El Roble</h1>
            <p>Tu IP: <?= $clientIP ?> | Última actualización: <?= date('Y-m-d H:i:s') ?> | Entorno: Producción Web-Only</p>
            <button class="button refresh-btn" onclick="location.reload()">🔄 Actualizar</button>
            <button class="button auto-refresh" onclick="toggleAutoRefresh()">⏰ Auto-Refresh: OFF</button>
        </div>

        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" onclick="showTab('dashboard')">📊 Dashboard</div>
                <div class="tab" onclick="showTab('logs')">📝 Logs</div>
                <div class="tab" onclick="showTab('database')">🗄️ Base de Datos</div>
                <div class="tab" onclick="showTab('php')">🐘 PHP</div>
                <div class="tab" onclick="showTab('tools')">🛠️ Herramientas</div>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <h2>📊 Estado General del Sistema</h2>
                
                <?php
                // Información básica del sistema
                $memoryUsage = memory_get_usage(true);
                $memoryLimit = ini_get('memory_limit');
                $memoryLimitBytes = parse_size($memoryLimit);
                $usagePercent = round(($memoryUsage / $memoryLimitBytes) * 100, 1);
                
                $memoryStatus = $usagePercent < 70 ? 'progress-ok' : ($usagePercent < 85 ? 'progress-warning' : 'progress-error');
                $memoryTextStatus = $usagePercent < 70 ? 'status-ok' : ($usagePercent < 85 ? 'status-warning' : 'status-error');
                
                function parse_size($size) {
                    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
                    $size = preg_replace('/[^0-9\.]/', '', $size);
                    if ($unit) {
                        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
                    } else {
                        return round($size);
                    }
                }
                
                // Tiempo de ejecución
                $execTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
                $timeStatus = $execTime < 2 ? 'status-ok' : ($execTime < 5 ? 'status-warning' : 'status-error');
                
                // Estado de la base de datos
                try {
                    if (file_exists('../config/database.php')) {
                        include_once '../config/database.php';
                        if (defined('DB_HOST')) {
                            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                            ]);
                            
                            // Contar usuarios
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuarios");
                            $userCount = $stmt->fetch()['count'];
                            
                            // Verificar última actividad
                            $stmt = $pdo->query("SELECT MAX(fecha_modificacion) as last_activity FROM usuarios");
                            $lastActivity = $stmt->fetch()['last_activity'];
                            
                            $dbStatus = 'status-ok';
                            $dbText = "✅ Conectado ($userCount usuarios)";
                        } else {
                            $dbStatus = 'status-error';
                            $dbText = "❌ Configuración DB incompleta";
                        }
                    } else {
                        $dbStatus = 'status-error';
                        $dbText = "❌ No se encontró config/database.php";
                    }
                } catch (Exception $e) {
                    $dbStatus = 'status-error';
                    $dbText = "❌ Error: " . $e->getMessage();
                }
                ?>
                
                <div class="grid">
                    <div class="card">
                        <h3>💾 Memoria</h3>
                        <div class="metric <?= $memoryTextStatus ?>">
                            <?= round($memoryUsage / 1024 / 1024, 1) ?> MB
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?= $memoryStatus ?>" style="width: <?= $usagePercent ?>%"></div>
                        </div>
                        <p>Límite: <?= $memoryLimit ?> (<?= $usagePercent ?>%)</p>
                    </div>

                    <div class="card">
                        <h3>⚡ Tiempo de Respuesta</h3>
                        <div class="metric <?= $timeStatus ?>">
                            <?= round($execTime * 1000, 1) ?> ms
                        </div>
                        <p>Tiempo de carga de esta página</p>
                    </div>

                    <div class="card">
                        <h3>🗄️ Base de Datos</h3>
                        <div class="metric <?= $dbStatus ?>">
                            <?= $dbText ?>
                        </div>
                        <p>Última actividad: <?= $lastActivity ?: 'N/A' ?></p>
                    </div>

                    <div class="card">
                        <h3>🌐 Servidor Web</h3>
                        <div class="metric status-ok">
                            ✅ Activo
                        </div>
                        <p><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido' ?></p>
                    </div>
                </div>
            </div>

            <!-- Logs Tab -->
            <div id="logs" class="tab-content">
                <h2>📝 Análisis de Logs</h2>
                
                <div class="info-box">
                    <strong>ℹ️ Información:</strong> Los logs se leen directamente desde los archivos del servidor web.
                </div>

                <h3>Errores PHP Recientes</h3>
                <div class="log-content">
                    <?php
                    $errorLog = ini_get('error_log');
                    if ($errorLog && file_exists($errorLog)) {
                        $lines = file($errorLog);
                        $recentErrors = array_slice($lines, -30);
                        echo empty($recentErrors) ? "✅ No hay errores recientes en PHP" : implode('', $recentErrors);
                    } else {
                        echo "ℹ️ Archivo de errores PHP no accesible o no configurado";
                    }
                    ?>
                </div>

                <h3>Logs de la Aplicación (Debug Web)</h3>
                <div class="log-content">
                    <?php
                    $appLog = __DIR__ . '/../logs/web_debug.log';
                    if (file_exists($appLog)) {
                        $lines = file($appLog);
                        $recentLogs = array_slice($lines, -50);
                        echo empty($recentLogs) ? "✅ No hay logs recientes" : implode('', $recentLogs);
                    } else {
                        echo "ℹ️ No se encontró log de aplicación";
                    }
                    ?>
                </div>

                <button class="button" onclick="refreshLogs()">🔄 Refrescar Logs</button>
                <button class="button danger" onclick="clearAppLogs()">🗑️ Limpiar Logs de Debug</button>
            </div>

            <!-- Database Tab -->
            <div id="database" class="tab-content">
                <h2>🗄️ Información de Base de Datos</h2>
                
                <?php if (isset($pdo)): ?>
                <div class="grid">
                    <div class="card">
                        <h3>📊 Estadísticas</h3>
                        <?php
                        try {
                            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                            echo "<p><strong>Total de tablas:</strong> " . count($tables) . "</p>";
                            
                            $tableInfo = [];
                            foreach ($tables as $table) {
                                $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                                $tableInfo[] = ['name' => $table, 'count' => $count];
                            }
                            
                            echo "<div class='progress-bar'>";
                            echo "<div class='progress-fill progress-ok' style='width: 100%'></div>";
                            echo "</div>";
                        } catch (Exception $e) {
                            echo "<p class='status-error'>Error: " . $e->getMessage() . "</p>";
                        }
                        ?>
                    </div>

                    <div class="card">
                        <h3>📋 Tablas Principales</h3>
                        <table class="db-table">
                            <thead>
                                <tr><th>Tabla</th><th>Registros</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($tableInfo, 0, 10) as $table): ?>
                                <tr>
                                    <td><?= htmlspecialchars($table['name']) ?></td>
                                    <td><?= number_format($table['count']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3>🔍 Consulta Personalizada</h3>
                    <form onsubmit="executeCustomQuery(event)">
                        <textarea id="customQuery" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Escribe tu consulta SQL aquí (solo SELECT)"></textarea>
                        <button type="submit" class="button" style="margin-top: 10px;">🚀 Ejecutar Consulta</button>
                    </form>
                    <div id="queryResult" style="margin-top: 15px;"></div>
                </div>
                <?php else: ?>
                <div class="error-box">
                    <strong>❌ Error:</strong> No se pudo conectar a la base de datos. Verifica la configuración en <code>config/database.php</code>
                </div>
                <?php endif; ?>
            </div>

            <!-- PHP Tab -->
            <div id="php" class="tab-content">
                <h2>🐘 Configuración PHP</h2>
                
                <div class="grid">
                    <div class="card">
                        <h4>Versiones</h4>
                        <p><strong>PHP:</strong> <?= PHP_VERSION ?></p>
                        <p><strong>SAPI:</strong> <?= php_sapi_name() ?></p>
                    </div>

                    <div class="card">
                        <h4>Límites del Sistema</h4>
                        <p><strong>Memory Limit:</strong> <?= ini_get('memory_limit') ?></p>
                        <p><strong>Max Execution Time:</strong> <?= ini_get('max_execution_time') ?>s</p>
                        <p><strong>Upload Max Size:</strong> <?= ini_get('upload_max_filesize') ?></p>
                    </div>

                    <div class="card">
                        <h4>Manejo de Errores</h4>
                        <p><strong>Display Errors:</strong> <?= ini_get('display_errors') ? '<span class="status-warning">ON ⚠️</span>' : '<span class="status-ok">OFF ✅</span>' ?></p>
                        <p><strong>Log Errors:</strong> <?= ini_get('log_errors') ? '<span class="status-ok">ON ✅</span>' : '<span class="status-error">OFF ❌</span>' ?></p>
                        <p><strong>Error Log:</strong> <?= ini_get('error_log') ?: 'No configurado' ?></p>
                    </div>

                    <div class="card">
                        <h4>Extensiones Críticas</h4>
                        <?php
                        $criticalExts = ['pdo', 'pdo_mysql', 'curl', 'openssl', 'mbstring', 'json', 'filter'];
                        foreach ($criticalExts as $ext) {
                            $status = extension_loaded($ext) ? '✅' : '❌';
                            $class = extension_loaded($ext) ? 'status-ok' : 'status-error';
                            echo "<p class='$class'><strong>$ext:</strong> $status</p>";
                        }
                        ?>
                    </div>
                </div>

                <div class="card">
                    <h4>Archivos Críticos</h4>
                    <?php
                    $criticalFiles = [
                        '../public/index.php' => 'Index Principal',
                        '../vendor/autoload.php' => 'Composer Autoload',
                        '../config/database.php' => 'Configuración DB',
                        '../config/app.php' => 'Configuración App',
                        '../.htaccess' => 'Htaccess Raíz'
                    ];
                    
                    foreach ($criticalFiles as $file => $name) {
                        $status = file_exists($file) ? '✅' : '❌';
                        $class = file_exists($file) ? 'status-ok' : 'status-error';
                        $size = file_exists($file) ? ' (' . round(filesize($file) / 1024, 1) . ' KB)' : '';
                        echo "<p class='$class'><strong>$name:</strong> $status $size</p>";
                    }
                    ?>
                </div>
            </div>

            <!-- Tools Tab -->
            <div id="tools" class="tab-content">
                <h2>🛠️ Herramientas de Diagnóstico</h2>
                
                <div class="grid">
                    <div class="card">
                        <h3>🔍 Diagnóstico Completo</h3>
                        <p>Ejecuta un análisis completo del sistema</p>
                        <button class="button" onclick="runFullDiagnostic()">🚀 Ejecutar Diagnóstico</button>
                        <div id="diagnosticResult"></div>
                    </div>

                    <div class="card">
                        <h3>📥 Generar Reporte</h3>
                        <p>Descarga un reporte completo del estado del sistema</p>
                        <button class="button success" onclick="generateReport()">📥 Descargar Reporte</button>
                    </div>

                    <div class="card">
                        <h3>🧹 Limpiar Cache</h3>
                        <p>Limpia archivos temporales y cache del sistema</p>
                        <button class="button danger" onclick="clearCache()">🗑️ Limpiar Cache</button>
                    </div>

                    <div class="card">
                        <h3>📊 Verificar Rendimiento</h3>
                        <p>Analiza el rendimiento de la aplicación</p>
                        <button class="button" onclick="checkPerformance()">⚡ Verificar Rendimiento</button>
                        <div id="performanceResult"></div>
                    </div>
                </div>

                <div class="card">
                    <h3>🧪 Test de Conectividad</h3>
                    <p>Prueba la conectividad a servicios externos</p>
                    <button class="button" onclick="testConnectivity()">🌐 Probar Conectividad</button>
                    <div id="connectivityResult"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let autoRefreshInterval = null;

        function showTab(tabName) {
            // Ocultar todas las pestañas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Desactivar todas las pestañas
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar la pestaña seleccionada
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function toggleAutoRefresh() {
            const btn = event.target;
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                btn.textContent = '⏰ Auto-Refresh: OFF';
                btn.style.background = 'linear-gradient(135deg, #9b59b6, #8e44ad)';
            } else {
                autoRefreshInterval = setInterval(() => {
                    location.reload();
                }, 30000); // 30 segundos
                btn.textContent = '⏰ Auto-Refresh: ON';
                btn.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
            }
        }

        function refreshLogs() {
            location.reload();
        }

        function clearAppLogs() {
            if (confirm('¿Estás seguro de que quieres limpiar todos los logs de debug?')) {
                fetch('./web_debug_actions.php?action=clear_logs')
                    .then(response => response.text())
                    .then(data => {
                        alert('Logs limpiados correctamente');
                        location.reload();
                    })
                    .catch(error => {
                        alert('Error al limpiar logs: ' + error);
                    });
            }
        }

        function executeCustomQuery(event) {
            event.preventDefault();
            const query = document.getElementById('customQuery').value;
            const resultDiv = document.getElementById('queryResult');
            
            if (!query.trim().toUpperCase().startsWith('SELECT')) {
                resultDiv.innerHTML = '<div class="error-box"><strong>❌ Error:</strong> Solo se permiten consultas SELECT por seguridad.</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="info-box">Ejecutando consulta...</div>';
            
            fetch('./web_debug_actions.php?action=execute_query&query=' + encodeURIComponent(query))
                .then(response => response.text())
                .then(data => {
                    resultDiv.innerHTML = data;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error-box"><strong>❌ Error:</strong> ' + error + '</div>';
                });
        }

        function runFullDiagnostic() {
            const resultDiv = document.getElementById('diagnosticResult');
            resultDiv.innerHTML = '<div class="info-box">Ejecutando diagnóstico completo...</div>';
            
            fetch('./web_debug_actions.php?action=full_diagnostic')
                .then(response => response.text())
                .then(data => {
                    resultDiv.innerHTML = '<div class="code-block">' + data + '</div>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error-box"><strong>❌ Error:</strong> ' + error + '</div>';
                });
        }

        function generateReport() {
            window.open('./web_debug_actions.php?action=generate_report', '_blank');
        }

        function clearCache() {
            if (confirm('¿Estás seguro de que quieres limpiar el cache?')) {
                fetch('./web_debug_actions.php?action=clear_cache')
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                    })
                    .catch(error => {
                        alert('Error: ' + error);
                    });
            }
        }

        function checkPerformance() {
            const resultDiv = document.getElementById('performanceResult');
            resultDiv.innerHTML = '<div class="info-box">Analizando rendimiento...</div>';
            
            fetch('./web_debug_actions.php?action=check_performance')
                .then(response => response.text())
                .then(data => {
                    resultDiv.innerHTML = data;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error-box"><strong>❌ Error:</strong> ' + error + '</div>';
                });
        }

        function testConnectivity() {
            const resultDiv = document.getElementById('connectivityResult');
            resultDiv.innerHTML = '<div class="info-box">Probando conectividad...</div>';
            
            fetch('./web_debug_actions.php?action=test_connectivity')
                .then(response => response.text())
                .then(data => {
                    resultDiv.innerHTML = data;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error-box"><strong>❌ Error:</strong> ' + error + '</div>';
                });
        }
    </script>
    <?php endif; ?>
</body>
</html>