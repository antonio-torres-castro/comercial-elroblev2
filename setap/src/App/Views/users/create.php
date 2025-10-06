<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - SETAP</title>
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
            border-left: 4px solid var(--setap-primary);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 0.375rem;
        }

        .form-section h6 {
            color: var(--setap-primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .persona-result-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .persona-result-card:hover {
            border-color: var(--setap-primary);
            background: #f8f9fa;
        }

        .persona-result-card.selected {
            border: 2px solid var(--setap-primary);
            background: #e7f3ff;
        }

        .client-conditional {
            display: none;
        }
    </style>
</head>

<body class="bg-light">
    <?php use App\Helpers\Security; ?>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Crear Nuevo Usuario</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/users" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Lista
                        </a>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-10">
                        
                        <!-- Mostrar errores si existen -->
                        <?php if (isset($_SESSION['errors'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Se encontraron errores:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($_SESSION['errors'] as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['errors']); ?>
                        <?php endif; ?>

                        <!-- Mostrar mensaje de éxito si existe -->
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <!-- Recuperar datos anteriores si existen -->
                        <?php 
                        $oldInput = $_SESSION['old_input'] ?? [];
                        if (isset($_SESSION['old_input'])) {
                            unset($_SESSION['old_input']);
                        }
                        ?>

                        <!-- Paso 1: Buscar y Seleccionar Persona -->
                        <div class="form-section">
                            <h6><i class="bi bi-person-check"></i> Paso 1: Buscar y Seleccionar Persona</h6>
                            
                            <!-- Formulario de búsqueda -->
                            <form method="POST" action="/users/seek_personas" class="needs-validation" novalidate id="search-form">
                                <?= Security::renderCsrfField() ?>
                                
                                <!-- Formulario de búsqueda mejorado -->
                                <div class="row mb-3">
                                    <div class="col-md-5">
                                        <label for="persona_search" class="form-label">Buscar Persona</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="persona_search" 
                                               name="persona_search"
                                               placeholder="RUT completo o parte del nombre"
                                               value="<?= htmlspecialchars($oldInput['persona_search'] ?? '') ?>">
                                        <div class="form-text">Deje vacío para ver todas las personas</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="search_type" class="form-label">Tipo de Búsqueda</label>
                                        <select class="form-select" id="search_type" name="search_type">
                                            <option value="all" <?= ($oldInput['search_type'] ?? 'all') === 'all' ? 'selected' : '' ?>>Buscar en todo</option>
                                            <option value="rut" <?= ($oldInput['search_type'] ?? '') === 'rut' ? 'selected' : '' ?>>Solo por RUT</option>
                                            <option value="name" <?= ($oldInput['search_type'] ?? '') === 'name' ? 'selected' : '' ?>>Solo por nombre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" name="search_persona" class="btn btn-outline-primary me-2">
                                            <i class="bi bi-search"></i> Buscar
                                        </button>
                                        <button type="submit" name="search_persona" onclick="document.getElementById('persona_search').value='';" class="btn btn-outline-secondary">
                                            <i class="bi bi-list"></i> Ver Todas
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Estadísticas de búsqueda -->
                            <?php if (isset($_SESSION['search_stats'])): ?>
                                <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Resultados:</strong> <?= $_SESSION['search_stats']['total'] ?> persona(s) encontrada(s)
                                    </div>
                                    <div class="small">
                                        <span class="badge bg-success"><?= $_SESSION['search_stats']['available'] ?> disponibles</span>
                                        <span class="badge bg-warning"><?= $_SESSION['search_stats']['assigned'] ?> asignadas</span>
                                    </div>
                                </div>
                                <?php unset($_SESSION['search_stats']); ?>
                            <?php endif; ?>

                            <!-- Resultados de búsqueda mejorados -->
                            <?php if (isset($_SESSION['persona_results'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Seleccionar Persona *</label>
                                    <?php if (empty($_SESSION['persona_results'])): ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> No se encontraron personas con ese criterio de búsqueda.
                                        </div>
                                    <?php else: ?>
                                        <div class="row" style="max-height: 400px; overflow-y: auto;">
                                            <?php foreach ($_SESSION['persona_results'] as $persona): ?>
                                                <div class="col-md-6 mb-2">
                                                    <div class="persona-result-card <?= $persona['has_user'] ? 'border-warning' : '' ?>">
                                                        <div class="form-check">
                                                            <input class="form-check-input" 
                                                                   type="radio" 
                                                                   name="persona_id" 
                                                                   id="persona_<?= $persona['id'] ?>"
                                                                   value="<?= $persona['id'] ?>"
                                                                   <?= ($oldInput['persona_id'] ?? '') == $persona['id'] ? 'checked' : '' ?>
                                                                   onchange="updatePersonaSelection(this)">
                                                            <label class="form-check-label w-100" for="persona_<?= $persona['id'] ?>">
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <strong><?= htmlspecialchars($persona['nombre']) ?></strong>
                                                                        <?php if ($persona['has_user']): ?>
                                                                            <span class="badge bg-warning ms-2">Asignada a: <?= htmlspecialchars($persona['usuario_asociado']) ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <small class="text-muted">RUT: <?= htmlspecialchars($persona['rut']) ?></small>
                                                                    </div>
                                                                    <?php if (!empty($persona['telefono'])): ?>
                                                                        <div class="col-12">
                                                                            <small class="text-muted">Tel: <?= htmlspecialchars($persona['telefono']) ?></small>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php unset($_SESSION['persona_results']); ?>
                            <?php endif; ?>
                        </div>

                        <!-- Paso 2: Crear Usuario -->
                        <form method="POST" action="/users/store" class="needs-validation" novalidate id="create-form">
                            <?= Security::renderCsrfField() ?>
                            
                            <!-- Campo oculto para persona seleccionada -->
                            <input type="hidden" id="persona_id_hidden" name="persona_id_hidden" value="<?= htmlspecialchars($oldInput['persona_id'] ?? '') ?>">

                            <!-- Paso 2: Datos del Usuario -->
                            <div class="form-section">
                                <h6><i class="bi bi-person-gear"></i> Paso 2: Información del Usuario</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="email" 
                                                   name="email"
                                                   placeholder="usuario@dominio.com"
                                                   value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>"
                                                   required>
                                            <div class="invalid-feedback">Ingrese un email válido</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="nombre_usuario" class="form-label">Nombre de Usuario *</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="nombre_usuario" 
                                                   name="nombre_usuario"
                                                   placeholder="nombre_usuario"
                                                   value="<?= htmlspecialchars($oldInput['nombre_usuario'] ?? '') ?>"
                                                   pattern="[a-zA-Z0-9_]{3,20}"
                                                   required>
                                            <div class="form-text">3-20 caracteres, solo letras, números y guión bajo</div>
                                            <div class="invalid-feedback">El nombre de usuario debe tener entre 3 y 20 caracteres alfanuméricos</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Contraseña *</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password" 
                                                   name="password"
                                                   minlength="8"
                                                   required>
                                            <div class="form-text">Mínimo 8 caracteres</div>
                                            <div class="invalid-feedback">La contraseña debe tener al menos 8 caracteres</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password_confirm" class="form-label">Confirmar Contraseña *</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password_confirm" 
                                                   name="password_confirm"
                                                   required>
                                            <div class="invalid-feedback">Las contraseñas no coinciden</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 3: Configuración del Sistema -->
                            <div class="form-section">
                                <h6><i class="bi bi-gear"></i> Paso 3: Configuración del Sistema</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="usuario_tipo_id" class="form-label">Tipo de Usuario *</label>
                                            <select class="form-select" id="usuario_tipo_id" name="usuario_tipo_id" required onchange="toggleClientFields()">
                                                <option value="">Seleccione un tipo</option>
                                                <?php foreach ($userTypes as $type): ?>
                                                    <option value="<?= $type['id'] ?>" 
                                                            data-name="<?= htmlspecialchars(strtolower($type['nombre'])) ?>"
                                                            <?= ($oldInput['usuario_tipo_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($type['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">Debe seleccionar un tipo de usuario</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3 client-conditional" id="client-field">
                                            <label for="cliente_id" class="form-label">Cliente Asociado *</label>
                                            <select class="form-select" id="cliente_id" name="cliente_id">
                                                <option value="">Seleccione un cliente</option>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?= $client['id'] ?>"
                                                            <?= ($oldInput['cliente_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($client['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">Debe seleccionar un cliente para este tipo de usuario</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_inicio" class="form-label">Fecha de Inicio (Opcional)</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="fecha_inicio" 
                                                   name="fecha_inicio"
                                                   value="<?= htmlspecialchars($oldInput['fecha_inicio'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_termino" class="form-label">Fecha de Término (Opcional)</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="fecha_termino" 
                                                   name="fecha_termino"
                                                   value="<?= htmlspecialchars($oldInput['fecha_termino'] ?? '') ?>">
                                            <div class="invalid-feedback">La fecha de término debe ser posterior a la fecha de inicio</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="/users" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left"></i> Cancelar
                                        </a>
                                        <button type="submit" name="create_user" class="btn btn-primary">
                                            <i class="bi bi-person-plus"></i> Crear Usuario
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para actualizar selección de persona
        function updatePersonaSelection(radio) {
            // Limpiar clases de selección previa
            document.querySelectorAll('.persona-result-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Agregar clase a la seleccionada
            if (radio.checked) {
                radio.closest('.persona-result-card').classList.add('selected');
                document.getElementById('persona_id_hidden').value = radio.value;
                
                // Mostrar indicador visual de que hay una persona seleccionada
                showPersonaSelectedFeedback(radio);
            }
        }

        // Función para mostrar feedback visual cuando se selecciona una persona
        function showPersonaSelectedFeedback(radio) {
            const formSection = document.querySelector('.form-section');
            
            // Remover feedback previo
            const existingFeedback = formSection.querySelector('.persona-selected-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }
            
            // Obtener datos de la persona seleccionada
            const label = radio.closest('.persona-result-card').querySelector('label');
            const personaName = label.querySelector('strong').textContent;
            const personaRut = label.querySelector('small').textContent;
            
            // Crear feedback
            const feedback = document.createElement('div');
            feedback.className = 'alert alert-success persona-selected-feedback mt-3';
            feedback.innerHTML = `
                <i class="bi bi-check-circle"></i> 
                <strong>Persona seleccionada:</strong> ${personaName} - ${personaRut}
                <br><small>Ahora puede continuar con los datos del usuario en el Paso 2.</small>
            `;
            
            formSection.appendChild(feedback);
        }

        // Función para mostrar/ocultar campos condicionales de cliente
        function toggleClientFields() {
            const userTypeSelect = document.getElementById('usuario_tipo_id');
            const selectedOption = userTypeSelect.options[userTypeSelect.selectedIndex];
            const userTypeName = selectedOption ? selectedOption.getAttribute('data-name') : '';
            const clientField = document.getElementById('client-field');
            const clientSelect = document.getElementById('cliente_id');
            
            // Mostrar campo cliente solo para tipos 'client' y 'counterparty'
            if (userTypeName === 'client' || userTypeName === 'counterparty') {
                clientField.style.display = 'block';
                clientField.classList.add('client-conditional');
                clientSelect.setAttribute('required', 'required');
            } else {
                clientField.style.display = 'none';
                clientField.classList.remove('client-conditional');
                clientSelect.removeAttribute('required');
                clientSelect.value = '';
            }
        }

        // Inicialización al cargar la página
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                // Inicializar campos condicionales
                toggleClientFields();
                
                // Marcar persona seleccionada si existe
                const selectedPersonaId = document.getElementById('persona_id_hidden').value;
                if (selectedPersonaId) {
                    const radio = document.querySelector(`input[name="persona_id"][value="${selectedPersonaId}"]`);
                    if (radio) {
                        radio.checked = true;
                        updatePersonaSelection(radio);
                    }
                }
                
                // Configurar validación solo para el formulario de crear usuario
                const createForm = document.getElementById('create-form');
                if (createForm) {
                    createForm.addEventListener('submit', function(event) {
                        // Validar persona seleccionada
                        const personaId = document.getElementById('persona_id_hidden').value;
                        const personaRadios = document.querySelectorAll('input[name="persona_id"]');
                        let personaSelected = false;
                        
                        personaRadios.forEach(radio => {
                            if (radio.checked) {
                                personaSelected = true;
                                document.getElementById('persona_id_hidden').value = radio.value;
                            }
                        });

                        if (!personaSelected && !personaId) {
                            event.preventDefault();
                            event.stopPropagation();
                            
                            // Mostrar alerta más clara
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                            alertDiv.innerHTML = `
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Error:</strong> Debe seleccionar una persona antes de crear el usuario.
                                <br><small>Use el formulario de búsqueda en el Paso 1 para buscar y seleccionar una persona.</small>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            `;
                            
                            // Insertar alerta antes del formulario
                            createForm.parentNode.insertBefore(alertDiv, createForm);
                            
                            // Scroll hacia arriba para mostrar la alerta
                            alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                        
                        // Validar confirmación de contraseña
                        const password = document.getElementById('password').value;
                        const passwordConfirm = document.getElementById('password_confirm').value;
                        const passwordConfirmInput = document.getElementById('password_confirm');
                        
                        if (password !== passwordConfirm) {
                            passwordConfirmInput.setCustomValidity('Las contraseñas no coinciden');
                        } else {
                            passwordConfirmInput.setCustomValidity('');
                        }
                        
                        // Validar cliente si es requerido
                        const userTypeSelect = document.getElementById('usuario_tipo_id');
                        const selectedOption = userTypeSelect.options[userTypeSelect.selectedIndex];
                        const userTypeName = selectedOption ? selectedOption.getAttribute('data-name') : '';
                        const clientSelect = document.getElementById('cliente_id');
                        
                        if ((userTypeName === 'client' || userTypeName === 'counterparty') && !clientSelect.value) {
                            clientSelect.setCustomValidity('Debe seleccionar un cliente');
                        } else {
                            clientSelect.setCustomValidity('');
                        }
                        
                        if (createForm.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        createForm.classList.add('was-validated');
                    }, false);
                }
                
                // Configurar validación básica para el formulario de búsqueda (sin validaciones complejas)
                const searchForm = document.getElementById('search-form');
                if (searchForm) {
                    searchForm.addEventListener('submit', function(event) {
                        // No necesita validaciones complejas, solo envío directo
                    }, false);
                }
            }, false);
        })();
        
        // Validación en tiempo real de confirmación de contraseña
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const passwordConfirm = this.value;
            
            if (password !== passwordConfirm) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });

        // Validación de fechas
        document.getElementById('fecha_inicio').addEventListener('change', validateDates);
        document.getElementById('fecha_termino').addEventListener('change', validateDates);
        
        function validateDates() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaTermino = document.getElementById('fecha_termino').value;
            const fechaTerminoInput = document.getElementById('fecha_termino');
            
            if (fechaInicio && fechaTermino && fechaInicio > fechaTermino) {
                fechaTerminoInput.setCustomValidity('La fecha de término debe ser posterior a la fecha de inicio');
            } else {
                fechaTerminoInput.setCustomValidity('');
            }
        }
    </script>
</body>
</html>
