<?php

use App\Helpers\Security;
use App\Constants\AppConstants;
$isAdmin = $data['user']['id'] == 1;
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
                        <a href="<?= AppConstants::ROUTE_PROCESSES ?>/create" class="btn btn-sm btn-setap-primary">
                            <i class="bi bi-plus-circle"></i> Crear Proceso
                        </a>
                    </div>
                </div>

                <?php if (isset($data['error']) && !empty($data['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="bi bi-exclamation-triangle"></i> Error:</h6>
                        <p class="mb-0"><?= htmlspecialchars($data['error']); ?></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($data['success']) && !empty($data['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($data['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?= AppConstants::ROUTE_PROCESSES ?>">
                            <div class="row g-3">
                                <?php if ($isAdmin): ?>
                                    <div class="col-md-4">
                                        <label for="proveedor_id" class="form-label">Proveedor</label>
                                        <select class="form-select" id="proveedor_id" name="proveedor_id">
                                            <option value="">Todos los proveedores</option>
                                            <?php foreach ($data['suppliers'] as $supplier): ?>
                                                <option value="<?= $supplier['id']; ?>" <?= ($data['filters']['proveedor_id'] ?? '') == $supplier['id'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($supplier['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-4">
                                    <label for="nombre" class="form-label">Nombre del Proceso</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?= htmlspecialchars($data['filters']['nombre'] ?? ''); ?>" 
                                           placeholder="Buscar por nombre">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    <a href="<?= AppConstants::ROUTE_PROCESSES ?>" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Procesos</h5>
                        <span class="badge bg-primary"><?= count($data['processes']); ?> procesos</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <?php if ($isAdmin): ?>
                                            <th>Proveedor</th>
                                        <?php endif; ?>
                                        <th>Descripcion</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data['processes'])): ?>
                                        <tr>
                                            <td colspan="<?= $isAdmin ? '4' : '3'; ?>" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                                <p class="mb-0">No se encontraron procesos</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($data['processes'] as $process): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($process['nombre']); ?></strong>
                                                </td>
                                                <?php if ($isAdmin): ?>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?= htmlspecialchars($process['proveedor_nombre'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <?php 
                                                        $descripcion = $process['descripcion'] ?? '';
                                                        echo htmlspecialchars(mb_substr($descripcion, 0, 100));
                                                        if (mb_strlen($descripcion) > 100) {
                                                            echo '...';
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="<?= AppConstants::ROUTE_PROCESSES ?>/show/<?= $process['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" title="Ver">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="<?= AppConstants::ROUTE_PROCESSES ?>/edit/<?= $process['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $process['id']; ?>, '<?= htmlspecialchars(addslashes($process['nombre'])); ?>')" 
                                                            title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <form id="deleteForm" method="POST" action="<?= AppConstants::ROUTE_PROCESSES ?>/delete" style="display: none;">
        <?= Security::renderCsrfField() ?>
        <input type="hidden" name="id" id="deleteId">
    </form>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script src="/setap/public/js/process-list.js"></script>
</body>

</html>
