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

        .persona-search-card {
            border: 2px dashed #dee2e6;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .persona-search-card:hover {
            border-color: var(--setap-primary);
            background: #fff;
        }

        .persona-selected {
            border: 2px solid var(--setap-primary);
            background: #e7f3ff;
        }

        .persona-info {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .client-conditional {
            display: none;
        }

        .counterparty-search {
            display: none;
        }

        .password-strength {
            font-size: 0.875em;
            margin-top: 5px;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #198754; }

        .availability-check {
            font-size: 0.875em;
            margin-top: 5px;
        }

        .available { color: #198754; }
        .unavailable { color: #dc3545; }
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
                        <form id="userForm" method="POST" action="/users/store">
                            <?= Security::generateCsrfToken() ?>
                            
                            <!-- Paso 1: Seleccionar Persona -->
                            <div class="form-section">
                                <h6><i class="bi bi-person-check"></i> Paso 1: Seleccionar Persona</h6>
                                
                                <div id="personaSearchCard" class="persona-search-card" onclick="openPersonaModal()">
                                    <i class="bi bi-search display-4 text-muted"></i>
                                    <h5 class="mt-3">Buscar Persona</h5>
                                    <p class="text-muted">Haga clic para buscar y seleccionar una persona que no tenga usuario asociado</p>
                                    <button type="button" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Buscar Personas
                                    </button>
                                </div>

                                <div id="personaSelected" class="persona-info mt-3" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Nombre:</strong> <span id="selectedPersonaNombre"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>RUT:</strong> <span id="selectedPersonaRut"></span>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <strong>Teléfono:</strong> <span id="selectedPersonaTelefono"></span>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <strong>Dirección:</strong> <span id="selectedPersonaDireccion"></span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="clearPersonaSelection()">
                                        <i class="bi bi-x"></i> Cambiar Persona
                                    </button>
                                </div>

                                <input type="hidden" id="persona_id" name="persona_id" required>
                            </div>

                            <!-- Paso 2: Datos del Usuario -->
                            <div class="form-section" id="userDataSection" style="display: none;">
                                <h6><i class="bi bi-person-gear"></i> Paso 2: Datos del Usuario</h6>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div id="emailCheck" class="availability-check"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre_usuario" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
                                        <div id="usernameCheck" class="availability-check"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                                <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                            </button>
                                        </div>
                                        <div id="passwordStrength" class="password-strength"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="usuario_tipo_id" class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
                                        <select class="form-select" id="usuario_tipo_id" name="usuario_tipo_id" required onchange="handleUserTypeChange()">
                                            <option value="">Seleccionar tipo</option>
                                            <?php foreach ($userTypes as $type): ?>
                                                <option value="<?= $type['id'] ?>" data-name="<?= htmlspecialchars($type['nombre']) ?>">
                                                    <?= htmlspecialchars($type['nombre']) ?> - <?= htmlspecialchars($type['descripcion']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Sección de Cliente (solo para client y counterparty) -->
                                <div id="clientSection" class="client-conditional">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                                            <select class="form-select" id="cliente_id" name="cliente_id" onchange="handleClientChange()">
                                                <option value="">Seleccionar cliente</option>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?= $client['id'] ?>">
                                                        <?= htmlspecialchars($client['razon_social']) ?> (<?= htmlspecialchars($client['rut']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información adicional para counterparty -->
                                <div id="counterpartyInfo" class="counterparty-search">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Usuario Counterparty:</strong> La persona seleccionada debe estar registrada como contraparte del cliente.
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_termino" class="form-label">Fecha de Término</label>
                                        <input type="date" class="form-control" id="fecha_termino" name="fecha_termino">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='/users'">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                    <i class="bi bi-check-circle"></i> Crear Usuario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Buscar Personas -->
    <div class="modal fade" id="personaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-search"></i> Buscar Persona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="personaSearch" placeholder="Buscar por nombre o RUT..." onkeyup="searchPersonas()">
                    </div>
                    <div id="personaResults">
                        <!-- Resultados de búsqueda aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedPersona = null;
        const personaModal = new bootstrap.Modal(document.getElementById('personaModal'));

        // Cargar personas disponibles al abrir modal
        function openPersonaModal() {
            personaModal.show();
            loadAvailablePersonas();
        }

        // Cargar personas disponibles
        function loadAvailablePersonas(search = '') {
            const url = `/users/search-personas?search=${encodeURIComponent(search)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayPersonas(data.personas);
                    } else {
                        document.getElementById('personaResults').innerHTML = '<p class="text-danger">Error cargando personas</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('personaResults').innerHTML = '<p class="text-danger">Error de conexión</p>';
                });
        }

        // Mostrar personas en el modal
        function displayPersonas(personas) {
            const container = document.getElementById('personaResults');
            
            if (personas.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No se encontraron personas disponibles</p>';
                return;
            }

            let html = '<div class="row">';
            personas.forEach(persona => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card persona-card" onclick="selectPersona(${persona.id}, '${persona.nombre}', '${persona.rut}', '${persona.telefono || ''}', '${persona.direccion || ''}')" style="cursor: pointer;">
                            <div class="card-body">
                                <h6 class="card-title">${persona.nombre}</h6>
                                <p class="card-text">
                                    <small class="text-muted">RUT: ${persona.rut}</small><br>
                                    ${persona.telefono ? `<small>Tel: ${persona.telefono}</small><br>` : ''}
                                    ${persona.direccion ? `<small>${persona.direccion}</small>` : ''}
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        // Seleccionar persona
        function selectPersona(id, nombre, rut, telefono, direccion) {
            selectedPersona = { id, nombre, rut, telefono, direccion };
            
            // Actualizar UI
            document.getElementById('persona_id').value = id;
            document.getElementById('selectedPersonaNombre').textContent = nombre;
            document.getElementById('selectedPersonaRut').textContent = rut;
            document.getElementById('selectedPersonaTelefono').textContent = telefono || 'No especificado';
            document.getElementById('selectedPersonaDireccion').textContent = direccion || 'No especificada';
            
            document.getElementById('personaSearchCard').style.display = 'none';
            document.getElementById('personaSelected').style.display = 'block';
            document.getElementById('userDataSection').style.display = 'block';
            
            checkFormCompletion();
            personaModal.hide();
        }

        // Limpiar selección de persona
        function clearPersonaSelection() {
            selectedPersona = null;
            document.getElementById('persona_id').value = '';
            document.getElementById('personaSearchCard').style.display = 'block';
            document.getElementById('personaSelected').style.display = 'none';
            document.getElementById('userDataSection').style.display = 'none';
            checkFormCompletion();
        }

        // Buscar personas (con debounce)
        let searchTimeout;
        function searchPersonas() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const search = document.getElementById('personaSearch').value;
                loadAvailablePersonas(search);
            }, 300);
        }

        // Manejar cambio de tipo de usuario
        function handleUserTypeChange() {
            const select = document.getElementById('usuario_tipo_id');
            const selectedOption = select.options[select.selectedIndex];
            const userTypeName = selectedOption.getAttribute('data-name');
            
            const clientSection = document.getElementById('clientSection');
            const counterpartyInfo = document.getElementById('counterpartyInfo');
            const clienteSelect = document.getElementById('cliente_id');
            
            if (userTypeName === 'client' || userTypeName === 'counterparty') {
                clientSection.style.display = 'block';
                clienteSelect.required = true;
                
                if (userTypeName === 'counterparty') {
                    counterpartyInfo.style.display = 'block';
                } else {
                    counterpartyInfo.style.display = 'none';
                }
            } else {
                clientSection.style.display = 'none';
                counterpartyInfo.style.display = 'none';
                clienteSelect.required = false;
                clienteSelect.value = '';
            }
            
            checkFormCompletion();
        }

        // Manejar cambio de cliente
        function handleClientChange() {
            checkFormCompletion();
        }

        // Verificar disponibilidad de email
        let emailTimeout;
        document.getElementById('email').addEventListener('input', function() {
            clearTimeout(emailTimeout);
            emailTimeout = setTimeout(() => {
                const email = this.value;
                if (email.length > 0) {
                    checkEmailAvailability(email);
                }
            }, 500);
        });

        // Verificar disponibilidad de username
        let usernameTimeout;
        document.getElementById('nombre_usuario').addEventListener('input', function() {
            clearTimeout(usernameTimeout);
            usernameTimeout = setTimeout(() => {
                const username = this.value;
                if (username.length > 0) {
                    checkUsernameAvailability(username);
                }
            }, 500);
        });

        // Verificar fortaleza de contraseña
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        function checkEmailAvailability(email) {
            fetch(`/users/validate-field?field=email&value=${encodeURIComponent(email)}`)
                .then(response => response.json())
                .then(data => {
                    const checkDiv = document.getElementById('emailCheck');
                    if (data.valid) {
                        checkDiv.innerHTML = '<i class="bi bi-check-circle"></i> Email disponible';
                        checkDiv.className = 'availability-check available';
                    } else {
                        checkDiv.innerHTML = '<i class="bi bi-x-circle"></i> ' + data.message;
                        checkDiv.className = 'availability-check unavailable';
                    }
                    checkFormCompletion();
                });
        }

        function checkUsernameAvailability(username) {
            fetch(`/users/validate-field?field=username&value=${encodeURIComponent(username)}`)
                .then(response => response.json())
                .then(data => {
                    const checkDiv = document.getElementById('usernameCheck');
                    if (data.valid) {
                        checkDiv.innerHTML = '<i class="bi bi-check-circle"></i> Usuario disponible';
                        checkDiv.className = 'availability-check available';
                    } else {
                        checkDiv.innerHTML = '<i class="bi bi-x-circle"></i> ' + data.message;
                        checkDiv.className = 'availability-check unavailable';
                    }
                    checkFormCompletion();
                });
        }

        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            let strength = 0;
            let messages = [];

            if (password.length >= 8) strength++;
            else messages.push('mínimo 8 caracteres');

            if (/[A-Z]/.test(password)) strength++;
            else messages.push('una mayúscula');

            if (/[a-z]/.test(password)) strength++;
            else messages.push('una minúscula');

            if (/[0-9]/.test(password)) strength++;
            else messages.push('un número');

            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            else messages.push('un carácter especial');

            if (strength < 3) {
                strengthDiv.innerHTML = '<i class="bi bi-shield-x"></i> Débil - Falta: ' + messages.join(', ');
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength < 5) {
                strengthDiv.innerHTML = '<i class="bi bi-shield-check"></i> Media - Falta: ' + messages.join(', ');
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.innerHTML = '<i class="bi bi-shield-fill-check"></i> Fuerte';
                strengthDiv.className = 'password-strength strength-strong';
            }

            checkFormCompletion();
        }

        function checkFormCompletion() {
            const submitBtn = document.getElementById('submitBtn');
            const requiredFields = ['persona_id', 'email', 'nombre_usuario', 'password', 'usuario_tipo_id'];
            
            let allValid = true;
            
            // Verificar campos requeridos
            for (let field of requiredFields) {
                const element = document.getElementById(field);
                if (!element.value) {
                    allValid = false;
                    break;
                }
            }
            
            // Verificar si tipo requiere cliente
            const userTypeSelect = document.getElementById('usuario_tipo_id');
            const selectedOption = userTypeSelect.options[userTypeSelect.selectedIndex];
            const userTypeName = selectedOption ? selectedOption.getAttribute('data-name') : '';
            
            if ((userTypeName === 'client' || userTypeName === 'counterparty') && !document.getElementById('cliente_id').value) {
                allValid = false;
            }
            
            // Verificar availability checks
            const emailCheck = document.getElementById('emailCheck');
            const usernameCheck = document.getElementById('usernameCheck');
            
            if (emailCheck.classList.contains('unavailable') || usernameCheck.classList.contains('unavailable')) {
                allValid = false;
            }
            
            submitBtn.disabled = !allValid;
        }

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

        // Manejar envío del formulario
        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/users/store', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/users?success=' + encodeURIComponent(data.message);
                } else {
                    alert('Error: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
            });
        });
    </script>
</body>
</html>
