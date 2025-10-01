<?php use App\Helpers\Security; ?>

<!-- Sidebar -->
<nav class="col-md-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
            <a href="/dashboard" class="text-decoration-none text-dark">
                <i class="bi bi-grid-3x3-gap"></i>
                <strong>SETAP</strong>
            </a>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : ''; ?>" 
                   href="/dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'active' : ''; ?>" 
                   href="/users">
                    <i class="bi bi-person-gear"></i> Usuarios
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/personas') !== false ? 'active' : ''; ?>" 
                   href="/personas">
                    <i class="bi bi-people"></i> Personas
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/client') !== false ? 'active' : ''; ?>" 
                   href="/clients">
                    <i class="bi bi-building"></i> Clientes
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/projects') !== false ? 'active' : ''; ?>" 
                   href="/projects">
                    <i class="bi bi-kanban"></i> Proyectos
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/tasks') !== false ? 'active' : ''; ?>" 
                   href="/tasks">
                    <i class="bi bi-list-task"></i> Tareas
                </a>
            </li>
            
            <!-- Separador -->
            <li class="nav-item">
                <hr class="my-2">
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/client-counterparties') !== false ? 'active' : ''; ?>" 
                   href="/client-counterparties">
                    <i class="bi bi-person-badge"></i> Contrapartes
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/perfil') !== false ? 'active' : ''; ?>" 
                   href="/perfil">
                    <i class="bi bi-person-circle"></i> Mi Perfil
                </a>
            </li>
            
            <!-- Separador -->
            <li class="nav-item">
                <hr class="my-2">
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-danger" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
        
        <!-- Información del usuario -->
        <div class="mt-auto pt-3 border-top">
            <small class="text-muted d-block px-3">
                Usuario: <strong><?php echo htmlspecialchars($data['user']['username'] ?? 'N/A'); ?></strong>
            </small>
            <small class="text-muted d-block px-3">
                Rol: <?php echo htmlspecialchars($data['user']['rol'] ?? 'N/A'); ?>
            </small>
        </div>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    height: 100vh;
    overflow-y: auto;
}

.sidebar .nav-link {
    color: #333;
    border-radius: 0.25rem;
    margin: 0.125rem 0.5rem;
}

.sidebar .nav-link:hover {
    background-color: #f8f9fa;
    color: #007bff;
}

.sidebar .nav-link.active {
    background-color: #007bff;
    color: white;
}

.sidebar .nav-link.active:hover {
    background-color: #0056b3;
    color: white;
}

@media (max-width: 767.98px) {
    .sidebar {
        position: static;
        height: auto;
    }
}
</style>