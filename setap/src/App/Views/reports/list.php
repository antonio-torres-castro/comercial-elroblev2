<?php

use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
    <style>
        .report-card {
            transition: transform 0.2s;
            cursor: pointer;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .stats-card {
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .main-content {
            margin-top: 2rem;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main class="main-content">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2>
                        <i class="bi bi-bar-chart"></i> <?= AppConstants::UI_SYSTEM_REPORTS ?>
                    </h2>
                    <p class="text-muted">Genere y consulte reportes de actividad del sistema</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="<?= AppConstants::ROUTE_REPORTS ?>/create" class="btn btn-setap-primary">
                        <i class="bi bi-plus-circle"></i> <?= AppConstants::UI_NEW_REPORT ?>
                    </a>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (!empty($error) || !empty($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error ?? $_GET['error'] ?? '') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estadísticas Rápidas -->
            <?php if (isset($stats)): ?>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="text-setap-primary mb-2">
                                    <i class="bi bi-folder" style="font-size: 2rem;"></i>
                                </div>
                                <h3 class="mb-0"><?= $stats['total_projects'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Total Proyectos</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stats-card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="text-success mb-2">
                                    <i class="bi bi-list-task" style="font-size: 2rem;"></i>
                                </div>
                                <h3 class="mb-0"><?= $stats['total_tasks'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Total Tareas</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stats-card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="text-info mb-2">
                                    <i class="bi bi-people" style="font-size: 2rem;"></i>
                                </div>
                                <h3 class="mb-0"><?= $stats['total_users'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Total Usuarios</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stats-card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="text-warning mb-2">
                                    <i class="bi bi-building" style="font-size: 2rem;"></i>
                                </div>
                                <h3 class="mb-0"><?= $stats['total_clients'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Total Clientes</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tipos de Reportes Disponibles -->
            <div class="row">
                <div class="col-12">
                    <h4 class="mb-3">
                        <i class="bi bi-collection"></i> Reportes Disponibles
                    </h4>
                </div>
            </div>

            <div class="row">
                <!-- Reporte de Proyectos -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card report-card h-100 border-0 shadow-sm" onclick="generateReport('projects_summary')">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-setap-primary me-3">
                                    <i class="bi bi-folder" style="font-size: 2.5rem;"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">Resumen de Proyectos</h5>
                                    <p class="text-muted small mb-0">Estado y progreso</p>
                                </div>
                            </div>
                            <p class="card-text">
                                Reporte completo del estado de todos los proyectos, incluyendo fechas, progreso y recursos asignados.
                            </p>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 2-3 min
                                </small>
                                <span class="badge bg-setap-primary">Disponible</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reporte de Tareas -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card report-card h-100 border-0 shadow-sm" onclick="generateReport('tasks_summary')">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-success me-3">
                                    <i class="bi bi-list-task" style="font-size: 2.5rem;"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">Resumen de Tareas</h5>
                                    <p class="text-muted small mb-0">Actividad y rendimiento</p>
                                </div>
                            </div>
                            <p class="card-text">
                                Análisis detallado de las tareas completadas, pendientes y en progreso por período de tiempo.
                            </p>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 1-2 min
                                </small>
                                <span class="badge bg-success">Disponible</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reporte de Usuarios -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card report-card h-100 border-0 shadow-sm" onclick="generateReport('users_activity')">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-info me-3">
                                    <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">Actividad de Usuarios</h5>
                                    <p class="text-muted small mb-0">Sesiones y uso</p>
                                </div>
                            </div>
                            <p class="card-text">
                                Registro de actividad de usuarios, incluyendo inicios de sesión y acciones realizadas.
                            </p>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 1 min
                                </small>
                                <span class="badge bg-info">Disponible</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reporte de Clientes -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card report-card h-100 border-0 shadow-sm" onclick="generateReport('clients_summary')">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-warning me-3">
                                    <i class="bi bi-building" style="font-size: 2.5rem;"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">Resumen de Clientes</h5>
                                    <p class="text-muted small mb-0">Cartera y proyectos</p>
                                </div>
                            </div>
                            <p class="card-text">
                                Información consolidada de la cartera de clientes y sus proyectos asociados.
                            </p>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> 2 min
                                </small>
                                <span class="badge bg-warning">Disponible</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reporte Personalizado -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card report-card h-100 border-0 shadow-sm" onclick="window.location.href='<?= AppConstants::ROUTE_REPORTS ?>/create'">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-secondary me-3">
                                    <i class="bi bi-gear" style="font-size: 2.5rem;"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">Reporte Personalizado</h5>
                                    <p class="text-muted small mb-0">Configure parámetros</p>
                                </div>
                            </div>
                            <p class="card-text">
                                Cree reportes personalizados con filtros específicos y parámetros avanzados.
                            </p>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i> Variable
                                </small>
                                <span class="badge bg-secondary">Configurable</span>
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
        // Auto-hide alerts after 5 seconds (excepto los que están dentro de modales)
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert:not(.modal .alert)');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Función para generar reportes rápidos
        function generateReport(reportType) {
            // Crear formulario temporal para enviar datos
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= AppConstants::ROUTE_REPORTS; ?>/generate';

            // Agregar token CSRF
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = 'csrf_token';
            csrfToken.value = '<?= \App\Helpers\Security::getCsrfToken() ?>';
            form.appendChild(csrfToken);

            // Agregar tipo de reporte
            const reportTypeInput = document.createElement('input');
            reportTypeInput.type = 'hidden';
            reportTypeInput.name = 'report_type';
            reportTypeInput.value = reportType;
            form.appendChild(reportTypeInput);

            // Agregar fechas por defecto (último mes)
            const dateFrom = document.createElement('input');
            dateFrom.type = 'hidden';
            dateFrom.name = 'date_from';
            const lastMonth = new Date();
            lastMonth.setMonth(lastMonth.getMonth() - 1);
            dateFrom.value = lastMonth.toISOString().split('T')[0];
            form.appendChild(dateFrom);

            const dateTo = document.createElement('input');
            dateTo.type = 'hidden';
            dateTo.name = 'date_to';
            dateTo.value = new Date().toISOString().split('T')[0];
            form.appendChild(dateTo);

            // Enviar formulario
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>

</html>