<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Tarea - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .form-section {
            background: var(--setap-bg-light);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-section h5 {
            color: var(--setap-text-muted);
            border-bottom: 2px solid var(--setap-border-light);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .required {
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-light">
    <?php use App\Helpers\Security; ?>

    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>
                    <i class="bi bi-plus-circle"></i> Crear Nueva Tarea
                </h2>
                <p class="text-muted">Complete la información para crear una nueva tarea del proyecto.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/tasks" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Tareas
                </a>
            </div>
        </div>

        <!-- Mensajes de Error -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario de Creación -->
        <form method="POST" action="/tasks/store" id="createTaskForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCsrfToken()) ?>">

            <div class="row">
                <div class="col-12">
                    <!-- Información Básica -->
                    <div class="form-section">
                        <h5><i class="bi bi-info-circle"></i> Información de la Tarea</h5>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Tarea <span class="required">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   placeholder="Describe brevemente la tarea" maxlength="150"
                                   value="<?= htmlspecialchars($data['task']['nombre'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                      placeholder="Descripción detallada de la tarea (opcional)"
                                      maxlength="500"><?= htmlspecialchars($data['task']['descripcion'] ?? '') ?></textarea>
                            <div class="form-text">Campo opcional. Máximo 500 caracteres.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="proyecto_id" class="form-label">Proyecto <span class="required">*</span></label>
                                    <select class="form-select" id="proyecto_id" name="proyecto_id" required>
                                        <option value="">Seleccionar proyecto</option>
                                        <?php if (!empty($data['projects'])): ?>
                                            <?php foreach ($data['projects'] as $project): ?>
                                                <option value="<?= $project['id'] ?>"
                                                    <?= (isset($data['task']['proyecto_id']) && $data['task']['proyecto_id'] == $project['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($project['cliente_nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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
                                    <label for="estado_tipo_id" class="form-label">Estado <span class="required">*</span></label>
                                    <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                        <?php if (!empty($data['taskStates'])): ?>
                                            <?php foreach ($data['taskStates'] as $state): ?>
                                                <option value="<?= $state['id'] ?>"
                                                    <?= ((isset($data['task']['estado_tipo_id']) && $data['task']['estado_tipo_id'] == $state['id']) ||
                                                        (!isset($data['task']) && $state['id'] == 2)) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($state['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
                
                // Deshabilitar otros elementos del formulario para evitar double-submit
                const formElements = form.querySelectorAll('input, select, textarea, button');
                formElements.forEach(element => {
                    if (element !== createBtn) {
                        element.disabled = true;
                    }
                });
            });
        });
    </script>
</body>
</html>