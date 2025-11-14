<?php

use App\Constants\AppConstants;
use App\Helpers\Security;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo Tipos - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Grupo Tipos</h3>
            <a href="<?= AppConstants::ROUTE_GRUPO_TIPOS ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK ?>
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= htmlspecialchars($action) ?>">
                    <?= Security::renderCsrfField() ?>
                    <?php if (!empty($item)): ?>
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($item['nombre'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="text-end">
                        <a href="<?= AppConstants::ROUTE_GRUPO_TIPOS ?>" class="btn btn-secondary">
                            <i class="bi bi-x-lg"></i> <?= AppConstants::UI_BTN_CANCEL ?>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

</body>

</html>