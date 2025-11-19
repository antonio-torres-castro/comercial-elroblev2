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
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <!-- Filtros y Búsqueda -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2>
                            <i class="bi bi-list-check"></i> <?= AppConstants::UI_MY_TASK_MANAGEMENT; ?>
                            <span class="badge bg-secondary ms-2"><?= $totalRows ?> tareas</span>

                            <button class="btn btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros" aria-expanded="false" aria-controls="collapseFiltros">
                                <i class="bi bi-eye"></i> Filtros
                            </button>
                        </h2>
                    </div>
                    <div class="col-md-6 text-end" hidden>
                        <a href="<?= AppConstants::ROUTE_TASKS ?>/create" class="btn btn-setap-primary">
                            <i class="bi bi-plus-lg"></i> <?= AppConstants::UI_NEW_TASK ?>
                        </a>
                    </div>
                </div>

                <div class="collapse show" id="collapseFiltros">
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
                                            <select class="form-select" name="estado_tipo_id">
                                                <option value="">Todos</option>
                                                <?php if (!empty($data['taskStates'])): ?>
                                                    <?php foreach ($data['taskStates'] as $state): ?>
                                                        <option value="<?= $state['id'] ?>"
                                                            <?= (isset($_GET['estado_tipo_id']) && $_GET['estado_tipo_id'] == $state['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($state['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2" hidden>
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

                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-outline-setap-primary me-2">
                                                <i class="bi bi-search"></i> Filtrar
                                            </button>
                                            <a href="<?= AppConstants::ROUTE_MY_TASKS ?>" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                        </div>
                                    </form>
                                </div>
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
                                        <table class="table table-hover" id="myTasksTable">
                                            <thead>
                                                <tr>
                                                    <th>Tarea</th>
                                                    <th>Estado</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data['tasks'] as $task): ?>
                                                    <tr id="task-row-<?= $task['id'] ?>">
                                                        <td onclick="viewDetail(<?= $task['id'] ?>)"><!-- Tarea -->
                                                            <div class="fw-bold"><?= htmlspecialchars($task['tarea_nombre']) ?></div>
                                                            <?php if (!empty($task['descripcion'])): ?>
                                                                <small class="text-hide" hidden><?= htmlspecialchars($task['descripcion']) ?></small>
                                                                <small class="text-muted"><?= htmlspecialchars(substr($task['descripcion'], 0, 50)) ?>...</small>
                                                            <?php endif; ?>
                                                        </td><!-- fin Tarea -->
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

                                                                    <?php
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
                                                                            $nextStateId = 6;
                                                                            $buttonLabel = 'Terminar';
                                                                            $nextStateName = 'terminado';
                                                                            $nextStateTypeButton = 'warning';
                                                                            break;
                                                                        case 7:
                                                                            $nextStateId = 6;
                                                                            $buttonLabel = 'Terminar';
                                                                            $nextStateName = 'terminado';
                                                                            $nextStateTypeButton = 'warning';
                                                                            break;
                                                                    }
                                                                    ?>
                                                                    <?php if (!empty($nextStateId)): ?>
                                                                        <button class="btn btn-sm btn-outline-<?= $nextStateTypeButton ?> ms-2" type="button"
                                                                            onclick="confirmStateChange(<?= $task['id'] ?>, <?= $nextStateId ?>, '<?= $nextStateName ?>')">
                                                                            <?= $buttonLabel ?>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </small>
                                                        </td><!-- fin Estado -->
                                                        <td><!-- Fechas -->
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
                                                        </td><!-- fin Fechass -->
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
                                                    <a class="page-link" href="<?= $baseUrl . '&page=' . ($currentPage - 1) ?>">&laquo;</a>
                                                </li>

                                                <!-- Números -->
                                                <?php
                                                $start = max(1, $currentPage - 1);
                                                $end = min($totalPages, $currentPage + 1);
                                                for ($i = $start; $i <= $end; $i++): ?>
                                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                        <a class="page-link" href="<?= $baseUrl . '&page=' . $i ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>

                                                <!-- Botón Siguiente -->
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
                                    <p class="text-muted">Comienza creando tu primera tarea.</p>
                                    <a href="<?= AppConstants::ROUTE_TASKS ?>/create" class="btn btn-setap-primary">
                                        <i class="bi bi-plus-lg"></i> Crear Primera Tarea
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modal para cambiar estado -->
                <div class="modal fade" id="changeStateModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Cambiar Estado a Tarea</h5>
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

                <!-- Modal para ver descripcion de tarea -->
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

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Fecha</label>
                                    <div id="detailTaskFechaDuracion" class="text"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Estado</label>
                                    <div id="detailTaskStateName" class="text-success"></div>
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

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
    <!-- GAP 5: Task State Validation Utilities -->
    <script src="/setap/public/js/task-state-utils.js"></script>
    <script src="/setap/public/js/my-task-list.js"></script>

</body>

</html>