<?php

use App\Helpers\Security;

// Verificar si es edición o creación
$isEdit = isset($data['user_id']) && $data['user_id'];
$user = $isEdit ? $data['user'] ?? null : null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .form-section {
            background: var(--setap-bg-light);
            border-left: 4px solid var(--setap-primary);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-section h6 {
            color: var(--setap-primary);
            margin-bottom: 0.5rem;
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
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-setap">
        <div class="container">
            <a class="navbar-brand" href="/home">
                <i class="bi bi-grid-3x3-gap"></i> SETAP
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-light" href="/home">
                    <i class="bi bi-house"></i> Home
                </a>
                <a class="nav-link text-light" href="/users">
                    <i class="bi bi-people"></i> Usuarios
                </a>
                <a class="nav-link text-light" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-person-plus"></i> <?php echo $data['title']; ?></h2>
                        <p class="text-muted mb-0"><?php echo $data['subtitle']; ?></p>
                    </div>
                    <a href="/users" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>

                <!-- Alertas -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i>
                        Usuario <?php echo $isEdit ? 'actualizado' : 'creado'; ?> exitosamente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i>
                        Error al procesar la solicitud. Intente nuevamente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <div class="card shadow">
                    <div class="card-body">
                        <form method="POST" action="<?php echo $isEdit ? '/users/update' : '/users/store'; ?>" id="userForm">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Security::generateCsrfToken()); ?>">
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['user_id']); ?>">
                            <?php endif; ?>

                            <!-- Vista previa del avatar -->
                            <?php if ($isEdit && $user): ?>
                                <div class="text-center mb-4">
                                    <div class="user-avatar-preview" id="avatarPreview">
                                        <?php echo strtoupper(substr($user['nombre_completo'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <h5><?php echo htmlspecialchars($user['nombre_completo'] ?? ''); ?></h5>
                                    <p class="text-muted">@<?php echo htmlspecialchars($user['nombre_usuario'] ?? ''); ?></p>
                                </div>
                                <hr>
                            <?php endif; ?>

                            <div class="row">
                                <!-- Información Personal -->
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h6><i class="bi bi-person"></i> Información Personal</h6>

                                        <div class="mb-3">
                                            <label for="rut" class="form-label">RUT <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control"
                                                id="rut"
                                                name="rut"
                                                placeholder="12345678-9"
                                                pattern="[0-9]{7,8}-[0-9Kk]"
                                                value="<?php echo htmlspecialchars($user['rut'] ?? ''); ?>"
                                                <?php echo $isEdit ? 'readonly' : 'required'; ?>>
                                            <div class="invalid-feedback" id="rutFeedback"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control"
                                                id="nombre"
                                                name="nombre"
                                                placeholder="Ej: Juan Pérez González"
                                                minlength="3"
                                                value="<?php echo htmlspecialchars($user['nombre_completo'] ?? ''); ?>"
                                                required>
                                            <div class="invalid-feedback" id="nombreFeedback"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel"
                                                class="form-control"
                                                id="telefono"
                                                name="telefono"
                                                placeholder="+56 9 1234 5678"
                                                value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="direccion" class="form-label">Dirección</label>
                                            <textarea class="form-control"
                                                id="direccion"
                                                name="direccion"
                                                rows="2"
                                                placeholder="Dirección completa"><?php echo htmlspecialchars($user['direccion'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Sistema -->
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h6><i class="bi bi-shield-check"></i> Información del Sistema</h6>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email"
                                                class="form-control"
                                                id="email"
                                                name="email"
                                                placeholder="usuario@ejemplo.com"
                                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                                required>
                                            <div class="invalid-feedback" id="emailFeedback"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="nombre_usuario" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control"
                                                id="nombre_usuario"
                                                name="nombre_usuario"
                                                placeholder="usuario123"
                                                minlength="4"
                                                value="<?php echo htmlspecialchars($user['nombre_usuario'] ?? ''); ?>"
                                                <?php echo $isEdit ? 'readonly' : 'required'; ?>>
                                            <div class="invalid-feedback" id="usernameFeedback"></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="usuario_tipo_id" class="form-label">Rol <span class="text-danger">*</span></label>
                                            <select class="form-select" id="usuario_tipo_id" name="usuario_tipo_id" required>
                                                <option value="">Seleccione un rol</option>
                                                <?php foreach ($data['userTypes'] ?? [] as $type): ?>
                                                    <option value="<?php echo $type['id']; ?>"
                                                        <?php echo ($user['usuario_tipo_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($type['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="estado_tipo_id" class="form-label">Estado</label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                                <?php foreach ($data['estadosTipo'] ?? [] as $estado): ?>
                                                    <?php if ($estado['id'] < 5): // No mostrar "Eliminado" 
                                                    ?>
                                                        <option value="<?php echo $estado['id']; ?>"
                                                            <?php echo ($user['estado_tipo_id'] ?? 1) == $estado['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($estado['nombre']); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <?php if (!$isEdit): ?>
                                            <div class="mb-3">
                                                <label for="clave_hash" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password"
                                                        class="form-control"
                                                        id="clave_hash"
                                                        name="clave_hash"
                                                        placeholder="Mínimo 6 caracteres"
                                                        minlength="6"
                                                        required>
                                                    <span class="input-group-text password-toggle" onclick="togglePassword('clave_hash')">
                                                        <i class="bi bi-eye"></i>
                                                    </span>
                                                </div>
                                                <div class="invalid-feedback" id="passwordFeedback"></div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password"
                                                        class="form-control"
                                                        id="confirm_password"
                                                        name="confirm_password"
                                                        placeholder="Repite la contraseña"
                                                        minlength="6"
                                                        required>
                                                    <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                                                        <i class="bi bi-eye"></i>
                                                    </span>
                                                </div>
                                                <div class="invalid-feedback" id="confirmPasswordFeedback"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Fechas de vigencia (solo para edición) -->
                            <?php if ($isEdit): ?>
                                <div class="form-section">
                                    <h6><i class="bi bi-calendar"></i> Fechas de Vigencia</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                            <input type="date"
                                                class="form-control"
                                                id="fecha_inicio"
                                                name="fecha_inicio"
                                                value="<?php echo $user['fecha_inicio'] ?? ''; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fecha_termino" class="form-label">Fecha de Término</label>
                                            <input type="date"
                                                class="form-control"
                                                id="fecha_termino"
                                                name="fecha_termino"
                                                value="<?php echo $user['fecha_termino'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Botones -->
                            <div class="d-flex justify-content-between">
                                <a href="/users" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-setap-primary" id="submitBtn">
                                    <i class="bi bi-save"></i>
                                    <?php echo $isEdit ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
    <script>
        // Toggle de contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        // Validación en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('userForm');
            const isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;

            // Validación de RUT
            if (!isEdit) {
                document.getElementById('rut').addEventListener('blur', function() {
                    validateField('rut', this.value);
                });
            }

            // Validación de email
            document.getElementById('email').addEventListener('blur', function() {
                validateField('email', this.value);
            });

            // Validación de nombre de usuario
            if (!isEdit) {
                document.getElementById('nombre_usuario').addEventListener('blur', function() {
                    validateField('username', this.value);
                });
            }

            // Validación de confirmación de contraseña
            if (!isEdit) {
                document.getElementById('confirm_password').addEventListener('blur', function() {
                    const password = document.getElementById('clave_hash').value;
                    const confirm = this.value;

                    if (password !== confirm) {
                        this.classList.add('is-invalid');
                        document.getElementById('confirmPasswordFeedback').textContent = 'Las contraseñas no coinciden';
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        document.getElementById('confirmPasswordFeedback').textContent = '';
                    }
                });
            }

            // Actualizar avatar preview
            document.getElementById('nombre').addEventListener('input', function() {
                const avatarPreview = document.getElementById('avatarPreview');
                if (avatarPreview) {
                    avatarPreview.textContent = this.value.charAt(0).toUpperCase() || 'U';
                }
            });
        });

        function validateField(field, value) {
            if (!value) return;

            fetch(`/api/users/validate?field=${field}&value=${encodeURIComponent(value)}`)
                .then(response => response.json())
                .then(data => {
                    const input = document.querySelector(`[name="${field === 'username' ? 'nombre_usuario' : field}"]`);
                    const feedback = document.getElementById(field === 'username' ? 'usernameFeedback' : field + 'Feedback');

                    if (data.valid) {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                        feedback.textContent = data.message;
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                        feedback.textContent = data.message;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>