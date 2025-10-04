<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Proyecto - SETAP</title>
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
        .main-content {
            margin-top: 2rem;
        }
    </style>
</head>

<body class="bg-light">
    <?php use App\Helpers\Security; ?>

    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container mt-4">
        <main class="main-content">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>
                    <i class="bi bi-pencil-square"></i> Editar Proyecto
                </h2>
                <p class="text-muted">Proyecto: <?= htmlspecialchars($project['cliente_nombre']) ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/projects/show/<?= (int)$project['id'] ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver al Proyecto
                </a>
            </div>
        </div>

        <!-- Mensajes de Error/Éxito -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario de Edición -->
        <form method="POST" action="/projects/update" id="editProjectForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCsrfToken()) ?>">
            <input type="hidden" name="id" value="<?= (int)$project['id'] ?>">

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Información Básica -->
                    <div class="form-section">
                        <h5><i class="bi bi-info-circle"></i> Información Básica</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cliente_id" class="form-label">Cliente <span class="required">*</span></label>
                                    <select class="form-select" id="cliente_id" name="cliente_id" required>
                                        <option value="">Seleccionar Cliente</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value="<?= (int)$client['id'] ?>" 
                                                <?= $client['id'] == $project['cliente_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($client['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estado_tipo_id" class="form-label">Estado <span class="required">*</span></label>
                                    <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                        <?php foreach ($projectStates as $state): ?>
                                            <option value="<?= (int)$state['id'] ?>" 
                                                <?= $state['id'] == $project['estado_tipo_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($state['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_inicio" class="form-label">Fecha Inicio <span class="required">*</span></label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                        value="<?= date('Y-m-d', strtotime($project['fecha_inicio'])) ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_fin" class="form-label">Fecha Fin <span class="required">*</span></label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                        value="<?= date('Y-m-d', strtotime($project['fecha_fin'])) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                placeholder="Describe el proyecto..."><?= htmlspecialchars($project['descripcion'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Configuración de Tareas -->
                    <div class="form-section">
                        <h5><i class="bi bi-list-task"></i> Configuración de Tareas</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipos de Tareas Incluidas</label>
                            <div class="row">
                                <?php foreach ($taskTypes as $taskType): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                id="task_type_<?= $taskType['id'] ?>" 
                                                name="task_types[]" 
                                                value="<?= $taskType['id'] ?>"
                                                <?php 
                                                // Aquí deberías verificar si el tipo de tarea está asociado al proyecto
                                                // Por simplicidad, asumo que todos están marcados por defecto
                                                ?>>
                                            <label class="form-check-label" for="task_type_<?= $taskType['id'] ?>">
                                                <?= htmlspecialchars($taskType['nombre']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Selecciona los tipos de tareas que se incluirán en este proyecto.</div>
                        </div>
                    </div>

                    <!-- Contrapartes -->
                    <div class="form-section">
                        <h5><i class="bi bi-people"></i> Contrapartes del Cliente</h5>
                        
                        <div class="mb-3">
                            <label for="counterparts" class="form-label">Contrapartes Asignadas</label>
                            <select class="form-select" id="counterparts" name="counterparts[]" multiple size="4">
                                <?php foreach ($counterparts as $counterpart): ?>
                                    <option value="<?= (int)$counterpart['id'] ?>"
                                        <?php 
                                        // Aquí deberías verificar si la contraparte está asignada al proyecto
                                        // Por simplicidad, no marco ninguna por defecto
                                        ?>>
                                        <?= htmlspecialchars($counterpart['nombre']) ?> - <?= htmlspecialchars($counterpart['cargo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Mantén presionado Ctrl (Cmd en Mac) para seleccionar múltiples contrapartes.</div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/projects/show/<?= (int)$project['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success" id="saveBtn">
                            <i class="bi bi-check-lg"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editProjectForm');
            const saveBtn = document.getElementById('saveBtn');
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

            fechaInicio.addEventListener('change', validateDates);
            fechaFin.addEventListener('change', validateDates);

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    alert('Por favor, corrige los errores en las fechas.');
                    return;
                }

                if (!confirm('¿Estás seguro de que deseas guardar los cambios en este proyecto?')) {
                    e.preventDefault();
                    return;
                }

                // Mostrar indicador de carga
                saveBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Guardando...';
                saveBtn.disabled = true;
            });
        });
    </script>
</body>
</html>