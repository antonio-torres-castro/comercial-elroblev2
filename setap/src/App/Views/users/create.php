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
        .form-check-input:checked {
            background-color: var(--setap-primary);
            border-color: var(--setap-primary);
        }

        .password-strength {
            font-size: 0.875em;
            margin-top: 5px;
        }

        .strength-weak {
            color: #dc3545;
        }

        .strength-medium {
            color: #ffc107;
        }

        .strength-strong {
            color: #198754;
        }

        .availability-check {
            font-size: 0.875em;
            margin-top: 5px;
        }

        .available {
            color: #198754;
        }

        .unavailable {
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-light">
    <?php

    use App\Helpers\Security; ?>

    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
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
                    <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-person-plus"></i> Crear Nuevo Usuario
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/users/create" id="userForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Helpers\Security::generateCsrfToken()) ?>">

                            <!-- Información Personal -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">
                                        <i class="bi bi-person-vcard"></i> Información Personal
                                    </h5>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rut" class="form-label">RUT <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="rut" name="rut"
                                            placeholder="12345678-9" pattern="[0-9]{7,8}-[0-9Kk]" required>
                                        <div class="form-text">Formato: 12345678-9</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            placeholder="Ej: Juan Pérez González" minlength="3" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono"
                                            placeholder="+56 9 1234 5678">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <input type="text" class="form-control" id="direccion" name="direccion"
                                            placeholder="Dirección completa">
                                    </div>
                                </div>
                            </div>

                            <!-- Información de Usuario -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">
                                        <i class="bi bi-key"></i> Información de Usuario
                                    </h5>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="usuario@ejemplo.com" required>
                                        <div id="email-availability" class="availability-check"></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre_usuario" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario"
                                            placeholder="usuario123" minlength="4" required>
                                        <div id="username-availability" class="availability-check"></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password"
                                                minlength="8" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div id="password-strength" class="password-strength"></div>
                                        <div class="form-text">
                                            Mínimo 8 caracteres, incluye mayúsculas, minúsculas, números y símbolos
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="usuario_tipo_id" class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
                                        <select class="form-select" id="usuario_tipo_id" name="usuario_tipo_id" required>
                                            <option value="">Seleccionar tipo...</option>
                                            <?php foreach ($userTypes as $type): ?>
                                                <option value="<?= $type['id'] ?>">
                                                    <?= htmlspecialchars($type['nombre']) ?> - <?= htmlspecialchars($type['descripcion']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- GAP 1 y GAP 2: Selección de Cliente -->
                            <div class="row" id="client-selection" style="display: none;">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">
                                        <i class="bi bi-building"></i> Asignación de Cliente
                                    </h5>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cliente_id" name="cliente_id">
                                            <option value="">Seleccionar cliente...</option>
                                            <?php if (isset($clients) && is_array($clients)): ?>
                                                <?php foreach ($clients as $client): ?>
                                                    <option value="<?= $client['id'] ?>" data-rut="<?= htmlspecialchars($client['rut'] ?? '') ?>">
                                                        <?= htmlspecialchars($client['razon_social']) ?>
                                                        <?= !empty($client['rut']) ? ' - RUT: ' . htmlspecialchars($client['rut']) : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <div class="form-text">
                                            Seleccione el cliente al que pertenece este usuario.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12" id="client-validation-info" style="display: none;">
                                    <div class="alert alert-info">
                                        <strong>Nota importante:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li><strong>Usuario tipo "client":</strong> El RUT de la persona debe coincidir con el RUT del cliente.</li>
                                            <li><strong>Usuario tipo "counterparty":</strong> La persona debe estar registrada como contraparte del cliente.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="/users" class="btn btn-secondary me-md-2">
                                            <i class="bi bi-arrow-left"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-setap-primary" id="submitBtn">
                                            <i class="bi bi-check-lg"></i> Crear Usuario
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

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script>
        // Formatear RUT automáticamente
        document.getElementById('rut').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9kK]/g, '');
            if (value.length > 1) {
                value = value.slice(0, -1) + '-' + value.slice(-1);
            }
            e.target.value = value;
        });

        // Validar fortaleza de contraseña
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthDiv = document.getElementById('password-strength');

            let score = 0;
            let feedback = [];

            if (password.length >= 8) score++;
            else feedback.push('Al menos 8 caracteres');

            if (/[a-z]/.test(password)) score++;
            else feedback.push('Una letra minúscula');

            if (/[A-Z]/.test(password)) score++;
            else feedback.push('Una letra mayúscula');

            if (/[0-9]/.test(password)) score++;
            else feedback.push('Un número');

            if (/[^A-Za-z0-9]/.test(password)) score++;
            else feedback.push('Un carácter especial');

            if (score < 3) {
                strengthDiv.className = 'password-strength strength-weak';
                strengthDiv.innerHTML = '<i class="bi bi-shield-x"></i> Débil - Falta: ' + feedback.join(', ');
            } else if (score < 5) {
                strengthDiv.className = 'password-strength strength-medium';
                strengthDiv.innerHTML = '<i class="bi bi-shield-check"></i> Media - Falta: ' + feedback.join(', ');
            } else {
                strengthDiv.className = 'password-strength strength-strong';
                strengthDiv.innerHTML = '<i class="bi bi-shield-check-fill"></i> Fuerte';
            }
        });

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });

        // Verificar disponibilidad de email
        let emailTimeout;
        document.getElementById('email').addEventListener('input', function(e) {
            clearTimeout(emailTimeout);
            const email = e.target.value;
            const div = document.getElementById('email-availability');

            if (email.length > 0 && email.includes('@')) {
                emailTimeout = setTimeout(() => {
                    fetch(`/api/user-check?type=email&value=${encodeURIComponent(email)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.available) {
                                div.className = 'availability-check available';
                                div.innerHTML = '<i class="bi bi-check-circle"></i> Email disponible';
                            } else {
                                div.className = 'availability-check unavailable';
                                div.innerHTML = '<i class="bi bi-x-circle"></i> Email ya está en uso';
                            }
                        })
                        .catch(() => {
                            div.innerHTML = '';
                        });
                }, 500);
            } else {
                div.innerHTML = '';
            }
        });

        // Verificar disponibilidad de username
        let usernameTimeout;
        document.getElementById('nombre_usuario').addEventListener('input', function(e) {
            clearTimeout(usernameTimeout);
            const username = e.target.value;
            const div = document.getElementById('username-availability');

            if (username.length >= 4) {
                usernameTimeout = setTimeout(() => {
                    fetch(`/api/user-check?type=username&value=${encodeURIComponent(username)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.available) {
                                div.className = 'availability-check available';
                                div.innerHTML = '<i class="bi bi-check-circle"></i> Usuario disponible';
                            } else {
                                div.className = 'availability-check unavailable';
                                div.innerHTML = '<i class="bi bi-x-circle"></i> Usuario ya está en uso';
                            }
                        })
                        .catch(() => {
                            div.innerHTML = '';
                        });
                }, 500);
            } else {
                div.innerHTML = '';
            }
        });

        // GAP 1 y GAP 2: Manejar selección de tipo de usuario
        document.getElementById('usuario_tipo_id').addEventListener('change', function(e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const userType = selectedOption.text.split(' - ')[0].trim();
            const clientSection = document.getElementById('client-selection');
            const clientSelect = document.getElementById('cliente_id');
            const validationInfo = document.getElementById('client-validation-info');
            
            // Tipos de usuario que requieren cliente
            const clientUserTypes = ['client', 'counterparty'];
            const companyUserTypes = ['admin', 'planner', 'supervisor', 'executor'];
            
            if (clientUserTypes.includes(userType.toLowerCase())) {
                // Mostrar selección de cliente
                clientSection.style.display = 'block';
                clientSelect.required = true;
                validationInfo.style.display = 'block';
            } else {
                // Ocultar selección de cliente
                clientSection.style.display = 'none';
                clientSelect.required = false;
                clientSelect.value = '';
                validationInfo.style.display = 'none';
            }
        });
        
        // Validar RUT vs Cliente para usuarios tipo 'client'
        document.getElementById('cliente_id').addEventListener('change', function(e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const clientRut = selectedOption.getAttribute('data-rut');
            const userTypeSelect = document.getElementById('usuario_tipo_id');
            const userType = userTypeSelect.options[userTypeSelect.selectedIndex].text.split(' - ')[0].trim();
            const rutInput = document.getElementById('rut');
            
            if (userType.toLowerCase() === 'client' && clientRut && rutInput.value) {
                const cleanClientRut = clientRut.replace(/[^0-9kK]/g, '').toLowerCase();
                const cleanPersonRut = rutInput.value.replace(/[^0-9kK]/g, '').toLowerCase();
                
                if (cleanClientRut !== cleanPersonRut) {
                    alert('Atención: El RUT de la persona debe coincidir con el RUT del cliente seleccionado para usuarios tipo "client".');
                }
            }
        });
        
        // Validar RUT vs Cliente cuando se modifica el RUT
        document.getElementById('rut').addEventListener('blur', function(e) {
            const clientSelect = document.getElementById('cliente_id');
            const userTypeSelect = document.getElementById('usuario_tipo_id');
            
            if (clientSelect.value && userTypeSelect.value) {
                const selectedOption = clientSelect.options[clientSelect.selectedIndex];
                const clientRut = selectedOption.getAttribute('data-rut');
                const userType = userTypeSelect.options[userTypeSelect.selectedIndex].text.split(' - ')[0].trim();
                
                if (userType.toLowerCase() === 'client' && clientRut && e.target.value) {
                    const cleanClientRut = clientRut.replace(/[^0-9kK]/g, '').toLowerCase();
                    const cleanPersonRut = e.target.value.replace(/[^0-9kK]/g, '').toLowerCase();
                    
                    if (cleanClientRut !== cleanPersonRut) {
                        alert('Atención: El RUT de la persona debe coincidir con el RUT del cliente seleccionado para usuarios tipo "client".');
                    }
                }
            }
        });

        // Validar formulario antes de enviar
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const unavailable = document.querySelectorAll('.unavailable');

            if (unavailable.length > 0) {
                e.preventDefault();
                alert('Por favor, corrija los campos marcados como no disponibles antes de continuar.');
                return false;
            }

            // Deshabilitar botón para evitar doble envío
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Creando...';
        });
    </script>
</body>

</html>