<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title']); ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/setap-theme.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo htmlspecialchars($data['title']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/clients" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Lista
                        </a>
                    </div>
                </div>

                <!-- Mostrar errores de validación -->
                <?php if (!empty($data['errors'])): ?>
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle"></i> Errores de Validación:</h6>
                        <ul class="mb-0">
                            <?php foreach ($data['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo htmlspecialchars($data['subtitle']); ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/clients/store" id="clientForm">
                                    <?= \App\Helpers\Security::renderCsrfField() ?>

                                    <!-- Información Básica -->
                                    <h6 class="border-bottom pb-2 mb-3">Información Básica</h6>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label for="razon_social" class="form-label">
                                                Razón Social <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="razon_social" name="razon_social"
                                                   value="<?php echo htmlspecialchars($data['client']['razon_social'] ?? ''); ?>"
                                                   required maxlength="150">
                                            <div class="form-text">Nombre oficial de la empresa</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="rut" class="form-label">RUT</label>
                                            <input type="text" class="form-control" id="rut" name="rut"
                                                   value="<?php echo htmlspecialchars($data['client']['rut'] ?? ''); ?>"
                                                   maxlength="20" placeholder="12.345.678-9">
                                            <div class="form-text">RUT de la empresa (opcional)</div>
                                            <div id="rutError" class="text-danger" style="display: none;"></div>
                                        </div>
                                    </div>

                                    <!-- Contacto -->
                                    <h6 class="border-bottom pb-2 mb-3">Información de Contacto</h6>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                   value="<?php echo htmlspecialchars($data['client']['email'] ?? ''); ?>"
                                                   maxlength="150">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="text" class="form-control" id="telefono" name="telefono"
                                                   value="<?php echo htmlspecialchars($data['client']['telefono'] ?? ''); ?>"
                                                   maxlength="20">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <label for="direccion" class="form-label">Dirección</label>
                                            <textarea class="form-control" id="direccion" name="direccion"
                                                      rows="2" maxlength="255"><?php echo htmlspecialchars($data['client']['direccion'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Fechas de Contrato -->
                                    <h6 class="border-bottom pb-2 mb-3">Información de Contrato</h6>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label for="fecha_inicio_contrato" class="form-label">Fecha Inicio Contrato</label>
                                            <input type="date" class="form-control" id="fecha_inicio_contrato"
                                                   name="fecha_inicio_contrato"
                                                   value="<?php echo htmlspecialchars($data['client']['fecha_inicio_contrato'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="fecha_facturacion" class="form-label">Fecha Facturación</label>
                                            <input type="date" class="form-control" id="fecha_facturacion"
                                                   name="fecha_facturacion"
                                                   value="<?php echo htmlspecialchars($data['client']['fecha_facturacion'] ?? ''); ?>">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="fecha_termino_contrato" class="form-label">Fecha Término Contrato</label>
                                            <input type="date" class="form-control" id="fecha_termino_contrato"
                                                   name="fecha_termino_contrato"
                                                   value="<?php echo htmlspecialchars($data['client']['fecha_termino_contrato'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- Estado -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label for="estado_tipo_id" class="form-label">Estado</label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                                <?php foreach ($data['statusTypes'] as $status): ?>
                                                    <option value="<?php echo $status['id']; ?>"
                                                            <?php echo ($data['client']['estado_tipo_id'] ?? 1) == $status['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($status['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Botones -->
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="/clients" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-setap-primary">
                                            <i class="bi bi-floppy"></i> Guardar Cliente
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rutInput = document.getElementById('rut');
            const rutError = document.getElementById('rutError');

            // Formatear RUT mientras se escribe
            rutInput.addEventListener('input', function() {
                let value = this.value.replace(/[^0-9kK]/g, '');

                if (value.length > 1) {
                    // Separar cuerpo y dígito verificador
                    let body = value.slice(0, -1);
                    let dv = value.slice(-1).toUpperCase();

                    // Formatear cuerpo con puntos
                    body = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                    // Unir con guión
                    this.value = body + '-' + dv;
                }
            });

            // Validar RUT al perder el foco
            rutInput.addEventListener('blur', function() {
                const rut = this.value;
                if (rut && !validateRut(rut)) {
                    rutError.textContent = 'El formato del RUT es inválido';
                    rutError.style.display = 'block';
                    this.classList.add('is-invalid');
                } else {
                    rutError.style.display = 'none';
                    this.classList.remove('is-invalid');
                }
            });

            // Validar fechas
            const fechaInicio = document.getElementById('fecha_inicio_contrato');
            const fechaTermino = document.getElementById('fecha_termino_contrato');

            function validateDates() {
                if (fechaInicio.value && fechaTermino.value) {
                    if (new Date(fechaTermino.value) <= new Date(fechaInicio.value)) {
                        fechaTermino.setCustomValidity('La fecha de término debe ser posterior a la fecha de inicio');
                    } else {
                        fechaTermino.setCustomValidity('');
                    }
                }
            }

            fechaInicio.addEventListener('change', validateDates);
            fechaTermino.addEventListener('change', validateDates);
        });

        function validateRut(rut) {
            // Limpiar RUT
            rut = rut.replace(/[^0-9kK]/g, '');

            if (rut.length < 2) return false;

            let body = rut.slice(0, -1);
            let dv = rut.slice(-1).toUpperCase();

            // Calcular dígito verificador
            let sum = 0;
            let multiplier = 2;

            for (let i = body.length - 1; i >= 0; i--) {
                sum += parseInt(body[i]) * multiplier;
                multiplier = multiplier === 7 ? 2 : multiplier + 1;
            }

            let expectedDv = 11 - (sum % 11);

            if (expectedDv === 11) expectedDv = '0';
            else if (expectedDv === 10) expectedDv = 'K';
            else expectedDv = expectedDv.toString();

            return dv === expectedDv;
        }
    </script>
</body>
</html>