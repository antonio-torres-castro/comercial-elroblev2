<?php

use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= AppConstants::UI_EDIT_TASK_TITLE ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
    <style>
        .form-section {
            background: var(--setap-bg-light);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-section h5 {
            color: var(--setap-text-muted);
            border-bottom: 2px solid var(--setap-border-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .required {
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-light">
    <?php

    use App\Helpers\Security; ?>

    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="card-title mb-0">
                            <i class="bi bi-pencil-square"></i> <?= AppConstants::UI_EDIT_TASK_TITLE ?>: <?= htmlspecialchars($task['tarea_nombre']) ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= AppConstants::ROUTE_TASKS ?>/update" id="taskEditForm">
                            <?= \App\Helpers\Security::renderCsrfField() ?>
                            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">

                            <div class="row">
                                <div class="col-12">
                                    <!-- Información Básica -->
                                    <div class="form-section">
                                        <h5><i class="bi bi-info-circle"></i> Información Básica</h5>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="proyecto_id" class="form-label">Proyecto <span class="required">*</span></label>
                                                    <select class="form-select" id="proyecto_id" name="proyecto_id" required>
                                                        <option value="">Seleccionar Proyecto</option>
                                                        <?php foreach ($projects as $project): ?>
                                                            <option value="<?= (int)$project['id'] ?>"
                                                                <?= $project['id'] == $task['proyecto_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($project['cliente_nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="tarea_tipo_id" class="form-label">Tipo de Tarea <span class="required">*</span></label>
                                                    <select class="form-select" id="tarea_tipo_id" name="tarea_tipo_id" required>
                                                        <option value="">Seleccionar Tipo</option>
                                                        <?php foreach ($taskTypes as $type): ?>
                                                            <option value="<?= (int)$type['id'] ?>"
                                                                <?= $type['id'] == $task['tarea_tipo_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($type['nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre de la Tarea <span class="required">*</span></label>
                                            <input type="text" class="form-control" id="nombre" name="nombre"
                                                value="<?= htmlspecialchars($task['tarea_descripcion']) ?>"
                                                placeholder="Describe brevemente la tarea" minlength="3" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="descripcion" class="form-label">Descripción</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                                placeholder="Descripción detallada de la tarea (opcional)"><?= htmlspecialchars($task['descripcion'] ?? '') ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Planificación -->
                                    <div class="form-section">
                                        <h5><i class="bi bi-calendar"></i> Planificación</h5>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fecha_inicio" class="form-label">Fecha Inicio <span class="required">*</span></label>
                                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                                        value="<?= date('Y-m-d', strtotime($task['fecha_inicio'])) ?>" required>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fecha_fin" class="form-label">Fecha Fin <span class="required">*</span></label>
                                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                                        value="<?= date('Y-m-d', strtotime($task['fecha_fin'])) ?>" required>
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
                                                        <?php foreach ($users as $user): ?>
                                                            <option value="<?= (int)$user['id'] ?>"
                                                                <?= $user['id'] == $task['usuario_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($user['nombre_completo']) ?> (<?= htmlspecialchars($user['nombre_usuario']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="estado_tipo_id" class="form-label">Estado <span class="required">*</span></label>
                                                    <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                                        <option value="<?= $task['estado_tipo_id'] ?>" selected>
                                                            <?= htmlspecialchars($task['estado']) ?> (Actual)
                                                        </option>
                                                    </select>
                                                    <div class="form-text">
                                                        <i class="bi bi-info-circle"></i>
                                                        Solo se muestran transiciones válidas según el estado actual y tu rol.
                                                    </div>
                                                    <!-- GAP 5: Warning si es tarea aprobada -->
                                                    <?php if ($task['estado_tipo_id'] == 8): ?>
                                                        <div class="alert alert-warning mt-2 mb-0">
                                                            <i class="bi bi-exclamation-triangle"></i>
                                                            <strong>Tarea Aprobada:</strong> Solo Admin y Planner pueden modificar tareas aprobadas.
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Botones de Acción -->
                                    <div class="mt-4 text-end">
                                        <a href="<?= AppConstants::ROUTE_TASKS ?>" class="btn btn-secondary me-2">
                                            <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK ?>
                                        </a>
                                        <button type="submit" class="btn btn-warning" id="updateBtn">
                                            <i class="bi bi-check-lg"></i> Actualizar Tarea
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('taskEditForm');
            const updateBtn = document.getElementById('updateBtn');
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
                    alert('<?= \App\Constants\AppConstants::ERROR_INVALID_DATES ?>');
                    return;
                }

                const nombre = document.getElementById('nombre').value.trim();
                const proyecto_id = document.getElementById('proyecto_id').value;
                const tarea_tipo_id = document.getElementById('tarea_tipo_id').value;

                if (!nombre || !proyecto_id || !tarea_tipo_id) {
                    e.preventDefault();
                    alert('<?= \App\Constants\AppConstants::ERROR_REQUIRED_FIELDS ?>');
                    return;
                }

                if (!confirm('¿Estás seguro de que deseas actualizar esta tarea?')) {
                    e.preventDefault();
                    return;
                }

                // Mostrar indicador de carga
                updateBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Actualizando...';
                updateBtn.disabled = true;
            });

            // GAP 5: Cargar transiciones válidas para el estado actual
            loadValidTransitions();
        });

        // GAP 5: Función para cargar transiciones válidas
        function loadValidTransitions() {
            const taskId = <?= $task['id'] ?>;
            const currentStateId = <?= $task['estado_tipo_id'] ?>;
            const estadoSelect = document.getElementById('estado_tipo_id');

            fetch(`/setap/tasks/valid-transitions?task_id=${taskId}`)
                .then(response => response.json())
                .then(data => {
                    // Limpiar opciones actuales (excepto la primera que es el estado actual)
                    while (estadoSelect.children.length > 1) {
                        estadoSelect.removeChild(estadoSelect.lastChild);
                    }

                    // Agregar transiciones válidas
                    if (data.transitions && data.transitions.length > 0) {
                        data.transitions.forEach(transition => {
                            const option = document.createElement('option');
                            option.value = transition.id;
                            option.textContent = transition.nombre;
                            option.title = transition.descripcion;
                            estadoSelect.appendChild(option);
                        });

                        // Mostrar ayuda sobre transiciones disponibles
                        const formText = estadoSelect.parentNode.querySelector('.form-text');
                        if (formText) {
                            formText.innerHTML = `
                                <i class="bi bi-info-circle"></i>
                                ${data.transitions.length} transición(es) válida(s) disponible(s) según tu rol.
                            `;
                        }
                    } else {
                        // Sin transiciones disponibles
                        const formText = estadoSelect.parentNode.querySelector('.form-text');
                        if (formText) {
                            formText.innerHTML = `
                                <i class="bi bi-exclamation-circle text-warning"></i>
                                No hay transiciones válidas disponibles desde el estado actual.
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error cargando transiciones:', error);
                    const formText = estadoSelect.parentNode.querySelector('.form-text');
                    if (formText) {
                        formText.innerHTML = `
                            <i class="bi bi-exclamation-triangle text-danger"></i>
                            Error al cargar las transiciones válidas.
                        `;
                    }
                });
        }

        // GAP 5: Validar cambio de estado antes de enviar formulario
        function validateStateChange() {
            const estadoSelect = document.getElementById('estado_tipo_id');
            const currentStateId = <?= $task['estado_tipo_id'] ?>;
            const newStateId = parseInt(estadoSelect.value);

            if (newStateId !== currentStateId) {
                const stateName = estadoSelect.options[estadoSelect.selectedIndex].text;
                return confirm(`¿Confirmas el cambio de estado a "${stateName}"?\n\nEsta acción puede requerir permisos especiales según tu rol.`);
            }

            return true;
        }

        // Agregar validación de estado al envío del formulario
        document.getElementById('editTaskForm').addEventListener('submit', function(e) {
            if (!validateStateChange()) {
                e.preventDefault();
                return false;
            }
        });
    </script>

    <!-- GAP 5: Task State Validation Utilities -->
    <script src="/setap/public/js/task-state-utils.js"></script>
</body>

</html>