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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
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
                <div class="row mb-2">
                    <div class="col-md-6">
                        <h2>
                            <i class="bi bi-diagram-3"></i> <?= htmlspecialchars($data['title']) ?>
                            <span class="badge bg-secondary ms-1"><?= $totalRows ?> tareas</span>
                            <button class="btn btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros" aria-expanded="false" aria-controls="collapseFiltros">
                                <i class="bi bi-eye"></i> Filtros
                            </button>
                        </h2>
                    </div>

                    <?php if ($_GET['show_btn_aprobar']): ?>
                        <div class="<?= ($_GET['show_btn_nuevo'] || $_GET['show_btn_terminar']) ? 'col-md-2' : 'col-md-6' ?> text-end btn-group <?= $_GET['show_btn_nuevo'] ? 'mb-2' : '' ?>" role="group">
                            <a onclick="confirmStateChangeForSelectedRows(8)" class="btn btn-success" id="btnAprobar">
                                <i class="bi bi-check2-square"></i> Aprobar
                            </a>

                            <?php if ($_GET['show_btn_terminar']): ?>
                                <a onclick="confirmStateChangeForSelectedRows(6)" class="btn btn-warning" id="btnTerminar">
                                    <i class="bi bi-send-check"></i> Terminar
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($_GET['show_btn_nuevo']): ?>
                        <div class="<?= $_GET['show_btn_aprobar'] ? 'col-md-1' : 'col-md-6' ?> text-end">
                            <a href="<?= AppConstants::ROUTE_TASKS ?>/create" class="btn btn-setap-primary">
                                <i class="bi bi-plus-lg"></i> <?= AppConstants::UI_NEW_TASK ?>
                            </a>
                        </div>

                        <div class="<?= $_GET['show_btn_aprobar'] ? 'col-md-1' : 'col-md-6' ?> text-end">
                            <a href="<?= AppConstants::ROUTE_TASKS_CREATE_BY_PROCESS ?>/create" class="btn btn-setap-primary">
                                <i class="bi bi-plus-lg"></i> <?= AppConstants::UI_NEW_PROCESS ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="collapse show" id="collapseFiltros">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" id="getFormFilter" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Proveedor</label>
                                            <select class="form-select" id="proveedor_id" name="proveedor_id" onchange="this.form.submit()">
                                                <option value="">Selecionar...</option>
                                                <?php foreach ($data['suppliers'] as $supplier): ?>
                                                    <option value="<?= $supplier['id']; ?>" <?= (isset($_GET['proveedor_id']) && $_GET['proveedor_id'] == $supplier['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($supplier['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Proyecto</label>
                                            <select class="form-select" name="proyecto_id" id="proyecto_id" onchange="this.form.submit()">
                                                <option value="">Selecionar...</option>
                                                <?php foreach ($data['projects'] as $project): ?>
                                                    <option value="<?= $project['id'] ?>" <?= (isset($_GET['proyecto_id']) && $_GET['proyecto_id'] == $project['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($project['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
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

                                        <div class="col-md-3">
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

                                        <div class="col-md-3">
                                            <label class="form-label">Buscar Tarea (Autocompletar)</label>
                                            <div class="position-relative">
                                                <input type="text" class="form-control" id="task_autocomplete" name="tarea_nombre" placeholder="Escriba para buscar tarea..." value="<?= htmlspecialchars($_GET['tarea_nombre'] ?? '') ?>" autocomplete="off">
                                                <div id="autocomplete_results" class="list-group position-absolute w-100 mt-1 d-none" style="z-index: 1050; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Estado</label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id[]" multiple>
                                                <?php foreach ($data['taskStates'] as $state): ?>
                                                    <option value="<?= $state['id'] ?>" <?= (isset($_GET['estado_tipo_id']) && is_array($_GET['estado_tipo_id']) && in_array($state['id'], $_GET['estado_tipo_id'])) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($state['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="excluye_eliminados" name="excluye_eliminados" value="1" <?= (isset($_GET['excluye_eliminados']) && $_GET['excluye_eliminados'] == 1) ? 'checked' : '' ?> onchange="this.form.submit()">
                                                <label class="form-check-label" for="excluye_eliminados">Excluye eliminados</label>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Ejecuta</label>
                                            <select class="form-select" name="usuario_id" onchange="this.form.submit()">
                                                <option value="-1">Todos</option>
                                                <?php foreach ($data['users'] as $user): ?>
                                                    <option value="<?= $user['id'] ?>" <?= (isset($_GET['usuario_id']) && $_GET['usuario_id'] == $user['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($user['nombre_usuario']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="excluye_no_asignados" name="excluye_no_asignados" value="1" <?= (isset($_GET['excluye_no_asignados']) && $_GET['excluye_no_asignados'] == 1) ? 'checked' : '' ?> onchange="this.form.submit()">
                                                <label class="form-check-label" for="excluye_no_asignados">Excluye no asignados</label>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label">Inicio</label>
                                            <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>" onchange="this.form.submit()">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Fin</label>
                                            <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>" onchange="this.form.submit()">
                                        </div>

                                        <div class="col-md-1 d-flex align-items-center">
                                            <button type="submit" class="btn btn-outline-setap-primary me-2">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <a href="<?= AppConstants::ROUTE_TASKS ?>/listDireccionEspacio" class="btn btn-outline-secondary">
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
                                        <table class="table table-hover align-middle" id="tasksTable">
                                            <thead>
                                                <tr>
                                                    <th>Tarea</th>
                                                    <th>Estado</th>
                                                    <?php if ($_GET['show_col_ejecuta']): ?>
                                                        <th>Ejecuta</th>
                                                    <?php endif; ?>
                                                    <th>Fecha</th>
                                                    <th><?= $_GET['show_col_acciones'] ? 'Acciones' : 'Acción' ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $columnCount = $_GET['show_col_ejecuta'] ? 5 : 4;
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
                                                            <td colspan="<?= $columnCount ?>">
                                                                <i class="bi bi-folder2-open me-2"></i><?= htmlspecialchars($task['proyecto_nombre']) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>

                                                    <?php if ($directionKey !== $currentDirectionKey):
                                                        $currentDirectionKey = $directionKey;
                                                        $currentSpaceKey = null;
                                                    ?>
                                                        <tr class="group-row-direction">
                                                            <td colspan="<?= $columnCount ?>">
                                                                <i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($direccionLabel) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>

                                                    <?php if ($spaceKey !== $currentSpaceKey):
                                                        $currentSpaceKey = $spaceKey;
                                                    ?>
                                                        <tr class="group-row-space">
                                                            <td colspan="<?= $columnCount ?>">
                                                                <i class="bi bi-diagram-2 me-2"></i><?= htmlspecialchars($spaceRootLabel) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>

                                                    <tr class="clickable-row" id="task-row-<?= $task['id'] ?>" data-state-id="<?= (int)$task['estado_tipo_id'] ?>">
                                                        <td class="task-column">
                                                            <div class="fw-bold"><?= htmlspecialchars($task['tarea_nombre']) ?></div>
                                                            <?php if (!empty($task['descripcion'])): ?>
                                                                <small class="text-muted d-block"><?= htmlspecialchars(substr($task['descripcion'], 0, 100)) ?>...</small>
                                                            <?php endif; ?>
                                                            <small class="text-muted">
                                                                Espacio: <?= htmlspecialchars($task['espacio_nombre'] ?? 'Sin espacio') ?>
                                                                | Nivel: <?= htmlspecialchars((string)($task['espacio_nivel'] ?? 'Sin nivel')) ?>
                                                                | Orden: <?= htmlspecialchars((string)($task['espacio_orden'] ?? 'Sin orden')) ?>
                                                            </small>
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
                                                                ?>
                                                                <div class="d-flex align-items-center">
                                                                    <span class="badge <?= $badgeClass ?>" id="status-badge-<?= $task['id'] ?>">
                                                                        <?= $statusText ?>
                                                                    </span>
                                                                    <?php if ($_GET['show_btn_activity']): ?>
                                                                        <div class="dropdown ms-2">
                                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="stateDropdown<?= $task['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false" onclick="loadValidTransitions(<?= $task['id'] ?>)">
                                                                                <i class="bi bi-arrow-repeat"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" id="stateMenu<?= $task['id'] ?>">
                                                                                <li><span class="dropdown-item-text text-muted"><?= AppConstants::UI_LOADING; ?></span></li>
                                                                            </ul>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </small>
                                                        </td>
                                                        <?php if ($_GET['show_col_ejecuta']): ?>
                                                            <td>
                                                                <small>
                                                                    <?php if (!empty($task['ejecutor_nombre'])): ?>
                                                                        <i class="bi bi-person"></i> <?= htmlspecialchars($task['ejecutor_nombre']) ?>
                                                                    <?php else: ?>
                                                                        Sin asignar
                                                                    <?php endif; ?>
                                                                </small>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td>
                                                            <?php if (!empty($task['fecha_inicio'])): ?>
                                                                <small>
                                                                    <?= date('d/m/Y', strtotime($task['fecha_inicio'])) ?><br>
                                                                    <?php if (!empty($task['duracion_horas'])): ?>
                                                                        <strong>HH:</strong> <?= $task['duracion_horas'] ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            <?php else: ?>
                                                                <small class="text-muted">Error</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="<?= AppConstants::ROUTE_TASKS_SHOW ?>/<?= $task['id'] ?>" class="btn btn-outline-info" title="Ver detalles">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <?php if ($_GET['show_col_acciones']): ?>
                                                                    <?php
                                                                    $filterParams = $_GET;
                                                                    unset($filterParams['id']);
                                                                    $queryString = http_build_query($filterParams);
                                                                    $editUrl = AppConstants::ROUTE_TASKS_EDIT . "?id=" . $task['id'] . ($queryString ? "&" . $queryString : "");
                                                                    ?>
                                                                    <a href="<?= $editUrl ?>" class="btn btn-outline-setap-primary" title="Editar">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                    <button type="button" class="btn btn-outline-danger" onclick="deleteTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['tarea_nombre']) ?>', <?= $task['estado_tipo_id'] ?>)" title="Eliminar" id="delete-btn-<?= $task['id'] ?>">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
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
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal fade" id="deleteModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmar Eliminación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Estás seguro de que deseas eliminar la tarea <strong id="deleteTaskName"></strong>?</p>
                                <p class="text-muted small">Esta acción no se puede deshacer.</p>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="deleteScope" id="deleteScopeSingle" value="0" checked>
                                        <label class="form-check-label" for="deleteScopeSingle">Eliminar ocurrencia</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="deleteScope" id="deleteScopeAll" value="1">
                                        <label class="form-check-label" for="deleteScopeAll">Eliminar todas las ocurrencias</label>
                                    </div>
                                </div>
                                <div id="deleteWarning" class="alert alert-warning d-none">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span id="deleteWarningMessage"></span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="changeStateModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Cambiar Estado de Tarea</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="changeStateForm">
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
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="confirmChangeState">Cambiar Estado</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="changeStateFSRModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Cambiar Estado de Tarea</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="changeStateFormFSR">
                                    <?= Security::renderCsrfField() ?>
                                    <input type="hidden" id="changeStateTaskIdsFSR" name="task_ids">
                                    <input type="hidden" id="changeStateNewStateFSR" name="new_state">
                                    <div class="mb-3">
                                        <label class="form-label">Tarea:</label>
                                        <div id="changeStateTaskNameFSR" class="fw-bold text-primary"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nuevo Estado:</label>
                                        <div id="changeStateNewStateNameFSR" class="fw-bold text-success"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="changeStateReasonFSR" class="form-label">Motivo del cambio:</label>
                                        <textarea class="form-control" id="changeStateReasonFSR" name="reason" rows="3" placeholder="Describe el motivo del cambio de estado..."></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="confirmChangeStateFSR">Cambiar Estado</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script src="/setap/public/js/task-state-utils.js"></script>
    <script src="/setap/public/js/task-list.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
</body>

</html>
