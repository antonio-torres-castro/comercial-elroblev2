<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .navbar-brand {
            font-weight: bold;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
            position: relative;
            overflow-y: auto;
        }

        /* Asegurar que el contenido principal no se superponga */
        .main-content {
            padding-left: 15px;
            padding-right: 15px;
        }

        /* Para pantallas grandes asegurar posición correcta */
        @media (min-width: 768px) {
            .sidebar {
                position: sticky;
                top: 56px;
                height: calc(100vh - 56px);
                z-index: 1;
            }
            
            .main-content {
                margin-left: 0;
            }
        }

        /* Responsividad para el sidebar en móviles */
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                top: 56px;
                left: -100%;
                width: 280px;
                height: calc(100vh - 56px);
                z-index: 1000;
                transition: left 0.3s ease;
                overflow-y: auto;
            }
            
            .sidebar.show {
                left: 0;
                box-shadow: 0 0 0 100vmax rgba(0,0,0,.5);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }

        .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
        }

        .nav-link:hover {
            background-color: #e9ecef;
            color: #007bff;
        }

        .nav-link.active {
            background-color: #007bff;
            color: white;
        }

        .stats-card {
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <?php

    use App\Helpers\Security; ?>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <i class="bi bi-building"></i> SETAP
            </a>

            <!-- Botón hamburguesa para sidebar -->
            <button class="navbar-toggler d-md-none" type="button" id="sidebarToggle" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Botón hamburguesa para menú de usuario -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($dashboardData['user']['nombre_completo'] ?? $dashboardData['user']['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Configuración</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="/logout"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Overlay para cerrar sidebar en móviles -->
            <div class="sidebar-overlay" id="sidebarOverlay"></div>
            
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar" id="sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/dashboard">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>

                        <?php if (!empty($dashboardData['menus'])): ?>
                            <?php foreach ($dashboardData['menus'] as $menu): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo htmlspecialchars($menu['url']); ?>">
                                        <i class="bi bi-<?php echo htmlspecialchars($menu['icono'] ?? 'circle'); ?>"></i>
                                        <?php echo htmlspecialchars($menu['nombre']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Menús por defecto si no hay configuración dinámica -->
                            <?php
                            if (Security::hasMenuAccess('users')): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/users">
                                        <i class="bi bi-people"></i> Usuarios
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (Security::hasMenuAccess('projects')): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/projects">
                                        <i class="bi bi-briefcase"></i> Proyectos
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (Security::hasMenuAccess('reports')): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/reports">
                                        <i class="bi bi-bar-chart"></i> Reportes
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-calendar"></i>
                            <?php echo date('d/m/Y H:i'); ?>
                        </button>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <?php if (Security::hasPermission('Read')): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-primary mb-2">
                                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                                    </div>
                                    <h3 class="mb-0"><?php echo $dashboardData['stats']['total_usuarios']; ?></h3>
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
                                    <h3 class="mb-0"><?php echo $dashboardData['stats']['total_proyectos']; ?></h3>
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
                                    <h3 class="mb-0"><?php echo $dashboardData['stats']['proyectos_activos']; ?></h3>
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
                                    <h3 class="mb-0"><?php echo $dashboardData['stats']['tareas_pendientes']; ?></h3>
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
                                    <i class="bi bi-house-door text-primary"></i>
                                    Bienvenido, <?php echo htmlspecialchars($dashboardData['user']['nombre_completo'] ?? $dashboardData['user']['username']); ?>
                                </h5>
                                <p class="card-text">
                                    Has iniciado sesión como <strong><?php echo htmlspecialchars($dashboardData['user']['rol']); ?></strong>.
                                    Desde aquí puedes acceder a todas las funcionalidades del sistema según tus permisos.
                                </p>

                                <div class="row mt-3">
                                    <?php if (Security::hasMenuAccess('users')): ?>
                                        <div class="col-md-6 mb-2">
                                            <a href="/users" class="btn btn-outline-primary w-100">
                                                <i class="bi bi-people"></i> Gestionar Usuarios
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (Security::hasMenuAccess('projects')): ?>
                                        <div class="col-md-6 mb-2">
                                            <a href="/projects" class="btn btn-outline-success w-100">
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
                                    <li><strong>Usuario:</strong> <?php echo htmlspecialchars($dashboardData['user']['username']); ?></li>
                                    <li><strong>Email:</strong> <?php echo htmlspecialchars($dashboardData['user']['email']); ?></li>
                                    <li><strong>Rol:</strong> <?php echo htmlspecialchars($dashboardData['user']['rol']); ?></li>
                                    <li><strong>Último acceso:</strong> <?php echo date('d/m/Y H:i'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

            // Funcionalidad del sidebar móvil
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            if (sidebarToggle && sidebar && sidebarOverlay) {
                // Abrir sidebar
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.add('show');
                    sidebarOverlay.classList.add('show');
                    document.body.style.overflow = 'hidden';
                });

                // Cerrar sidebar al hacer clic en el overlay
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                });

                // Cerrar sidebar con la tecla Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                });

                // Cerrar sidebar al hacer clic en un enlace del menú (móviles)
                const sidebarLinks = sidebar.querySelectorAll('a.nav-link');
                sidebarLinks.forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 768) {
                            sidebar.classList.remove('show');
                            sidebarOverlay.classList.remove('show');
                            document.body.style.overflow = '';
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>