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
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/tasks/create" class="btn btn-sm btn-setap-primary">
                            <i class="bi bi-plus-circle"></i> Nueva Tarea
                        </a>
                    </div>
                </div>

                <!-- Filtros y Búsqueda -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2>
                            <i class="bi bi-list-check"></i> <?php echo $data['title']; ?>
                            <span class="badge bg-secondary ms-2"><?= count($data['tasks'] ?? []) ?> tareas</span>
                        </h2>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="/tasks/create" class="btn btn-setap-primary">
                            <i class="bi bi-plus-lg"></i> Nueva Tarea
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Proyecto</label>
                                        <select class="form-select" name="proyecto_id">
                                            <option value="">Todos los proyectos</option>
                                            <?php if (!empty($data['projects'])): ?>
                                                <?php foreach ($data['projects'] as $project): ?>
                                                    <option value="<?= $project['id'] ?>"
                                                        <?= (isset($_GET['proyecto_id']) && $_GET['proyecto_id'] == $project['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($project['cliente_nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="estado_tipo_id">
                                            <option value="">Todos los estados</option>
                                            <?php if (!empty($data['taskStates'])): ?>
                                                <?php foreach ($data['taskStates'] as $state): ?>
                                                    <option value="<?= $state['id'] ?>"
                                                        <?= (isset($_GET['estado_tipo_id']) && $_GET['estado_tipo_id'] == $state['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($state['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Asignado a</label>
                                        <select class="form-select" name="usuario_id">
                                            <option value="">Todos los usuarios</option>
                                            <?php if (!empty($data['users'])): ?>
                                                <?php foreach ($data['users'] as $user): ?>
                                                    <option value="<?= $user['id'] ?>"
                                                        <?= (isset($_GET['usuario_id']) && $_GET['usuario_id'] == $user['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($user['nombre_usuario']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-outline-setap-primary me-2">
                                            <i class="bi bi-search"></i> Filtrar
                                        </button>
                                        <a href="/tasks" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-lg"></i> Limpiar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Tareas -->
                <div class="row">
                    <div class="col-12">
                        <?php if (!empty($data['tasks'])): ?>
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Tarea</th>
                                                    <th>Proyecto</th>
                                                    <th>Estado</th>
                                                    <th>Asignado a</th>
                                                    <th>Fechas</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data['tasks'] as $task): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold"><?= htmlspecialchars($task['tarea_nombre']) ?></div>
                                                            <?php if (!empty($task['descripcion'])): ?>
                                                                <small class="text-muted"><?= htmlspecialchars(substr($task['descripcion'], 0, 100)) ?>...</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info text-dark">
                                                                <?= htmlspecialchars($task['cliente_nombre']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $badgeClass = 'bg-secondary';
                                                            switch ($task['estado_tipo_id']) {
                                                                case 1:
                                                                    $badgeClass = 'bg-success';
                                                                    break; // Activo
                                                                case 2:
                                                                    $badgeClass = 'bg-warning';
                                                                    break; // Pendiente
                                                                case 3:
                                                                    $badgeClass = 'bg-danger';
                                                                    break;  // Inactivo
                                                                case 8:
                                                                    $badgeClass = 'bg-primary';
                                                                    break; // Completado
                                                            }
                                                            ?>
                                                            <span class="badge <?= $badgeClass ?>">
                                                                <?= htmlspecialchars($task['estado']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($task['asignado_a'])): ?>
                                                                <i class="bi bi-person"></i> <?= htmlspecialchars($task['asignado_a']) ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Sin asignar</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($task['fecha_inicio'])): ?>
                                                                <small>
                                                                    <strong>Inicio:</strong> <?= date('d/m/Y', strtotime($task['fecha_inicio'])) ?><br>
                                                                    <?php if (!empty($task['fecha_fin'])): ?>
                                                                        <strong>Fin:</strong> <?= date('d/m/Y', strtotime($task['fecha_fin'])) ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            <?php else: ?>
                                                                <span class="text-muted">Sin fechas</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="/tasks/show/<?= $task['id'] ?>" class="btn btn-outline-info" title="Ver detalles">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <a href="/tasks/edit/<?= $task['id'] ?>" class="btn btn-outline-setap-primary" title="Editar">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-outline-danger"
                                                                    onclick="deleteTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['tarea_nombre']) ?>')"
                                                                    title="Eliminar">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="bi bi-inbox display-1 text-muted"></i>
                                    <h4 class="mt-3">No hay tareas registradas</h4>
                                    <p class="text-muted">Comienza creando tu primera tarea.</p>
                                    <a href="/tasks/create" class="btn btn-setap-primary">
                                        <i class="bi bi-plus-lg"></i> Crear Primera Tarea
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modal para confirmar eliminación -->
                <div class="modal fade" id="deleteModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmar Eliminación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Estás seguro de que deseas eliminar la tarea <strong id="deleteTaskName"></strong>?</p>
                                <p class="text-muted small">Esta acción no se puede deshacer.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    let taskToDelete = null;

                    function deleteTask(id, name) {
                        taskToDelete = id;
                        document.getElementById('deleteTaskName').textContent = name;
                        new bootstrap.Modal(document.getElementById('deleteModal')).show();
                    }

                    document.getElementById('confirmDelete').addEventListener('click', function() {
                        if (taskToDelete) {
                            fetch(`/tasks/delete/${taskToDelete}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        alert('Error al eliminar la tarea: ' + (data.message || 'Error desconocido'));
                                    }
                                })
                                .catch(error => {
                                    alert('Error de conexión al servidor');
                                    console.error('Error:', error);
                                });
                        }
                        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                    });
                </script>
            </main>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
</body>

</html>