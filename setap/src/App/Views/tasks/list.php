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
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <!-- Titulo y boton nuevo -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2>
                            <i class="bi bi-list-check"></i> <?php echo AppConstants::UI_TASK_MANAGEMENT; ?>
                            <span class="badge bg-secondary ms-1"><?= $totalRows ?> tareas</span>
                        </h2>

                    </div>

                    <?php if ($_GET['show_btn_nuevo']): ?>
                        <div class="col-md-6 text-end">
                            <a href="<?= AppConstants::ROUTE_TASKS ?>/create" class="btn btn-setap-primary">
                                <i class="bi bi-plus-lg"></i> <?= AppConstants::UI_NEW_TASK ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Filtros -->
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

                                    <div class="col-md-2">
                                        <label for="fecha_inicio" class="form-label">Inicio</label>
                                        <input type="date" class="form-control" name="fecha_inicio" id="fecha_hasta"
                                            value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="fecha_fin" class="form-label">Fin</label>
                                        <input type="date" class="form-control" name="fecha_fin" id="fecha_hasta"
                                            value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-2 d-flex align-items-center">
                                        <button type="submit" class="btn btn-outline-setap-primary me-2">
                                            <i class="bi bi-search"></i> Filtrar
                                        </button>
                                        <a href="<?= AppConstants::ROUTE_TASKS ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Tareas -->
                <div class="row">
                    <div class="col-12">
                        <?php if (!empty($data['tasks'])): ?>
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive-xl">
                                        <table class="table table-hover" id="tasksTable">
                                            <thead>
                                                <tr>
                                                    <th>Tarea</th>
                                                    <?php if ($_GET['show_col_proyecto']): ?>
                                                        <th>Proyecto</th>
                                                    <?php endif; ?>
                                                    <th>Estado</th>
                                                    <?php if ($_GET['show_col_ejecuta']): ?>
                                                        <th>Ejecuta</th>
                                                    <?php endif; ?>
                                                    <th>Fecha</th>
                                                    <?php if ($_GET['show_col_acciones']): ?>
                                                        <th>Acciones</th>
                                                    <?php else: ?>
                                                        <th>Acción</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data['tasks'] as $task): ?>
                                                    <tr class="clickable-row" id="task-row-<?= $task['id'] ?>">
                                                        <td id="task-column"><!-- Tarea -->
                                                            <div class="fw-bold"><?= htmlspecialchars($task['tarea_nombre']) ?></div>
                                                            <?php if (!empty($task['descripcion'])): ?>
                                                                <small class="text-muted"><?= htmlspecialchars(substr($task['descripcion'], 0, 100)) ?>...</small>
                                                            <?php endif; ?>
                                                        </td><!-- fin Tarea -->
                                                        <td <?= $_GET['show_col_proyecto'] ? '' : 'hidden' ?>><!-- Proyecto -->
                                                            <small>
                                                                <?= htmlspecialchars($task['cliente_nombre']) ?>
                                                            </small>
                                                        </td><!-- fin Proyecto -->
                                                        <td><!-- Estado -->
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
                                                                        <!-- GAP 5: Botón para cambiar estado -->
                                                                        <div class="dropdown ms-2">
                                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                                                id="stateDropdown<?= $task['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false"
                                                                                onclick="loadValidTransitions(<?= $task['id'] ?>)">
                                                                                <i class="bi bi-arrow-repeat"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" id="stateMenu<?= $task['id'] ?>">
                                                                                <li><span class="dropdown-item-text text-muted"><?php echo AppConstants::UI_LOADING; ?></span></li>
                                                                            </ul>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                </div>
                                                            </small>
                                                        </td><!-- fin Estado -->
                                                        <td <?= $_GET['show_col_ejecuta'] ? '' : 'hidden' ?>><!-- Asignado -->
                                                            <small>
                                                                <?php if (!empty($task['ejecutor_nombre'])): ?>
                                                                    <i class="bi bi-person"></i> <?= htmlspecialchars($task['ejecutor_nombre']) ?>
                                                                <?php else: ?>
                                                                    Sin asignar
                                                                <?php endif; ?>
                                                            </small>
                                                        </td><!-- fin Asignado -->
                                                        <td><!-- Fechas -->
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
                                                        </td><!-- fin Fechass -->
                                                        <td><!-- Acciones -->
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="<?= AppConstants::ROUTE_TASKS_SHOW ?>/<?= $task['id'] ?>" class="btn btn-outline-info" title="Ver detalles">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <?php if ($_GET['show_col_acciones']): ?>
                                                                    <a href="<?= AppConstants::ROUTE_TASKS_EDIT ?>?id=<?= $task['id'] ?>" class="btn btn-outline-setap-primary" title="Editar">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                    <!-- GAP 5: Validar si puede eliminar según estado -->
                                                                    <button type="button" class="btn btn-outline-danger"
                                                                        onclick="deleteTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['tarea_nombre']) ?>', <?= $task['estado_tipo_id'] ?>)"
                                                                        title="Eliminar" id="delete-btn-<?= $task['id'] ?>">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td><!-- fin Acciones -->
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Paginación -->
                                    <?php if ($totalPages > 1): ?>
                                        <nav aria-label="Navegación de páginas" class="mt-3">
                                            <ul class="pagination justify-content-center">
                                                <?php
                                                $queryString = $_GET;
                                                unset($queryString['page']);
                                                $baseUrl = '?' . http_build_query($queryString);
                                                ?>

                                                <!-- Botón Anterior -->
                                                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="<?= $baseUrl . '&page=' . ($currentPage - 1) ?>">Anterior</a>
                                                </li>

                                                <!-- Números -->
                                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                        <a class="page-link" href="<?= $baseUrl . '&page=' . $i ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>

                                                <!-- Botón Siguiente -->
                                                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="<?= $baseUrl . '&page=' . ($currentPage + 1) ?>">Siguiente</a>
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

                <!-- Modal para confirmar eliminación -->
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

                <!-- Modal para cambiar estado -->
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
                                        <textarea class="form-control" id="changeStateReason" name="reason" rows="3"
                                            placeholder="Describe el motivo del cambio de estado..."></textarea>
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


            </main>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
    <!-- GAP 5: Task State Validation Utilities -->
    <script src="/setap/public/js/task-state-utils.js"></script>
    <script src="/setap/public/js/task-list.js"></script>

    <!-- Choices.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

</body>

</html>