<?php

use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Persona - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
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
    <?php

    use App\Helpers\Security; ?>

    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>
                    <i class="bi bi-person-plus"></i> Crear Nueva Persona
                </h2>
                <p class="text-muted">Complete los datos para registrar una nueva persona en el sistema.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= AppConstants::ROUTE_PERSONAS ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK_TO_PERSONAS ?>
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

        <!-- Modales para mensajes -->
        <!-- Modal de Error de RUT -->
        <div class="modal fade" id="rutErrorModal" tabindex="-1" aria-labelledby="rutErrorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="rutErrorModalLabel">
                            <i class="bi bi-exclamation-triangle"></i> RUT Inválido
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Por favor, ingresa un RUT válido.</p>
                        <small class="text-muted">El formato debe ser: 12.345.678-9</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="bi bi-check-lg"></i> Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmación -->
        <div class="modal fade" id="confirmCreateModal" tabindex="-1" aria-labelledby="confirmCreateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="confirmCreateModalLabel">
                            <i class="bi bi-question-circle"></i> Confirmar Creación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">¿Estás seguro de que deseas crear esta persona?</p>
                        <div class="bg-light p-3 rounded">
                            <strong>Datos a registrar:</strong>
                            <div class="mt-2">
                                <small class="text-muted d-block"><strong>RUT:</strong> <span id="confirm-rut"></span></small>
                                <small class="text-muted d-block"><strong>Nombre:</strong> <span id="confirm-nombre"></span></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-success" id="confirmCreateBtn">
                            <i class="bi bi-check-lg"></i> Sí, Crear Persona
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Creación -->
        <form method="POST" action="<?= AppConstants::ROUTE_PERSONAS ?>/store" id="createPersonaForm">
            <?= \App\Helpers\Security::renderCsrfField() ?>

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
                                        placeholder="12.345.678-9" maxlength="20">
                                    <input type="hidden" id="rut_clean" name="rut_clean">
                                    <div class="form-text">Formato: 12.345.678-9 o 12345678-9</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo <span class="required">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required
                                        placeholder="Nombre y apellidos" maxlength="150">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono"
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
                                                <?= $estado['id'] == 2 ? 'selected' : '' ?>>
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
                                placeholder="Dirección completa..." maxlength="255"></textarea>
                            <div class="form-text">Campo opcional. Máximo 255 caracteres.</div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="mt-4 text-end">
                            <a href="/personas" class="btn btn-secondary me-2">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success" id="createBtn">
                                <i class="bi bi-check-lg"></i> Crear Persona
                            </button>
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
            const form = document.getElementById('createPersonaForm');
            const createBtn = document.getElementById('createBtn');
            const rutInput = document.getElementById('rut');
            const nombreInput = document.getElementById('nombre');

            // Referencias a los modales
            const rutErrorModal = new bootstrap.Modal(document.getElementById('rutErrorModal'));
            const confirmCreateModal = new bootstrap.Modal(document.getElementById('confirmCreateModal'));
            const confirmCreateBtn = document.getElementById('confirmCreateBtn');

            // Variable para controlar el envío del formulario
            let formSubmissionConfirmed = false;

            // Inicializar campo oculto si hay valor inicial en el RUT
            if (rutInput.value) {
                let value = rutInput.value.replace(/[^0-9kK]/g, '');
                document.getElementById('rut_clean').value = value.toLowerCase();
            }

            // Formatear RUT mientras se escribe
            rutInput.addEventListener('input', function() {
                let value = this.value.replace(/[^0-9kK]/g, '');

                // Actualizar campo oculto con RUT limpio (solo números y K/k, sin puntos ni guión)
                document.getElementById('rut_clean').value = value.toLowerCase();

                if (value.length > 1) {
                    let rut = value.slice(0, -1);
                    let dv = value.slice(-1);

                    // Formatear RUT con puntos para mostrar en el input
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

            // Función para mostrar el modal de error de RUT
            function showRutErrorModal() {
                rutErrorModal.show();
                // Enfocar el campo RUT cuando se cierre el modal
                document.getElementById('rutErrorModal').addEventListener('hidden.bs.modal', function() {
                    rutInput.focus();
                }, {
                    once: true
                });
            }

            // Función para mostrar el modal de confirmación
            function showConfirmCreateModal() {
                // Actualizar los datos en el modal de confirmación
                document.getElementById('confirm-rut').textContent = rutInput.value || 'No especificado';
                document.getElementById('confirm-nombre').textContent = nombreInput.value || 'No especificado';
                confirmCreateModal.show();
            }

            // Manejar confirmación de creación
            confirmCreateBtn.addEventListener('click', function() {
                formSubmissionConfirmed = true;
                confirmCreateModal.hide();

                // Proceder con el envío del formulario
                // Mostrar indicador de carga
                createBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Creando...';
                createBtn.disabled = true;

                // No deshabilitar los inputs del formulario - esto impide que se envíen los datos
                // Solo deshabilitar botones adicionales si existen
                const additionalButtons = form.querySelectorAll('button:not([type="submit"])');
                additionalButtons.forEach(button => {
                    button.disabled = true;
                });

                // Enviar el formulario
                form.submit();
            });

            // Envío del formulario
            form.addEventListener('submit', function(e) {
                // Si ya fue confirmado, permitir el envío
                if (formSubmissionConfirmed) {
                    return;
                }

                e.preventDefault(); // Siempre prevenir el envío inicial

                // Validar RUT antes de mostrar confirmación
                if (rutInput.value && !validateRut(rutInput.value)) {
                    showRutErrorModal();
                    return;
                }

                // Mostrar modal de confirmación
                showConfirmCreateModal();
            });

            // Evitar que se cierre el modal de confirmación con ESC si hay datos importantes
            document.getElementById('confirmCreateModal').addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    // Permitir cerrar con ESC
                }
            });
        });
    </script>
</body>

</html>