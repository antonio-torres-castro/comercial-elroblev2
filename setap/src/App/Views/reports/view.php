<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($reportTitle ?? 'Reporte') ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .report-header {
            background: linear-gradient(135deg, var(--setap-primary), var(--setap-primary-light));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .report-data-table {
            font-size: 0.9rem;
        }
        .report-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .main-content {
            margin-top: 2rem;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .report-header {
                background: var(--setap-primary) !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navegación Unificada (no se imprime) -->
    <div class="no-print">
        <?php include __DIR__ . '/../layouts/navigation.php'; ?>
    </div>

    <!-- Header del Reporte -->
    <div class="report-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-1">
                        <i class="bi bi-file-earmark-text"></i>
                        <?= htmlspecialchars($reportTitle ?? 'Reporte del Sistema') ?>
                    </h1>
                    <p class="mb-0 opacity-75">
                        Generado el <?= date('d/m/Y H:i', strtotime($generatedAt ?? 'now')) ?>
                    </p>
                </div>
                <div class="col-md-4 text-end no-print">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-light" onclick="window.print()">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                        <button type="button" class="btn btn-light" onclick="exportReport('pdf')">
                            <i class="bi bi-file-pdf"></i> PDF
                        </button>
                        <button type="button" class="btn btn-light" onclick="exportReport('excel')">
                            <i class="bi bi-file-excel"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <main class="main-content">
            <!-- Botones de Acción -->
            <div class="row mb-4 no-print">
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <a href="/reports" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Reportes
                        </a>
                        <a href="/reports/create" class="btn btn-setap-primary">
                            <i class="bi bi-plus-circle"></i> Nuevo Reporte
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contenido del Reporte -->
            <?php if (isset($reportData) && !empty($reportData)): ?>
                
                <!-- Resumen Ejecutivo -->
                <?php if (isset($reportData['summary'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card report-card">
                            <div class="card-header bg-setap-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-graph-up"></i> Resumen Ejecutivo
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($reportData['summary'] as $key => $value): ?>
                                        <div class="col-md-3 mb-3">
                                            <div class="text-center">
                                                <h3 class="text-setap-primary mb-1"><?= htmlspecialchars($value) ?></h3>
                                                <p class="text-muted mb-0"><?= ucwords(str_replace('_', ' ', $key)) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Datos Detallados -->
                <?php if (isset($reportData['data']) && !empty($reportData['data'])): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card report-card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-table"></i> Datos Detallados
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover report-data-table">
                                        <thead class="table-setap-primary">
                                            <tr>
                                                <?php if (!empty($reportData['data'])): ?>
                                                    <?php foreach (array_keys($reportData['data'][0]) as $column): ?>
                                                        <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $column))) ?></th>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reportData['data'] as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $cell): ?>
                                                        <td><?= htmlspecialchars($cell) ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Paginación o información adicional -->
                                <div class="mt-3 text-muted">
                                    <small>
                                        <i class="bi bi-info-circle"></i>
                                        Mostrando <?= count($reportData['data']) ?> registros
                                        <?php if (isset($reportData['total_records'])): ?>
                                            de <?= $reportData['total_records'] ?> totales
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Gráficos o Análisis (si existen) -->
                <?php if (isset($reportData['charts']) && !empty($reportData['charts'])): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card report-card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-bar-chart"></i> Análisis Gráfico
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Aquí se pueden incluir gráficos generados -->
                                <div class="text-center text-muted">
                                    <i class="bi bi-graph-up" style="font-size: 3rem;"></i>
                                    <p class="mt-3">Gráficos y análisis visual en desarrollo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Estado sin datos -->
                <div class="row">
                    <div class="col-12">
                        <div class="card report-card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">No hay datos para mostrar</h4>
                                <p class="text-muted">
                                    No se encontraron datos para los parámetros especificados. 
                                    Intente ajustar los filtros o el período de tiempo.
                                </p>
                                <a href="/reports/create" class="btn btn-setap-primary">
                                    <i class="bi bi-gear"></i> Configurar Nuevo Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Información del Reporte -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card report-card">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-info-circle"></i> Información del Reporte
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Tipo:</strong> <?= htmlspecialchars($reportTitle ?? 'N/A') ?></p>
                                    <p class="mb-1"><strong>Generado:</strong> <?= date('d/m/Y H:i:s', strtotime($generatedAt ?? 'now')) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Usuario:</strong> <?= htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Sistema') ?></p>
                                    <p class="mb-1"><strong>Sistema:</strong> SETAP v1.0</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
    <script>
        // Función para exportar reportes
        function exportReport(format) {
            // Obtener parámetros del reporte actual
            const urlParams = new URLSearchParams(window.location.search);
            const exportUrl = `/reports/export?format=${format}&type=${urlParams.get('type') || 'custom'}`;
            
            // Crear enlace temporal para descarga
            const link = document.createElement('a');
            link.href = exportUrl;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Mejorar impresión
        window.addEventListener('beforeprint', function() {
            document.title = '<?= htmlspecialchars($reportTitle ?? 'Reporte') ?> - <?= date('d-m-Y') ?>';
        });

        // Configurar tabla responsiva para impresión
        window.addEventListener('beforeprint', function() {
            const tables = document.querySelectorAll('.table-responsive');
            tables.forEach(table => {
                table.style.overflow = 'visible';
            });
        });

        window.addEventListener('afterprint', function() {
            const tables = document.querySelectorAll('.table-responsive');
            tables.forEach(table => {
                table.style.overflow = 'auto';
            });
        });
    </script>
</body>
</html>