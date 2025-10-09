<?php use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= AppConstants::UI_PERSONA_MANAGEMENT ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .stats-card {
            border-left: 4px solid var(--setap-primary);
        }
        .stats-card.success {
            border-left-color: #28a745;
        }
        .stats-card.warning {
            border-left-color: #ffc107;
        }
        .stats-card.info {
            border-left-color: var(--setap-primary-light);
        }
    </style>
</head>

<body class="bg-light">
    <?php use App\Helpers\Security; ?>

    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>
                    <i class="bi bi-people"></i> <?= AppConstants::UI_PERSONA_MANAGEMENT ?>
                    <span class="badge bg-secondary ms-2"><?= count($personas) ?> personas</span>
                </h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="/personas/create" class="btn btn-setap-primary">
                    <i class="bi bi-plus-circle"></i> <?= AppConstants::UI_NEW_PERSONA ?>
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

        <!-- Estadísticas -->
        <?php if (!empty($stats)): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Total Personas</h6>
                                <h3 class="mb-0"><?= (int)($stats['total'] ?? 0) ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-people-fill text-setap-primary fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Activos</h6>
                                <h3 class="mb-0 text-success"><?= (int)($stats['activos'] ?? 0) ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-person-check-fill text-success fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Inactivos</h6>
                                <h3 class="mb-0 text-warning"><?= (int)($stats['inactivos'] ?? 0) ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-person-x-fill text-warning fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-muted">Creados Hoy</h6>
                                <h3 class="mb-0 text-info"><?= (int)($stats['creados_hoy'] ?? 0) ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-person-plus-fill text-info fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="/personas" class="row align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" name="search" id="search"
                               value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                               placeholder="Nombre, RUT o teléfono...">
                    </div>
                    <div class="col-md-3">
                        <label for="estado_tipo_id" class="form-label">Estado</label>
                        <select class="form-select" name="estado_tipo_id" id="estado_tipo_id">
                            <option value="">Todos los estados</option>
                            <?php foreach ($estadosTipo as $estado): ?>
                                <option value="<?= $estado['id'] ?>"
                                        <?= ($filters['estado_tipo_id'] ?? '') == $estado['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-setap-primary">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="/personas" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Personas -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lista de Personas</h5>
            </div>
            <div class="card-body">
                <?php if (empty($personas)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No se encontraron personas.</p>
                        <a href="/personas/create" class="btn btn-setap-primary">
                            <i class="bi bi-plus-circle"></i> Crear Primera Persona
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-setap-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>RUT</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personas as $persona): ?>
                                <tr>
                                    <td><?= (int)$persona['id'] ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($persona['rut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($persona['nombre']) ?></strong>
                                        <?php if (!empty($persona['direccion'])): ?>
                                            <br><small class="text-muted">
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($persona['direccion']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($persona['telefono'])): ?>
                                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($persona['telefono']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($persona['estado_tipo_id']) {
                                            1 => 'secondary', // Creado
                                            2 => 'success',   // Activo
                                            3 => 'warning',   // Inactivo
                                            default => 'dark'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= htmlspecialchars($persona['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y H:i', strtotime($persona['fecha_Creado'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/personas/edit?id=<?= (int)$persona['id'] ?>"
                                               class="btn btn-outline-setap-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                    onclick="confirmDelete(<?= (int)$persona['id'] ?>, '<?= htmlspecialchars($persona['nombre']) ?>')"
                                                    title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar a la persona <strong id="personaName"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="/personas/delete" class="d-inline" id="deleteForm">
                        <?= \App\Helpers\Security::renderCsrfField() ?>
                        <input type="hidden" name="id" id="deletePersonaId">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

    <script>
        function confirmDelete(id, name) {
            document.getElementById('deletePersonaId').value = id;
            document.getElementById('personaName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Auto-enviar formulario cuando cambie el filtro de estado
        document.getElementById('estado_tipo_id').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>