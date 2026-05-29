<?php
use App\Constants\AppConstants;
$isAdmin = $data['user']['id'] == 1;
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
            <h1 class="h2"><?= htmlspecialchars($data['title']); ?></h1>
            <div class="btn-toolbar gap-2">
                <a href="<?= AppConstants::ROUTE_SERVICES_CATALOG ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-boxes"></i> Catalogo
                </a>
                <a href="<?= AppConstants::ROUTE_SERVICES_PLAN ?>" class="btn btn-sm btn-setap-primary">
                    <i class="bi bi-calendar-plus"></i> Planificar Servicio
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5></div>
            <div class="card-body">
                <form method="GET" action="<?= AppConstants::ROUTE_SERVICES ?>">
                    <div class="row g-3">
                        <?php if ($isAdmin): ?>
                            <div class="col-md-4">
                                <label class="form-label" for="proveedor_id">Proveedor</label>
                                <select class="form-select" id="proveedor_id" name="proveedor_id">
                                    <option value="">Todos</option>
                                    <?php foreach ($data['suppliers'] as $supplier): ?>
                                        <option value="<?= $supplier['id']; ?>" <?= ($data['filters']['proveedor_id'] ?? '') == $supplier['id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($supplier['razon_social']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label class="form-label" for="cliente_id">Cliente servicio</label>
                            <select class="form-select" id="cliente_id" name="cliente_id">
                                <option value="">Todos</option>
                                <?php foreach ($data['clients'] as $client): ?>
                                    <option value="<?= $client['id']; ?>" <?= ($data['filters']['cliente_id'] ?? '') == $client['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($client['razon_social']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="estado_operacional_id">Estado</label>
                            <select class="form-select" id="estado_operacional_id" name="estado_operacional_id">
                                <option value="">Todos</option>
                                <option value="2" <?= ($data['filters']['estado_operacional_id'] ?? '') == 2 ? 'selected' : ''; ?>>Activo</option>
                                <option value="3" <?= ($data['filters']['estado_operacional_id'] ?? '') == 3 ? 'selected' : ''; ?>>Suspendido</option>
                                <option value="4" <?= ($data['filters']['estado_operacional_id'] ?? '') == 4 ? 'selected' : ''; ?>>Termino anticipado</option>
                                <option value="8" <?= ($data['filters']['estado_operacional_id'] ?? '') == 8 ? 'selected' : ''; ?>>Completado</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-activity"></i> Tracking transversal</h5>
                <span class="badge bg-primary"><?= count($data['services']); ?> servicios</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Servicio</th>
                                <th>Cliente</th>
                                <?php if ($isAdmin): ?><th>Proveedor</th><?php endif; ?>
                                <th>Fechas</th>
                                <th>Avance</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($data['services'])): ?>
                            <tr><td colspan="<?= $isAdmin ? 7 : 6; ?>" class="text-center text-muted py-4">No hay servicios planificados</td></tr>
                        <?php endif; ?>
                        <?php foreach ($data['services'] as $service): ?>
                            <?php $progress = (float)($service['porcentaje_calculado'] ?? $service['porcentaje_avance']); ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($service['nombre'] ?: $service['servicio_nombre']); ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($service['servicio_nombre']); ?> v<?= htmlspecialchars((string)$service['version']); ?></div>
                                </td>
                                <td><?= htmlspecialchars($service['cliente_nombre']); ?></td>
                                <?php if ($isAdmin): ?><td><?= htmlspecialchars($service['proveedor_nombre'] ?? '-'); ?></td><?php endif; ?>
                                <td><?= htmlspecialchars($service['fecha_inicio']); ?> / <?= htmlspecialchars($service['fecha_termino_estimada']); ?></td>
                                <td style="min-width: 170px;">
                                    <div class="progress" role="progressbar" aria-valuenow="<?= $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar" style="width: <?= min(100, $progress); ?>%"><?= $progress; ?>%</div>
                                    </div>
                                    <div class="text-muted small"><?= (int)$service['tareas_completadas']; ?> de <?= (int)$service['tareas_total']; ?> tareas</div>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($service['estado_nombre'] ?? 'Planificado'); ?></span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-info" href="<?= AppConstants::ROUTE_SERVICES ?>/show/<?= $service['id']; ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
</body>
</html>
