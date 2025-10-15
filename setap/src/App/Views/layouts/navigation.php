<?php

use App\Helpers\Security;
use App\Models\Menu;
use App\Constants\AppConstants;

// Obtener menús agrupados del usuario actual
$groupedMenus = [];
$ungroupedMenus = [];
try {
    if (\App\Helpers\Security::isAuthenticated()) {
        $menuModel = new Menu();
        $userId = $_SESSION['user_id'] ?? 0;

        // Obtener menús agrupados (con desplegables)
        $groupedMenus = $menuModel->getGroupedMenusForUser($userId);

        // Obtener menús sin grupo (individuales)
        $ungroupedMenus = $menuModel->getUngroupedMenusForUser($userId);
    }
} catch (Exception $e) {
    error_log("Error obteniendo menús de navegación: " . $e->getMessage());
    $groupedMenus = [];
    $ungroupedMenus = [];
}
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-setap">
    <div class="container">
        <a class="navbar-brand" href="<?= AppConstants::ROUTE_HOME ?>">
            <i class="bi bi-building"></i> SETAP - Comercial El Roble
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Home siempre visible -->
                <li class="nav-item">
                    <a class="nav-link text-light" href="<?= AppConstants::ROUTE_HOME ?>">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>

                <!-- Menús agrupados con desplegables -->
                <?php if (!empty($groupedMenus)): ?>
                    <?php foreach ($groupedMenus as $groupData): ?>
                        <?php $group = $groupData['group']; ?>
                        <?php $menus = $groupData['menus']; ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-light" href="#" id="navbarDropdown_<?php echo $group['id']; ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-<?php echo htmlspecialchars($group['icono'] ?? 'folder'); ?>"></i>
                                <?php echo htmlspecialchars($group['display']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown_<?php echo $group['id']; ?>">
                                <?php foreach ($menus as $menu): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo htmlspecialchars($menu['url']); ?>">
                                            <i class="bi bi-<?php echo htmlspecialchars($menu['icono'] ?? 'circle'); ?>"></i>
                                            <?php echo htmlspecialchars($menu['display']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Menús individuales (sin grupo) -->
                <?php if (!empty($ungroupedMenus)): ?>
                    <?php foreach ($ungroupedMenus as $menu): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo htmlspecialchars($menu['url']); ?>">
                                <i class="bi bi-<?php echo htmlspecialchars($menu['icono'] ?? 'circle'); ?>"></i>
                                <?php echo htmlspecialchars($menu['display']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Menús por defecto si no hay configuración dinámica -->
                <?php if (empty($groupedMenus) && empty($ungroupedMenus)): ?>
                    <?php if (\App\Helpers\Security::hasMenuAccess('manage_users')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?= AppConstants::ROUTE_USERS ?>">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (\App\Helpers\Security::hasMenuAccess('manage_clients')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?= AppConstants::ROUTE_CLIENTS ?>">
                                <i class="bi bi-building"></i> Clientes
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (\App\Helpers\Security::hasMenuAccess('manage_projects')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?= AppConstants::ROUTE_PROJECTS ?>">
                                <i class="bi bi-folder"></i> Proyectos
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (\App\Helpers\Security::hasMenuAccess('manage_tasks')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?= AppConstants::ROUTE_TASKS ?>">
                                <i class="bi bi-list-task"></i> Tareas
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (\App\Helpers\Security::hasMenuAccess('manage_menus')): ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?= AppConstants::ROUTE_MENUS ?>">
                                <i class="bi bi-list-ul"></i> Menús
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Dropdown de usuario -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Usuario'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="<?= AppConstants::ROUTE_PERFIL ?>">
                                <i class="bi bi-person"></i> Mi Perfil
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= AppConstants::ROUTE_LOGOUT ?>">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Script para inicializar dropdowns -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Asegurar que Bootstrap esté disponible antes de inicializar
        if (typeof bootstrap !== 'undefined') {
            // Inicializar todos los dropdowns manualmente
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });

            console.log('Dropdowns de navegación inicializados:', dropdownList.length);
        } else {
            console.error('Bootstrap no está disponible para inicializar dropdowns');
        }
    });
</script>