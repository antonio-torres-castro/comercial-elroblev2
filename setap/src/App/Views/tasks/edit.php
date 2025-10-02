<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tarea - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .form-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-section h5 {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
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

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/home">
                <i class="bi bi-grid-3x3-gap"></i> SETAP
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-light" href="/home">
                    <i class="bi bi-house"></i> Home
                </a>
                <a class="nav-link text-light" href="/tasks">
                    <i class="bi bi-list-task"></i> Tareas
                </a>
                <a class="nav-link text-light" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/home">Home</a></li>
                <li class="breadcrumb-item"><a href="/tasks">Tareas</a></li>
                <li class="breadcrumb-item active">Editar Tarea</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="card-title mb-0">
                            <i class="bi bi-pencil-square"></i> Editar Tarea: <?= htmlspecialchars($task['nombre']) ?>
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

                        <form method="POST" action="/tasks/update" id="taskEditForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCsrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">

                            <div class="row">
                                <div class="col-md-8">
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
                                                value="<?= htmlspecialchars($task['nombre']) ?>"
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
                                                        <?php foreach ($taskStates as $state): ?>
                                                            <option value="<?= (int)$state['id'] ?>" 
                                                                <?= $state['id'] == $task['estado_tipo_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($state['nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Panel Lateral -->
                                <div class="col-md-4">
                                    <!-- Información de la Tarea -->
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información de la Tarea</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>ID:</strong> <?= (int)$task['id'] ?></p>
                                            <p><strong>Proyecto:</strong><br><?= htmlspecialchars($task['cliente_nombre']) ?></p>
                                            <p><strong>Creada:</strong><br><?= date('d/m/Y H:i', strtotime($task['fecha_Creado'])) ?></p>
                                            <?php if (!empty($task['fecha_modificacion'])): ?>
                                                <p><strong>Modificada:</strong><br><?= date('d/m/Y H:i', strtotime($task['fecha_modificacion'])) ?></p>
                                            <?php endif; ?>
                                            <p><strong>Estado Actual:</strong><br>
                                                <span class="badge bg-<?= match($task['estado_tipo_id']) {
                                                    1 => 'secondary', // Creada
                                                    5 => 'primary',   // En proceso
                                                    6 => 'warning',   // Pendiente
                                                    8 => 'success',   // Completada
                                                    default => 'dark'
                                                } ?>">
                                                    <?= htmlspecialchars($task['estado']) ?>
                                                </span>
                                            </p>
                                            <?php if (!empty($task['asignado_a'])): ?>
                                                <p><strong>Asignada a:</strong><br><?= htmlspecialchars($task['asignado_a']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="card mt-3">
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-warning" id="updateBtn">
                                                    <i class="bi bi-check-lg"></i> Actualizar Tarea
                                                </button>
                                                <a href="/tasks" class="btn btn-secondary">
                                                    <i class="bi bi-arrow-left"></i> Volver a Tareas
                                                </a>
                                            </div>
                                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
                    alert('Por favor, corrige los errores en las fechas.');
                    return;
                }

                const nombre = document.getElementById('nombre').value.trim();
                const proyecto_id = document.getElementById('proyecto_id').value;
                const tarea_tipo_id = document.getElementById('tarea_tipo_id').value;

                if (!nombre || !proyecto_id || !tarea_tipo_id) {
                    e.preventDefault();
                    alert('Por favor, completa todos los campos obligatorios.');
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
        });
    </script>
</body>
</html>