<?php

use App\Helpers\Security;
use App\Constants\AppConstants;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permisos de Usuario - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
    <style>
        .user-header {
            background: linear-gradient(45deg, var(--setap-primary), var(--setap-primary-dark));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .user-avatar-large {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .permission-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .permission-card:hover {
            transform: translateY(-2px);
        }

        .permission-badge {
            background: var(--setap-success);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            margin: 0.2rem;
            display: inline-block;
        }

        .menu-badge {
            background: var(--setap-info);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            margin: 0.2rem;
            display: inline-block;
        }

        .empty-state {
            text-align: center;
            color: var(--setap-muted);
            padding: 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main class="main-content">
            <!-- Header de usuario -->
            <div class="user-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar-large">
                            <?= strtoupper(substr($user['nombre_usuario'], 0, 2)) ?>
                        </div>
                    </div>
                    <div class="col">
                        <h4 class="mb-1"><?= htmlspecialchars($user['nombre_usuario']) ?></h4>
                        <p class="mb-0"><?= htmlspecialchars($user['nombre_completo'] ?? '') ?></p>
                        <small class="opacity-75">Tipo: <?= htmlspecialchars($user['rol'] ?? 'N/A') ?></small>
                    </div>
                    <div class="col-auto">
                        <a href="<?= AppConstants::ROUTE_USERS ?>" class="btn btn-light">
                            <i class="bi bi-arrow-left"></i> Volver a Usuarios
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de permisos y menús -->
            <div class="row">
                <!-- Permisos del usuario -->
                <div class="col-md-6 mb-4">
                    <div class="card permission-card h-100">
                        <div class="card-header bg-transparent border-bottom-0 pb-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-check text-success me-2" style="font-size: 1.5rem;"></i>
                                <h5 class="mb-0">Permisos Asignados</h5>
                            </div>
                            <p class="text-muted small mb-0">Permisos que tiene el usuario a nivel sistémico</p>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($userPermissions)): ?>
                                <div class="mb-3">
                                    <small class="text-muted">Total: <?= count($userPermissions) ?> permisos</small>
                                </div>
                                <?php foreach ($userPermissions as $permission): ?>
                                    <div class="permission-badge"
                                        title="<?= htmlspecialchars($permission['descripcion']) ?>
Asignado: <?= date('d/m/Y', strtotime($permission['fecha_creacion'])) ?>">
                                        <i class="bi bi-check-circle me-1"></i>
                                        <?= htmlspecialchars($permission['nombre']) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-shield-x"></i>
                                    <p>No hay permisos asignados</p>
                                    <small class="text-muted">Este usuario no tiene permisos específicos asignados a su tipo de usuario.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Menús del usuario -->
                <div class="col-md-6 mb-4">
                    <div class="card permission-card h-100">
                        <div class="card-header bg-transparent border-bottom-0 pb-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-list-ul text-info me-2" style="font-size: 1.5rem;"></i>
                                <h5 class="mb-0">Acceso a Menús</h5>
                            </div>
                            <p class="text-muted small mb-0">Menús a los que puede acceder el usuario</p>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($userMenus)): ?>
                                <div class="mb-3">
                                    <small class="text-muted">Total: <?= count($userMenus) ?> menús</small>
                                </div>
                                <?php foreach ($userMenus as $menu): ?>
                                    <div class="menu-badge"
                                        title="<?= htmlspecialchars($menu['descripcion']) ?>
Asignado: <?= date('d/m/Y', strtotime($menu['fecha_creacion'])) ?>">
                                        <i class="bi bi-menu-button-wide me-1"></i>
                                        <?= htmlspecialchars($menu['nombre']) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-menu-button"></i>
                                    <p>No hay menús asignados</p>
                                    <small class="text-muted">Este usuario no tiene acceso a menús específicos.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="row">
                <div class="col-12">
                    <div class="card permission-card">
                        <div class="card-header bg-transparent border-bottom-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle text-warning me-2" style="font-size: 1.5rem;"></i>
                                <h5 class="mb-0">Información del Sistema de Permisos</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6 class="text-primary">Permisos por Rol</h6>
                                    <p class="small text-muted">
                                        Los permisos se asignan a nivel del tipo de usuario. Todos los usuarios
                                        con el mismo tipo comparten los mismos permisos básicos.
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-info">Acceso a Menús</h6>
                                    <p class="small text-muted">
                                        El acceso a menús se controla mediante la relación entre el tipo de usuario
                                        y los menús del sistema.
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-success">Estado Activo</h6>
                                    <p class="small text-muted">
                                        Solo se muestran permisos y menús que están actualmente activos
                                        (estado_tipo_id = 1).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>

    <script>
        // Mejorar experiencia con tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips si están disponibles
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>