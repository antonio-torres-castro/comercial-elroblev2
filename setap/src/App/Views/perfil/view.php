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
    <link rel="stylesheet" href="/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <main class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h1><?php echo $data['title']; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= AppConstants::ROUTE_PERFIL ?>/edit" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Editar Perfil
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i>
                        <?php echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="bi bi-person-circle"></i> Información Personal</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Nombre Completo:</strong></td>
                                <td><?php echo htmlspecialchars($data['user']['nombre_completo'] ?? 'No especificado'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Usuario:</strong></td>
                                <td><?php echo htmlspecialchars($data['user']['nombre_usuario'] ?? 'No especificado'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($data['user']['email'] ?? 'No especificado'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>RUT:</strong></td>
                                <td><?php echo htmlspecialchars($data['user']['rut'] ?? 'No especificado'); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h5><i class="bi bi-geo-alt"></i> Información de Contacto</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Teléfono:</strong></td>
                                <td><?php echo htmlspecialchars($data['user']['telefono'] ?? 'No especificado'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Dirección:</strong></td>
                                <td><?php echo htmlspecialchars($data['user']['direccion'] ?? 'No especificado'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Rol:</strong></td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($data['user']['rol'] ?? 'Sin rol'); ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Estado:</strong></td>
                                <td><span class="badge bg-success"><?php echo htmlspecialchars($data['user']['estado'] ?? 'Activo'); ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
</body>

</html>