<?php

use App\Helpers\Security;
use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Choices.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />

    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="row mb-2">
                    <div class="col-md-8">
                        <h2>
                            <i class="bi bi-clock-history"></i> Horas planificadas
                            <span class="badge bg-secondary ms-1"><?= $totalRows ?> periodos</span>

                            <button class="btn btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros" aria-expanded="false" aria-controls="collapseFiltros">
                                <i class="bi bi-eye"></i> Filtros
                            </button>
                        </h2>
                    </div>
                </div>

                <div class="collapse show" id="collapseFiltros">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" id="getFormFilter" class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label">Proyecto</label>
                                            <select class="form-select" name="proyecto_id">
                                                <option value="">Todos</option>
                                                <?php if (!empty($data['projects'])): ?>
                                                    <?php foreach ($data['projects'] as $project): ?>
                                                        <option value="<?= $project['id'] ?>"
                                                            <?= (isset($_GET['proyecto_id']) && $_GET['proyecto_id'] == $project['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($project['cliente_nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Estado</label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id[]" multiple>
                                                <?php if (!empty($data['taskStates'])): ?>
                                                    <?php foreach ($data['taskStates'] as $state): ?>
                                                        <option value="<?= $state['id'] ?>"
                                                            <?= (isset($_GET['estado_tipo_id']) && is_array($_GET['estado_tipo_id']) && in_array($state['id'], $_GET['estado_tipo_id'])) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($state['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Ejecuta</label>
                                            <select class="form-select" name="usuario_id">
                                                <option value="">Todos</option>
                                                <?php if (!empty($data['users'])): ?>
                                                    <?php foreach ($data['users'] as $user): ?>
                                                        <option value="<?= $user['id'] ?>"
                                                            <?= (isset($_GET['usuario_id']) && $_GET['usuario_id'] == $user['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($user['nombre_usuario']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-1">
                                            <label class="form-label">Lapso</label>
                                            <select class="form-select" name="modo">
                                                <option value="dia" <?= (isset($_GET['modo']) && $_GET['modo'] === 'dia') ? 'selected' : '' ?>>Día</option>
                                                <option value="semana" <?= (isset($_GET['modo']) && $_GET['modo'] === 'semana') ? 'selected' : '' ?>>Semana</option>
                                                <option value="mes" <?= (isset($_GET['modo']) && $_GET['modo'] === 'mes') ? 'selected' : '' ?>>Mes</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label for="fecha_inicio" class="form-label">Inicio</label>
                                            <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio"
                                                value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="fecha_fin" class="form-label">Fin</label>
                                            <input type="date" class="form-control" name="fecha_fin" id="fecha_fin"
                                                value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
                                        </div>

                                        <div class="col-md-1 d-flex align-items-center">
                                            <button type="submit" class="btn btn-outline-setap-primary me-2">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <a href="<?= AppConstants::ROUTE_TASKS_HOURS ?>" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <?php if (!empty($data['hoursRows'])): ?>
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive-xl">
                                        <table class="table table-hover" id="hoursTable">
                                            <thead>
                                                <tr>
                                                    <th>Lapso</th>
                                                    <th>Periodo</th>
                                                    <th>Total</th>
                                                    <th>Personas</th>
                                                    <th>Activo</th>
                                                    <th>Iniciado</th>
                                                    <th>Terminado</th>
                                                    <th>Aprobado</th>
                                                    <th>Rechazado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $baseQuery = $_GET;
                                                unset($baseQuery['page']);
                                                unset($baseQuery['modo']);
                                                $baseQueryString = http_build_query($baseQuery);
                                                ?>
                                                <?php foreach ($data['hoursRows'] as $row): ?>
                                                    <?php
                                                    $lapsoLabel = $row['lapso'] === 'semana' ? 'Semana' : ($row['lapso'] === 'mes' ? 'Mes' : 'Día');
                                                    $periodoInicio = $row['periodo_inicio'] ?? '';
                                                    $periodoFin = $row['periodo_fin'] ?? $periodoInicio;
                                                    $query = $baseQuery;
                                                    $query['fecha_inicio'] = $periodoInicio;
                                                    $query['fecha_fin'] = $periodoFin;
                                                    $tasksUrl = AppConstants::ROUTE_TASKS . '?' . http_build_query($query);
                                                    ?>
                                                    <tr>
                                                        <td><?= $lapsoLabel ?></td>
                                                        <td>
                                                            <?php if (!empty($periodoInicio)): ?>
                                                                <?= date('d/m/Y', strtotime($periodoInicio)) ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= number_format((float)($row['total_horas'] ?? 0), 2, ',', '.') ?></td>
                                                        <td><?= number_format((float)($row['personas'] ?? 0), 2, ',', '.') ?></td>
                                                        <td><?= number_format((float)($row['horas_activo'] ?? 0), 2, ',', '.') ?></td>
                                                        <td><?= number_format((float)($row['horas_iniciada'] ?? 0), 2, ',', '.') ?></td>
                                                        <td><?= number_format((float)($row['horas_terminada'] ?? 0), 2, ',', '.') ?></td>
                                                        <td><?= number_format((float)($row['horas_aprobada'] ?? 0), 2, ',', '.') ?></td>
                                                        <td><?= number_format((float)($row['horas_rechazada'] ?? 0), 2, ',', '.') ?></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="<?= htmlspecialchars($tasksUrl) ?>" class="btn btn-outline-info" title="Ver tareas">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-outline-secondary btn-personas"
                                                                    data-periodo-inicio="<?= htmlspecialchars($periodoInicio) ?>"
                                                                    data-periodo-fin="<?= htmlspecialchars($periodoFin) ?>"
                                                                    data-query-base="<?= htmlspecialchars($baseQueryString) ?>"
                                                                    title="Ver colaboradores">
                                                                    <i class="bi bi-person"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <?php if ($totalPages > 1): ?>
                                        <nav aria-label="Navegación de páginas" class="mt-3">
                                            <ul class="pagination justify-content-center">
                                                <?php
                                                $queryString = $_GET;
                                                unset($queryString['page']);
                                                $baseUrl = '?' . http_build_query($queryString);
                                                ?>

                                                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="<?= $baseUrl . '&page=' . ($currentPage - 1) ?>">&laquo;</a>
                                                </li>

                                                <?php
                                                $start = max(1, $currentPage - 1);
                                                $end = min($totalPages, $currentPage + 1);
                                                for ($i = $start; $i <= $end; $i++): ?>
                                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                        <a class="page-link" href="<?= $baseUrl . '&page=' . $i ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>

                                                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="<?= $baseUrl . '&page=' . ($currentPage + 1) ?>">&raquo;</a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="bi bi-inbox display-1 text-muted"></i>
                                    <h4 class="mt-3">No hay horas planificadas</h4>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal fade" id="personasModal" tabindex="-1">
                    <div class="modal-dialog modal-small">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Colaboradores asignados</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Usuario</th>
                                            </tr>
                                        </thead>
                                        <tbody id="personasModalBody">
                                            <tr>
                                                <td colspan="2" class="text-center text-muted">Seleccione un periodo</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
    <script src="/setap/public/js/task-hours.js"></script>

    <!-- Choices.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
</body>

</html>