<?php use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= AppConstants::UI_CREATE_PROJECT_TITLE ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
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
                    <i class="bi bi-plus-circle"></i> <?= AppConstants::UI_CREATE_NEW_PROJECT ?>
                </h2>
                <p class="text-muted">Complete los datos para crear un nuevo proyecto en el sistema.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= AppConstants::ROUTE_PROJECTS ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK_TO_PROJECTS ?>
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
            <?= \App\Helpers\Security::renderCsrfField() ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- <?= AppConstants::UI_BASIC_INFORMATION ?> -->
                    <div class="form-section">
                        <h5><i class="bi bi-info-circle"></i> <?= AppConstants::UI_BASIC_INFORMATION ?></h5>

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
                                    <option value="<?= (int)$counterpart['id'] ?>" data-cliente-id="<?= (int)$counterpart['cliente_id'] ?>">
                                        <?= htmlspecialchars($counterpart['nombre']) ?>
                                        - <?= htmlspecialchars($counterpart['cargo']) ?>
                                        (<?= htmlspecialchars($counterpart['cliente_nombre']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Persona de contacto del cliente para este proyecto.
                                <span id="contraparte-filter-info" class="text-muted"></span>
                            </div>
                        </div>


                    </div>

                    <!-- Botones de Acción -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?= AppConstants::ROUTE_PROJECTS ?>" class="btn btn-secondary">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success" id="createBtn">
                            <i class="bi bi-plus-lg"></i> <?= AppConstants::UI_CREATE_PROJECT ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
        </main>
    </div>

    <!-- Scripts -->
    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

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

            // Filtrar contrapartes por cliente seleccionado (sin AJAX)
            clienteSelect.addEventListener('change', function() {
                const clienteId = this.value;
                const filterInfo = document.getElementById('contraparte-filter-info');
                
                // Resetear selección de contraparte
                contraparteSelect.value = '';

                let visibleCount = 0;
                let totalCount = 0;

                // Mostrar/ocultar opciones de contraparte según cliente seleccionado
                Array.from(contraparteSelect.options).forEach(option => {
                    if (option.value === '') {
                        // Opción predeterminada siempre visible
                        option.style.display = '';
                        option.disabled = false;
                        return;
                    }

                    totalCount++;
                    const contraparteClienteId = option.getAttribute('data-cliente-id');
                    
                    if (!clienteId) {
                        // Sin cliente seleccionado: mostrar todas las contrapartes
                        option.style.display = '';
                        option.disabled = false;
                        visibleCount++;
                    } else if (contraparteClienteId === clienteId) {
                        // Contraparte pertenece al cliente seleccionado: mostrar
                        option.style.display = '';
                        option.disabled = false;
                        visibleCount++;
                    } else {
                        // Contraparte NO pertenece al cliente: ocultar
                        option.style.display = 'none';
                        option.disabled = true;
                    }
                });

                // Actualizar placeholder del select de contrapartes
                const firstOption = contraparteSelect.querySelector('option[value=""]');
                if (clienteId) {
                    const clienteNombre = clienteSelect.options[clienteSelect.selectedIndex].text;
                    firstOption.textContent = `Seleccionar Contraparte de ${clienteNombre}`;
                    
                    // Mostrar información del filtrado
                    if (visibleCount === 0) {
                        filterInfo.innerHTML = '<br><small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Este cliente no tiene contrapartes disponibles.</small>';
                    } else {
                        filterInfo.innerHTML = `<br><small class="text-success"><i class="bi bi-funnel"></i> Mostrando ${visibleCount} contraparte(s) de este cliente.</small>`;
                    }
                } else {
                    firstOption.textContent = 'Seleccionar Contraparte';
                    filterInfo.innerHTML = `<br><small class="text-info"><i class="bi bi-info-circle"></i> ${totalCount} contrapartes disponibles. Selecciona un cliente para filtrar.</small>`;
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

                // No deshabilitar los inputs del formulario - esto impide que se envíen los datos
                // Solo deshabilitar botones adicionales si existen
                const additionalButtons = form.querySelectorAll('button:not([type="submit"])');
                additionalButtons.forEach(button => {
                    button.disabled = true;
                });
            });

            // Inicialización
            clienteSelect.dispatchEvent(new Event('change'));
        });
    </script>
</body>
</html>