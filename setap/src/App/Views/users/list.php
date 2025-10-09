<?php

use App\Helpers\Security;
use App\Constants\AppConstants;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
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

        .main-content {
            margin-top: 2rem;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main class="main-content">
        <!-- Header y Filtros -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>
                    <i class="bi bi-people"></i> Gestión de Usuarios
                    <span class="badge bg-secondary ms-2"><?= count($users) ?> usuarios</span>
                </h2>
            </div>
            <div class="col-md-6 text-end">
                <?php if (\App\Helpers\Security::hasPermission('Create')): ?>
                    <a href="/users/create" class="btn btn-setap-primary">
                        <i class="bi bi-person-plus"></i> <?= AppConstants::UI_NEW_USER ?>
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
                                <th>Cliente</th> <!-- GAP 1 y GAP 2: Nueva columna -->
                                <th>Estado</th>
                                <th>Registro</th>
                                <th class="table-actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
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
                                            <!-- GAP 1 y GAP 2: Mostrar cliente asignado -->
                                            <?php if (!empty($user['cliente_id']) && !empty($user['cliente_nombre'])): ?>
                                                <div class="small">
                                                    <i class="bi bi-building"></i>
                                                    <span class="fw-semibold"><?= htmlspecialchars($user['cliente_nombre']) ?></span>
                                                </div>
                                                <div class="text-muted smaller">
                                                    ID: <?= $user['cliente_id'] ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">
                                                    <i class="bi bi-dash"></i> No asignado
                                                </span>
                                            <?php endif; ?>
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
                                                <?php if (\App\Helpers\Security::hasPermission('Read')): ?>
                                                    <button type="button" class="btn btn-outline-info"
                                                        onclick="showUserDetailsModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_usuario']) ?>', '<?= htmlspecialchars($user['email']) ?>', '<?= $user['estado_tipo_id'] ?>', '<?= htmlspecialchars($user['fecha_Creado']) ?>')"
                                                        title="Ver detalles">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <?php if (\App\Helpers\Security::hasPermission('Modify')): ?>
                                                    <a href="/users/edit?id=<?= $user['id'] ?>"
                                                        class="btn btn-outline-warning"
                                                        title="<?= AppConstants::UI_BTN_EDIT ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="/users/permissions?user_id=<?= $user['id'] ?>"
                                                        class="btn btn-outline-secondary"
                                                        title="Permisos">
                                                        <i class="bi bi-shield-lock"></i>
                                                    </a>
                                                    
                                                    <!-- Botón cambiar contraseña -->
                                                    <button type="button" class="btn btn-outline-primary"
                                                        onclick="showChangePasswordModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_usuario']) ?>')"
                                                        title="Cambiar contraseña">
                                                        <i class="bi bi-key"></i>
                                                    </button>
                                                    
                                                    <!-- Toggle status form -->
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <form method="POST" action="/users/toggle-status" style="display: inline-block;" 
                                                              onsubmit="return confirmToggleUserStatus(this, '<?= $user['estado_tipo_id'] == 1 ? 'desactivar' : 'activar' ?>')">
                                                            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::getCsrfToken() ?>">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="new_status" value="<?= $user['estado_tipo_id'] == 1 ? '2' : '1' ?>">
                                                            <button type="submit" 
                                                                    class="btn btn-outline-<?= $user['estado_tipo_id'] == 1 ? 'secondary' : 'success' ?>"
                                                                    title="<?= $user['estado_tipo_id'] == 1 ? 'Desactivar' : 'Activar' ?>">
                                                                <i class="bi bi-power"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if (\App\Helpers\Security::hasPermission('Eliminate') && $user['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre_usuario']) ?>')"
                                                        title="<?= AppConstants::UI_BTN_DELETE ?>">
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= AppConstants::UI_BTN_CANCEL ?></button>
                    <form method="POST" action="/users/delete" style="display: inline;" id="deleteUserForm">
                        <?= \App\Helpers\Security::renderCsrfField() ?>
                        <input type="hidden" name="id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger"><?= AppConstants::UI_BTN_DELETE ?></button>
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
                <form method="POST" action="/users/change-password" id="passwordForm" onsubmit="return validatePasswordForm()">
                    <div class="modal-body">
                        <?= \App\Helpers\Security::renderCsrfField() ?>
                        <input type="hidden" name="user_id" id="passwordUserId">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Cambiar contraseña del usuario: <strong id="passwordUserName"></strong>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Nueva Contraseña *</label>
                            <input type="password" class="form-control" name="new_password" id="newPassword"
                                placeholder="Mínimo 6 caracteres" minlength="6" required>
                            <div class="invalid-feedback" id="newPasswordFeedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" id="confirmPassword"
                                placeholder="Repite la nueva contraseña" minlength="6" required>
                            <div class="invalid-feedback" id="confirmPasswordFeedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-setap-primary">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </main>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
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

        function showUserDetailsModal(userId, userName, email, statusId, fechaCreacion) {
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            const modalBody = document.getElementById('userModalBody');
            
            const statusText = statusId == 2 ? 'Activo' : 'Inactivo';
            const statusClass = statusId == 2 ? 'bg-success' : 'bg-secondary';
            
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="user-avatar-large mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                            ${userName.charAt(0).toUpperCase()}
                        </div>
                        <h5 class="mb-1">@${userName}</h5>
                        <p class="text-muted mb-3">${email}</p>
                        <span class="badge ${statusClass} role-badge">
                            ${statusText}
                        </span>
                    </div>
                    <div class="col-md-8">
                        <h6 class="fw-bold mb-3"><i class="bi bi-person-badge"></i> Información del Usuario</h6>
                        <div class="row mb-2">
                            <div class="col-sm-4"><strong>Usuario:</strong></div>
                            <div class="col-sm-8">${userName}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4"><strong>Email:</strong></div>
                            <div class="col-sm-8">${email}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4"><strong>Estado:</strong></div>
                            <div class="col-sm-8"><span class="badge ${statusClass}">${statusText}</span></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4"><strong>Fecha de Registro:</strong></div>
                            <div class="col-sm-8">${new Date(fechaCreacion).toLocaleDateString('es-ES')}</div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="/users/edit?id=${userId}" class="btn btn-outline-setap-primary btn-sm">
                        <i class="bi bi-pencil"></i> <?= AppConstants::UI_BTN_EDIT ?>
                    </a>
                    <button type="button" class="btn btn-outline-warning btn-sm" 
                            onclick="bootstrap.Modal.getInstance(document.getElementById('userModal')).hide(); showChangePasswordModal(${userId}, '${userName}');">
                        <i class="bi bi-key"></i> Cambiar Contraseña
                    </button>
                </div>
            `;
            
            modal.show();
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

        // Instancia global del modal de contraseña
        let passwordModalInstance = null;

        function changePassword(userId) {
            if (!passwordModalInstance) {
                passwordModalInstance = new bootstrap.Modal(document.getElementById('passwordModal'));
            }
            
            document.getElementById('passwordUserId').value = userId;
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            
            // Limpiar validaciones
            const form = document.getElementById('passwordForm');
            form.classList.remove('was-validated');
            
            // Limpiar clases de validación de los inputs
            document.getElementById('newPassword').classList.remove('is-invalid', 'is-valid');
            document.getElementById('confirmPassword').classList.remove('is-invalid', 'is-valid');
            
            // Resetear botón de envío
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-key"></i> Cambiar Contraseña';
            }
            
            passwordModalInstance.show();
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

        function showChangePasswordModal(userId, userName) {
            // Inicializar modal si no existe
            if (!passwordModalInstance) {
                passwordModalInstance = new bootstrap.Modal(document.getElementById('passwordModal'));
            }
            
            document.getElementById('passwordUserId').value = userId;
            
            // Verificar que el elemento existe antes de asignar el nombre
            const passwordUserNameElement = document.getElementById('passwordUserName');
            if (passwordUserNameElement) {
                passwordUserNameElement.textContent = userName;
            }
            
            // Limpiar campos
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            
            // Limpiar validaciones
            const form = document.getElementById('passwordForm');
            form.classList.remove('was-validated');
            
            // Limpiar clases de validación de los inputs
            document.getElementById('newPassword').classList.remove('is-invalid', 'is-valid');
            document.getElementById('confirmPassword').classList.remove('is-invalid', 'is-valid');
            
            // Resetear botón de envío
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-key"></i> Cambiar Contraseña';
            }
            
            passwordModalInstance.show();
        }
        
        function validatePasswordForm() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Limpiar validaciones anteriores
            const newPasswordInput = document.getElementById('newPassword');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const newPasswordFeedback = document.getElementById('newPasswordFeedback');
            const confirmPasswordFeedback = document.getElementById('confirmPasswordFeedback');
            
            let isValid = true;
            
            // Validar longitud de contraseña
            if (newPassword.length < 6) {
                newPasswordInput.classList.add('is-invalid');
                newPasswordFeedback.textContent = 'La contraseña debe tener al menos 6 caracteres';
                isValid = false;
            } else {
                newPasswordInput.classList.remove('is-invalid');
                newPasswordInput.classList.add('is-valid');
                newPasswordFeedback.textContent = '';
            }
            
            // Validar coincidencia de contraseñas
            if (newPassword !== confirmPassword) {
                confirmPasswordInput.classList.add('is-invalid');
                confirmPasswordFeedback.textContent = 'Las contraseñas no coinciden';
                isValid = false;
            } else if (confirmPassword.length >= 6) {
                confirmPasswordInput.classList.remove('is-invalid');
                confirmPasswordInput.classList.add('is-valid');
                confirmPasswordFeedback.textContent = '';
            }
            
            if (isValid) {
                // Deshabilitar botón para evitar múltiples envíos
                const submitButton = document.querySelector('#passwordForm button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="bi bi-clock"></i> Cambiando...';
            }
            
            return isValid;
        }

        // Auto-hide alerts after 5 seconds (excepto los que están dentro de modales)
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert:not(.modal .alert)');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>