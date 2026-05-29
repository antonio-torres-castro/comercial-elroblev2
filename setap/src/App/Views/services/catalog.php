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
                <a href="<?= AppConstants::ROUTE_SERVICES ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-activity"></i> Tracking
                </a>
                <a href="<?= AppConstants::ROUTE_SERVICES_PLAN ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar-plus"></i> Planificar
                </a>
                <a href="<?= AppConstants::ROUTE_SERVICES_CREATE ?>" class="btn btn-sm btn-setap-primary">
                    <i class="bi bi-plus-circle"></i> Crear Servicio
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5></div>
            <div class="card-body">
                <form method="GET" action="<?= AppConstants::ROUTE_SERVICES_CATALOG ?>">
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
                            <label class="form-label" for="nombre">Servicio</label>
                            <input class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($data['filters']['nombre'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="activo">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="">Todos</option>
                                <option value="1" <?= ($data['filters']['activo'] ?? '') === '1' ? 'selected' : ''; ?>>Activo</option>
                                <option value="0" <?= ($data['filters']['activo'] ?? '') === '0' ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
                            <a class="btn btn-secondary" href="<?= AppConstants::ROUTE_SERVICES_CATALOG ?>"><i class="bi bi-x-circle"></i></a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-boxes"></i> Servicios</h5>
                <span class="badge bg-primary"><?= count($data['services']); ?> servicios</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Servicio</th>
                                <th>Tipo</th>
                                <?php if ($isAdmin): ?><th>Proveedor</th><?php endif; ?>
                                <th>Version</th>
                                <th>Duracion</th>
                                <th>Procesos</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($data['services'])): ?>
                            <tr><td colspan="<?= $isAdmin ? 7 : 6; ?>" class="text-center text-muted py-4">No hay servicios definidos</td></tr>
                        <?php endif; ?>
                        <?php foreach ($data['services'] as $service): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($service['nombre']); ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($service['codigo'] ?? ''); ?></div>
                                </td>
                                <td><?= htmlspecialchars($service['tipo_nombre'] ?? '-'); ?></td>
                                <?php if ($isAdmin): ?><td><?= htmlspecialchars($service['proveedor_nombre'] ?? '-'); ?></td><?php endif; ?>
                                <td><span class="badge bg-info">v<?= htmlspecialchars((string)($service['version_actual'] ?? 1)); ?></span></td>
                                <td><?= htmlspecialchars((string)($service['tiempo_estimado_dias'] ?? 0)); ?> dias</td>
                                <td><?= htmlspecialchars((string)$service['procesos_count']); ?></td>
                                <td>
                                    <span class="badge <?= (int)$service['activo'] === 1 ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?= (int)$service['activo'] === 1 ? 'Activo' : 'Inactivo'; ?>
                                    </span>
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
