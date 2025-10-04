<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyecto - <?= htmlspecialchars($project['cliente_nombre']) ?> - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .task-item {
            border-left: 3px solid;
            transition: all 0.2s;
        }
        .task-item:hover {
            background-color: var(--setap-bg-light);
        }
        .timeline-item {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: -20px;
            width: 2px;
            background: var(--setap-border-light);
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 6px;
            top: 6px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--setap-primary);
        }
        .timeline-item:last-child::before {
            display: none;
        }
    </style>
</head>
<body class="bg-light">
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Header del Proyecto -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>
                    <i class="bi bi-building"></i> <?= htmlspecialchars($project['cliente_nombre']) ?>
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
                    <span class="badge <?= $statusClass ?> ms-2"><?= htmlspecialchars($project['estado_nombre']) ?></span>
                </h2>
                <p class="text-muted">
                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($project['direccion'] ?: 'Ubicación no especificada') ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/projects/edit?id=<?= $project['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar Proyecto
                </a>
                <button type="button" class="btn btn-setap-primary" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                    <i class="bi bi-arrow-repeat"></i> Cambiar Estado
                </button>
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

        <!-- Estadísticas del Proyecto -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-setap-primary">
                    <div class="card-body text-center">
                        <h3 class="text-setap-primary"><?= $stats['total_tareas'] ?? 0 ?></h3>
                        <p class="mb-0">Total Tareas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-success">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?= $stats['tareas_aprobadas'] ?? 0 ?></h3>
                        <p class="mb-0">Completadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning">
                    <div class="card-body text-center">
                        <h3 class="text-warning"><?= $stats['tareas_iniciadas'] ?? 0 ?></h3>
                        <p class="mb-0">En Progreso</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-setap-primary-light">
                    <div class="card-body text-center">
                        <h3 class="text-setap-primary-light"><?= round($stats['horas_planificadas'] ?? 0, 1) ?></h3>
                        <p class="mb-0">Horas Planificadas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progreso General -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-graph-up"></i> Progreso General</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $progress = $stats['progreso_porcentaje'] ?? 0;
                        $progressClass = match(true) {
                            $progress >= 80 => 'bg-success',
                            $progress >= 50 => 'bg-setap-primary',
                            $progress >= 25 => 'bg-warning',
                            default => 'bg-danger'
                        };
                        ?>
                        <div class="progress mb-3" style="height: 20px;">
                            <div class="progress-bar <?= $progressClass ?>" style="width: <?= $progress ?>%">
                                <?= $progress ?>%
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <strong>Fecha Inicio:</strong><br>
                                <?= date('d/m/Y', strtotime($project['fecha_inicio'])) ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Fecha Fin:</strong><br>
                                <?= $project['fecha_fin'] ? date('d/m/Y', strtotime($project['fecha_fin'])) : 'No definida' ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Tipo de Tarea:</strong><br>
                                <?= htmlspecialchars($project['tipo_tarea']) ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Cliente RUT:</strong><br>
                                <?= htmlspecialchars($project['cliente_rut']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="row">
            <!-- Información del Proyecto -->
            <div class="col-md-8">
                <!-- Tareas del Proyecto -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-list-task"></i> Tareas del Proyecto</h5>
                        <a href="/tasks/create?project_id=<?= $project['id'] ?>" class="btn btn-sm btn-setap-primary">
                            <i class="bi bi-plus"></i> Nueva Tarea
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-list-task display-3 text-muted"></i>
                                <h5 class="mt-3">No hay tareas asignadas</h5>
                                <p class="text-muted">Comienza agregando tareas a este proyecto.</p>
                                <a href="/tasks/create?project_id=<?= $project['id'] ?>" class="btn btn-setap-primary">
                                    <i class="bi bi-plus"></i> Crear Primera Tarea
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <?php
                                $taskBorderClass = match($task['estado_tipo_id']) {
                                    5 => 'border-setap-primary-light',     // Iniciado
                                    6 => 'border-warning',  // Terminado
                                    7 => 'border-danger',   // Rechazado
                                    8 => 'border-success',  // Aprobado
                                    default => 'border-secondary'
                                };
                                ?>
                                <div class="task-item <?= $taskBorderClass ?> p-3 mb-3 rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-2"><?= htmlspecialchars($task['tarea_nombre']) ?></h6>
                                            <p class="text-muted mb-2"><?= htmlspecialchars($task['tarea_descripcion']) ?></p>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small class="text-muted">
                                                        <strong>Planificador:</strong> <?= htmlspecialchars($task['planificador_nombre']) ?><br>
                                                        <strong>Ejecutor:</strong> <?= htmlspecialchars($task['ejecutor_nombre'] ?: 'No asignado') ?><br>
                                                        <strong>Supervisor:</strong> <?= htmlspecialchars($task['supervisor_nombre'] ?: 'No asignado') ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted">
                                                        <strong>Fecha Inicio:</strong> <?= date('d/m/Y H:i', strtotime($task['fecha_inicio'])) ?><br>
                                                        <strong>Duración:</strong> <?= $task['duracion_horas'] ?> horas<br>
                                                        <strong>Prioridad:</strong> <?= $task['prioridad'] ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= match($task['estado_tipo_id']) {
                                                5 => 'setap-primary-light',
                                                6 => 'warning',
                                                7 => 'danger',
                                                8 => 'success',
                                                default => 'secondary'
                                            } ?>">
                                                <?= htmlspecialchars($task['estado_nombre']) ?>
                                            </span>
                                            <div class="mt-2">
                                                <a href="/tasks/show?id=<?= $task['id'] ?>" class="btn btn-sm btn-outline-setap-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar con información adicional -->
            <div class="col-md-4">
                <!-- Información de Contraparte -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-person-badge"></i> Información de Contraparte</h5>
                    </div>
                    <div class="card-body">
                        <h6><?= htmlspecialchars($project['contraparte_nombre']) ?></h6>
                        <?php if ($project['contraparte_cargo']): ?>
                            <p class="text-muted mb-2"><?= htmlspecialchars($project['contraparte_cargo']) ?></p>
                        <?php endif; ?>
                        <?php if ($project['contraparte_email']): ?>
                            <p class="mb-1">
                                <i class="bi bi-envelope"></i> 
                                <a href="mailto:<?= htmlspecialchars($project['contraparte_email']) ?>">
                                    <?= htmlspecialchars($project['contraparte_email']) ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($project['contraparte_telefono']): ?>
                            <p class="mb-1">
                                <i class="bi bi-telephone"></i> 
                                <a href="tel:<?= htmlspecialchars($project['contraparte_telefono']) ?>">
                                    <?= htmlspecialchars($project['contraparte_telefono']) ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Feriados del Proyecto -->
                <?php if (!empty($holidays)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-calendar-x"></i> Feriados del Proyecto</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($holidays as $holiday): ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-calendar-x text-danger me-2"></i>
                                <?= date('d/m/Y', strtotime($holiday)) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Acciones Rápidas -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning"></i> Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/tasks/create?project_id=<?= $project['id'] ?>" class="btn btn-outline-setap-primary">
                                <i class="bi bi-plus-circle"></i> Agregar Tarea
                            </a>
                            <a href="/projects/report?id=<?= $project['id'] ?>" class="btn btn-outline-setap-primary">
                                <i class="bi bi-file-earmark-text"></i> Generar Reporte
                            </a>
                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                                <i class="bi bi-arrow-repeat"></i> Cambiar Estado
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Cambiar Estado -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Estado del Proyecto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/projects/change-status">
                    <div class="modal-body">
                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                        <div class="mb-3">
                            <label for="new_status_id" class="form-label">Nuevo Estado</label>
                            <select class="form-select" name="new_status_id" id="new_status_id" required>
                                <option value="">Seleccionar estado...</option>
                                <option value="1" <?= $project['estado_tipo_id'] == 1 ? 'selected disabled' : '' ?>>Creado</option>
                                <option value="2" <?= $project['estado_tipo_id'] == 2 ? 'selected disabled' : '' ?>>Activo</option>
                                <option value="3" <?= $project['estado_tipo_id'] == 3 ? 'selected disabled' : '' ?>>Inactivo</option>
                                <option value="5" <?= $project['estado_tipo_id'] == 5 ? 'selected disabled' : '' ?>>Iniciado</option>
                                <option value="6" <?= $project['estado_tipo_id'] == 6 ? 'selected disabled' : '' ?>>Terminado</option>
                                <option value="8" <?= $project['estado_tipo_id'] == 8 ? 'selected disabled' : '' ?>>Aprobado</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            El cambio de estado afectará el flujo del proyecto y sus tareas.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-setap-primary">Cambiar Estado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Actualizar estadísticas cada 30 segundos
        setInterval(function() {
            // Aquí podrías hacer una llamada AJAX para actualizar las estadísticas
            // sin recargar toda la página
        }, 30000);
    </script>
</body>
</html>