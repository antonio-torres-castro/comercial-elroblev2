<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Proyecto - SETAP</title>
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
                <a class="nav-link text-light" href="/projects">
                    <i class="bi bi-folder"></i> Proyectos
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
                <li class="breadcrumb-item"><a href="/projects">Proyectos</a></li>
                <li class="breadcrumb-item active">Crear Proyecto</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>
                    <i class="bi bi-plus-circle"></i> Crear Nuevo Proyecto
                </h2>
                <p class="text-muted">Complete los datos para crear un nuevo proyecto en el sistema.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/projects" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Proyectos
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
        <form method="POST" action="/projects/store" id="createProjectForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCsrfToken()) ?>">

            <div class="row">
                <div class="col-md-8">
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
                                            <option value="<?= (int)$client['id'] ?>">
                                                <?= htmlspecialchars($client['nombre']) ?>
                                                <?php if (!empty($client['rut'])): ?>
                                                    - RUT: <?= htmlspecialchars($client['rut']) ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tarea_tipo_id" class="form-label">Tipo de Tarea <span class="required">*</span></label>
                                    <select class="form-select" id="tarea_tipo_id" name="tarea_tipo_id" required>
                                        <option value="">Seleccionar Tipo de Tarea</option>
                                        <?php foreach ($taskTypes as $taskType): ?>
                                            <option value="<?= (int)$taskType['id'] ?>">
                                                <?= htmlspecialchars($taskType['nombre']) ?>
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
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                                    <div class="form-text">Campo opcional. Deja en blanco si no se define aún.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección del Proyecto</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                placeholder="Ingresa la dirección donde se desarrollará el proyecto..."></textarea>
                        </div>
                    </div>

                    <!-- Configuración del Proyecto -->
                    <div class="form-section">
                        <h5><i class="bi bi-people"></i> Configuración del Proyecto</h5>
                        
                        <div class="mb-3">
                            <label for="contraparte_id" class="form-label">Contraparte del Cliente <span class="required">*</span></label>
                            <select class="form-select" id="contraparte_id" name="contraparte_id" required>
                                <option value="">Seleccionar Contraparte</option>
                                <?php foreach ($counterparts as $counterpart): ?>
                                    <option value="<?= (int)$counterpart['id'] ?>">
                                        <?= htmlspecialchars($counterpart['nombre']) ?> 
                                        - <?= htmlspecialchars($counterpart['cargo']) ?>
                                        (<?= htmlspecialchars($counterpart['cliente_nombre']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Persona de contacto del cliente para este proyecto.</div>
                        </div>

                        <div class="mb-3">
                            <label for="feriados" class="form-label">Días Festivos Especiales</label>
                            <input type="text" class="form-control" id="feriados" name="feriados"
                                placeholder="Ej: 2024-12-25, 2024-01-01, 2024-05-21">
                            <div class="form-text">Fechas especiales no laborables para este proyecto, separadas por comas (formato: YYYY-MM-DD).</div>
                        </div>
                    </div>
                </div>

                <!-- Panel Lateral -->
                <div class="col-md-4">
                    <!-- Instrucciones -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Instrucciones</h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <p><strong>Campos Obligatorios:</strong></p>
                                <ul>
                                    <li>Cliente</li>
                                    <li>Tipo de Tarea</li>
                                    <li>Fecha de Inicio</li>
                                    <li>Contraparte del Cliente</li>
                                </ul>
                                
                                <p><strong>Estado Inicial:</strong></p>
                                <p>El proyecto se creará con estado "Activo" y podrá ser modificado posteriormente.</p>
                                
                                <p><strong>Nota:</strong></p>
                                <p>Una vez creado, podrás agregar tareas específicas y gestionar el seguimiento del proyecto.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success" id="createBtn">
                                    <i class="bi bi-plus-lg"></i> Crear Proyecto
                                </button>
                                <a href="/projects" class="btn btn-secondary">
                                    <i class="bi bi-x-lg"></i> Cancelar
                                </a>
                            </div>
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
            const form = document.getElementById('createProjectForm');
            const createBtn = document.getElementById('createBtn');
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaFin = document.getElementById('fecha_fin');
            const clienteSelect = document.getElementById('cliente_id');
            const contraparteSelect = document.getElementById('contraparte_id');

            // Establecer fecha mínima como hoy
            const today = new Date().toISOString().split('T')[0];
            fechaInicio.min = today;
            fechaFin.min = today;

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

            // Actualizar fecha mínima de fin cuando cambia fecha de inicio
            fechaInicio.addEventListener('change', function() {
                if (this.value) {
                    fechaFin.min = this.value;
                }
                validateDates();
            });

            fechaFin.addEventListener('change', validateDates);

            // Filtrar contrapartes por cliente seleccionado
            clienteSelect.addEventListener('change', function() {
                const clienteId = this.value;
                
                // Habilitar todas las opciones primero
                Array.from(contraparteSelect.options).forEach(option => {
                    if (option.value !== '') {
                        option.style.display = '';
                        option.disabled = false;
                    }
                });

                // Si hay cliente seleccionado, filtrar contrapartes
                if (clienteId) {
                    // Esta implementación es básica. En un entorno real, podrías hacer
                    // una llamada AJAX para obtener las contrapartes del cliente seleccionado
                    // Por ahora, mostramos todas las contrapartes
                    
                    // Resetear selección de contraparte
                    contraparteSelect.value = '';
                } else {
                    // Sin cliente, resetear selección de contraparte
                    contraparteSelect.value = '';
                }
            });

            // Validación del formato de feriados
            const feriadosInput = document.getElementById('feriados');
            feriadosInput.addEventListener('blur', function() {
                const value = this.value.trim();
                if (value) {
                    // Validar formato de fechas separadas por comas
                    const fechas = value.split(',').map(f => f.trim());
                    const fechaRegex = /^\d{4}-\d{2}-\d{2}$/;
                    
                    const fechasInvalidas = fechas.filter(fecha => !fechaRegex.test(fecha));
                    
                    if (fechasInvalidas.length > 0) {
                        this.setCustomValidity('Formato de fecha inválido. Use YYYY-MM-DD separado por comas');
                    } else {
                        this.setCustomValidity('');
                    }
                } else {
                    this.setCustomValidity('');
                }
            });

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    alert('Por favor, corrige los errores en las fechas.');
                    return;
                }

                if (!confirm('¿Estás seguro de que deseas crear este nuevo proyecto?')) {
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

            // Inicialización
            clienteSelect.dispatchEvent(new Event('change'));
        });
    </script>
</body>
</html>