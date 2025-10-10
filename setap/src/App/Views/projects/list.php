<?php use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= AppConstants::UI_PROJECT_MANAGEMENT ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .project-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .progress-bar-custom {
            height: 8px;
        }
        .status-badge {
            font-size: 0.75rem;
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
                    <i class="bi bi-folder"></i> <?= AppConstants::UI_PROJECT_MANAGEMENT ?>
                    <span class="badge bg-secondary ms-2"><?= count($projects) ?> proyectos</span>
                </h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="/projects/create" class="btn btn-setap-primary">
                    <i class="bi bi-plus-circle"></i> <?= AppConstants::UI_NEW_PROJECT ?>
                </a>
                <a href="/projects/search" class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i> <?= AppConstants::UI_ADVANCED_SEARCH_BTN ?>
                </a>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="<?= AppConstants::ROUTE_PROJECTS ?>" class="row align-items-end">
                    <div class="col-md-3">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select class="form-select" name="cliente_id" id="cliente_id">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"
                                        <?= ($filters['cliente_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($client['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="estado_tipo_id" class="form-label">Estado</label>
                        <select class="form-select" name="estado_tipo_id" id="estado_tipo_id">
                            <option value="">Todos los estados</option>
                            <?php foreach ($projectStates as $state): ?>
                                <option value="<?= $state['id'] ?>"
                                        <?= ($filters['estado_tipo_id'] ?? '') == $state['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($state['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_desde" class="form-label">Desde</label>
                        <input type="date" class="form-control" name="fecha_desde" id="fecha_desde"
                               value="<?= htmlspecialchars($filters['fecha_desde'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_hasta" class="form-label">Hasta</label>
                        <input type="date" class="form-control" name="fecha_hasta" id="fecha_hasta"
                               value="<?= htmlspecialchars($filters['fecha_hasta'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-setap-primary me-2">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                        <a href="<?= AppConstants::ROUTE_PROJECTS ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Proyectos -->
        <?php if (empty($projects)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-folder-x display-1 text-muted"></i>
                    <h4 class="mt-3">No hay proyectos</h4>
                    <p class="text-muted">No se encontraron proyectos con los filtros seleccionados.</p>
                    <a href="/projects/create" class="btn btn-setap-primary">
                        <i class="bi bi-plus-circle"></i> Crear Primer Proyecto
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($projects as $project): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card project-card h-100" onclick="window.location.href='/projects/show?id=<?= $project['id'] ?>'">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-building"></i> <?= htmlspecialchars($project['cliente_nombre']) ?>
                                </h6>
                                <?php
                                $statusClass = match($project['estado_tipo_id']) {
                                    1 => 'bg-setap-primary',    // Creado
                                    2 => 'bg-success',    // Activo
                                    3 => 'bg-warning',    // Inactivo
                                    5 => 'bg-setap-primary-light',       // Iniciado
                                    6 => 'bg-warning',    // Terminado
                                    8 => 'bg-success',    // Aprobado
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $statusClass ?> status-badge">
                                    <?= htmlspecialchars($project['estado_nombre']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="text-setap-primary">Ubicación:</h6>
                                    <p class="mb-1"><?= htmlspecialchars($project['direccion'] ?: 'No especificada') ?></p>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-setap-primary">Fechas:</h6>
                                    <div class="small">
                                        <div><strong>Inicio:</strong> <?= date('d/m/Y', strtotime($project['fecha_inicio'])) ?></div>
                                        <?php if ($project['fecha_fin']): ?>
                                            <div><strong>Fin:</strong> <?= date('d/m/Y', strtotime($project['fecha_fin'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-setap-primary">Progreso:</h6>
                                    <?php
                                    $totalTasks = $project['total_tareas'] ?? 0;
                                    $completedTasks = $project['tareas_completadas'] ?? 0;
                                    $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                                    ?>
                                    <div class="progress progress-bar-custom mb-2">
                                        <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <div class="small text-muted">
                                        <?= $completedTasks ?> de <?= $totalTasks ?> tareas completadas (<?= $progress ?>%)
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-setap-primary">Contraparte:</h6>
                                    <div class="small">
                                        <div><?= htmlspecialchars($project['contraparte_nombre']) ?></div>
                                        <?php if ($project['contraparte_email']): ?>
                                            <div class="text-muted"><?= htmlspecialchars($project['contraparte_email']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Tipo: <?= htmlspecialchars($project['tipo_tarea']) ?>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/projects/show?id=<?= $project['id'] ?>"
                                           class="btn btn-outline-setap-primary"
                                           onclick="event.stopPropagation()" title="Ver Proyecto">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/projects/edit?id=<?= $project['id'] ?>"
                                           class="btn btn-outline-secondary"
                                           onclick="event.stopPropagation()" title="Editar Proyecto">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="/proyecto-feriados?proyecto_id=<?= $project['id'] ?>"
                                           class="btn btn-outline-info"
                                           onclick="event.stopPropagation()" title="Gestionar Feriados">
                                            <i class="bi bi-calendar-x"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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

        // Configurar fechas por defecto
        document.addEventListener('DOMContentLoaded', function() {
            const fechaDesde = document.getElementById('fecha_desde');
            const fechaHasta = document.getElementById('fecha_hasta');

            // Si no hay fecha desde, establecer inicio del año actual
            if (!fechaDesde.value) {
                const startOfYear = new Date(new Date().getFullYear(), 0, 1);
                fechaDesde.max = new Date().toISOString().split('T')[0];
            }

            // Si no hay fecha hasta, establecer fecha actual
            if (!fechaHasta.value) {
                fechaHasta.max = new Date().toISOString().split('T')[0];
            }

            // Validar que fecha hasta sea mayor que fecha desde
            fechaDesde.addEventListener('change', function() {
                fechaHasta.min = this.value;
            });

            fechaHasta.addEventListener('change', function() {
                fechaDesde.max = this.value;
            });
        });
    </script>
</body>
</html>