<?php use App\Helpers\Security; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lectura de Cumplimiento - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
    <style>
        .document-body { max-width: 980px; margin: 0 auto; }
        .document-body img { max-width: 100%; height: auto; }
    </style>
</head>

<body class="bg-light">
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main>
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h2 class="mb-0"><i class="bi bi-book"></i> <?= htmlspecialchars($version['titulo']) ?></h2>
                    <div class="text-muted small">Version <?= htmlspecialchars($version['version']) ?> · vigencia <?= htmlspecialchars($version['fecha_inicio_vigencia'] ?? '-') ?> a <?= htmlspecialchars($version['fecha_fin_vigencia'] ?? '-') ?></div>
                </div>
                <a href="/setap/compliance/my" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>

            <?php if (!empty($data['error'])): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($data['error']) ?></div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-body document-body">
                    <?= $version['contenido_html'] ?>
                </div>
            </div>

            <?php if (empty($reading['password_confirmado'])): ?>
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="/setap/compliance/accept" class="row g-3 align-items-end">
                            <?= Security::renderCsrfField() ?>
                            <input type="hidden" name="reading_id" value="<?= (int)$reading['id'] ?>">
                            <input type="hidden" name="version_id" value="<?= (int)$version['id'] ?>">
                            <div class="col-md-8">
                                <label class="form-label" for="password">Confirmar lectura con contrasena</label>
                                <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" class="btn btn-setap-primary">
                                    <i class="bi bi-check2-square"></i> Confirmar lectura
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success"><i class="bi bi-check-circle"></i> Lectura aceptada.</div>
                <?php if (!empty($version['requiere_evaluacion'])): ?>
                    <a class="btn btn-setap-primary" href="/setap/compliance/evaluation/<?= (int)$reading['id'] ?>"><i class="bi bi-ui-checks"></i> Ir a evaluacion</a>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
</body>

</html>
