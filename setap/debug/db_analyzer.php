<?php

/**
 * Analizador de Base de Datos Web-Only
 * Complementa phpMyAdmin con análisis específico para la aplicación
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
    $logEntry = "[$timestamp] [IP:$ip] [DB_ANALYZER] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

writeAppLog("Acceso al analizador de base de datos");

// Conectar a la base de datos
$pdo = null;
$dbConnected = false;
$errorMessage = '';

try {
    if (file_exists('../config/database.php')) {
        include_once '../config/database.php';
        if (defined('DB_HOST')) {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $dbConnected = true;
        } else {
            $errorMessage = "Configuración de base de datos incompleta";
        }
    } else {
        $errorMessage = "No se encontró el archivo de configuración de base de datos";
    }
} catch (Exception $e) {
    $errorMessage = "Conexión: " . $e->getMessage();
}

// Obtener información de las tablas
$tables = [];
if ($dbConnected) {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $errorMessage = "Error al obtener tablas: " . $e->getMessage();
    }
}

// Obtener parámetros de consulta
$action = $_GET['action'] ?? 'overview';
$selectedTable = $_GET['table'] ?? '';
$query = $_GET['query'] ?? '';

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🗄️ Analizador de Base de Datos - Comercial El Roble</title>
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

        .nav {
            background: #ecf0f1;
            padding: 15px 20px;
            display: flex;
            gap: 20px;
            border-bottom: 1px solid #bdc3c7;
        }

        .nav a {
            color: #2c3e50;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .nav a:hover,
        .nav a.active {
            background: #3498db;
            color: white;
        }

        .content {
            padding: 20px;
        }

        .error-box {
            background: #fdf2f2;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 6px 6px 0;
        }

        .success-box {
            background: #f0f9f4;
            border-left: 4px solid #27ae60;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 6px 6px 0;
        }

        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 6px 6px 0;
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
        }

        .card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .db-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 14px;
        }

        .db-table th,
        .db-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .db-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            cursor: pointer;
        }

        .db-table tr:hover {
            background: #f1f3f4;
        }

        .metric {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #2c3e50;
        }

        .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn.danger {
            background: #e74c3c;
        }

        .btn.danger:hover {
            background: #c0392b;
        }

        .btn.success {
            background: #27ae60;
        }

        .btn.success:hover {
            background: #229954;
        }

        .query-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .query-form textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
        }

        .query-form select,
        .query-form input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px;
        }

        .result-table {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .table-info {
            background: #ecf0f1;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Analizador de Base de Datos</h1>
            <p>IP: <?= $clientIP ?> | Conectado: <?= $dbConnected ? '✅ Sí' : '❌ No' ?> | Fecha: <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <div class="nav">
            <a href="?action=overview" <?= $action === 'overview' ? 'class="active"' : '' ?>>📊 Resumen</a>
            <a href="?action=tables" <?= $action === 'tables' ? 'class="active"' : '' ?>>📋 Tablas</a>
            <a href="?action=query" <?= $action === 'query' ? 'class="active"' : '' ?>>🔍 Consulta</a>
            <a href="?action=users" <?= $action === 'users' ? 'class="active"' : '' ?>>👥 Usuarios</a>
            <a href="?action=health" <?= $action === 'health' ? 'class="active"' : '' ?>>🏥 Salud</a>
        </div>

        <div class="content">
            <?php if (!$dbConnected): ?>
                <div class="error-box">
                    <h3>❌ Error de Conexión</h3>
                    <p><?= htmlspecialchars($errorMessage) ?></p>
                    <p><strong>Soluciones:</strong></p>
                    <ul>
                        <li>Verifica que el archivo <code>config/database.php</code> existe y tiene la configuración correcta</li>
                        <li>Confirma que las credenciales de base de datos son válidas</li>
                        <li>Contacta al administrador del hosting para verificar la conectividad</li>
                    </ul>
                </div>
            <?php else: ?>

                <?php if ($action === 'overview'): ?>
                    <h2>📊 Resumen de Base de Datos</h2>

                    <?php
                    // Obtener estadísticas generales
                    $totalTables = count($tables);
                    $totalSize = 0;
                    $tableStats = [];

                    foreach ($tables as $table) {
                        try {
                            $stmt = $pdo->query("SELECT 
                                COUNT(*) as row_count,
                                ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                                FROM information_schema.TABLES 
                                WHERE table_schema = '" . DB_NAME . "' 
                                AND table_name = '$table'");
                            $stats = $stmt->fetch();
                            $tableStats[] = [
                                'name' => $table,
                                'rows' => $stats['row_count'],
                                'size_mb' => $stats['size_mb']
                            ];
                            $totalSize += $stats['size_mb'];
                        } catch (Exception $e) {
                            $tableStats[] = ['name' => $table, 'rows' => 0, 'size_mb' => 0];
                        }
                    }
                    ?>

                    <div class="grid">
                        <div class="card">
                            <h3>📊 Estadísticas Generales</h3>
                            <div class="metric"><?= $totalTables ?></div>
                            <p>Total de tablas</p>
                            <div class="metric"><?= round($totalSize, 2) ?> MB</div>
                            <p>Tamaño total de datos</p>
                        </div>

                        <div class="card">
                            <h3>🔗 Información de Conexión</h3>
                            <p><strong>Host:</strong> <?= DB_HOST ?></p>
                            <p><strong>Base de datos:</strong> <?= DB_NAME ?></p>
                            <p><strong>Usuario:</strong> <?= DB_USER ?></p>
                            <p><strong>Charset:</strong> utf8mb4</p>
                        </div>

                        <div class="card">
                            <h3>⚡ Estado de Conexión</h3>
                            <div class="metric status-ok">✅ Activa</div>
                            <p>Conexión establecida</p>
                            <p>Tiempo: <?= round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) ?>s</p>
                        </div>
                    </div>

                    <div class="card">
                        <h3>📋 Tablas Principales</h3>
                        <div class="result-table">
                            <table class="db-table">
                                <thead>
                                    <tr>
                                        <th>Tabla</th>
                                        <th>Registros</th>
                                        <th>Tamaño (MB)</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableStats as $table): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($table['name']) ?></strong></td>
                                            <td><?= number_format($table['rows']) ?></td>
                                            <td><?= $table['size_mb'] ?></td>
                                            <td>
                                                <a href="?action=table_detail&table=<?= urlencode($table['name']) ?>" class="btn">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($action === 'tables'): ?>
                    <h2>📋 Gestión de Tablas</h2>

                    <div class="grid">
                        <?php foreach ($tables as $table): ?>
                            <?php
                            // Obtener información de la tabla
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                                $rowCount = $stmt->fetchColumn();

                                $stmt = $pdo->query("DESCRIBE `$table`");
                                $columns = $stmt->fetchAll();
                            } catch (Exception $e) {
                                $rowCount = 0;
                                $columns = [];
                            }
                            ?>
                            <div class="card">
                                <h3><?= htmlspecialchars($table) ?></h3>
                                <div class="metric"><?= number_format($rowCount) ?></div>
                                <p>Registros</p>

                                <div class="table-info">
                                    <strong>Columnas:</strong>
                                    <?php foreach (array_slice($columns, 0, 3) as $column): ?>
                                        <span style="background: #ecf0f1; padding: 2px 6px; border-radius: 3px; margin: 2px; font-size: 12px;">
                                            <?= htmlspecialchars($column['Field']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (count($columns) > 3): ?>
                                        <span style="font-size: 12px; color: #7f8c8d;">+<?= count($columns) - 3 ?> más</span>
                                    <?php endif; ?>
                                </div>

                                <a href="?action=table_detail&table=<?= urlencode($table) ?>" class="btn">Ver Detalles</a>
                                <a href="?action=table_data&table=<?= urlencode($table) ?>" class="btn success">Ver Datos</a>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($action === 'query'): ?>
                    <h2>🔍 Consultas SQL</h2>

                    <div class="query-form">
                        <h3>Ejecutar Consulta</h3>
                        <form action="" method="GET">
                            <input type="hidden" name="action" value="query">

                            <div>
                                <label>Tipo de consulta:</label>
                                <select name="query_type" id="queryType" onchange="toggleQueryOptions()">
                                    <option value="show">SHOW (Tablas, Databases, etc.)</option>
                                    <option value="select">SELECT (Solo lectura)</option>
                                    <option value="count">COUNT (Contar registros)</option>
                                </select>
                            </div>

                            <div id="queryOptions">
                                <label>Consulta personalizada:</label>
                                <textarea name="query" id="customQuery" placeholder="Escribe tu consulta SQL aquí..."><?= htmlspecialchars($query) ?></textarea>
                            </div>

                            <div id="tableOptions" style="display: none;">
                                <label>Seleccionar tabla:</label>
                                <select name="table">
                                    <option value="">Selecciona una tabla...</option>
                                    <?php foreach ($tables as $table): ?>
                                        <option value="<?= htmlspecialchars($table) ?>"><?= htmlspecialchars($table) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn">🚀 Ejecutar Consulta</button>
                        </form>
                    </div>

                    <?php if (!empty($query)): ?>
                        <?php
                        try {
                            // Solo permitir ciertos tipos de consultas por seguridad
                            $queryType = $_GET['query_type'] ?? 'show';

                            if ($queryType === 'show') {
                                $stmt = $pdo->query($query);
                            } elseif ($queryType === 'select') {
                                // Verificar que es solo SELECT
                                if (!preg_match('/^\s*SELECT\s+/i', $query)) {
                                    throw new Exception("Solo se permiten consultas SELECT");
                                }
                                $stmt = $pdo->query($query);
                            } elseif ($queryType === 'count') {
                                $table = $_GET['table'] ?? '';
                                if (empty($table)) {
                                    throw new Exception("Debes seleccionar una tabla");
                                }
                                $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
                            }

                            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            echo '<div class="success-box"><strong>✅ Éxito:</strong> Consulta ejecutada. Se encontraron ' . count($results) . ' resultados.</div>';

                            if (!empty($results)) {
                                echo '<div class="result-table">';
                                echo '<table class="db-table">';

                                // Headers
                                $headers = array_keys($results[0]);
                                echo '<thead><tr>';
                                foreach ($headers as $header) {
                                    echo '<th>' . htmlspecialchars($header) . '</th>';
                                }
                                echo '</tr></thead>';

                                // Data
                                echo '<tbody>';
                                foreach ($results as $row) {
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
                        } catch (Exception $e) {
                            echo '<div class="error-box"><strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>
                    <?php endif; ?>

                <?php elseif ($action === 'users'): ?>
                    <h2>👥 Información de Usuarios</h2>

                    <?php
                    if (in_array('usuarios', $tables)) {
                        try {
                            $stmt = $pdo->query("SELECT 
                                COUNT(*) as total,
                                COUNT(CASE WHEN activo = 1 THEN 1 END) as activos,
                                COUNT(CASE WHEN fecha_Creado >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as nuevos_mes
                                FROM usuarios");
                            $stats = $stmt->fetch();

                            echo '<div class="grid">';
                            echo '<div class="card"><h3>📊 Total Usuarios</h3><div class="metric">' . number_format($stats['total']) . '</div></div>';
                            echo '<div class="card"><h3>✅ Activos</h3><div class="metric">' . number_format($stats['activos']) . '</div></div>';
                            echo '<div class="card"><h3>🆕 Nuevos (30 días)</h3><div class="metric">' . number_format($stats['nuevos_mes']) . '</div></div>';
                            echo '</div>';

                            // Usuarios recientes
                            $stmt = $pdo->query("SELECT id, nombre_usuario, email, estado_tipo_id, fecha_Creado 
                                               FROM usuarios 
                                               ORDER BY fecha_Creado DESC 
                                               LIMIT 10");
                            $recentUsers = $stmt->fetchAll();

                            echo '<div class="card">';
                            echo '<h3>🆕 Usuarios Recientes</h3>';
                            echo '<div class="result-table">';
                            echo '<table class="db-table">';
                            echo '<thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Estado</th><th>Fecha</th></tr></thead>';
                            echo '<tbody>';
                            foreach ($recentUsers as $user) {
                                $status = $user['estado_tipo_id'] == 2 ? 'Activo' : 'Otro';
                                $statusClass = $user['estado_tipo_id'] > 1 ? 'status-ok' : 'status-error';
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                                echo '<td>' . htmlspecialchars($user['nombre_usuario']) . '</td>';
                                echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                                echo '<td class="' . $statusClass . '">' . $status . '</td>';
                                echo '<td>' . htmlspecialchars($user['fecha_Creado']) . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                            echo '</div>';
                            echo '</div>';
                        } catch (Exception $e) {
                            echo '<div class="error-box"><strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                    } else {
                        echo '<div class="info-box">ℹ️ No se encontró la tabla "usuarios" en la base de datos.</div>';
                    }
                    ?>

                <?php elseif ($action === 'health'): ?>
                    <h2>🏥 Salud de la Base de Datos</h2>

                    <?php
                    $healthChecks = [];

                    // Check 1: Conectividad
                    $healthChecks[] = [
                        'name' => 'Conectividad',
                        'status' => $dbConnected ? 'ok' : 'error',
                        'message' => $dbConnected ? 'Conexión establecida correctamente' : 'Error de conexión'
                    ];

                    // Check 2: Tamaño de tablas
                    $largeTables = [];
                    foreach ($tableStats as $table) {
                        if ($table['size_mb'] > 100) {
                            $largeTables[] = $table['name'] . ' (' . $table['size_mb'] . ' MB)';
                        }
                    }
                    $healthChecks[] = [
                        'name' => 'Tamaño de Tablas',
                        'status' => empty($largeTables) ? 'ok' : 'warning',
                        'message' => empty($largeTables) ? 'Todas las tablas tienen tamaño normal' : 'Tablas grandes: ' . implode(', ', $largeTables)
                    ];

                    // Check 3: Usuarios activos vs inactivos
                    if (in_array('usuarios', $tables)) {
                        try {
                            $stmt = $pdo->query("SELECT 
                                COUNT(*) as total,
                                COUNT(CASE WHEN activo = 1 THEN 1 END) as activos
                                FROM usuarios");
                            $userStats = $stmt->fetch();

                            $inactiveRatio = $userStats['total'] > 0 ? ($userStats['total'] - $userStats['activos']) / $userStats['total'] : 0;
                            $healthChecks[] = [
                                'name' => 'Ratio de Usuarios Activos',
                                'status' => $inactiveRatio < 0.8 ? 'ok' : 'warning',
                                'message' => round(($userStats['activos'] / max($userStats['total'], 1)) * 100, 1) . '% usuarios activos'
                            ];
                        } catch (Exception $e) {
                            // Ignorar error
                        }
                    }

                    // Mostrar resultados
                    echo '<div class="grid">';
                    foreach ($healthChecks as $check) {
                        $statusClass = $check['status'] === 'ok' ? 'success-box' : ($check['status'] === 'warning' ? 'info-box' : 'error-box');
                        $icon = $check['status'] === 'ok' ? '✅' : ($check['status'] === 'warning' ? '⚠️' : '❌');

                        echo '<div class="' . $statusClass . '">';
                        echo '<h3>' . $icon . ' ' . htmlspecialchars($check['name']) . '</h3>';
                        echo '<p>' . htmlspecialchars($check['message']) . '</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                    ?>

                <?php elseif ($action === 'table_detail'): ?>
                    <h2>📋 Detalles de Tabla: <?= htmlspecialchars($selectedTable) ?></h2>

                    <?php if (in_array($selectedTable, $tables)): ?>
                        <?php
                        try {
                            // Información de columnas
                            $stmt = $pdo->query("DESCRIBE `$selectedTable`");
                            $columns = $stmt->fetchAll();

                            // Información de índices
                            $stmt = $pdo->query("SHOW INDEX FROM `$selectedTable`");
                            $indexes = $stmt->fetchAll();

                            // Estadísticas
                            $stmt = $pdo->query("SELECT COUNT(*) as row_count FROM `$selectedTable`");
                            $rowCount = $stmt->fetchColumn();

                            echo '<div class="grid">';
                            echo '<div class="card"><h3>📊 Estadísticas</h3><div class="metric">' . number_format($rowCount) . '</div><p>Registros</p></div>';
                            echo '<div class="card"><h3>📋 Columnas</h3><div class="metric">' . count($columns) . '</div><p>Campos definidos</p></div>';
                            echo '<div class="card"><h3>🔑 Índices</h3><div class="metric">' . count($indexes) . '</div><p>Índices creados</p></div>';
                            echo '</div>';

                            echo '<div class="card">';
                            echo '<h3>📋 Estructura de Columnas</h3>';
                            echo '<div class="result-table">';
                            echo '<table class="db-table">';
                            echo '<thead><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por Defecto</th><th>Extra</th></tr></thead>';
                            echo '<tbody>';
                            foreach ($columns as $column) {
                                echo '<tr>';
                                echo '<td><strong>' . htmlspecialchars($column['Field']) . '</strong></td>';
                                echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                                echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                                echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                                echo '<td>' . htmlspecialchars($column['Default'] ?? 'NULL') . '</td>';
                                echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                            echo '</div>';
                            echo '</div>';

                            if (!empty($indexes)) {
                                echo '<div class="card">';
                                echo '<h3>🔑 Índices</h3>';
                                echo '<div class="result-table">';
                                echo '<table class="db-table">';
                                echo '<thead><tr><th>Nombre</th><th>Columna</th><th>Único</th><th>Cardinalidad</th></tr></thead>';
                                echo '<tbody>';
                                foreach ($indexes as $index) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($index['Key_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($index['Column_name']) . '</td>';
                                    echo '<td>' . ($index['Non_unique'] ? 'No' : 'Sí') . '</td>';
                                    echo '<td>' . number_format($index['Cardinality']) . '</td>';
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="error-box"><strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>

                    <?php else: ?>
                        <div class="error-box">❌ La tabla seleccionada no existe.</div>
                    <?php endif; ?>

                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleQueryOptions() {
            const queryType = document.getElementById('queryType').value;
            const queryOptions = document.getElementById('queryOptions');
            const tableOptions = document.getElementById('tableOptions');

            if (queryType === 'show') {
                queryOptions.style.display = 'block';
                tableOptions.style.display = 'none';
            } else if (queryType === 'select') {
                queryOptions.style.display = 'block';
                tableOptions.style.display = 'none';
            } else if (queryType === 'count') {
                queryOptions.style.display = 'none';
                tableOptions.style.display = 'block';
            }
        }

        // Ejecutar al cargar la página
        document.addEventListener('DOMContentLoaded', toggleQueryOptions);
    </script>
</body>

</html>