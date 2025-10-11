<?php

use App\Helpers\Security;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenedor de Accesos - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .user-type-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            transition: transform 0.2s;
        }
        
        .user-type-card:hover {
            transform: translateY(-2px);
        }
        
        .menu-checkbox {
            margin: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.2s;
        }
        
        .menu-checkbox:hover {
            background: #e9ecef;
        }
        
        .menu-checkbox.checked {
            background: var(--setap-primary);
            color: white;
            border-color: var(--setap-primary);
        }
        
        .menu-group {
            background: var(--setap-light);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .menu-group-title {
            color: var(--setap-primary);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .save-btn {
            position: sticky;
            bottom: 20px;
            z-index: 1000;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main class="main-content">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2>
                        <i class="bi bi-menu-button text-info"></i> Mantenedor de Accesos
                    </h2>
                    <p class="text-muted">Administra los menús que puede acceder cada tipo de usuario</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group" role="group">
                        <a href="<?= \App\Constants\AppConstants::ROUTE_USERS ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-people"></i> Gestión de Usuarios
                        </a>
                        <a href="/permisos" class="btn btn-outline-primary">
                            <i class="bi bi-shield-lock"></i> Gestión de Permisos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tipos de Usuario -->
            <div class="row">
                <?php foreach ($userTypes as $userType): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card user-type-card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-person-badge"></i> <?= htmlspecialchars($userType['nombre']) ?>
                            </h5>
                            <small><?= htmlspecialchars($userType['descripcion']) ?></small>
                        </div>
                        <div class="card-body">
                            <form class="access-form" data-user-type-id="<?= $userType['id'] ?>">
                                <!-- Agrupar menús por grupo -->
                                <?php 
                                $groupedMenus = [];
                                foreach ($allMenus as $menu) {
                                    $grupo = $menu['grupo_nombre'] ?: 'Sin Grupo';
                                    $groupedMenus[$grupo][] = $menu;
                                }
                                ?>
                                
                                <?php foreach ($groupedMenus as $grupoNombre => $menus): ?>
                                <div class="menu-group">
                                    <div class="menu-group-title">
                                        <i class="bi bi-folder"></i> <?= htmlspecialchars($grupoNombre) ?>
                                    </div>
                                    
                                    <?php foreach ($menus as $menu): ?>
                                    <?php 
                                    $hasAccess = isset($accessByUserType[$userType['id']]) && 
                                                in_array($menu['id'], $accessByUserType[$userType['id']]);
                                    ?>
                                    <div class="form-check menu-checkbox <?= $hasAccess ? 'checked' : '' ?>">
                                        <input class="form-check-input" type="checkbox" 
                                               name="menu_ids[]" value="<?= $menu['id'] ?>"
                                               id="menu_<?= $userType['id'] ?>_<?= $menu['id'] ?>"
                                               <?= $hasAccess ? 'checked' : '' ?>>
                                        <label class="form-check-label" 
                                               for="menu_<?= $userType['id'] ?>_<?= $menu['id'] ?>">
                                            <i class="bi bi-<?= $menu['icono'] ?: 'circle' ?>"></i>
                                            <?= htmlspecialchars($menu['display'] ?: $menu['nombre']) ?>
                                            <?php if ($menu['descripcion']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($menu['descripcion']) ?></small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="mt-3 save-btn">
                                    <button type="submit" class="btn btn-setap-primary w-100">
                                        <i class="bi bi-check-circle"></i> Guardar Accesos
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Información adicional -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle text-primary"></i> Información del Mantenedor
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6 class="text-primary">Gestión de Accesos</h6>
                                    <p class="small text-muted">
                                        Configure qué menús puede acceder cada tipo de usuario. 
                                        Los cambios se aplican inmediatamente a todos los usuarios del tipo.
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-info">Tipos de Usuario</h6>
                                    <p class="small text-muted">
                                        Cada tarjeta representa un tipo de usuario diferente. 
                                        Seleccione los menús que desea habilitar para cada tipo.
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-success">Estado Activo</h6>
                                    <p class="small text-muted">
                                        Solo se muestran menús y tipos de usuario activos. 
                                        Los cambios se guardan automáticamente al hacer clic en "Guardar Accesos".
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
        document.addEventListener('DOMContentLoaded', function() {
            // Manejar cambios en checkboxes para actualizar estilos
            document.querySelectorAll('.menu-checkbox input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const container = this.closest('.menu-checkbox');
                    if (this.checked) {
                        container.classList.add('checked');
                    } else {
                        container.classList.remove('checked');
                    }
                });
            });

            // Manejar envío de formularios
            document.querySelectorAll('.access-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const userTypeId = this.dataset.userTypeId;
                    const formData = new FormData(this);
                    formData.append('user_type_id', userTypeId);
                    
                    // Mostrar loading en el botón
                    const btn = this.querySelector('button[type="submit"]');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';
                    btn.disabled = true;
                    
                    fetch('/accesos/update', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mostrar éxito
                            btn.innerHTML = '<i class="bi bi-check-circle"></i> ¡Guardado!';
                            btn.classList.remove('btn-setap-primary');
                            btn.classList.add('btn-success');
                            
                            // Mostrar notificación
                            showNotification('Accesos actualizados correctamente', 'success');
                            
                            // Restaurar botón después de 2 segundos
                            setTimeout(() => {
                                btn.innerHTML = originalText;
                                btn.classList.remove('btn-success');
                                btn.classList.add('btn-setap-primary');
                                btn.disabled = false;
                            }, 2000);
                        } else {
                            throw new Error(data.message || 'Error al guardar');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error al guardar los accesos: ' + error.message, 'error');
                        
                        // Restaurar botón
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
                });
            });
        });

        function showNotification(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="bi ${icon}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</body>

</html>