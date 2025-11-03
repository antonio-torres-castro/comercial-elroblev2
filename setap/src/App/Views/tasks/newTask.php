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

                <!-- tarea_tipos Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-list-task"></i>Tareas</h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshHolidaysTable()">
                                    <i class="fas fa-sync"></i> Actualizar
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="holidays-table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Descripción</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tareas-tbody">
                                            <?php foreach ($data['tareas'] as $tarea): ?>
                                                <tr>
                                                    <td><?= $tarea['nombre'] ?></td>
                                                    <td><?= $tarea['descripcion'] ?></td>
                                                    <td><?= $tarea['estado'] ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $tarea['estado_tipo_id'] == 2 ? 'success' : 'secondary' ?>">
                                                            <?= htmlspecialchars($tarea['estado']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editHoliday(<?= $feriado['id'] ?>)" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteHoliday(<?= $feriado['id'] ?>)" title="Eliminar">
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
    </script>

    <!-- GAP 5: Task State Validation Utilities -->
    <script src="/setap/public/js/task-state-utils.js"></script>
</body>

</html>