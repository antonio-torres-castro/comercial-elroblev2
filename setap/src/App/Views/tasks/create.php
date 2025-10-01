<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Tarea - SETAP</title>
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
            <a class="navbar-brand" href="/dashboard">
                <i class="bi bi-grid-3x3-gap"></i> SETAP
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-light" href="/dashboard">
                    <i class="bi bi-house"></i> Dashboard
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
                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/tasks">Tareas</a></li>
                <li class="breadcrumb-item active">Nueva Tarea</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="card-title mb-0">
                            <i class="bi bi-plus-circle"></i> Nueva Tarea
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/tasks/create" id="taskForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCsrfToken()) ?>">

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
                                                            <option value="<?= (int)$project['id'] ?>">
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
                                                            <option value="<?= (int)$type['id'] ?>">
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
                                                placeholder="Describe brevemente la tarea" minlength="3" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="descripcion" class="form-label">Descripción</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                                placeholder="Descripción detallada de la tarea (opcional)"></textarea>
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
                                                        value="<?= date('Y-m-d') ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fecha_fin" class="form-label">Fecha Fin <span class="required">*</span></label>
                                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
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
                                                            <option value="<?= (int)$user['id'] ?>">
                                                                <?= htmlspecialchars($user['nombre_completo']) ?> (<?= htmlspecialchars($user['nombre_usuario']) ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="form-text">Opcional. Puedes asignar la tarea más tarde.</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="estado_tipo_id" class="form-label">Estado Inicial</label>
                                                    <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                                        <?php foreach ($taskStates as $state): ?>
                                                            <option value="<?= (int)$state['id'] ?>" <?= $state['id'] == 1 ? 'selected' : '' ?>>
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
                                    <!-- Ayuda -->
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="bi bi-question-circle"></i> Ayuda</h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="small mb-2"><strong>Proyecto:</strong> Selecciona el proyecto al que pertenece esta tarea.</p>
                                            <p class="small mb-2"><strong>Tipo de Tarea:</strong> Define la categoría de trabajo.</p>
                                            <p class="small mb-2"><strong>Fechas:</strong> La fecha de fin debe ser posterior a la de inicio.</p>
                                            <p class="small mb-0"><strong>Asignación:</strong> Puedes dejar sin asignar y hacerlo más tarde.</p>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="card mt-3">
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-success" id="createBtn">
                                                    <i class="bi bi-plus-lg"></i> Crear Tarea
                                                </button>
                                                <a href="/tasks" class="btn btn-secondary">
                                                    <i class="bi bi-x-lg"></i> Cancelar
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
            const form = document.getElementById('taskForm');
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

            // Establecer fecha fin mínima cuando se cambia fecha inicio
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

                // Mostrar indicador de carga
                createBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Creando...';
                createBtn.disabled = true;
            });

            // Establecer fecha fin por defecto (7 días después del inicio)
            if (!fechaFin.value) {
                const inicioDate = new Date(fechaInicio.value);
                inicioDate.setDate(inicioDate.getDate() + 7);
                fechaFin.value = inicioDate.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>