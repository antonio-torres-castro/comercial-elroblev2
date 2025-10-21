<?php

use App\Helpers\Security;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenedor de Permisos - SETAP</title>
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
        
        .permission-checkbox {
            margin: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.2s;
        }
        
        .permission-checkbox:hover {
            background: #e9ecef;
        }
        
        .permission-checkbox.checked {
            background: var(--setap-success);
            color: white;
            border-color: var(--setap-success);
        }
        
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 0.5rem;
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
                        <i class="bi bi-shield-lock text-success"></i> Mantenedor de Permisos
                    </h2>
                    <p class="text-muted">Administra los permisos sistémicos de cada tipo de usuario</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group" role="group">
                        <a href="/users" class="btn btn-outline-secondary">
                            <i class="bi bi-people"></i> Gestión de Usuarios
                        </a>
                        <a href="/accesos" class="btn btn-outline-info">
                            <i class="bi bi-menu-button"></i> Gestión de Accesos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tipos de Usuario -->
            <div class="row">
                <?php foreach ($userTypes as $userType): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card user-type-card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-person-badge"></i> <?= htmlspecialchars($userType['nombre']) ?>
                            </h5>
                            <small><?= htmlspecialchars($userType['descripcion']) ?></small>
                        </div>
                        <div class="card-body">
                            <form class="permissions-form" action="/permissions/update" method="POST" data-user-type-id="<?= $userType['id'] ?>">
                                <!-- Token CSRF para seguridad -->
                                <?= Security::renderCsrfField() ?>
                                <!-- ID del tipo de usuario -->
                                <input type="hidden" name="user_type_id" value="<?= $userType['id'] ?>">
                                <div class="permission-grid">
                                    <?php foreach ($allPermissions as $permission): ?>
                                    <?php 
                                    $hasPermission = isset($permissionsByUserType[$userType['id']]) && 
                                                   in_array($permission['id'], $permissionsByUserType[$userType['id']]);
                                    ?>
                                    <div class="form-check permission-checkbox <?= $hasPermission ? 'checked' : '' ?>">
                                        <input class="form-check-input" type="checkbox" 
                                               name="permission_ids[]" value="<?= $permission['id'] ?>"
                                               id="permission_<?= $userType['id'] ?>_<?= $permission['id'] ?>"
                                               <?= $hasPermission ? 'checked' : '' ?>>
                                        <label class="form-check-label" 
                                               for="permission_<?= $userType['id'] ?>_<?= $permission['id'] ?>">
                                            <strong><?= htmlspecialchars($permission['nombre']) ?></strong>
                                            <?php if ($permission['descripcion']): ?>
                                                <br><small class="opacity-75"><?= htmlspecialchars($permission['descripcion']) ?></small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3 save-btn">
                                    <button type="submit" class="btn btn-setap-primary w-100">
                                        <i class="bi bi-shield-check"></i> Guardar Permisos
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Resumen de permisos disponibles -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-list-check text-primary"></i> Permisos Disponibles en el Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($allPermissions as $permission): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-shield-check text-success me-2 mt-1"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($permission['nombre']) ?></strong>
                                            <?php if ($permission['descripcion']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($permission['descripcion']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
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
                                    <h6 class="text-primary">Gestión de Permisos</h6>
                                    <p class="small text-muted">
                                        Configure qué permisos sistémicos tiene cada tipo de usuario. 
                                        Los permisos controlan las acciones que pueden realizar en el sistema.
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-success">Aplicación Inmediata</h6>
                                    <p class="small text-muted">
                                        Los cambios en permisos se aplican inmediatamente a todos los usuarios 
                                        que pertenecen al tipo de usuario modificado.
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-warning">Precaución</h6>
                                    <p class="small text-muted">
                                        Tenga cuidado al modificar permisos de tipos de usuario críticos. 
                                        Podría afectar el acceso de usuarios importantes del sistema.
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
            document.querySelectorAll('.permission-checkbox input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const container = this.closest('.permission-checkbox');
                    if (this.checked) {
                        container.classList.add('checked');
                    } else {
                        container.classList.remove('checked');
                    }
                });
            });

            // Manejar envío de formularios
            document.querySelectorAll('.permissions-form').forEach(form => {
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
                    
                    fetch('/permisos/update', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mostrar éxito
                            btn.innerHTML = '<i class="bi bi-shield-check"></i> ¡Guardado!';
                            btn.classList.remove('btn-setap-primary');
                            btn.classList.add('btn-success');
                            
                            // Mostrar notificación usando sistema estándar
                            showAlert('Permisos actualizados correctamente', 'success');
                            
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
                        showAlert('Error al guardar los permisos: ' + error.message, 'danger');
                        
                        // Restaurar botón
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
                });
            });
        });

        // Sistema estándar de alertas SETAP ya cargado
        // La función showAlert está disponible globalmente desde alert-system.js
    </script>
</body>

</html>