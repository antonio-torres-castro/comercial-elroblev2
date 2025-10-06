<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <!-- Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .navbar-brand {
            font-weight: bold;
        }

        .stats-card {
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .main-content {
            margin-top: 2rem;
        }
    </style>
</head>

<body class="bg-light">
    <?php use App\Helpers\Security; ?>

    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/layouts/navigation.php'; ?>

    <!-- Contenido Principal -->
    <div class="container-fluid">
        <main class="main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-calendar"></i>
                            <?php echo date('d/m/Y H:i'); ?>
                        </button>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <?php if (\App\Helpers\Security::hasPermission('Read')): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-setap-primary mb-2">
                                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo $homeData['stats']['total_usuarios']; ?></h3>
                                    <p class="text-muted mb-0">Total Usuarios</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-success mb-2">
                                        <i class="bi bi-briefcase" style="font-size: 2rem;"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo $homeData['stats']['total_proyectos']; ?></h3>
                                    <p class="text-muted mb-0">Total Proyectos</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-warning mb-2">
                                        <i class="bi bi-play-circle" style="font-size: 2rem;"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo $homeData['stats']['proyectos_activos']; ?></h3>
                                    <p class="text-muted mb-0">Proyectos Activos</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-danger mb-2">
                                        <i class="bi bi-clock" style="font-size: 2rem;"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo $homeData['stats']['tareas_pendientes']; ?></h3>
                                    <p class="text-muted mb-0">Tareas Pendientes</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Bienvenida -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-house-door text-setap-primary"></i>
                                    Bienvenido, <?php echo htmlspecialchars($homeData['user']['nombre_completo'] ?? $homeData['user']['username']); ?>
                                </h5>
                                <p class="card-text">
                                    Has iniciado sesión como <strong><?php echo htmlspecialchars($homeData['user']['rol']); ?></strong>.
                                    Desde aquí puedes acceder a todas las funcionalidades del sistema según tus permisos.
                                </p>

                                <div class="row mt-3">
                                    <?php if (\App\Helpers\Security::hasMenuAccess('users')): ?>
                                        <div class="col-md-6 mb-2">
                                            <a href="/users" class="btn btn-outline-setap-primary w-100">
                                                <i class="bi bi-people"></i> Gestionar Usuarios
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (\App\Helpers\Security::hasMenuAccess('projects')): ?>
                                        <div class="col-md-6 mb-2">
                                            <a href="/projects" class="btn btn-outline-setap-primary w-100">
                                                <i class="bi bi-briefcase"></i> Ver Proyectos
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi bi-info-circle text-info"></i>
                                    Información de Sesión
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Usuario:</strong> <?php echo htmlspecialchars($homeData['user']['username']); ?></li>
                                    <li><strong>Email:</strong> <?php echo htmlspecialchars($homeData['user']['email']); ?></li>
                                    <li><strong>Rol:</strong> <?php echo htmlspecialchars($homeData['user']['rol']); ?></li>
                                    <li><strong>Último acceso:</strong> <?php echo date('d/m/Y H:i'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
        </main>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/layouts/scripts-base.php"; ?>
    <script>
        // Funcionalidad básica
        document.addEventListener('DOMContentLoaded', function() {
            // Confirmar logout
            const logoutLink = document.querySelector('a[href="/logout"]');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    if (!confirm('¿Está seguro que desea cerrar sesión?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>

</html>