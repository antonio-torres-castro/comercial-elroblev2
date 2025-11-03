<?php

use App\Helpers\Security;
use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title']; ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="javascript:history.back()" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK ?>
                        </a>
                    </div>
                </div>

                <!-- Mensajes de error -->
                <?php if (isset($data['error']) && !empty($data['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="bi bi-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
                        <p class="mb-0"><?= htmlspecialchars($data['error']); ?></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-pencil"></i> <?= $data['subtitle']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="createTaskForm" method="POST" action="<?= AppConstants::ROUTE_TASKS ?>/store">
                                    <?= Security::renderCsrfField() ?>

                                    <div class="row g-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="nueva_tarea_nombre" class="form-label">
                                                    Nombre<span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="nueva_tarea_nombre" name="nueva_tarea_nombre"
                                                    placeholder="Nombre descriptivo de la tarea"
                                                    value="<?= htmlspecialchars($_POST['nueva_tarea_nombre'] ?? ''); ?>"
                                                    maxlength="150">
                                            </div>
                                            <div class="col-md-6">
                                                <!-- Estado -->
                                                <label for="estado_tipo_id" class="form-label">Estado<span class="text-danger">*</span></label>
                                                <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                                    <?php foreach ($data['taskStates'] as $state): ?>
                                                        <option value="<?= $state['id']; ?>"
                                                            <?= (!empty($_POST['estado_tipo_id']) && $_POST['estado_tipo_id'] == $state['id']) || $state['id'] == 1 ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($state['nombre']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <label for="nueva_tarea_descripcion" class="form-label">Descripción</label>
                                                <textarea class="form-control" id="nueva_tarea_descripcion" name="nueva_tarea_descripcion"
                                                    placeholder="Descripción detallada de la tarea" rows="1"><?= htmlspecialchars($_POST['nueva_tarea_descripcion'] ?? ''); ?></textarea>
                                            </div>
                                        </div>

                                        <!-- Botones -->
                                        <div class="col-12">
                                            <hr>
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="<?= AppConstants::ROUTE_TASKS ?>" class="btn btn-secondary">
                                                    <i class="bi bi-x-lg"></i> <?= AppConstants::UI_BTN_CANCEL ?>
                                                </a>
                                                <button id="createBtn" type="submit" class="btn btn-setap-primary">
                                                    <i class="bi bi-plus-circle"></i> Crear
                                                </button>
                                            </div>
                                        </div>

                                    </div>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- tareas Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-list-task"></i>Tareas</h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshTasksTable()">
                                    <i class="fas fa-sync"></i> Actualizar
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tasks-table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Descripción</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tasks-tbody">
                                            <?php foreach ($data['tasks'] as $tarea): ?>
                                                <tr>
                                                    <td id="tdNombre" <?= $tarea['id'] ?>><?= $tarea['nombre'] ?></td>
                                                    <td id="tdDescripcion" <?= $tarea['id'] ?>><?= $tarea['descripcion'] ?></td>
                                                    <td id="tdEstadoTipoId" <?= $tarea['id'] ?> hidden><?= $tarea['estado_tipo_id'] ?></td>
                                                    <td id="tdEstado" <?= $tarea['id'] ?>>
                                                        <span class="badge bg-<?= $tarea['estado_tipo_id'] == 2 ? 'success' : 'secondary' ?>">
                                                            <?= htmlspecialchars($tarea['estado']) ?>
                                                        </span>
                                                    </td>
                                                    <td id="tdAccionId" <?= $tarea['id'] ?>>
                                                        <button id="tdBtnEdit" <?= $tarea['id'] ?> class="btn btn-sm btn-outline-primary" onclick="editTask(<?= $tarea['id'] ?>)" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button id="tdBtnDel" <?= $tarea['id'] ?> class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?= $tarea['id'] ?>)" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
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


    <!-- Edit Task Modal -->
    <div class="modal fade" id="editTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="edit-task-form">
                    <div class="modal-body">
                        <?= Security::renderCsrfField() ?>
                        <input type="hidden" name="id" id="edit-task-id">

                        <div class="mb-3">
                            <label for="editTareaNombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="editTareaNombre" name="editTareaNombre" value="" maxlength="150">
                        </div>

                        <div class="mb-3">
                            <label for="editTareaDescripcion" class="form-label">Descripcion</label>
                            <input type="text" class="form-control" id="editTareaDescripcion" name="editTareaDescripcion" value="" maxlength="300">
                        </div>
                        <div class="mb-3">
                            <!-- Estado -->
                            <label for="editEstadoTipoId" class="form-label">Estado<span class="text-danger">*</span></label>
                            <select class="form-select" id="editEstadoTipoId" name="editEstadoTipoId" required>
                                <?php foreach ($data['taskStates'] as $state): ?>
                                    <option value="<?= $state['id']; ?>">
                                        <?= htmlspecialchars($state['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <!-- ********Boton Submit *********************-->
                        <button type="submit" class="btn btn-primary" id="btn-actualizar-editTaskModal">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>

    <script>
        // Validación y envío
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createTaskForm');
            const createBtn = document.getElementById('createBtn');

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                if (!confirm('¿Deseas crear esta tarea?')) {
                    e.preventDefault();
                    return;
                }

                createBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Enviando...';
                createBtn.disabled = true;
            });
        });

        /**
         * Inicializar manejador de formulario
         */
        function initializeFormHandlers() {
            // Formulario edición
            const editForm = document.getElementById('edit-task-form');
            if (editForm) {
                editForm.addEventListener('submit', handleEditSubmit);
            }
        }

        /**
         * Actualizar tabla de tareas
         */
        async function refreshTasksTable() {
            try {
                const response = await fetch(`/setap/tasks/refreshTasksTable`);
                const data = await response.json();

                if (data.success) {
                    updateTasksTable(data.tareas);
                } else {
                    console.error('Error al cargar tareas:', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        /**
         * Actualizar contenido de la tabla de feriados
         */
        function updateHolidaysTable(tareas) {
            const tbody = document.getElementById('holidays-tbody');
            let html = '';

            tareas.forEach(tarea => {
                html += `
        <tr>
            <td>${tarea.nombre}</td>
            <td>${tarea.descripcion}</td>
            <td>
                <span class="badge bg-${tarea.estado_tipo_id == 2 ? 'success' : 'secondary'}">
                    ${tarea.estado}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editTask(${tarea.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(${tarea.id})" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
            });

            tbody.innerHTML = html;
        }

        /**
         * Editar feriado
         */
        async function editTask(id) {
            try {
                // Aquí podrías cargar los datos del feriado específico
                // Por simplicidad, usaremos los datos de la tabla
                const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));

                // Configurar el formulario modal
                document.getElementById('edit-task-id').value = id;
                document.getElementById('editTareaNombre').value = document.getElementById('tdNombre' + id).value;
                document.getElementById('editTareaDescripcion').value = document.getElementById('tdDescripcion' + id).value;
                document.getElementById('editEstadoTipoId').value = document.getElementById('tdEstadoTipoId' + id).value;

                modal.show();
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Error al cargar datos de tarea');
            }
        }

        /**
         * Eliminar feriado
         */
        async function deleteTask(id) {
            if (!confirm('¿Está seguro de que desea eliminar esta tarea?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', getCsrfToken());
                formData.append('id', id);

                const response = await fetch('/setap/tasks/deleteT', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('success', data.message);
                    refreshTasksTable();
                } else {
                    showAlert('error', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Error al eliminar tarea');
            }
        }
    </script>
    <!-- GAP 5: Task State Validation Utilities -->
    <script src="/setap/public/js/task-state-utils.js"></script>
</body>

</html>