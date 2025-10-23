<?php

use App\Constants\AppConstants;
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
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= AppConstants::ROUTE_CLIENT_COUNTERPARTIES ?>" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Lista
                        </a>
                    </div>
                </div>

                <!-- Mensajes de error -->
                <?php if (isset($data['errors']) && !empty($data['errors'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="bi bi-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
                        <ul class="mb-0">
                            <?php foreach ($data['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-badge"></i> <?php echo $data['subtitle']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo $data['action'] === 'edit' ? AppConstants::ROUTE_CLIENT_COUNTERPARTIE . '/update' : AppConstants::ROUTE_CLIENT_COUNTERPARTIE . '/store'; ?>">
                                    <?= \App\Helpers\Security::renderCsrfField() ?>

                                    <?php if (safe($data, 'action') === 'edit' && safe($data, 'counterpartie')): ?>
                                        <input type="hidden" name="id" value="<?php echo safeHtml($data, 'counterpartie.id'); ?>">
                                    <?php endif; ?>

                                    <div class="row g-3">
                                        <!-- Cliente -->
                                        <div class="col-md-6">
                                            <label for="cliente_id" class="form-label">
                                                Cliente <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select <?php echo isset($data['errors']) && in_array('cliente_id', array_column($data['errors'], 'field')) ? 'is-invalid' : ''; ?>"
                                                id="cliente_id" name="cliente_id" required>
                                                <option value="">Seleccionar cliente...</option>
                                                <?php foreach ($data['clients'] as $client): ?>
                                                    <option value="<?php echo $client['id']; ?>"
                                                        <?php echo safeSelected($data, 'counterpartie.cliente_id', $client['id']); ?>>
                                                        <?php echo htmlspecialchars($client['razon_social']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Selecciona el cliente al que pertenece esta contraparte.</div>
                                        </div>

                                        <!-- Persona -->
                                        <div class="col-md-6">
                                            <label for="persona_id" class="form-label">
                                                Persona <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select <?php echo isset($data['errors']) && in_array('persona_id', array_column($data['errors'], 'field')) ? 'is-invalid' : ''; ?>"
                                                id="persona_id" name="persona_id" required>
                                                <option value="">Seleccionar persona...</option>
                                                <?php foreach ($data['personas'] as $persona): ?>
                                                    <option value="<?php echo $persona['id']; ?>"
                                                        <?php
                                                        $selected = false;
                                                        if ($data['action'] === 'edit' && $data['counterpartie']) {
                                                            $selected = ($data['counterpartie']['persona_id'] == $persona['id']);
                                                        } elseif (isset($_POST['persona_id'])) {
                                                            $selected = ($_POST['persona_id'] == $persona['id']);
                                                        }
                                                        echo $selected ? 'selected' : '';
                                                        ?>>
                                                        <?php echo htmlspecialchars($persona['nombre'] . ' - ' . $persona['rut']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Selecciona la persona que actuará como contraparte.</div>
                                        </div>

                                        <!-- Cargo -->
                                        <div class="col-md-6">
                                            <label for="cargo" class="form-label">Cargo</label>
                                            <input type="text" class="form-control" id="cargo" name="cargo"
                                                placeholder="Ej: Gerente de Proyecto, Coordinador, etc."
                                                value="<?php
                                                        if ($data['action'] === 'edit' && $data['counterpartie']) {
                                                            echo htmlspecialchars($data['counterpartie']['cargo'] ?? '');
                                                        } elseif (isset($_POST['cargo'])) {
                                                            echo htmlspecialchars($_POST['cargo']);
                                                        }
                                                        ?>" maxlength="100">
                                            <div class="form-text">Cargo o posición de la persona en el cliente (opcional).</div>
                                        </div>

                                        <!-- Estado -->
                                        <div class="col-md-6">
                                            <label for="estado_tipo_id" class="form-label">
                                                Estado <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                                <?php foreach ($data['statusTypes'] as $status): ?>
                                                    <option value="<?php echo $status['id']; ?>"
                                                        <?php
                                                        $selected = false;
                                                        if ($data['action'] === 'edit' && $data['counterpartie']) {
                                                            $selected = ($data['counterpartie']['estado_tipo_id'] == $status['id']);
                                                        } elseif (isset($_POST['estado_tipo_id'])) {
                                                            $selected = ($_POST['estado_tipo_id'] == $status['id']);
                                                        } elseif ($status['id'] == 2) { // Activo por defecto
                                                            $selected = true;
                                                        }
                                                        echo $selected ? 'selected' : '';
                                                        ?>>
                                                        <?php echo htmlspecialchars($status['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Información de contacto específica para este cliente -->
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="text-muted">
                                                <i class="bi bi-telephone"></i> Información de Contacto Específica
                                                <small class="text-muted">(para este cliente)</small>
                                            </h6>
                                            <p class="small text-muted">
                                                Puedes especificar información de contacto específica para esta relación cliente-contraparte.
                                                Si no se especifica, se usará la información de contacto general de la persona.
                                            </p>
                                        </div>

                                        <!-- Teléfono -->
                                        <div class="col-md-6">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                                placeholder="Ej: +56 9 1234 5678"
                                                value="<?php
                                                        if ($data['action'] === 'edit' && $data['counterpartie']) {
                                                            echo htmlspecialchars($data['counterpartie']['telefono'] ?? '');
                                                        } elseif (isset($_POST['telefono'])) {
                                                            echo htmlspecialchars($_POST['telefono']);
                                                        }
                                                        ?>" maxlength="20">
                                            <div class="form-text">Teléfono específico para contacto relacionado a este cliente.</div>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Ej: contraparte@cliente.com"
                                                value="<?php
                                                        if ($data['action'] === 'edit' && $data['counterpartie']) {
                                                            echo htmlspecialchars($data['counterpartie']['email'] ?? '');
                                                        } elseif (isset($_POST['email'])) {
                                                            echo htmlspecialchars($_POST['email']);
                                                        }
                                                        ?>" maxlength="150">
                                            <div class="form-text">Email específico para contacto relacionado a este cliente.</div>
                                        </div>

                                        <!-- Botones -->
                                        <div class="col-12">
                                            <hr>
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="/client-counterparties" class="btn btn-secondary">
                                                    <i class="bi bi-x-lg"></i> Cancelar
                                                </a>
                                                <button type="submit" class="btn btn-setap-primary">
                                                    <i class="bi bi-<?php echo $data['action'] === 'edit' ? 'pencil' : 'plus-circle'; ?>"></i>
                                                    <?php echo $data['action'] === 'edit' ? 'Actualizar' : 'Crear'; ?> Contraparte
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Información adicional si estamos editando -->
                        <?php if ($data['action'] === 'edit' && $data['counterpartie']): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="bi bi-info-circle"></i> Información de la Contraparte
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <dl class="row">
                                                <dt class="col-sm-4">ID:</dt>
                                                <dd class="col-sm-8"><?php echo $data['counterpartie']['id']; ?></dd>

                                                <dt class="col-sm-4">Fecha Creación:</dt>
                                                <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($data['counterpartie']['fecha_Creado'])); ?></dd>

                                                <dt class="col-sm-4">Última Modificación:</dt>
                                                <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($data['counterpartie']['fecha_modificacion'])); ?></dd>
                                            </dl>
                                        </div>
                                        <div class="col-md-6">
                                            <dl class="row">
                                                <dt class="col-sm-4">Persona:</dt>
                                                <dd class="col-sm-8">
                                                    <?php echo htmlspecialchars($data['counterpartie']['persona_nombre']); ?>
                                                    <br><small class="text-muted">RUT: <?php echo htmlspecialchars($data['counterpartie']['persona_rut']); ?></small>
                                                </dd>

                                                <dt class="col-sm-4">Cliente:</dt>
                                                <dd class="col-sm-8"><?php echo htmlspecialchars($data['counterpartie']['cliente_nombre']); ?></dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
</body>

</html>