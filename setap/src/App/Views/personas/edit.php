<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Persona - SETAP</title>
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
    </style>
</head>

<body class="bg-light">
    <?php use App\Helpers\Security; ?>

    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>
                    <i class="bi bi-pencil-square"></i> Editar Persona
                </h2>
                <p class="text-muted">Persona: <?= htmlspecialchars($persona['nombre']) ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/personas" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Personas
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

        <!-- Formulario de Edición -->
        <form method="POST" action="/personas/update" id="editPersonaForm">
            <?= \App\Helpers\Security::renderCsrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$persona['id'] ?>">

            <div class="row">
                <div class="col-md-8">
                    <!-- Información Personal -->
                    <div class="form-section">
                        <h5><i class="bi bi-person"></i> Información Personal</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rut" class="form-label">RUT <span class="required">*</span></label>
                                    <input type="text" class="form-control" id="rut" name="rut" required
                                           value="<?= htmlspecialchars($persona['rut']) ?>"
                                           placeholder="12.345.678-9" maxlength="20">
                                    <div class="form-text">Formato: 12.345.678-9 o 12345678-9</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo <span class="required">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required
                                           value="<?= htmlspecialchars($persona['nombre']) ?>"
                                           placeholder="Nombre y apellidos" maxlength="150">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono"
                                           value="<?= htmlspecialchars($persona['telefono'] ?? '') ?>"
                                           placeholder="+56 9 1234 5678" maxlength="20">
                                    <div class="form-text">Campo opcional</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estado_tipo_id" class="form-label">Estado <span class="required">*</span></label>
                                    <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                        <?php foreach ($estadosTipo as $estado): ?>
                                            <option value="<?= (int)$estado['id'] ?>"
                                                <?= $estado['id'] == $persona['estado_tipo_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($estado['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                      placeholder="Dirección completa..." maxlength="255"><?= htmlspecialchars($persona['direccion'] ?? '') ?></textarea>
                            <div class="form-text">Campo opcional. Máximo 255 caracteres.</div>
                        </div>
                    </div>
                </div>

                <!-- Panel Lateral -->
                <div class="col-md-4">
                    <!-- Información de la Persona -->
                    <div class="card">
                        <div class="card-header bg-setap-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información de la Persona</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>ID:</strong> <?= (int)$persona['id'] ?></p>
                            <p><strong>Creado:</strong><br><?= date('d/m/Y H:i', strtotime($persona['fecha_Creado'])) ?></p>
                            <?php if (!empty($persona['fecha_modificacion'])): ?>
                                <p><strong>Modificado:</strong><br><?= date('d/m/Y H:i', strtotime($persona['fecha_modificacion'])) ?></p>
                            <?php endif; ?>
                            <p><strong>Estado Actual:</strong><br>
                                <span class="badge bg-<?= match($persona['estado_tipo_id']) {
                                    1 => 'secondary', // Creado
                                    2 => 'success',   // Activo
                                    3 => 'warning',   // Inactivo
                                    default => 'dark'
                                } ?>">
                                    <?= htmlspecialchars($persona['estado']) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Instrucciones -->
                    <div class="card mt-3">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Importante</h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <p><strong>Cambio de RUT:</strong></p>
                                <p>Ten cuidado al modificar el RUT, ya que puede afectar otras relaciones en el sistema.</p>

                                <p><strong>Estado:</strong></p>
                                <p>Si cambias el estado a "Inactivo", la persona no aparecerá en las listas de selección de otros módulos.</p>

                                <p><strong>Eliminación:</strong></p>
                                <p>Si esta persona está asociada a usuarios o contrapartes, no podrá ser eliminada hasta que se eliminen esas relaciones.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success" id="saveBtn">
                                    <i class="bi bi-check-lg"></i> Guardar Cambios
                                </button>
                                <a href="/personas" class="btn btn-secondary">
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
    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editPersonaForm');
            const saveBtn = document.getElementById('saveBtn');
            const rutInput = document.getElementById('rut');
            const nombreInput = document.getElementById('nombre');

            // Formatear RUT mientras se escribe
            rutInput.addEventListener('input', function() {
                let value = this.value.replace(/[^0-9kK]/g, '');

                if (value.length > 1) {
                    let rut = value.slice(0, -1);
                    let dv = value.slice(-1);

                    // Formatear RUT con puntos
                    rut = rut.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                    this.value = rut + '-' + dv.toUpperCase();
                } else {
                    this.value = value.toUpperCase();
                }
            });

            // Capitalizar nombre
            nombreInput.addEventListener('blur', function() {
                this.value = this.value.split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
            });

            // Validación del RUT
            function validateRut(rut) {
                // Remover puntos y guión
                rut = rut.replace(/[^0-9kK]/g, '');

                if (rut.length < 8) return false;

                let body = rut.slice(0, -1);
                let dv = rut.slice(-1).toUpperCase();

                // Calcular dígito verificador
                let sum = 0;
                let multiplier = 2;

                for (let i = body.length - 1; i >= 0; i--) {
                    sum += parseInt(body[i]) * multiplier;
                    multiplier = multiplier === 7 ? 2 : multiplier + 1;
                }

                let calculatedDv = 11 - (sum % 11);
                if (calculatedDv === 11) calculatedDv = '0';
                if (calculatedDv === 10) calculatedDv = 'K';

                return dv === calculatedDv.toString();
            }

            // Validar RUT en tiempo real
            rutInput.addEventListener('blur', function() {
                if (this.value && !validateRut(this.value)) {
                    this.setCustomValidity('El RUT ingresado no es válido');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                }
            });

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                // Validar RUT antes de enviar
                if (rutInput.value && !validateRut(rutInput.value)) {
                    e.preventDefault();
                    alert('Por favor, ingresa un RUT válido.');
                    rutInput.focus();
                    return;
                }

                if (!confirm('¿Estás seguro de que deseas guardar los cambios en esta persona?')) {
                    e.preventDefault();
                    return;
                }

                // Mostrar indicador de carga
                saveBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Guardando...';
                saveBtn.disabled = true;

                // No deshabilitar los inputs del formulario - esto impide que se envíen los datos
                // Solo deshabilitar botones adicionales si existen
                const additionalButtons = form.querySelectorAll('button:not([type="submit"])');
                additionalButtons.forEach(button => {
                    button.disabled = true;
                });
            });
        });
    </script>
</body>
</html>