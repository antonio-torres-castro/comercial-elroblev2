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
            <a href="<?= AppConstants::ROUTE_GRUPO_TIPOS ?>/create" class="btn btn-primary">
                <i class="bi bi-plus"></i> Nuevo
            </a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= (int)$item['id'] ?></td>
                                <td><?= htmlspecialchars($item['nombre']) ?></td>
                                <td><?= htmlspecialchars($item['descripcion'] ?? '') ?></td>
                                <td class="text-end">
                                    <a href="<?= AppConstants::ROUTE_GRUPO_TIPOS ?>/edit?id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>

</body>

</html>