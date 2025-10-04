<?php

use App\Helpers\Security;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - SETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/setap-theme.css">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, var(--setap-primary), var(--setap-primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-avatar-large {
            background: linear-gradient(45deg, var(--setap-primary), var(--setap-primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .role-badge {
            font-size: 0.75rem;
            padding: 0.25em 0.5em;
        }

        .table-actions {
            white-space: nowrap;
        }

        .search-box {
            max-width: 300px;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-setap">
        <div class="container">
            <a class="navbar-brand" href="/home">
                <i class="bi bi-grid-3x3-gap"></i> SETAP
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-light" href="/home">
                    <i class="bi bi-house"></i> Home
                </a>
                <a class="nav-link text-light active" href="/users">
                    <i class="bi bi-people"></i> Usuarios
                </a>
                <a class="nav-link text-light" href="/logout">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/home">Home</a></li>
                <li class="breadcrumb-item active">Usuarios</li>
            </ol>
        </nav>

        <!-- Header y Filtros -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>
                    <i class="bi bi-people"></i> Gestión de Usuarios
                    <span class="badge bg-secondary ms-2"><?= count($users) ?> usuarios</span>
                </h2>
            </div>
            <div class="col-md-6 text-end">
                <?php if (Security::hasPermission('Create')): ?>
                    <a href="/users/create" class="btn btn-setap-primary">
                        <i class="bi bi-person-plus"></i> Nuevo Usuario
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros y Búsqueda -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="input-group search-box">
                            <input type="text" class="form-control" id="searchInput"
                                placeholder="Buscar usuarios...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="roleFilter">
                            <option value="">Todos los roles</option>
                            <?php
                            $uniqueRoles = array_unique(array_column($users, 'rol'));
                            foreach ($uniqueRoles as $role):
                            ?>
                                <option value="<?= htmlspecialchars($role) ?>">
                                    <?= htmlspecialchars(ucfirst($role)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </div>
                    <div class="col-md-3 text-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary" id="exportBtn">
                                <i class="bi bi-download"></i> Exportar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="refreshBtn">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead class="table-setap-primary">
                            <tr>
                                <th>Usuario</th>
                                <th>Información Personal</th>
                                <th>Contacto</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th class="table-actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox"></i> No hay usuarios registrados
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr data-user-id="<?= $user['id'] ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?= strtoupper(substr($user['nombre_completo'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($user['nombre_usuario']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($user['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($user['nombre_completo']) ?></div>
                                            <div class="text-muted small">RUT: <?= htmlspecialchars($user['rut']) ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($user['telefono'])): ?>
                                                <div><i class="bi bi-telephone"></i> <?= htmlspecialchars($user['telefono']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($user['direccion'])): ?>
                                                <div class="text-muted small">
                                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($user['direccion']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = match ($user['rol']) {
                                                'admin' => 'bg-danger',
                                                'planner' => 'bg-primary',
                                                'supervisor' => 'bg-warning text-dark',
                                                'executor' => 'bg-success',
                                                'client' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?> role-badge">
                                                <?= htmlspecialchars(ucfirst($user['rol'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = strtolower($user['estado']) === 'activo' ? 'success' : 'warning';
                                            $statusText = $user['estado'];
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <i class="bi bi-circle-fill"></i> <?= htmlspecialchars($statusText) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small text-muted">
                                                <?= date('d/m/Y', strtotime($user['fecha_Creado'])) ?>
                                            </div>
                                        </td>
                                        <td class="table-actions">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if (Security::hasPermission('Read')): ?>
                                                    <button type="button" class="btn btn-outline-info"
                                                        onclick="viewUser(<?= $user['id'] ?>)"
                                                        title="Ver detalles">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <?php if (Security::hasPermission('Modify')): ?>
                                                    <a href="/users/edit?id=<?= $user['id'] ?>"
                                                        class="btn btn-outline-warning"
                                                        title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="/users/permissions?user_id=<?= $user['id'] ?>"
                                                        class="btn btn-outline-secondary"
                                                        title="Permisos">
                                                        <i class="bi bi-shield-lock"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if (Security::hasPermission('Eliminate') && $user['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_usuario']) ?>')"
                                                        title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Usuario -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userModalBody">
                    <!-- Contenido cargado dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Confirmar Eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar al usuario <strong id="deleteUserName"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="/users/delete" style="display: inline;" id="deleteUserForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Helpers\Security::generateCsrfToken()) ?>">
                        <input type="hidden" name="id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Cambio de Contraseña -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="passwordForm">
                        <input type="hidden" id="passwordUserId">
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="newPassword"
                                placeholder="Mínimo 6 caracteres" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirmPassword"
                                placeholder="Repite la nueva contraseña" minlength="6" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-setap-primary" onclick="savePassword()">Cambiar Contraseña</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Búsqueda en tiempo real
        document.getElementById('searchInput').addEventListener('input', function() {
            filterTable();
        });

        // Filtro por rol
        document.getElementById('roleFilter').addEventListener('change', function() {
            filterTable();
        });

        // Limpiar filtros
        document.getElementById('clearFilters').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            document.getElementById('roleFilter').value = '';
            filterTable();
        });

        // Actualizar página
        document.getElementById('refreshBtn').addEventListener('click', function() {
            window.location.reload();
        });

        // Exportar datos
        document.getElementById('exportBtn').addEventListener('click', function() {
            exportToCSV();
        });

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const rows = document.querySelectorAll('#usersTable tbody tr[data-user-id]');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const role = row.querySelector('.role-badge').textContent.toLowerCase();

                const matchesSearch = searchTerm === '' || text.includes(searchTerm);
                const matchesRole = roleFilter === '' || role.includes(roleFilter.toLowerCase());

                row.style.display = matchesSearch && matchesRole ? '' : 'none';
            });
        }

        function viewUser(userId) {
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            const modalBody = document.getElementById('userModalBody');

            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
            modal.show();

            // Cargar detalles del usuario via AJAX
            fetch(`/api/users/details?id=${userId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text().then(text => {
                        console.log('Response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Invalid JSON response: ' + text);
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    if (data.success) {
                        const user = data.user;
                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="user-avatar-large mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                        ${user.nombre_completo.charAt(0).toUpperCase()}
                                    </div>
                                    <h5 class="mb-1">${user.nombre_completo}</h5>
                                    <p class="text-muted mb-3">@${user.nombre_usuario}</p>
                                    <span class="badge ${user.estado_tipo_id == 2 ? 'bg-success' : 'bg-secondary'} role-badge">
                                        ${user.estado || 'Sin estado'}
                                    </span>
                                </div>
                                <div class="col-md-8">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-person-badge"></i> Información Personal</h6>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>RUT:</strong></div>
                                        <div class="col-sm-8">${user.rut || 'No especificado'}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Email:</strong></div>
                                        <div class="col-sm-8">${user.email}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Teléfono:</strong></div>
                                        <div class="col-sm-8">${user.telefono || 'No especificado'}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4"><strong>Dirección:</strong></div>
                                        <div class="col-sm-8">${user.direccion || 'No especificada'}</div>
                                    </div>
                                    
                                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-check"></i> Información del Sistema</h6>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Rol:</strong></div>
                                        <div class="col-sm-8">
                                            <span class="badge bg-primary role-badge">${user.rol}</span>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Fecha de Registro:</strong></div>
                                        <div class="col-sm-8">${new Date(user.fecha_Creado).toLocaleDateString('es-ES')}</div>
                                    </div>
                                    ${user.fecha_inicio ? `
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Fecha de Inicio:</strong></div>
                                        <div class="col-sm-8">${new Date(user.fecha_inicio).toLocaleDateString('es-ES')}</div>
                                    </div>
                                    ` : ''}
                                    ${user.fecha_termino ? `
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Fecha de Término:</strong></div>
                                        <div class="col-sm-8">${new Date(user.fecha_termino).toLocaleDateString('es-ES')}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-setap-primary btn-sm" onclick="editUser(${userId})">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="changePassword(${userId})">
                                    <i class="bi bi-key"></i> Cambiar Contraseña
                                </button>
                                <button type="button" class="btn ${user.estado_tipo_id == 1 ? 'btn-outline-secondary' : 'btn-outline-success'} btn-sm" 
                                        onclick="toggleUserStatus(${userId}, ${user.estado_tipo_id})">
                                    <i class="bi bi-power"></i> ${user.estado_tipo_id == 1 ? 'Desactivar' : 'Activar'}
                                </button>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                Error al cargar los detalles del usuario: ${data.message || 'Error desconocido'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            Error de conexión: ${error.message}
                        </div>
                    `;
                });
        }

        function deleteUser(userId, userName) {
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteUserId').value = userId;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function exportToCSV() {
            const table = document.getElementById('usersTable');
            const rows = Array.from(table.querySelectorAll('tr:not([style*="display: none"])'));

            let csv = [];

            // Headers
            const headers = Array.from(rows[0].querySelectorAll('th'))
                .slice(0, -1) // Excluir columna de acciones
                .map(th => th.textContent.trim());
            csv.push(headers.join(','));

            // Data rows
            rows.slice(1).forEach(row => {
                if (row.dataset.userId) {
                    const cells = Array.from(row.querySelectorAll('td'))
                        .slice(0, -1) // Excluir columna de acciones
                        .map(td => `"${td.textContent.trim().replace(/"/g, '""')}"`);
                    csv.push(cells.join(','));
                }
            });

            // Download
            const blob = new Blob([csv.join('\n')], {
                type: 'text/csv'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `usuarios_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function editUser(userId) {
            window.location.href = `/users/edit?id=${userId}`;
        }

        function changePassword(userId) {
            const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
            document.getElementById('passwordUserId').value = userId;
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            modal.show();
        }

        function toggleUserStatus(userId, currentStatus) {
            const action = currentStatus == 1 ? 'desactivar' : 'activar';

            if (confirm(`¿Estás seguro de que deseas ${action} este usuario?`)) {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('new_status', currentStatus == 1 ? 2 : 1);

                fetch('/users/toggle-status', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error al cambiar el estado del usuario: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error de conexión al servidor');
                    });
            }
        }

        function savePassword() {
            const userId = document.getElementById('passwordUserId').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                return;
            }

            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('new_password', newPassword);

            fetch('/users/change-password', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('passwordModal'));
                        modal.hide();
                        alert('Contraseña cambiada exitosamente');
                    } else {
                        alert('Error al cambiar la contraseña: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al servidor');
                });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>