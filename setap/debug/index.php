<?php
/**
 * Índice de Herramientas de Debug Web-Only
 * Página principal de acceso a todas las herramientas
 * Autor: MiniMax Agent
 */

// Configuración de seguridad
$allowedIPs = [
    '127.0.0.1',
    'localhost',
    // REEMPLAZA CON TU IP PÚBLICA
    '181.72.88.67'
];

$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$isAuthorized = in_array($clientIP, $allowedIPs) || $allowedIPs[0] === 'TU_IP_PUBLICA_AQUI';

if (!$isAuthorized) {
    http_response_code(403);
    die('<h1>403 - Acceso Denegado</h1><p>Esta herramienta solo está disponible para IPs autorizadas.</p><p>Tu IP: ' . htmlspecialchars($clientIP) . '</p>');
}

function writeAppLog($message) {
    $logFile = __DIR__ . '/../logs/web_debug.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logEntry = "[$timestamp] [IP:$ip] [DEBUG_INDEX] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

writeAppLog("Acceso al índice de debug");

// Información del sistema
$memoryUsage = memory_get_usage(true);
$memoryLimit = ini_get('memory_limit');
$memoryLimitBytes = parse_size($memoryLimit);
$usagePercent = round(($memoryUsage / $memoryLimitBytes) * 100, 1);

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛠️ Herramientas de Debug - Comercial El Roble</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; 
            padding: 20px; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white; 
            padding: 40px; 
            text-align: center;
        }
        .header h1 { 
            font-size: 2.5em; 
            margin-bottom: 15px; 
        }
        .status-bar {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 15px;
        }
        .status-item {
            text-align: center;
        }
        .status-value {
            font-size: 1.5em;
            font-weight: bold;
        }
        .tools-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            gap: 30px; 
            padding: 40px; 
        }
        .tool-card { 
            background: #f8f9fa; 
            border-radius: 12px; 
            padding: 30px; 
            text-align: center; 
            transition: all 0.3s ease; 
            border: 2px solid transparent;
        }
        .tool-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 40px rgba(0,0,0,0.1); 
            border-color: #3498db;
        }
        .tool-icon { 
            font-size: 3em; 
            margin-bottom: 20px; 
            display: block;
        }
        .tool-title { 
            font-size: 1.5em; 
            color: #2c3e50; 
            margin-bottom: 15px; 
            font-weight: 600;
        }
        .tool-description { 
            color: #7f8c8d; 
            margin-bottom: 20px; 
            line-height: 1.6;
        }
        .tool-link { 
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white; 
            padding: 12px 24px; 
            text-decoration: none; 
            border-radius: 6px; 
            display: inline-block; 
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .tool-link:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        .info-section {
            background: #ecf0f1;
            padding: 30px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .warning-box {
            background: #fef9e7;
            border-left: 4px solid #f39c12;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .success-box {
            background: #f0f9f4;
            border-left: 4px solid #27ae60;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .quick-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        .quick-link {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .quick-link:hover {
            background: #229954;
        }
        .footer {
            background: #34495e;
            color: white;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛠️ Centro de Debug Web-Only</h1>
            <p>Herramientas de diagnóstico para producción sin acceso a consola</p>
            
            <div class="status-bar">
                <div class="status-item">
                    <div class="status-value"><?= $memoryUsage < 50 * 1024 * 1024 ? '✅' : '⚠️' ?></div>
                    <div>Memoria</div>
                    <small><?= round($memoryUsage / 1024 / 1024, 1) ?> MB</small>
                </div>
                <div class="status-item">
                    <div class="status-value"><?= PHP_VERSION >= '7.4' ? '✅' : '⚠️' ?></div>
                    <div>PHP</div>
                    <small><?= PHP_VERSION ?></small>
                </div>
                <div class="status-item">
                    <div class="status-value">🌐</div>
                    <div>Servidor</div>
                    <small><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido' ?></small>
                </div>
                <div class="status-item">
                    <div class="status-value">🕐</div>
                    <div>Hora</div>
                    <small><?= date('H:i:s') ?></small>
                </div>
            </div>
        </div>

        <div class="warning-box">
            <h3>🔒 Acceso Autorizado</h3>
            <p>Bienvenido, tu IP <strong><?= htmlspecialchars($clientIP) ?></strong> está autorizada para acceder a estas herramientas.</p>
            <p><strong>Importante:</strong> Estas herramientas son solo para debugging. Elimínalas cuando termines el diagnóstico.</p>
        </div>

        <div class="tools-grid">
            <div class="tool-card">
                <span class="tool-icon">📊</span>
                <h3 class="tool-title">Panel Principal</h3>
                <p class="tool-description">
                    Dashboard completo con estado del sistema, métricas en tiempo real, 
                    configuración de PHP y herramientas de diagnóstico automático.
                </p>
                <a href="web_debug_panel.php" class="tool-link">🚀 Abrir Panel</a>
            </div>

            <div class="tool-card">
                <span class="tool-icon">📝</span>
                <h3 class="tool-title">Visor de Logs</h3>
                <p class="tool-description">
                    Lee y analiza logs de PHP, Apache y aplicación desde el navegador. 
                    Incluye búsqueda, filtrado y resaltado de términos.
                </p>
                <a href="log_viewer.php" class="tool-link">📖 Ver Logs</a>
            </div>

            <div class="tool-card">
                <span class="tool-icon">🗄️</span>
                <h3 class="tool-title">Analizador de BD</h3>
                <p class="tool-description">
                    Analiza la base de datos sin phpMyAdmin. Estadísticas, consultas SQL seguras, 
                    gestión de tablas y verificación de salud.
                </p>
                <a href="db_analyzer.php" class="tool-link">🔍 Analizar BD</a>
            </div>

            <div class="tool-card">
                <span class="tool-icon">📚</span>
                <h3 class="tool-title">Documentación</h3>
                <p class="tool-description">
                    Guías completas de uso, troubleshooting y mejores prácticas 
                    para debugging en producción sin consola.
                </p>
                <a href="GUIA_DEBUG_WEB_ONLY.md" class="tool-link" target="_blank">📖 Ver Guía</a>
            </div>
        </div>

        <div class="info-section">
            <h3>🎯 Flujo de Debug Recomendado</h3>
            <div class="quick-links">
                <a href="web_debug_panel.php" class="quick-link">1. Panel Principal</a>
                <span>→</span>
                <a href="log_viewer.php" class="quick-link">2. Revisar Logs</a>
                <span>→</span>
                <a href="db_analyzer.php" class="quick-link">3. Verificar BD</a>
                <span>→</span>
                <a href="phpMyAdmin_URL" class="quick-link" target="_blank">4. phpMyAdmin</a>
            </div>
        </div>

        <div class="success-box">
            <h3>✅ Herramientas Configuradas</h3>
            <p>Todas las herramientas están listas para usar. Recuerda:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Configurar tu IP pública en todos los archivos antes de usar</li>
                <li>Crear el directorio <code>/setap/logs/</code> con permisos de escritura</li>
                <li>Usar solo cuando sea necesario para debugging</li>
                <li>Eliminar las herramientas después de resolver los problemas</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>Debug Web-Only v1.0</strong> | Autor: MiniMax Agent | <?= date('Y-m-d H:i:s') ?></p>
            <p>Diseñado específicamente para entornos de producción sin acceso a consola</p>
        </div>
    </div>
</body>
</html>