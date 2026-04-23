<?php

use App\Helpers\Security;
use App\Constants\AppConstants;

$buildDireccionLabel = static function (array $task): string {
    $parts = [];
    $street = trim(implode(' ', array_filter([
        $task['direccion_calle'] ?? '',
        $task['direccion_numero'] ?? '',
        $task['direccion_letra'] ?? ''
    ], static fn($value) => $value !== null && $value !== '')));

    if ($street !== '') {
        $parts[] = $street;
    }

    foreach (['direccion_comuna', 'direccion_provincia', 'direccion_region'] as $field) {
        if (!empty($task[$field])) {
            $parts[] = $task[$field];
        }
    }

    return !empty($parts) ? implode(' - ', $parts) : 'Sin dirección';
};
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?> - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
    <style>
        .group-row-project td {
            background: #212529;
            color: #fff;
            font-weight: 600;
        }

        .group-row-direction td {
            background: #e9ecef;
            font-weight: 600;
        }

        .group-row-space td {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2>
                            <i class="bi bi-diagram-3"></i> <?= htmlspecialchars($data['title']) ?>
                            <span class="badge bg-secondary ms-2"><?= $totalRows ?> tareas</span>
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
                                            <select class="form-select" name="proyecto_id" id="proyecto_id" onchange="this.form.submit()">
                                                <option value="">Seleccionar...</option>
                                                <?php foreach ($data['projects'] as $project): ?>
                                                    <option value="<?= $project['id'] ?>" <?= (isset($_GET['proyecto_id']) && $_GET['proyecto_id'] == $project['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($project['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label">Dirección</label>
                                            <select class="form-select" name="direccion_id" id="direccion_id" onchange="this.form.submit()">
                                                <option value="">Seleccionar...</option>
                                                <?php foreach ($data['projectAdresses'] as $direccion): ?>
                                                    <option value="<?= $direccion['id'] ?>" <?= (isset($_GET['direccion_id']) && $_GET['direccion_id'] == $direccion['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($direccion['calle'] . ' ' . $direccion['numero'] . ' ' . $direccion['comuna'] . '-' . $direccion['provincia'] . '-' . $direccion['region']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label">Espacio Padre</label>
                                            <select class="form-select" name="espacio_padre_id" id="espacio_padre_id" onchange="this.form.submit()">
                                                <option value="">Seleccionar...</option>
                                                <?php foreach ($data['espaciosPadre'] as $espacio): ?>
                                                    <option value="<?= $espacio['id'] ?>" <?= (isset($_GET['espacio_padre_id']) && $_GET['espacio_padre_id'] == $espacio['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($espacio['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-1">
                                            <label class="form-label">Estado</label>
                                            <select class="form-select" name="estado_tipo_id" onchange="this.form.submit()">
                                                <option value="">Todos</option>
                                                <?php foreach ($data['taskStates'] as $state): ?>
                                                    <option value="<?= $state['id'] ?>" <?= (isset($_GET['estado_tipo_id']) && $_GET['estado_tipo_id'] == $state['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($state['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label">Inicio</label>
                                            <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>" onchange="this.form.submit()">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Fin</label>
                                            <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>" onchange="this.form.submit()">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Buscar Tarea (Autocompletar)</label>
                                            <div class="position-relative">
                                                <input type="text" class="form-control" id="task_autocomplete" name="tarea_nombre" placeholder="Escriba para buscar tarea..." value="<?= htmlspecialchars($_GET['tarea_nombre'] ?? '') ?>" autocomplete="off">
                                                <div id="autocomplete_results" class="list-group position-absolute w-100 mt-1 d-none" style="z-index: 1050; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-outline-setap-primary me-2">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <a href="<?= AppConstants::ROUTE_MY_TASKS ?>/listDireccionEspacio" class="btn btn-outline-secondary">
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
                        <?php if (!empty($data['tasks'])): ?>
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive-xl">
                                        <table class="table table-hover align-middle" id="myTasksTable">
                                            <thead>
                                                <tr>
                                                    <th>Tarea</th>
                                                    <th>Estado</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $currentProjectKey = null;
                                                $currentDirectionKey = null;
                                                $currentSpaceKey = null;
                                                foreach ($data['tasks'] as $task):
                                                    $direccionLabel = $buildDireccionLabel($task);
                                                    $spaceRootLabel = $task['espacio_padre_mas_alto_nombre'] ?? '';
                                                    if ($spaceRootLabel === '') {
                                                        $spaceRootLabel = $task['espacio_nombre'] ?? 'Sin espacio';
                                                    }

                                                    $projectKey = (string)($task['proyecto_id'] ?? 'sin-proyecto');
                                                    $directionKey = (string)($task['direccion_id'] ?? 'sin-direccion');
                                                    $spaceKey = $projectKey . '|' . $directionKey . '|' . ($task['espacio_padre_mas_alto_id'] ?? $task['espacio_id'] ?? 'sin-espacio');

                                                    if ($projectKey !== $currentProjectKey):
                                                        $currentProjectKey = $projectKey;
                                                        $currentDirectionKey = null;
                                                        $currentSpaceKey = null;
                                                ?>
                                                        <tr class="group-row-project">
                                                            <td colspan="3">
                                                                <i class="bi bi-folder2-open me-2"></i><?= htmlspecialchars($task['proyecto_nombre']) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>

                                                    <?php if ($directionKey !== $currentDirectionKey):
                                                        $currentDirectionKey = $directionKey;
                                                        $currentSpaceKey = null;
                                                    ?>
                                                        <tr class="group-row-direction">
                                                            <td colspan="3">
                                                                <i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($direccionLabel) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>

                                                    <?php if ($spaceKey !== $currentSpaceKey):
                                                        $currentSpaceKey = $spaceKey;
                                                    ?>
                                                        <tr class="group-row-space">
                                                            <td colspan="3">
                                                                <i class="bi bi-diagram-2 me-2"></i><?= htmlspecialchars($spaceRootLabel) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>

                                                    <tr id="task-row-<?= $task['id'] ?>">
                                                        <td onclick="viewDetail(<?= $task['id'] ?>)">
                                                            <div class="fw-bold"><?= htmlspecialchars($task['tarea_nombre']) ?></div>
                                                            <?php if (!empty($task['descripcion'])): ?>
                                                                <small class="text-hide" hidden><?= htmlspecialchars($task['descripcion']) ?></small>
                                                                <small class="text-muted d-block"><?= htmlspecialchars(substr($task['descripcion'], 0, 50)) ?>...</small>
                                                            <?php endif; ?>
                                                            <small class="text-muted">
                                                                Espacio: <?= htmlspecialchars($task['espacio_nombre'] ?? 'Sin espacio') ?>
                                                                | Nivel: <?= htmlspecialchars((string)($task['espacio_nivel'] ?? 'Sin nivel')) ?>
                                                                | Orden: <?= htmlspecialchars((string)($task['espacio_orden'] ?? 'Sin orden')) ?>
                                                            </small>
                                                            <span id="space-<?= $task['id'] ?>" hidden><?= htmlspecialchars($task['espacio_nombre'] ?? 'Sin espacio') ?></span>
                                                            <span id="code-<?= $task['id'] ?>" hidden><?= htmlspecialchars($task['espacio_codigo'] ?? 'Sin código') ?></span>
                                                            <span id="level-<?= $task['id'] ?>" hidden><?= htmlspecialchars((string)($task['espacio_nivel'] ?? 'Sin nivel')) ?></span>
                                                            <span id="order-<?= $task['id'] ?>" hidden><?= htmlspecialchars((string)($task['espacio_orden'] ?? 'Sin orden')) ?></span>
                                                            <span id="parent-space-<?= $task['id'] ?>" hidden><?= htmlspecialchars($spaceRootLabel) ?></span>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                <?php
                                                                $badgeClass = 'bg-secondary';
                                                                $statusText = '';
                                                                switch ($task['estado_tipo_id']) {
                                                                    case 1:
                                                                        $badgeClass = 'bg-warning text-dark';
                                                                        $statusText = 'Creado';
                                                                        break;
                                                                    case 2:
                                                                        $badgeClass = 'bg-primary';
                                                                        $statusText = 'Activo';
                                                                        break;
                                                                    case 3:
                                                                        $badgeClass = 'bg-secondary';
                                                                        $statusText = 'Inactivo';
                                                                        break;
                                                                    case 5:
                                                                        $badgeClass = 'bg-info';
                                                                        $statusText = 'Iniciado';
                                                                        break;
                                                                    case 6:
                                                                        $badgeClass = 'bg-warning';
                                                                        $statusText = 'Terminado';
                                                                        break;
                                                                    case 7:
                                                                        $badgeClass = 'bg-danger';
                                                                        $statusText = 'Rechazado';
                                                                        break;
                                                                    case 8:
                                                                        $badgeClass = 'bg-success';
                                                                        $statusText = 'Aprobado';
                                                                        break;
                                                                    default:
                                                                        $statusText = htmlspecialchars($task['estado']);
                                                                }

                                                                $nextStateId = null;
                                                                $buttonLabel = '';
                                                                $nextStateName = '';
                                                                $nextStateTypeButton = '';
                                                                switch ((int)$task['estado_tipo_id']) {
                                                                    case 2:
                                                                        $nextStateId = 5;
                                                                        $buttonLabel = 'Iniciar';
                                                                        $nextStateName = 'iniciado';
                                                                        $nextStateTypeButton = 'info';
                                                                        break;
                                                                    case 5:
                                                                    case 7:
                                                                        $nextStateId = 6;
                                                                        $buttonLabel = 'Terminar';
                                                                        $nextStateName = 'terminado';
                                                                        $nextStateTypeButton = 'warning';
                                                                        break;
                                                                }
                                                                ?>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="badge <?= $badgeClass ?>" id="status-badge-<?= $task['id'] ?>">
                                                                        <?= $statusText ?>
                                                                    </span>
                                                                    <?php if (!empty($nextStateId)): ?>
                                                                        <button class="btn btn-sm btn-outline-<?= $nextStateTypeButton ?> ms-2" type="button" onclick="confirmStateChange(<?= $task['id'] ?>, <?= $nextStateId ?>, '<?= $nextStateName ?>')">
                                                                            <?= $buttonLabel ?>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($task['fecha_inicio'])): ?>
                                                                <small id="date-hh-<?= $task['id'] ?>">
                                                                    <?= date('d/m/Y', strtotime($task['fecha_inicio'])) ?><br>
                                                                    <?php if (!empty($task['duracion_horas'])): ?>
                                                                        <strong>HH:</strong> <?= $task['duracion_horas'] ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            <?php else: ?>
                                                                <small class="text-muted">Error</small>
                                                            <?php endif; ?>
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
                                                for ($i = $start; $i <= $end; $i++):
                                                ?>
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
                                    <h4 class="mt-3">No hay tareas registradas</h4>
                                    <p class="text-muted">No se encontraron tareas para los filtros seleccionados.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal fade" id="changeStateModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Cambiar Estado a Tarea</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="changeStateForm" method="POST" enctype="multipart/form-data">
                                    <?= Security::renderCsrfField() ?>
                                    <input type="hidden" id="changeStateTaskId" name="task_id">
                                    <input type="hidden" id="changeStateNewState" name="new_state">

                                    <div class="mb-3">
                                        <label class="form-label">Tarea:</label>
                                        <div id="changeStateTaskName" class="fw-bold text-primary"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nuevo Estado:</label>
                                        <div id="changeStateNewStateName" class="fw-bold text-success"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="changeStateReason" class="form-label">Motivo del cambio (opcional):</label>
                                        <textarea class="form-control" id="changeStateReason" name="reason" rows="3" placeholder="Describe el motivo del cambio de estado..."></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="changeStatePhotosCamera" class="form-label">Tomar foto (cámara):</label>
                                        <input type="file" class="form-control" id="changeStatePhotosCamera" accept="image/*" capture="environment" multiple>
                                    </div>

                                    <div class="mb-3">
                                        <label for="changeStatePhotos" class="form-label">Fotos de evidencia (galería):</label>
                                        <input type="file" class="form-control" id="changeStatePhotos" accept="image/*" multiple>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="confirmChangeState">Cambiar Estado</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="detailTaskModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tarea</label>
                                    <div id="detailTaskName" class="fw-bold text-primary"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Descripción</label>
                                    <div id="detailTaskDescripcion" class="text"></div>
                                </div>
                                <div class="row">
                                    <div class="col-5">
                                        <label class="form-label fw-bold">Fecha</label>
                                        <div id="detailTaskFechaDuracion" class="text"></div>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fw-bold">Estado</label>
                                        <div id="detailTaskStateName" class="text-success"></div>
                                    </div>
                                </div>

                                <div class="col-4">
                                    <label class="form-label fw-bold">Espacio</label>
                                    <div id="detailTaskSpaceName" class="text"></div>
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <label class="form-label fw-bold">Código</label>
                                        <div id="detailTaskSpaceCode" class="text"></div>
                                    </div>

                                    <div class="col-4">
                                        <label class="form-label fw-bold">Nivel</label>
                                        <div id="detailTaskSpaceLevel" class="text"></div>
                                    </div>

                                    <div class="col-4">
                                        <label class="form-label fw-bold">Orden</label>
                                        <div id="detailTaskSpaceOrder" class="text"></div>
                                    </div>
                                </div>

                                <div class="col-4">
                                    <label class="form-label fw-bold">Espacio padre</label>
                                    <div id="detailTaskParentSpaceName" class="text"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Salir</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script src="/setap/public/js/task-state-utils.js"></script>
    <script src="/setap/public/js/my-task-list.js"></script>
</body>

</html>
