<?php

/**
 * Visor de Logs Web-Only para Producción
 * Lee logs del servidor sin necesidad de consola
 * 
 */

// Configuración de seguridad
$allowedIPs = [
    '127.0.0.1',
    'localhost',
    '::1',
    '181.72.88.67'
];

$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$isAuthorized = in_array($clientIP, $allowedIPs) || $allowedIPs[0] === '181.72.88.67';

if (!$isAuthorized) {
    http_response_code(403);
    die('<h1>403 - Acceso Denegado</h1><p>Esta herramienta solo está disponible para IPs autorizadas.</p>');
}

function writeAppLog($message)
{
    $logFile = __DIR__ . '/../logs/web_debug.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logEntry = "[$timestamp] [IP:$ip] [LOG_VIEWER] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

writeAppLog("Acceso al visor de logs");

// Obtener parámetros
$logType = $_GET['type'] ?? 'app';
$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 50;
$search = $_GET['search'] ?? '';
$highlight = $_GET['highlight'] ?? '';

$logFiles = [
    'app' => __DIR__ . '/../logs/web_debug.log',
    'php_error' => ini_get('error_log'),
    'apache_error' => '/var/log/apache2/error.log',
    'apache_access' => '/var/log/apache2/access.log'
];

$currentLog = $logFiles[$logType] ?? $logFiles['app'];
$logTitle = ucfirst(str_replace('_', ' ', $logType));

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 Visor de Logs - Comercial El Roble</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
        }

        .controls {
            background: #ecf0f1;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        label {
            font-weight: 600;
            color: #2c3e50;
        }

        select,
        input,
        button {
            padding: 8px 12px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            background: #3498db;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #2980b9;
        }

        .log-content {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 70vh;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.4;
        }

        .log-line {
            padding: 2px 0;
            border-bottom: 1px solid #34495e;
        }

        .log-error {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .log-warning {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .log-info {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .log-success {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .highlight {
            background: #f39c12 !important;
            color: #2c3e50 !important;
            font-weight: bold;
        }

        .status-bar {
            background: #34495e;
            color: #ecf0f1;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <button class="back-btn" onclick="window.close()">← Volver</button>

    <div class="container">
        <div class="header">
            <h1>📝 Visor de Logs - <?= $logTitle ?></h1>
            <p>IP: <?= $clientIP ?> | Archivo: <?= basename($currentLog) ?> | Última actualización: <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <div class="controls">
            <div class="control-group">
                <label for="logType">Tipo de Log:</label>
                <select id="logType" onchange="changeLogType()">
                    <option value="app" <?= $logType === 'app' ? 'selected' : '' ?>>Aplicación Debug</option>
                    <option value="php_error" <?= $logType === 'php_error' ? 'selected' : '' ?>>Errores PHP</option>
                    <option value="apache_error" <?= $logType === 'apache_error' ? 'selected' : '' ?>>Errores Apache</option>
                    <option value="apache_access" <?= $logType === 'apache_access' ? 'selected' : '' ?>>Accesos Apache</option>
                </select>
            </div>

            <div class="control-group">
                <label for="lines">Líneas:</label>
                <select id="lines" onchange="changeLines()">
                    <option value="25" <?= $lines == 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $lines == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $lines == 100 ? 'selected' : '' ?>>100</option>
                    <option value="200" <?= $lines == 200 ? 'selected' : '' ?>>200</option>
                    <option value="500" <?= $lines == 500 ? 'selected' : '' ?>>500</option>
                    <option value="0" <?= $lines == 0 ? 'selected' : '' ?>>Todas</option>
                </select>
            </div>

            <div class="control-group">
                <label for="search">Buscar:</label>
                <input type="text" id="search" value="<?= htmlspecialchars($search) ?>" placeholder="Término de búsqueda..." onkeyup="searchLogs()">
            </div>

            <div class="control-group">
                <label for="highlight">Resaltar:</label>
                <input type="text" id="highlight" value="<?= htmlspecialchars($highlight) ?>" placeholder="Texto a resaltar..." onkeyup="highlightLogs()">
            </div>

            <button onclick="refreshLogs()">🔄 Actualizar</button>
            <button onclick="downloadLog()">💾 Descargar</button>
        </div>

        <div class="status-bar">
            <div>
                <span id="statusText">Cargando logs...</span>
            </div>
            <div>
                <span id="lineCount">0 líneas</span>
            </div>
        </div>

        <div class="log-content" id="logContent">
            <?php
            if (!file_exists($currentLog)) {
                echo "❌ Archivo de log no encontrado: " . htmlspecialchars($currentLog);
            } else {
                $allLines = file($currentLog);
                $logLines = $lines > 0 ? array_slice($allLines, -$lines) : $allLines;

                // Aplicar filtros
                if (!empty($search)) {
                    $logLines = array_filter($logLines, function ($line) use ($search) {
                        return stripos($line, $search) !== false;
                    });
                }

                $logLines = array_values($logLines); // Reindexar array
                $lineCount = count($logLines);

                echo "<script>document.getElementById('lineCount').textContent = '$lineCount líneas';</script>";

                if (empty($logLines)) {
                    echo "ℹ️ No se encontraron líneas que coincidan con los filtros";
                } else {
                    foreach ($logLines as $index => $line) {
                        $lineNumber = count($allLines) - count($logLines) + $index + 1;
                        $trimmedLine = trim($line);

                        // Determinar tipo de log
                        $logClass = 'log-line';
                        if (stripos($trimmedLine, 'error') !== false) {
                            $logClass .= ' log-error';
                        } elseif (stripos($trimmedLine, 'warning') !== false) {
                            $logClass .= ' log-warning';
                        } elseif (stripos($trimmedLine, 'info') !== false || stripos($trimmedLine, 'notice') !== false) {
                            $logClass .= ' log-info';
                        } elseif (stripos($trimmedLine, 'success') !== false || stripos($trimmedLine, 'ok') !== false) {
                            $logClass .= ' log-success';
                        }

                        // Resaltar texto si se especifica
                        if (!empty($highlight) && stripos($trimmedLine, $highlight) !== false) {
                            $logClass .= ' highlight';
                        }

                        echo "<div class='$logClass'>[" . str_pad($lineNumber, 4, '0', STR_PAD_LEFT) . "] " . htmlspecialchars($trimmedLine) . "</div>";
                    }
                }
            }
            ?>
        </div>
    </div>

    <script>
        function changeLogType() {
            const logType = document.getElementById('logType').value;
            const lines = document.getElementById('lines').value;
            const search = document.getElementById('search').value;
            const highlight = document.getElementById('highlight').value;

            let url = `?type=${logType}&lines=${lines}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (highlight) url += `&highlight=${encodeURIComponent(highlight)}`;

            window.location.href = url;
        }

        function changeLines() {
            const logType = document.getElementById('logType').value;
            const lines = document.getElementById('lines').value;
            const search = document.getElementById('search').value;
            const highlight = document.getElementById('highlight').value;

            let url = `?type=${logType}&lines=${lines}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (highlight) url += `&highlight=${encodeURIComponent(highlight)}`;

            window.location.href = url;
        }

        function searchLogs() {
            const logType = document.getElementById('logType').value;
            const lines = document.getElementById('lines').value;
            const search = document.getElementById('search').value;
            const highlight = document.getElementById('highlight').value;

            let url = `?type=${logType}&lines=${lines}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (highlight) url += `&highlight=${encodeURIComponent(highlight)}`;

            // Actualizar URL sin recargar (para una experiencia más fluida)
            history.replaceState(null, '', url);
        }

        function highlightLogs() {
            const logType = document.getElementById('logType').value;
            const lines = document.getElementById('lines').value;
            const search = document.getElementById('search').value;
            const highlight = document.getElementById('highlight').value;

            let url = `?type=${logType}&lines=${lines}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (highlight) url += `&highlight=${encodeURIComponent(highlight)}`;

            history.replaceState(null, '', url);

            // Aplicar resaltado en tiempo real
            applyHighlighting();
        }

        function applyHighlighting() {
            const highlight = document.getElementById('highlight').value;
            const logLines = document.querySelectorAll('.log-line');

            logLines.forEach(line => {
                line.classList.remove('highlight');
                if (highlight && line.textContent.toLowerCase().includes(highlight.toLowerCase())) {
                    line.classList.add('highlight');
                }
            });
        }

        function refreshLogs() {
            const logType = document.getElementById('logType').value;
            const lines = document.getElementById('lines').value;
            const search = document.getElementById('search').value;
            const highlight = document.getElementById('highlight').value;

            let url = `?type=${logType}&lines=${lines}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (highlight) url += `&highlight=${encodeURIComponent(highlight)}`;

            window.location.href = url;
        }

        function downloadLog() {
            const logType = document.getElementById('logType').value;
            window.open(`?type=${logType}&download=1`, '_blank');
        }

        // Auto-refresh cada 30 segundos
        setTimeout(function() {
            refreshLogs();
        }, 30000);

        // Aplicar resaltado inicial
        document.addEventListener('DOMContentLoaded', function() {
            applyHighlighting();
        });
    </script>
</body>

</html>