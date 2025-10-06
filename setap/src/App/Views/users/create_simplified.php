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

                        <form method="POST" action="/users/store" class="needs-validation" novalidate>
                            <?= Security::renderCsrfField() ?>
                            
                            <!-- Paso 1: Buscar y Seleccionar Persona -->
                            <div class="form-section">
                                <h6><i class="bi bi-person-check"></i> Paso 1: Buscar y Seleccionar Persona</h6>
                                
                                <!-- Formulario de búsqueda -->
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="persona_search" class="form-label">Buscar Persona *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="persona_search" 
                                               name="persona_search"
                                               placeholder="Escriba el RUT, nombre o apellido"
                                               value="<?= htmlspecialchars($oldInput['persona_search'] ?? '') ?>">
                                        <div class="form-text">Busque por RUT (ej: 12345678-9), nombre o apellido</div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" name="search_persona" class="btn btn-outline-primary">
                                            <i class="bi bi-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>

                                <!-- Resultados de búsqueda -->
                                <?php if (isset($_SESSION['persona_results'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Seleccionar Persona</label>
                                        <?php if (empty($_SESSION['persona_results'])): ?>
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i> No se encontraron personas disponibles con ese criterio de búsqueda.
                                            </div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($_SESSION['persona_results'] as $persona): ?>
                                                    <div class="col-md-6 mb-2">
                                                        <div class="persona-result-card">
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
                                                                            <strong><?= htmlspecialchars($persona['nombre_completo']) ?></strong>
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

                                <!-- Campo hidden para persona seleccionada -->
                                <input type="hidden" name="persona_id_hidden" id="persona_id_hidden" value="<?= htmlspecialchars($oldInput['persona_id'] ?? '') ?>">
                                
                                <?php if (!empty($oldInput['persona_id'])): ?>
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle"></i> Persona seleccionada. Puede continuar con el formulario.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Paso 2: Credenciales de Acceso -->
                            <div class="form-section">
                                <h6><i class="bi bi-key"></i> Paso 2: Credenciales de Acceso</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email"
                                               value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>"
                                               required>
                                        <div class="invalid-feedback">
                                            Ingrese un email válido.
                                        </div>
                                        <div class="form-text">El email será usado para notificaciones del sistema</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="nombre_usuario" class="form-label">Nombre de Usuario *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="nombre_usuario" 
                                               name="nombre_usuario"
                                               value="<?= htmlspecialchars($oldInput['nombre_usuario'] ?? '') ?>"
                                               pattern="[a-zA-Z0-9_]{3,20}"
                                               title="3-20 caracteres, solo letras, números y guiones bajos"
                                               required>
                                        <div class="invalid-feedback">
                                            3-20 caracteres, solo letras, números y guiones bajos.
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Contraseña *</label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password" 
                                                   name="password"
                                                   minlength="8"
                                                   required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                                <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">
                                            Mínimo 8 caracteres.
                                        </div>
                                        <div class="form-text">
                                            Mínimo 8 caracteres, incluya mayúsculas, minúsculas, números y símbolos.
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="password_confirm" class="form-label">Confirmar Contraseña *</label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password_confirm" 
                                                   name="password_confirm"
                                                   required>
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirm')">
                                                <i class="bi bi-eye" id="password_confirmToggleIcon"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback" id="password_confirm_feedback">
                                            Las contraseñas deben coincidir.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Paso 3: Configuración del Usuario -->
                            <div class="form-section">
                                <h6><i class="bi bi-gear"></i> Paso 3: Configuración del Usuario</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="usuario_tipo_id" class="form-label">Tipo de Usuario *</label>
                                        <select class="form-select" id="usuario_tipo_id" name="usuario_tipo_id" required onchange="toggleClientFields()">
                                            <option value="">Seleccione un tipo</option>
                                            <?php if (isset($userTypes)): ?>
                                                <?php foreach ($userTypes as $type): ?>
                                                    <option value="<?= $type['id'] ?>" 
                                                            data-name="<?= $type['name'] ?>"
                                                            <?= ($oldInput['usuario_tipo_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($type['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Debe seleccionar un tipo de usuario.
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="fecha_inicio" 
                                               name="fecha_inicio"
                                               value="<?= htmlspecialchars($oldInput['fecha_inicio'] ?? '') ?>">
                                        <div class="form-text">Fecha desde la cual el usuario tendrá acceso</div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="fecha_termino" class="form-label">Fecha de Término</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="fecha_termino" 
                                               name="fecha_termino"
                                               value="<?= htmlspecialchars($oldInput['fecha_termino'] ?? '') ?>">
                                        <div class="form-text">Fecha hasta la cual el usuario tendrá acceso</div>
                                    </div>
                                    
                                    <!-- Cliente (condicional) -->
                                    <div class="col-md-6 client-conditional" id="clientField">
                                        <label for="cliente_id" class="form-label">Cliente *</label>
                                        <select class="form-select" id="cliente_id" name="cliente_id">
                                            <option value="">Seleccione un cliente</option>
                                            <?php if (isset($clients)): ?>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?= $client['id'] ?>"
                                                            <?= ($oldInput['cliente_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($client['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Debe seleccionar un cliente.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="/users" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" name="create_user" class="btn btn-primary" id="submitBtn">
                                    <i class="bi bi-check-circle"></i> Crear Usuario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Funciones básicas sin AJAX
        
        // Mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + 'ToggleIcon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        // Mostrar/ocultar campos de cliente
        function toggleClientFields() {
            const userTypeSelect = document.getElementById('usuario_tipo_id');
            const selectedOption = userTypeSelect.options[userTypeSelect.selectedIndex];
            const userTypeName = selectedOption ? selectedOption.getAttribute('data-name') : '';
            const clientField = document.getElementById('clientField');
            const clientSelect = document.getElementById('cliente_id');
            
            if (userTypeName === 'client' || userTypeName === 'counterparty') {
                clientField.style.display = 'block';
                clientSelect.required = true;
            } else {
                clientField.style.display = 'none';
                clientSelect.required = false;
                clientSelect.value = '';
            }
        }

        // Actualizar selección de persona
        function updatePersonaSelection(radio) {
            // Remover clase selected de todos
            document.querySelectorAll('.persona-result-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Agregar clase selected al seleccionado
            radio.closest('.persona-result-card').classList.add('selected');
            
            // Actualizar campo hidden
            document.getElementById('persona_id_hidden').value = radio.value;
        }

        // Validación HTML5 personalizada
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
                
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
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

                        if (!personaSelected && !personaId && !document.querySelector('input[name="search_persona"]') && !document.querySelector('input[name="create_user"]')) {
                            alert('Debe seleccionar una persona antes de crear el usuario');
                            event.preventDefault();
                            event.stopPropagation();
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
                        
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
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