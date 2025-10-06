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



        .persona-result-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 0.75rem;
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
                            <?= \App\Helpers\Security::renderCsrfField() ?>
                            <input type="hidden" name="id" value="<?= (int)$userToEdit['id'] ?>">
                            <input type="hidden" name="current_persona_id" value="<?= (int)$userToEdit['persona_id'] ?>">

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
                                        
                                        <!-- Solo mostrar la información de la persona, sin permitir edición -->
                                        <div class="mb-3">
                                            <label class="form-label">RUT de la Persona</label>
                                            <input type="text" class="form-control" 
                                                value="<?= htmlspecialchars($userToEdit['rut']) ?>" readonly>
                                            <div class="form-text">Este campo no se puede editar desde aquí. Para modificar datos personales, edite la persona directamente.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Nombre Completo</label>
                                            <input type="text" class="form-control" 
                                                value="<?= htmlspecialchars($userToEdit['nombre_completo']) ?>" readonly>
                                            <div class="form-text">Este campo no se puede editar desde aquí. Para modificar datos personales, edite la persona directamente.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Teléfono</label>
                                            <input type="text" class="form-control" 
                                                value="<?= htmlspecialchars($userToEdit['telefono'] ?? '') ?>" readonly>
                                            <div class="form-text">Este campo no se puede editar desde aquí. Para modificar datos personales, edite la persona directamente.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Dirección</label>
                                            <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($userToEdit['direccion'] ?? '') ?></textarea>
                                            <div class="form-text">Este campo no se puede editar desde aquí. Para modificar datos personales, edite la persona directamente.</div>
                                        </div>

                                        <!-- Búsqueda de personas para cambio -->
                                        <div class="mb-3">
                                            <label class="form-label">Buscar Nueva Persona (Opcional)</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           id="persona_search" 
                                                           name="persona_search"
                                                           placeholder="RUT o nombre para buscar"
                                                           value="<?= htmlspecialchars($_SESSION['old_input']['persona_search'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-select" id="search_type" name="search_type">
                                                        <option value="all" <?= ($_SESSION['old_input']['search_type'] ?? 'all') === 'all' ? 'selected' : '' ?>>Todo</option>
                                                        <option value="rut" <?= ($_SESSION['old_input']['search_type'] ?? '') === 'rut' ? 'selected' : '' ?>>RUT</option>
                                                        <option value="name" <?= ($_SESSION['old_input']['search_type'] ?? '') === 'name' ? 'selected' : '' ?>>Nombre</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <button type="submit" name="search_persona" class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-search"></i> Buscar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

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

                                        <!-- Resultados de búsqueda -->
                                        <?php if (isset($_SESSION['persona_results'])): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Seleccionar Nueva Persona</label>
                                                <?php if (empty($_SESSION['persona_results'])): ?>
                                                    <div class="alert alert-warning">
                                                        <i class="bi bi-exclamation-triangle"></i> No se encontraron personas con ese criterio.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="row" style="max-height: 300px; overflow-y: auto;">
                                                        <?php foreach ($_SESSION['persona_results'] as $persona): ?>
                                                            <div class="col-12 mb-2">
                                                                <div class="persona-result-card small <?= $persona['has_user'] ? 'border-warning' : '' ?> <?= $persona['id'] == $userToEdit['persona_id'] ? 'border-info bg-info bg-opacity-10' : '' ?>">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" 
                                                                               type="radio" 
                                                                               name="new_persona_id" 
                                                                               id="new_persona_<?= $persona['id'] ?>"
                                                                               value="<?= $persona['id'] ?>"
                                                                               <?= ($_SESSION['old_input']['new_persona_id'] ?? '') == $persona['id'] ? 'checked' : '' ?>>
                                                                        <label class="form-check-label w-100" for="new_persona_<?= $persona['id'] ?>">
                                                                            <strong><?= htmlspecialchars($persona['nombre']) ?></strong>
                                                                            - RUT: <?= htmlspecialchars($persona['rut']) ?>
                                                                            <?php if ($persona['id'] == $userToEdit['persona_id']): ?>
                                                                                <span class="badge bg-info ms-2">ACTUAL</span>
                                                                            <?php elseif ($persona['has_user']): ?>
                                                                                <span class="badge bg-warning ms-2">Asignada a: <?= htmlspecialchars($persona['usuario_asociado']) ?></span>
                                                                            <?php endif; ?>
                                                                            <?php if (!empty($persona['telefono'])): ?>
                                                                                <br><small class="text-muted">Tel: <?= htmlspecialchars($persona['telefono']) ?></small>
                                                                            <?php endif; ?>
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

                                        <!-- Campo persona actual (solo lectura para información) -->
                                        <div class="mb-3">
                                            <label for="persona_id" class="form-label">Persona Actualmente Asociada</label>
                                            <input type="text" class="form-control" 
                                                   value="<?= htmlspecialchars($userToEdit['nombre_completo']) ?> - RUT: <?= htmlspecialchars($userToEdit['rut']) ?>" 
                                                   readonly>
                                            <input type="hidden" name="persona_id" value="<?= (int)$userToEdit['persona_id'] ?>">
                                            <div class="form-text">Para cambiar la persona, use la búsqueda de arriba y seleccione una nueva persona.</div>
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

            // Nota: Se eliminó la carga AJAX de personas ya que no se usa en esta vista

            // Nota: Se eliminó la función showErrorMessage ya que no se usa después de quitar AJAX

            // Validar lógica de negocio según tipo de usuario (simplificada - sin AJAX)
            function validateBusinessLogic() {
                const clientSelect = document.getElementById('cliente_id');
                const userTypeSelect = document.getElementById('usuario_tipo_id');
                
                const userType = userTypeSelect.options[userTypeSelect.selectedIndex].text.split(' - ')[0].trim().toLowerCase();
                
                // Limpiar mensajes de validación previos
                clearValidationMessages();
                
                // Validar usuarios tipo 'client' y 'counterparty'
                if (userType === 'client' || userType === 'counterparty') {
                    if (!clientSelect.value) {
                        showValidationError(`Usuario tipo "${userType}" debe tener un cliente asociado.`);
                        return false;
                    }
                }
                
                // Validar usuarios internos (no deben tener cliente)
                else if (['admin', 'planner', 'supervisor', 'executor'].includes(userType)) {
                    if (clientSelect.value) {
                        showValidationError(`Usuario tipo "${userType}" no debe tener cliente asociado.`);
                        return false;
                    }
                }
                
                // Nota: Validaciones adicionales de RUT y contraparte se manejan en el backend
                return true;
            }

            // Mostrar mensaje de error de validación
            function showValidationError(message) {
                let errorDiv = document.getElementById('validation-error-message');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.id = 'validation-error-message';
                    errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-2';
                    errorDiv.innerHTML = `
                        <i class="bi bi-exclamation-triangle"></i> <span class="error-text"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Insertar antes del formulario
                    const form = document.getElementById('userEditForm');
                    form.insertBefore(errorDiv, form.firstElementChild);
                }
                
                errorDiv.querySelector('.error-text').textContent = message;
                errorDiv.style.display = 'block';
            }

            // Mostrar mensaje informativo
            function showInfoMessage(message) {
                let infoDiv = document.getElementById('validation-info-message');
                if (!infoDiv) {
                    infoDiv = document.createElement('div');
                    infoDiv.id = 'validation-info-message';
                    infoDiv.className = 'alert alert-info alert-dismissible fade show mt-2';
                    infoDiv.innerHTML = `
                        <i class="bi bi-info-circle"></i> <span class="info-text"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Insertar antes del formulario
                    const form = document.getElementById('userEditForm');
                    form.insertBefore(infoDiv, form.firstElementChild);
                }
                
                infoDiv.querySelector('.info-text').textContent = message;
                infoDiv.style.display = 'block';
                
                // Auto-hide después de 5 segundos
                setTimeout(() => {
                    if (infoDiv && infoDiv.parentNode) {
                        infoDiv.style.display = 'none';
                    }
                }, 5000);
            }

            // Limpiar mensajes de validación
            function clearValidationMessages() {
                const errorDiv = document.getElementById('validation-error-message');
                const infoDiv = document.getElementById('validation-info-message');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
                if (infoDiv) {
                    infoDiv.style.display = 'none';
                }
            }

            // Función de compatibilidad para llamadas existentes
            function validateClientPersonaRut() {
                return validateBusinessLogic();
            }

            // Agregar listeners para validación de negocio
            document.getElementById('cliente_id').addEventListener('change', validateBusinessLogic);
            document.getElementById('usuario_tipo_id').addEventListener('change', function() {
                toggleClientSection();
                validateBusinessLogic();
            });

            // Nota: Se eliminó la validación AJAX de email en tiempo real - ahora se valida en el backend
            // Nota: Se eliminaron los listeners de persona_id ya que es un campo hidden, no un select

            // Validación del formulario
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const usuario_tipo_id = document.getElementById('usuario_tipo_id').value;
                const persona_id = document.getElementById('persona_id').value;
                const clientSection = document.getElementById('client-selection');
                const clientSelect = document.getElementById('cliente_id');

                if (!email || !usuario_tipo_id || !persona_id) {
                    e.preventDefault();
                    alert('Por favor, complete todos los campos requeridos (Email, Tipo de Usuario, Persona).');
                    return;
                }

                // Validar selección de cliente si es requerida
                if (clientSection.style.display !== 'none' && clientSelect.required && !clientSelect.value) {
                    e.preventDefault();
                    alert('Debe seleccionar un cliente para este tipo de usuario.');
                    return;
                }

                // Validar lógica de negocio (tipos de usuario, clientes, etc.)
                if (!validateBusinessLogic()) {
                    e.preventDefault();
                    return;
                }

                // Validar formato del email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Por favor, ingrese un email válido.');
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