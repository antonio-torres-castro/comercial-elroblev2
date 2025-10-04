<?php 
use App\Helpers\Security; 
use App\Models\Menu;

// Obtener menús dinámicos del usuario actual
$navigationMenus = [];
try {
    if (Security::isAuthenticated()) {
        $menuModel = new Menu();
        $userId = $_SESSION['user_id'] ?? 0;
        $navigationMenus = $menuModel->getMenusForUser($userId);
    }
} catch (Exception $e) {
    error_log("Error obteniendo menús de navegación: " . $e->getMessage());
    $navigationMenus = [];
}
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-setap">
    <div class="container">
        <a class="navbar-brand" href="/home">
            <i class="bi bi-grid-3x3-gap"></i> SETAP
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Home siempre visible -->
                <li class="nav-item">
                    <a class="nav-link text-light" href="/home">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                
                <!-- Menús dinámicos desde base de datos -->
                <?php if (!empty($navigationMenus)): ?>
                    <?php foreach ($navigationMenus as $menu): ?>
                        <?php if (!empty($menu['url']) && $menu['url'] !== '/home'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-light" href="<?php echo htmlspecialchars($menu['url']); ?>">
                                    <i class="bi bi-<?php echo htmlspecialchars($menu['icono'] ?? 'circle'); ?>"></i>
                                    <?php echo htmlspecialchars($menu['display']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Menús por defecto si no hay configuración dinámica -->
                    <?php if (Security::hasMenuAccess('manage_users')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="/users">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Security::hasMenuAccess('manage_clients')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="/clients">
                                <i class="bi bi-building"></i> Clientes
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Security::hasMenuAccess('manage_projects')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="/projects">
                                <i class="bi bi-folder"></i> Proyectos
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Security::hasMenuAccess('manage_tasks')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="/tasks">
                                <i class="bi bi-list-task"></i> Tareas
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Security::hasMenuAccess('manage_menus')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="/menus">
                                <i class="bi bi-list-ul"></i> Menús
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Dropdown de usuario -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> 
                        <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Usuario'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/perfil">
                                <i class="bi bi-person"></i> Mi Perfil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/logout">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>