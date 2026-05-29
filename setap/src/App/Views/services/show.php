<?php
use App\Constants\AppConstants;
use App\Helpers\Security;
$service = $data['service'];
$tracking = $data['tracking'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']); ?> - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>
<body>
<?php include __DIR__ . '/../layouts/navigation.php'; ?>
<div class="container-fluid mt-4">
    <main class="col-12 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <div>
                <h1 class="h2 mb-0"><?= htmlspecialchars($service['nombre'] ?: $service['servicio_nombre']); ?></h1>
                <div class="text-muted"><?= htmlspecialchars($service['cliente_nombre']); ?> · <?= htmlspecialchars($service['version_nombre_snapshot'] ?? ''); ?></div>
            </div>
            <a href="<?= AppConstants::ROUTE_SERVICES ?>" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-list-check"></i> Tareas del servicio</h5></div>
                    <div class="card-body">
                        <div class="progress mb-3" role="progressbar" aria-valuenow="<?= (float)$service['porcentaje_calculado']; ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: <?= min(100, (float)$service['porcentaje_calculado']); ?>%"><?= (float)$service['porcentaje_calculado']; ?>%</div>
                        </div>
                        <div class="list-group">
                            <?php foreach ($tracking['tasks'] as $task): ?>
                                <?php
                                $state = (int)$task['estado_tipo_id'];
                                $icon = in_array($state, [6, 8], true) ? 'check-circle-fill text-success' : ($state === 5 ? 'hourglass-split text-warning' : 'circle text-muted');
                                ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><i class="bi bi-<?= $icon; ?>"></i> <?= htmlspecialchars($task['tarea_nombre']); ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars(substr((string)$task['fecha_inicio'], 0, 10)); ?> · <?= htmlspecialchars($task['estado_nombre'] ?? '-'); ?></div>
                                    </div>
                                    <?php if ($data['canAdmin'] && $state === 5): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($task['ejecutor_nombre'] ?? 'Sin ejecutor'); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($tracking['tasks'])): ?>
                                <div class="text-center text-muted py-4">No hay tareas operacionales relacionadas</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-diagram-3"></i> Procesos e insumos</h5></div>
                    <div class="card-body">
                        <div class="accordion" id="processAccordion">
                            <?php foreach ($tracking['processes'] as $index => $process): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#process<?= $index; ?>">
                                            <?= htmlspecialchars($process['proceso_nombre']); ?>
                                        </button>
                                    </h2>
                                    <div id="process<?= $index; ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : ''; ?>" data-bs-parent="#processAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <h6>Insumos</h6>
                                                    <ul class="mb-0">
                                                        <?php foreach ($process['insumos'] as $item): ?>
                                                            <li><?= htmlspecialchars($item['nombre']); ?> (<?= htmlspecialchars((string)$item['cantidad']); ?> <?= htmlspecialchars($item['unidad_medida'] ?? ''); ?>)</li>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($process['insumos'])): ?><li class="text-muted">Sin insumos definidos</li><?php endif; ?>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Activos</h6>
                                                    <ul class="mb-0">
                                                        <?php foreach ($process['activos'] as $item): ?>
                                                            <li><?= htmlspecialchars($item['nombre']); ?> (<?= htmlspecialchars((string)$item['cantidad']); ?>)</li>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($process['activos'])): ?><li class="text-muted">Sin activos definidos</li><?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-info-circle"></i> Resumen</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Estado</dt><dd class="col-sm-7"><?= htmlspecialchars($service['estado_nombre'] ?? 'Planificado'); ?></dd>
                            <dt class="col-sm-5">Inicio</dt><dd class="col-sm-7"><?= htmlspecialchars($service['fecha_inicio']); ?></dd>
                            <dt class="col-sm-5">Termino estimado</dt><dd class="col-sm-7"><?= htmlspecialchars($service['fecha_termino_estimada']); ?></dd>
                            <dt class="col-sm-5">Tareas</dt><dd class="col-sm-7"><?= (int)$service['tareas_total']; ?></dd>
                        </dl>
                    </div>
                </div>

                <?php if ($data['canAdmin']): ?>
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-tools"></i> Administracion</h5></div>
                        <div class="card-body">
                            <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/replan" class="mb-3">
                                <?= Security::renderCsrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$service['id']; ?>">
                                <label class="form-label" for="desde_fecha">Replanificar desde</label>
                                <input class="form-control mb-2" id="desde_fecha" name="desde_fecha" type="date" required>
                                <label class="form-label" for="dias_desfase">Dias de desfase</label>
                                <input class="form-control mb-2" id="dias_desfase" name="dias_desfase" type="number" required>
                                <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-calendar2-week"></i> Replanificar futuras</button>
                            </form>
                            <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/suspend" class="mb-3">
                                <?= Security::renderCsrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$service['id']; ?>">
                                <button class="btn btn-outline-warning w-100" type="submit"><i class="bi bi-pause-circle"></i> Suspender</button>
                            </form>
                            <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/finish-early">
                                <?= Security::renderCsrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$service['id']; ?>">
                                <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-stop-circle"></i> Termino anticipado</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
</body>
</html>
