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

                            <!-- Información Personal -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">
                                        <i class="bi bi-person-vcard"></i> Información Personal
                                    </h5>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rut" class="form-label">RUT <span class="text-muted">(Solo lectura)</span></label>
                                        <input type="text" class="form-control" id="rut" name="rut"
                                            value="<?= htmlspecialchars($userToEdit['rut']) ?>" readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            value="<?= htmlspecialchars($userToEdit['nombre_completo']) ?>"
                                            placeholder="Ej: Juan Pérez González" minlength="3" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono"
                                            value="<?= htmlspecialchars($userToEdit['telefono'] ?? '') ?>"
                                            placeholder="+56 9 1234 5678">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <input type="text" class="form-control" id="direccion" name="direccion"
                                            value="<?= htmlspecialchars($userToEdit['direccion'] ?? '') ?>"
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
                                        <label for="nombre_usuario" class="form-label">Nombre de Usuario <span class="text-muted">(Solo lectura)</span></label>
                                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario"
                                            value="<?= htmlspecialchars($userToEdit['nombre_usuario']) ?>" readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?= htmlspecialchars($userToEdit['email']) ?>"
                                            placeholder="usuario@dominio.com" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
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
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="estado" class="form-label">Estado Actual</label>
                                        <input type="text" class="form-control" id="estado" name="estado"
                                            value="<?= htmlspecialchars($userToEdit['estado'] ?? 'Activo') ?>" readonly>
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

            // Validación del formulario
            form.addEventListener('submit', function(e) {
                const nombre = document.getElementById('nombre').value.trim();
                const email = document.getElementById('email').value.trim();
                const usuario_tipo_id = document.getElementById('usuario_tipo_id').value;
                const clientSection = document.getElementById('client-selection');
                const clientSelect = document.getElementById('cliente_id');

                if (!nombre || !email || !usuario_tipo_id) {
                    e.preventDefault();
                    alert('Por favor, completa todos los campos obligatorios.');
                    return;
                }

                // Validar selección de cliente si es requerida
                if (clientSection.style.display !== 'none' && clientSelect.required && !clientSelect.value) {
                    e.preventDefault();
                    alert('Por favor, selecciona un cliente para este tipo de usuario.');
                    return;
                }

                // Validar formato del email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Por favor, ingresa un email válido.');
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
        });
    </script>
</body>
</html>