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
                        <a href="<?= AppConstants::ROUTE_TASKS ?>" class="btn btn-sm btn-secondary">
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

                        <form id="createTaskForm" method="POST" action="<?= AppConstants::ROUTE_TASKS ?>/store">
                            <?= Security::renderCsrfField() ?>
                            <!-- Definicion tarea catalogo:inicio-->
                            <!-- Tarea Catálogo -->
                            <div class="col-md-12">
                                <select class="form-select" id="tarea_id" name="tarea_id" required>
                                    <option value="">Seleccionar tarea existente...</option>
                                    <?php foreach ($data['tasks'] as $taskType): ?>
                                        <option value="<?= $taskType['id']; ?>"
                                            <?= (isset($_POST['tarea_id']) && $_POST['tarea_id'] == $taskType['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($taskType['nombre']); ?> - <?= htmlspecialchars($taskType['descripcion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="nueva" <?= (isset($_POST['tarea_id']) && $_POST['tarea_id'] == 'nueva') ? 'selected' : ''; ?>>
                                        ➕ Crear nueva tarea
                                    </option>
                                </select>
                                <div class="form-text mb-3">Seleccione del catálogo o cree una nueva.</div>
                            </div>
                            <!-- Campos para nueva tarea (ocultos por defecto) -->
                            <div class="col-12" id="nueva-tarea-fields" style="display: none;">
                                <div class="card border-primary mb-2">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva tarea en estado activo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="nueva_tarea_nombre" class="form-label">
                                                    Nombre llave <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="nueva_tarea_nombre" name="nueva_tarea_nombre"
                                                    placeholder="Nombre descriptivo de la tarea"
                                                    value="<?= htmlspecialchars($_POST['nueva_tarea_nombre'] ?? ''); ?>"
                                                    maxlength="150">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="nueva_tarea_descripcion" class="form-label">Descripción</label>
                                                <textarea class="form-control" id="nueva_tarea_descripcion" name="nueva_tarea_descripcion"
                                                    placeholder="Descripción detallada de la tarea" rows="3"><?= htmlspecialchars($_POST['nueva_tarea_descripcion'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Definicion tarea catalogo:fin-->

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-list-task"></i> <?= $data['subtitle']; ?></h5>
                                </div>
                                <div class="card-body">

                                    <div class="row g-3">
                                        <!-- Proyecto -->
                                        <div class="col-md-12">
                                            <label for="proyecto_id" class="form-label">
                                                Proyecto <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="proyecto_id" name="proyecto_id" required>
                                                <option value="">Seleccionar proyecto...</option>
                                                <?php foreach ($data['projects'] as $project): ?>
                                                    <option value="<?= $project['id']; ?>"
                                                        <?= ($data['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($project['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Asignaciones usuario:inicio -->
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="text-muted"><i class="bi bi-people"></i> Asignación de Usuarios</h6>
                                        </div>

                                        <!-- Ejecutor -->
                                        <div class="col-md-6">
                                            <label for="ejecutor_id" class="form-label">Ejecutor</label>
                                            <select class="form-select" id="ejecutor_id" name="ejecutor_id" required>
                                                <option value="">Sin asignar</option>
                                                <?php foreach ($data['executor_users'] as $user): ?>
                                                    <option value="<?= $user['id']; ?>"
                                                        <?= (isset($_POST['ejecutor_id']) && $_POST['ejecutor_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($user['nombre_completo'] . ' (' . $user['nombre_usuario'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Inicia/Termina tarea.</div>
                                        </div>

                                        <!-- Supervisor -->
                                        <div class="col-md-6">
                                            <label for="supervisor_id" class="form-label">Supervisor</label>
                                            <select class="form-select" id="supervisor_id" name="supervisor_id" required>
                                                <option value="">Sin supervisor</option>
                                                <?php foreach ($data['supervisor_users'] as $user): ?>
                                                    <option value="<?= $user['id']; ?>"
                                                        <?= (isset($_POST['supervisor_id']) && $_POST['supervisor_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($user['nombre_completo'] . ' (' . $user['nombre_usuario'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Aprueba/Rechaza tarea.</div>
                                        </div>
                                        <!-- Asignaciones usuario:fin -->

                                        <!-- Programación -->
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="text-muted"><i class="bi bi-calendar"></i> Programación</h6>
                                        </div>

                                        <!-- Tarea Tipo -->
                                        <div class="col-md-2">
                                            <label for="tarea_tipo_id" class="form-label">Tipo<span class="text-danger">*</span></label>
                                            <select class="form-select" id="tarea_tipo_id" name="tarea_tipo_id" required>
                                                <option value="">Seleccionar tipo</option>
                                                <?php foreach ($data['taskTypes'] as $type): ?>
                                                    <option value="<?= $type['id']; ?>"
                                                        <?= (isset($data['task']['tarea_tipo_id']) && $data['task']['tarea_tipo_id'] == $type['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($type['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Estado -->
                                        <div class="col-md-2">
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

                                        <!-- Fecha de inicio -->
                                        <div class="col-md-2">
                                            <label for="fecha_inicio" class="form-label">Inicio<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required
                                                value="<?= htmlspecialchars($_POST['fecha_inicio'] ?? date('Y-m-d')); ?>">
                                        </div>

                                        <div class="col-md-2">
                                            <label for="fecha_fin" class="form-label">Fin<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required
                                                value="<?= htmlspecialchars($_POST['fecha_fin'] ?? date('Y-m-d')); ?>">
                                        </div>

                                        <!-- Duración -->
                                        <div class="col-md-2">
                                            <label for="duracion_horas" class="form-label">Duración(horas)</label>
                                            <input type="number" class="form-control" id="duracion_horas" name="duracion_horas" step="0.5" min="0.5" max="24" required
                                                value="<?= htmlspecialchars($_POST['duracion_horas'] ?? '1.0'); ?>">
                                        </div>

                                        <!-- Prioridad -->
                                        <div class="col-md-2">
                                            <label for="prioridad" class="form-label">Prioridad</label>
                                            <select class="form-select" id="prioridad" name="prioridad" required>
                                                <option value="0" <?= ($_POST['prioridad'] ?? '') === '0' ? 'selected' : ''; ?>>0 - Baja</option>
                                                <option value="3" <?= ($_POST['prioridad'] ?? '') === '3' ? 'selected' : ''; ?>>3 - Normal</option>
                                                <option value="5" <?= (!isset($_POST['prioridad']) || $_POST['prioridad'] == '5') ? 'selected' : ''; ?>>5 - Media</option>
                                                <option value="7" <?= ($_POST['prioridad'] ?? '') === '7' ? 'selected' : ''; ?>>7 - Alta</option>
                                                <option value="10" <?= ($_POST['prioridad'] ?? '') === '10' ? 'selected' : ''; ?>>10 - Crítica</option>
                                            </select>
                                        </div>

                                        <!-- Botones -->
                                        <div class="col-12">
                                            <hr>
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="<?= AppConstants::ROUTE_TASKS ?>" class="btn btn-secondary">
                                                    <i class="bi bi-x-lg"></i> <?= AppConstants::UI_BTN_CANCEL ?>
                                                </a>
                                                <button id="createBtn" type="submit" class="btn btn-setap-primary">
                                                    <i class="bi bi-plus-circle"></i> Asignar Tarea
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>


                        </form>

                    </div>
                </div>

            </main>

        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>

    <script>
        // Mostrar/ocultar campos de nueva tarea
        const tareaSelect = document.getElementById('tarea_id');
        const nuevaTareaFields = document.getElementById('nueva-tarea-fields');
        const nuevaTareaNombre = document.getElementById('nueva_tarea_nombre');

        tareaSelect.addEventListener('change', function() {
            if (this.value === 'nueva') {
                nuevaTareaFields.style.display = 'block';
                nuevaTareaNombre.setAttribute('required', 'required');
            } else {
                nuevaTareaFields.style.display = 'none';
                nuevaTareaNombre.removeAttribute('required');
            }
        });
        tareaSelect.dispatchEvent(new Event('change'));

        // Validación y envío
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createTaskForm');
            const createBtn = document.getElementById('createBtn');
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaFin = document.getElementById('fecha_fin');

            function validateDates() {
                if (fechaInicio.value && fechaFin.value) {
                    const inicio = new Date(fechaInicio.value);
                    const fin = new Date(fechaFin.value);
                    if (fin < inicio) {
                        fechaFin.setCustomValidity('Fecha fin menor que fecha de inicio');
                        return false;
                    } else {
                        fechaFin.setCustomValidity('');
                    }
                }
                return true;
            }

            fechaInicio.addEventListener('change', () => {
                fechaFin.min = fechaInicio.value;
                validateDates();
            });

            fechaFin.addEventListener('change', validateDates);

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    alert('Corrige las fechas antes de enviar.');
                    return;
                }

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