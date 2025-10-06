<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - SETAP</title>
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

        .form-section {
            background: var(--setap-bg-light);
            border-left: 4px solid var(--setap-primary);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.375rem;
        }

        .form-section h6 {
            color: var(--setap-primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .user-avatar-preview {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, var(--setap-primary), var(--setap-primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        .password-toggle {
            cursor: pointer;
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
    <?php use App\Helpers\Security; ?>

    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="card-title mb-0">
                            <i class="bi bi-person-gear"></i> Editar Usuario: <?= htmlspecialchars($userToEdit['nombre_completo']) ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/users/update" id="userEditForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Helpers\Security::generateCsrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$userToEdit['id'] ?>">

                            <!-- Vista previa del avatar -->
                            <div class="text-center mb-4">
                                <div class="user-avatar-preview" id="avatarPreview">
                                    <?= strtoupper(substr($userToEdit['nombre_completo'] ?? 'U', 0, 1)) ?>
                                </div>
                                <h5><?= htmlspecialchars($userToEdit['nombre_completo']) ?></h5>
                                <p class="text-muted">@<?= htmlspecialchars($userToEdit['nombre_usuario']) ?></p>
                            </div>
                            <hr>

                            <div class="row">
                                <!-- Información Personal -->
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h6><i class="bi bi-person"></i> Información Personal</h6>
                                        <div class="mb-3">
                                            <label for="rut" class="form-label">RUT <span class="text-muted">(Solo lectura)</span></label>
                                            <input type="text" class="form-control" id="rut" name="rut"
                                                value="<?= htmlspecialchars($userToEdit['rut']) ?>" readonly>
                                            <div class="invalid-feedback" id="rutFeedback"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre" name="nombre"
                                                value="<?= htmlspecialchars($userToEdit['nombre_completo']) ?>"
                                                placeholder="Ej: Juan Pérez González" minlength="3" required>
                                            <div class="invalid-feedback" id="nombreFeedback"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                                value="<?= htmlspecialchars($userToEdit['telefono'] ?? '') ?>"
                                                placeholder="+56 9 1234 5678">
                                        </div>

                                        <div class="mb-3">
                                            <label for="direccion" class="form-label">Dirección</label>
                                            <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                                placeholder="Dirección completa"><?= htmlspecialchars($userToEdit['direccion'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Sistema -->
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h6><i class="bi bi-shield-check"></i> Información del Sistema</h6>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?= htmlspecialchars($userToEdit['email']) ?>"
                                                placeholder="usuario@dominio.com" required>
                                            <div class="invalid-feedback" id="emailFeedback"></div>
                                            <div id="email-availability" class="availability-check"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="nombre_usuario" class="form-label">Nombre de Usuario <span class="text-muted">(Solo lectura)</span></label>
                                            <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario"
                                                value="<?= htmlspecialchars($userToEdit['nombre_usuario']) ?>" readonly>
                                            <div class="invalid-feedback" id="usernameFeedback"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="usuario_tipo_id" class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
                                            <select class="form-select" id="usuario_tipo_id" name="usuario_tipo_id" required>
                                                <option value="">Selecciona un tipo de usuario</option>
                                                <?php if (isset($userTypes) && is_array($userTypes)): ?>
                                                    <?php foreach ($userTypes as $tipo): ?>
                                                        <option value="<?= (int)$tipo['id'] ?>"
                                                            <?= $tipo['id'] == $userToEdit['usuario_tipo_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($tipo['nombre']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="estado_tipo_id" class="form-label">Estado</label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                                <?php if (isset($estadosTipo) && is_array($estadosTipo)): ?>
                                                    <?php foreach ($estadosTipo as $estado): ?>
                                                        <?php if ($estado['id'] < 5): // No mostrar "Eliminado" ?>
                                                            <option value="<?= (int)$estado['id'] ?>"
                                                                <?= ($userToEdit['estado_tipo_id'] ?? 1) == $estado['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($estado['nombre']) ?>
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fechas de vigencia -->
                            <div class="form-section">
                                <h6><i class="bi bi-calendar"></i> Fechas de Vigencia</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                                value="<?= $userToEdit['fecha_inicio'] ?? '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_termino" class="form-label">Fecha de Término</label>
                                            <input type="date" class="form-control" id="fecha_termino" name="fecha_termino"
                                                value="<?= $userToEdit['fecha_termino'] ?? '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Asignación de Cliente -->
                            <div class="row mb-4" id="client-selection" style="display: none;">
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
                                                    <option value="<?= $client['id'] ?>"
                                                        data-rut="<?= htmlspecialchars($client['rut'] ?? '') ?>"
                                                        <?= (isset($userToEdit['cliente_id']) && $client['id'] == $userToEdit['cliente_id']) ? 'selected' : '' ?>>
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

                            <!-- Información Adicional -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">
                                        <i class="bi bi-info-circle"></i> Información Adicional
                                    </h5>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha de Creación</label>
                                        <input type="text" class="form-control"
                                            value="<?= date('d/m/Y H:i', strtotime($userToEdit['fecha_Creado'])) ?>" readonly>
                                    </div>
                                </div>

                                <?php if (isset($userToEdit['fecha_modificacion']) && $userToEdit['fecha_modificacion']): ?>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Última Modificación</label>
                                        <input type="text" class="form-control"
                                            value="<?= date('d/m/Y H:i', strtotime($userToEdit['fecha_modificacion'])) ?>" readonly>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="/users" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-warning" id="updateBtn">
                                            <i class="bi bi-check-lg"></i> Actualizar Usuario
                                        </button>
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
    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('userEditForm');
            const updateBtn = document.getElementById('updateBtn');

            // Mostrar/ocultar sección de cliente basado en el tipo de usuario actual
            function toggleClientSection() {
                const userTypeSelect = document.getElementById('usuario_tipo_id');
                const selectedOption = userTypeSelect.options[userTypeSelect.selectedIndex];
                const userType = selectedOption.text.split(' - ')[0].trim();
                const clientSection = document.getElementById('client-selection');
                const clientSelect = document.getElementById('cliente_id');
                const validationInfo = document.getElementById('client-validation-info');

                // Tipos de usuario que requieren cliente
                const clientUserTypes = ['client', 'counterparty'];

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
            }

            // Inicializar la visibilidad de la sección de cliente
            toggleClientSection();

            // Manejar cambios en el tipo de usuario
            document.getElementById('usuario_tipo_id').addEventListener('change', toggleClientSection);

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

            // Validación de email en tiempo real
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

            // Actualizar avatar preview
            document.getElementById('nombre').addEventListener('input', function() {
                const avatarPreview = document.getElementById('avatarPreview');
                if (avatarPreview) {
                    avatarPreview.textContent = this.value.charAt(0).toUpperCase() || 'U';
                }
            });

            // Validación del formulario
            form.addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                const email = document.getElementById('email').value.trim();
                const usuario_tipo_id = document.getElementById('usuario_tipo_id').value;
                const clientSection = document.getElementById('client-selection');
                const clientSelect = document.getElementById('cliente_id');

                if (!nombre || !email || !usuario_tipo_id) {
                    e.preventDefault();
                    alert('<?= \App\Constants\AppConstants::ERROR_REQUIRED_FIELDS ?>');
                    return;
                }

                // Validar selección de cliente si es requerida
                if (clientSection.style.display !== 'none' && clientSelect.required && !clientSelect.value) {
                    e.preventDefault();
                    alert('<?= \App\Constants\AppConstants::ERROR_CLIENT_REQUIRED ?>');
                    return;
                }

                // Validar formato del email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('<?= \App\Constants\AppConstants::ERROR_INVALID_EMAIL ?>');
                    return;
                }

                // Confirmar actualización
                if (!confirm('¿Estás seguro de que deseas actualizar este usuario?')) {
                    e.preventDefault();
                    return;
                }

                // Mostrar indicador de carga
                updateBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Actualizando...';
                updateBtn.disabled = true;
            });

            // Auto-hide alerts después de 5 segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (bootstrap.Alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
</body>
</html>