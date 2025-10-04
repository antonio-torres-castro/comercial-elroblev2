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
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: var(--setap-bg-light);
            position: sticky;
            top: 0;
            overflow-y: auto;
        }
        
        .nav-link {
            color: var(--setap-text-muted);
            padding: 0.75rem 1rem;
        }
        
        .nav-link:hover {
            background-color: var(--setap-bg-light);
            color: var(--setap-primary);
        }
        
        .nav-link.active {
            background-color: var(--setap-primary);
            color: white;
        }
        
        .badge-status {
            font-size: 0.7rem;
        }
        
        .icon-preview {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php use App\Helpers\Security; ?>
    
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (Security::hasPermission('Create') || Security::hasPermission('All')): ?>
                            <a href="/menu" class="btn btn-sm btn-setap-primary">
                                <i class="bi bi-plus-circle"></i> Nuevo Menú
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Alertas de mensajes -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        $messages = [
                            'created' => 'Menú creado exitosamente',
                            'updated' => 'Menú actualizado exitosamente',
                            'deleted' => 'Menú eliminado exitosamente',
                            'status_changed' => 'Estado del menú actualizado exitosamente'
                        ];
                        echo $messages[$_GET['success']] ?? 'Operación realizada exitosamente';
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo $data['subtitle']; ?></h5>
                                <span class="badge bg-secondary">
                                    Total: <?php echo count($data['menus']); ?> menús
                                </span>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['success_message'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                    <?php unset($_SESSION['success_message']); ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($data['menus'])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead class="table-setap-primary">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Icono</th>
                                                    <th>Nombre interno</th>
                                                    <th>Título visible</th>
                                                    <th>URL</th>
                                                    <th>Orden</th>
                                                    <th>Estado</th>
                                                    <th>Creado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data['menus'] as $menu): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($menu['id']); ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <i class="bi bi-<?php echo htmlspecialchars($menu['icono'] ?? 'circle'); ?> icon-preview text-setap-primary"></i>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($menu['nombre']); ?></strong>
                                                            <?php if (!empty($menu['descripcion'])): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($menu['descripcion'], 0, 50)); ?><?php echo strlen($menu['descripcion']) > 50 ? '...' : ''; ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-setap-primary"><?php echo htmlspecialchars($menu['display'] ?? 'Sin título'); ?></span>
                                                        </td>
                                                        <td>
                                                            <code><?php echo htmlspecialchars($menu['url']); ?></code>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-setap-primary-light"><?php echo htmlspecialchars($menu['orden']); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $estado_id = $menu['estado_tipo_id'];
                                                            $estado_info = [
                                                                1 => ['badge' => 'warning', 'texto' => 'Creado'],
                                                                2 => ['badge' => 'success', 'texto' => 'Activo'], 
                                                                3 => ['badge' => 'secondary', 'texto' => 'Inactivo'],
                                                                4 => ['badge' => 'danger', 'texto' => 'Eliminado'],
                                                                5 => ['badge' => 'setap-primary-light', 'texto' => 'Iniciado'],
                                                                6 => ['badge' => 'setap-primary', 'texto' => 'Terminado'],
                                                                7 => ['badge' => 'danger', 'texto' => 'Rechazado'],
                                                                8 => ['badge' => 'success', 'texto' => 'Aprobado']
                                                            ];
                                                            $estado = $estado_info[$estado_id] ?? ['badge' => 'dark', 'texto' => 'Desconocido'];
                                                            ?>
                                                            <span class="badge bg-<?php echo $estado['badge']; ?> badge-status">
                                                                <?php echo $estado['texto']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php 
                                                                $fecha = new DateTime($menu['fecha_creacion']);
                                                                echo $fecha->format('d/m/Y H:i');
                                                                ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <?php if (Security::hasPermission('Modify') || Security::hasPermission('All')): ?>
                                                                    <a href="/menu/<?php echo $menu['id']; ?>" 
                                                                       class="btn btn-outline-setap-primary btn-sm" 
                                                                       title="Editar">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (Security::hasPermission('Read') || Security::hasPermission('All')): ?>
                                                                    <button type="button" 
                                                                            class="btn btn-outline-setap-primary-light btn-sm" 
                                                                            title="Ver detalles"
                                                                            onclick="showMenuDetails(<?php echo htmlspecialchars(json_encode($menu)); ?>)">
                                                                        <i class="bi bi-eye"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (Security::hasPermission('Modify') || Security::hasPermission('All')): ?>
                                                                    <button type="button" 
                                                                            class="btn btn-outline-<?php echo ($menu['estado_tipo_id'] == 2) ? 'warning' : 'success'; ?> btn-sm" 
                                                                            title="<?php echo ($menu['estado_tipo_id'] == 2) ? 'Desactivar' : 'Activar'; ?>"
                                                                            onclick="toggleMenuStatus(<?php echo $menu['id']; ?>, <?php echo ($menu['estado_tipo_id'] == 2) ? '3' : '2'; ?>)">
                                                                        <i class="bi bi-<?php echo ($menu['estado_tipo_id'] == 2) ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (Security::hasPermission('Delete') || Security::hasPermission('All')): ?>
                                                                    <button type="button" 
                                                                            class="btn btn-outline-danger btn-sm" 
                                                                            title="Eliminar"
                                                                            onclick="deleteMenu(<?php echo $menu['id']; ?>)">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning text-center">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <strong>No hay menús registrados</strong><br>
                                        Haz clic en "Nuevo Menú" para crear el primer registro.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para detalles del menú -->
    <div class="modal fade" id="menuDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Menú</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="menuDetailsContent">
                        <!-- Contenido dinámico -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
    <script>
        function showMenuDetails(menu) {
            const estadoInfo = {
                1: { badge: 'warning', texto: 'Creado' },
                2: { badge: 'success', texto: 'Activo' },
                3: { badge: 'secondary', texto: 'Inactivo' },
                4: { badge: 'danger', texto: 'Eliminado' },
                5: { badge: 'setap-primary-light', texto: 'Iniciado' },
                6: { badge: 'setap-primary', texto: 'Terminado' },
                7: { badge: 'danger', texto: 'Rechazado' },
                8: { badge: 'success', texto: 'Aprobado' }
            };
            
            const estado = estadoInfo[menu.estado_tipo_id] || { badge: 'dark', texto: 'Desconocido' };
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>ID:</strong> ${menu.id}<br>
                        <strong>Nombre interno:</strong> ${menu.nombre}<br>
                        <strong>Título visible:</strong> <span class="badge bg-setap-primary">${menu.display || 'Sin título'}</span><br>
                        <strong>URL:</strong> <code>${menu.url}</code><br>
                        <strong>Orden:</strong> ${menu.orden}
                    </div>
                    <div class="col-md-6">
                        <strong>Icono:</strong> <i class="bi bi-${menu.icono}"></i> ${menu.icono}<br>
                        <strong>Estado:</strong> <span class="badge bg-${estado.badge}">${estado.texto}</span><br>
                        <strong>Creado:</strong> ${new Date(menu.fecha_creacion).toLocaleDateString('es-ES')}<br>
                        <strong>Actualizado:</strong> ${menu.fecha_modificacion ? new Date(menu.fecha_modificacion).toLocaleDateString('es-ES') : 'N/A'}
                    </div>
                </div>
                ${menu.descripcion ? `<div class="row mt-3"><div class="col-12"><strong>Descripción:</strong><br><p class="mb-0">${menu.descripcion}</p></div></div>` : ''}
            `;
            
            document.getElementById('menuDetailsContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('menuDetailsModal')).show();
        }

        function toggleMenuStatus(menuId, newStatus) {
            const statusNames = {
                2: 'activar',
                3: 'desactivar'
            };
            const action = statusNames[newStatus] || 'cambiar estado de';
            
            if (confirm(`¿Está seguro que desea ${action} este menú?`)) {
                // Obtener el token CSRF
                const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
                
                // Crear formulario para envío
                const formData = new FormData();
                formData.append('id', menuId);
                formData.append('status', newStatus);
                formData.append('csrf_token', csrfToken);
                
                fetch('/menus/toggle-status', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar la página para mostrar los cambios
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo cambiar el estado'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al cambiar el estado');
                });
            }
        }

        function deleteMenu(menuId) {
            if (confirm('¿Está seguro que desea eliminar este menú? Esta acción no se puede deshacer.')) {
                // Obtener el token CSRF
                const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
                
                // Crear formulario para envío
                const formData = new FormData();
                formData.append('id', menuId);
                formData.append('csrf_token', csrfToken);
                
                fetch('/menus/delete', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar la página para mostrar los cambios
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo eliminar el menú'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al eliminar el menú');
                });
            }
        }

        // Tooltip para botones
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>