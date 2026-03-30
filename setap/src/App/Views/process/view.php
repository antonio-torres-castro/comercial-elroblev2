<?php

use App\Helpers\Security;
use App\Constants\AppConstants;
$isAdmin = $data['user']['id'] == 1;
$processTasks = $data['processTasks'] ?? [];
$process = $data['process'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title']; ?> - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= AppConstants::ROUTE_PROCESSES ?>" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK ?>
                        </a>
                        <?php if ($process): ?>
                            <a href="<?= AppConstants::ROUTE_PROCESSES ?>/edit/<?= $process['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i> Editar Proceso
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos del Proceso</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($process): ?>
                                    <div class="row g-3">
                                        <?php if ($isAdmin): ?>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Proveedor</label>
                                                <p class="mb-0">
                                                    <span class="badge bg-info">
                                                        <?= htmlspecialchars($process['proveedor_nombre'] ?? 'N/A'); ?>
                                                    </span>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Nombre del Proceso</label>
                                            <p class="mb-0"><?= htmlspecialchars($process['nombre']); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Descripcion</label>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($process['descripcion'] ?? 'Sin descripcion')); ?></p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle"></i> Seleccione un proceso para ver sus detalles.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-list-task"></i> Tareas del Proceso</h5>
                                <span class="badge bg-primary" id="taskCount"><?= count($processTasks); ?> tareas</span>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($processTasks)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Tarea</th>
                                                    <th>Duracion</th>
                                                    <th>Categoria</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $totalHoras = 0;
                                                foreach ($processTasks as $task): 
                                                    $totalHoras += (float)$task['hh'];
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($task['tarea_nombre']); ?></td>
                                                        <td><?= number_format($task['hh'], 1); ?> hrs</td>
                                                        <td><?= htmlspecialchars($task['categoria_nombre'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-info btn-view-task" 
                                                                    data-task-id="<?= $task['tarea_id']; ?>">
                                                                <i class="bi bi-eye"></i> Ver
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-secondary">
                                                <tr>
                                                    <th colspan="2" class="text-end">Total Horas:</th>
                                                    <th><?= number_format($totalHoras, 1); ?> hrs</th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mb-0">Este proceso no tiene tareas asignadas</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="viewTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nombre:</dt>
                        <dd class="col-sm-8" id="viewTaskNombre">-</dd>
                        <dt class="col-sm-4">Descripcion:</dt>
                        <dd class="col-sm-8" id="viewTaskDescripcion">-</dd>
                        <dt class="col-sm-4">Categoria:</dt>
                        <dd class="col-sm-8" id="viewTaskCategoria">-</dd>
                        <dt class="col-sm-4">Estado:</dt>
                        <dd class="col-sm-8" id="viewTaskEstado">-</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script src="/setap/public/js/process-view.js"></script>
</body>

</html>
