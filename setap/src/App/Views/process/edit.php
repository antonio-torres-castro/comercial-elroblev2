<?php

use App\Helpers\Security;
use App\Constants\AppConstants;

$isAdmin = $data['user']['id'] == 1;
$processTasks = $data['processTasks'] ?? [];
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
    <style>
        .task-row:hover {
            background-color: #f8f9fa;
        }

        .task-search-results {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
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
                    </div>
                </div>

                <?php if (isset($data['errors']) && !empty($data['errors'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="bi bi-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
                        <ul class="mb-0">
                            <?php foreach ($data['errors'] as $error): ?>
                                <li><?= htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form id="processForm" method="POST" action="<?= AppConstants::ROUTE_PROCESSES ?>/update">
                    <?= Security::renderCsrfField() ?>
                    <input type="hidden" name="id" value="<?= $data['process_id']; ?>">
                    <input type="hidden" name="process_tasks_json" id="processTasksJson" value="">

                    <div class="row g-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Datos del Proceso</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <?php if ($isAdmin): ?>
                                            <div class="col-md-4">
                                                <label for="proveedor_id" class="form-label">
                                                    Proveedor<span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                                                    <option value="">Seleccione un proveedor</option>
                                                    <?php foreach ($data['suppliers'] as $supplier): ?>
                                                        <option value="<?= $supplier['id']; ?>"
                                                            <?= ($data['process']['proveedor_id'] ?? '') == $supplier['id'] ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($supplier['razon_social']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php else: ?>
                                            <input type="hidden" name="proveedor_id" id="proveedor_id"
                                                value="<?= $data['process']['proveedor_id'] ?? ''; ?>">
                                        <?php endif; ?>
                                        <div class="col-md-4">
                                            <label for="nombre" class="form-label">
                                                Nombre del Proceso<span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="nombre" name="nombre"
                                                maxlength="100" required
                                                value="<?= htmlspecialchars($data['process']['nombre'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="descripcion" class="form-label">Descripcion</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion"
                                                rows="2"><?= htmlspecialchars($data['process']['descripcion'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Agregar Tareas al Proceso</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label for="categoria_id" class="form-label">Categoria</label>
                                            <select class="form-select" id="categoria_id">
                                                <option value="">Todas las categorias</option>
                                                <?php foreach ($data['categories'] as $category): ?>
                                                    <option value="<?= $category['id']; ?>">
                                                        <?= htmlspecialchars($category['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label for="task_search" class="form-label">Buscar Tarea</label>
                                            <input type="text" class="form-control" id="task_search"
                                                placeholder="Buscar o seleccionar tarea..."
                                                autocomplete="on" role="combobox"
                                                aria-expanded="false"
                                                aria-controls="taskSearchResults">
                                            <div id="taskSearchResults"
                                                class="task-search-results list-group position-absolute w-100 mt-1 d-none"
                                                style="z-index: 1050;">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="tarea_hh" class="form-label">
                                                Duracion (hrs)<span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control" id="tarea_hh"
                                                min="0.5" step="0.5" value="0.5" required>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end gap-2">
                                            <button type="button" class="btn btn-setap-primary" id="btnAddTask">
                                                <i class="bi bi-plus-circle"></i> Agregar
                                            </button>
                                            <button type="button" class="btn btn-secondary" id="btnClearTasks">
                                                <i class="bi bi-trash"></i> Limpiar
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" id="btnNewTask">
                                                <i class="bi bi-folder-plus"></i> Nueva Tarea
                                            </button>
                                        </div>
                                    </div>
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
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="processTasksTable">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Tarea</th>
                                                    <th>Duracion</th>
                                                    <th>Categoria</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="processTasksBody">
                                                <?php foreach ($processTasks as $task): ?>
                                                    <tr data-task-id="<?= $task['tarea_id']; ?>" data-hh="<?= $task['hh']; ?>">
                                                        <td><?= htmlspecialchars($task['tarea_nombre']); ?></td>
                                                        <td><?= number_format($task['hh'], 1); ?> hrs</td>
                                                        <td><?= htmlspecialchars($task['categoria_nombre'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-info btn-view-task"
                                                                data-task-id="<?= $task['tarea_id']; ?>">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-task">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php if (empty($processTasks)): ?>
                                            <div class="text-center text-muted py-4" id="noTasksMessage">
                                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                                <p class="mb-0">No hay tareas agregadas al proceso</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= AppConstants::ROUTE_PROCESSES ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-lg"></i> <?= AppConstants::UI_BTN_CANCEL ?>
                                </a>
                                <button type="submit" class="btn btn-setap-primary" id="btnSaveProcess">
                                    <i class="bi bi-check-circle"></i> Actualizar Proceso
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
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

    <div class="modal fade" id="newTaskModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newTaskForm">
                    <div class="modal-body">
                        <?= Security::renderCsrfField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nueva_tarea_nombre" class="form-label">
                                    Nombre<span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nueva_tarea_nombre" name="nueva_tarea_nombre"
                                    maxlength="150" required>
                            </div>
                            <div class="col-md-3">
                                <label for="nueva_tarea_categoria" class="form-label">Categoria<span class="text-danger">*</span></label>
                                <select class="form-select" id="nueva_tarea_categoria" name="tarea_categoria_id" required>
                                    <?php foreach ($data['categories'] as $category): ?>
                                        <option value="<?= $category['id']; ?>">
                                            <?= htmlspecialchars($category['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="nueva_tarea_estado" class="form-label">Estado<span class="text-danger">*</span></label>
                                <select class="form-select" id="nueva_tarea_estado" name="estado_tipo_id" required>
                                    <option value="1">Activo</option>
                                    <option value="2">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="nueva_tarea_descripcion" class="form-label">Descripcion</label>
                                <textarea class="form-control" id="nueva_tarea_descripcion" name="nueva_tarea_descripcion"
                                    rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-setap-primary">
                            <i class="bi bi-check-circle"></i> Crear Tarea
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script src="/setap/public/js/process-edit.js"></script>
</body>

</html>