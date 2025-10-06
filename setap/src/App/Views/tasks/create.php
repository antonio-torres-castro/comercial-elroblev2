<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/tasks" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Tareas
                        </a>
                    </div>
                </div>

                <!-- Mensajes de error -->
                <?php if (isset($data['error']) && !empty($data['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="bi bi-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($data['error']); ?></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-list-task"></i> <?php echo $data['subtitle']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/tasks/store">
                                    <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\Security::generateCsrfToken(); ?>">

                                    <div class="row g-3">
                                        <!-- Proyecto -->
                                        <div class="col-md-6">
                                            <label for="proyecto_id" class="form-label">
                                                Proyecto <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="proyecto_id" name="proyecto_id" required>
                                                <option value="">Seleccionar proyecto...</option>
                                                <?php foreach ($data['projects'] as $project): ?>
                                                    <option value="<?php echo $project['id']; ?>"
                                                        <?php echo (isset($_POST['proyecto_id']) && $_POST['proyecto_id'] == $project['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($project['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Proyecto al que se asignará la tarea.</div>
                                        </div>

                                        <!-- Tipo de tarea -->
                                        <div class="col-md-6">
                                            <label for="tarea_id" class="form-label">
                                                Tarea <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="tarea_id" name="tarea_id" required>
                                                <option value="">Seleccionar tarea existente...</option>
                                                <?php foreach ($data['taskTypes'] as $taskType): ?>
                                                    <option value="<?php echo $taskType['id']; ?>"
                                                        <?php echo (isset($_POST['tarea_id']) && $_POST['tarea_id'] == $taskType['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($taskType['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="nueva" <?php echo (isset($_POST['tarea_id']) && $_POST['tarea_id'] == 'nueva') ? 'selected' : ''; ?>>
                                                    ➕ Crear nueva tarea
                                                </option>
                                            </select>
                                            <div class="form-text">Seleccione del catálogo o cree una nueva.</div>
                                        </div>

                                        <!-- Campos para nueva tarea (ocultos por defecto) -->
                                        <div class="col-12" id="nueva-tarea-fields" style="display: none;">
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white">
                                                    <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva Tarea</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label for="nueva_tarea_nombre" class="form-label">
                                                                Nombre de la nueva tarea <span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text" class="form-control" id="nueva_tarea_nombre" name="nueva_tarea_nombre"
                                                                   placeholder="Nombre descriptivo de la tarea"
                                                                   value="<?php echo htmlspecialchars($_POST['nueva_tarea_nombre'] ?? ''); ?>"
                                                                   maxlength="150">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="nueva_tarea_descripcion" class="form-label">Descripción</label>
                                                            <textarea class="form-control" id="nueva_tarea_descripcion" name="nueva_tarea_descripcion"
                                                                      placeholder="Descripción detallada de la tarea" rows="3"><?php echo htmlspecialchars($_POST['nueva_tarea_descripcion'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Asignaciones -->
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="text-muted">
                                                <i class="bi bi-people"></i> Asignación de Usuarios
                                            </h6>
                                        </div>

                                        <!-- Ejecutor -->
                                        <div class="col-md-4">
                                            <label for="ejecutor_id" class="form-label">Ejecutor</label>
                                            <select class="form-select" id="ejecutor_id" name="ejecutor_id">
                                                <option value="">Sin asignar</option>
                                                <?php foreach ($data['users'] as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>"
                                                        <?php echo (isset($_POST['ejecutor_id']) && $_POST['ejecutor_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['nombre_completo'] . ' (' . $user['nombre_usuario'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Usuario que ejecutará la tarea.</div>
                                        </div>

                                        <!-- Supervisor -->
                                        <div class="col-md-4">
                                            <label for="supervisor_id" class="form-label">Supervisor</label>
                                            <select class="form-select" id="supervisor_id" name="supervisor_id">
                                                <option value="">Sin supervisor</option>
                                                <?php foreach ($data['users'] as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>"
                                                        <?php echo (isset($_POST['supervisor_id']) && $_POST['supervisor_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['nombre_completo'] . ' (' . $user['nombre_usuario'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Usuario que supervisará la tarea.</div>
                                        </div>

                                        <!-- Estado -->
                                        <div class="col-md-4">
                                            <label for="estado_tipo_id" class="form-label">
                                                Estado Inicial <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                                <?php foreach ($data['taskStates'] as $state): ?>
                                                    <option value="<?php echo $state['id']; ?>"
                                                        <?php
                                                        $selected = false;
                                                        if (isset($_POST['estado_tipo_id'])) {
                                                            $selected = ($_POST['estado_tipo_id'] == $state['id']);
                                                        } elseif ($state['id'] == 1) { // Creado por defecto
                                                            $selected = true;
                                                        }
                                                        echo $selected ? 'selected' : '';
                                                        ?>>
                                                        <?php echo htmlspecialchars($state['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Programación -->
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="text-muted">
                                                <i class="bi bi-calendar"></i> Programación
                                            </h6>
                                        </div>

                                        <!-- Fecha de inicio -->
                                        <div class="col-md-4">
                                            <label for="fecha_inicio" class="form-label">
                                                Fecha de Inicio <span class="text-danger">*</span>
                                            </label>
                                            <input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio" required
                                                   value="<?php echo htmlspecialchars($_POST['fecha_inicio'] ?? date('Y-m-d\TH:i')); ?>">
                                        </div>

                                        <!-- Duración -->
                                        <div class="col-md-4">
                                            <label for="duracion_horas" class="form-label">Duración (horas)</label>
                                            <input type="number" class="form-control" id="duracion_horas" name="duracion_horas"
                                                   step="0.5" min="0.5" max="24"
                                                   value="<?php echo htmlspecialchars($_POST['duracion_horas'] ?? '1.0'); ?>">
                                            <div class="form-text">Duración estimada en horas (0.5 - 24).</div>
                                        </div>

                                        <!-- Prioridad -->
                                        <div class="col-md-4">
                                            <label for="prioridad" class="form-label">Prioridad</label>
                                            <select class="form-select" id="prioridad" name="prioridad">
                                                <option value="0" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] == '0') ? 'selected' : ''; ?>>0 - Baja</option>
                                                <option value="3" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] == '3') ? 'selected' : ''; ?>>3 - Normal</option>
                                                <option value="5" <?php echo ((!isset($_POST['prioridad'])) || $_POST['prioridad'] == '5') ? 'selected' : ''; ?>>5 - Media</option>
                                                <option value="7" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] == '7') ? 'selected' : ''; ?>>7 - Alta</option>
                                                <option value="10" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] == '10') ? 'selected' : ''; ?>>10 - Crítica</option>
                                            </select>
                                        </div>

                                        <!-- Botones -->
                                        <div class="col-12">
                                            <hr>
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="/tasks" class="btn btn-secondary">
                                                    <i class="bi bi-x-lg"></i> Cancelar
                                                </a>
                                                <button type="submit" class="btn btn-setap-primary">
                                                    <i class="bi bi-plus-circle"></i> Asignar Tarea
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

    <script>
        // Mostrar/ocultar campos de nueva tarea
        document.getElementById('tarea_id').addEventListener('change', function() {
            const nuevaTareaFields = document.getElementById('nueva-tarea-fields');
            const nuevaTareaNombre = document.getElementById('nueva_tarea_nombre');

            if (this.value === 'nueva') {
                nuevaTareaFields.style.display = 'block';
                nuevaTareaNombre.setAttribute('required', 'required');
            } else {
                nuevaTareaFields.style.display = 'none';
                nuevaTareaNombre.removeAttribute('required');
            }
        });

        // Trigger inicial
        document.getElementById('tarea_id').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tarea_tipo_id" class="form-label">Tipo de Tarea <span class="required">*</span></label>
                                    <select class="form-select" id="tarea_tipo_id" name="tarea_tipo_id" required>
                                        <option value="">Seleccionar tipo</option>
                                        <?php if (!empty($data['taskTypes'])): ?>
                                            <?php foreach ($data['taskTypes'] as $type): ?>
                                                <option value="<?= $type['id'] ?>"
                                                    <?= (isset($data['task']['tarea_tipo_id']) && $data['task']['tarea_tipo_id'] == $type['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($type['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Planificación -->
                    <div class="form-section">
                        <h5><i class="bi bi-calendar"></i> Planificación</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                           value="<?= $data['task']['fecha_inicio'] ?? '' ?>">
                                    <div class="form-text">Campo opcional</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                           value="<?= $data['task']['fecha_fin'] ?? '' ?>">
                                    <div class="form-text">Campo opcional</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Asignación -->
                    <div class="form-section">
                        <h5><i class="bi bi-person-check"></i> Asignación</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuario_id" class="form-label">Asignar a Usuario</label>
                                    <select class="form-select" id="usuario_id" name="usuario_id">
                                        <option value="">Sin asignar</option>
                                        <?php if (!empty($data['users'])): ?>
                                            <?php foreach ($data['users'] as $user): ?>
                                                <option value="<?= $user['id'] ?>"
                                                    <?= (isset($data['task']['usuario_id']) && $data['task']['usuario_id'] == $user['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($user['nombre_usuario']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text">Campo opcional. Se puede asignar posteriormente.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estado_tipo_id" class="form-label">Estado Inicial <span class="required">*</span></label>
                                    <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                        <?php if (!empty($data['taskStates'])): ?>
                                            <?php foreach ($data['taskStates'] as $state): ?>
                                                <?php
                                                // GAP 5: Solo permitir estados válidos para creación (1=Creado, 2=Activo)
                                                if (!in_array($state['id'], [1, 2])) continue;
                                                ?>
                                                <option value="<?= $state['id'] ?>"
                                                    <?= ((isset($data['task']['estado_tipo_id']) && $data['task']['estado_tipo_id'] == $state['id']) ||
                                                        (!isset($data['task']) && $state['id'] == 1)) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($state['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i>
                                        Las tareas se crean en estado "Creado" por defecto. Pueden activarse inmediatamente si están listas.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="mt-4 text-end">
                            <a href="/tasks" class="btn btn-secondary me-2">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success" id="createBtn">
                                <i class="bi bi-check-lg"></i> Crear Tarea
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createTaskForm');
            const createBtn = document.getElementById('createBtn');
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaFin = document.getElementById('fecha_fin');

            // Validación de fechas
            function validateDates() {
                if (fechaInicio.value && fechaFin.value) {
                    const inicio = new Date(fechaInicio.value);
                    const fin = new Date(fechaFin.value);

                    if (fin < inicio) {
                        fechaFin.setCustomValidity('La fecha de fin debe ser posterior a la fecha de inicio');
                        return false;
                    } else {
                        fechaFin.setCustomValidity('');
                        return true;
                    }
                }
                return true;
            }

            fechaInicio.addEventListener('change', function() {
                fechaFin.min = this.value;
                validateDates();
            });

            fechaFin.addEventListener('change', validateDates);

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    alert('Por favor, corrige los errores en las fechas.');
                    return;
                }

                const nombre = document.getElementById('nombre').value.trim();
                const proyecto_id = document.getElementById('proyecto_id').value;
                const tarea_tipo_id = document.getElementById('tarea_tipo_id').value;

                if (!nombre) {
                    e.preventDefault();
                    alert('El nombre de la tarea es obligatorio.');
                    document.getElementById('nombre').focus();
                    return;
                }

                if (!proyecto_id) {
                    e.preventDefault();
                    alert('Debe seleccionar un proyecto.');
                    document.getElementById('proyecto_id').focus();
                    return;
                }

                if (!tarea_tipo_id) {
                    e.preventDefault();
                    alert('Debe seleccionar un tipo de tarea.');
                    document.getElementById('tarea_tipo_id').focus();
                    return;
                }

                if (!confirm('¿Estás seguro de que deseas crear esta tarea?')) {
                    e.preventDefault();
                    return;
                }

                // Mostrar indicador de carga
                createBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Creando...';
                createBtn.disabled = true;

                // No deshabilitar los inputs del formulario - esto impide que se envíen los datos
                // Solo deshabilitar botones adicionales si existen
                const additionalButtons = form.querySelectorAll('button:not([type="submit"])');
                additionalButtons.forEach(button => {
                    button.disabled = true;
                });
            });
        });
    </script>

    <!-- GAP 5: Task State Validation Utilities -->
    <script src="/js/task-state-utils.js"></script>
</body>
</html>