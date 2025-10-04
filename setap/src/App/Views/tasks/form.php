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
                        <a href="/tasks" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo $data['subtitle']; ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= $data['task_id'] ? '/tasks/update/' . $data['task_id'] : '/tasks/store' ?>" id="taskForm">
                                    <div class="row">
                                        <div class="col-12">
                                            <!-- Información básica de la tarea -->
                                            <h6 class="border-bottom pb-2 mb-3">Información de la Tarea</h6>

                                            <div class="mb-3">
                                                <label for="nombre" class="form-label">Nombre de la Tarea <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="nombre" name="nombre"
                                                    value="<?= htmlspecialchars($data['task']['nombre'] ?? '') ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="descripcion" class="form-label">Descripción</label>
                                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($data['task']['descripcion'] ?? '') ?></textarea>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="proyecto_id" class="form-label">Proyecto <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="proyecto_id" name="proyecto_id" required>
                                                            <option value="">Seleccionar proyecto</option>
                                                            <?php if (!empty($data['projects'])): ?>
                                                                <?php foreach ($data['projects'] as $project): ?>
                                                                    <option value="<?= $project['id'] ?>"
                                                                        <?= (isset($data['task']['proyecto_id']) && $data['task']['proyecto_id'] == $project['id']) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($project['cliente_nombre']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="tarea_tipo_id" class="form-label">Tipo de Tarea <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="tarea_tipo_id" name="tarea_tipo_id" required>
                                                            <option value="">Seleccionar tipo</option>
                                                            <?php if (!empty($data['taskTypes'])): ?>
                                                                <?php foreach ($data['taskTypes'] as $type): ?>
                                                                    <option value="<?= $type['id'] ?>"
                                                                        <?= (isset($data['task']['tarea_tipo_id']) && $data['task']['tarea_tipo_id'] == $type['id']) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($type['nombre']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                                            value="<?= $data['task']['fecha_inicio'] ?? '' ?>">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                                            value="<?= $data['task']['fecha_fin'] ?? '' ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="usuario_id" class="form-label">Asignar a Usuario</label>
                                                        <select class="form-select" id="usuario_id" name="usuario_id">
                                                            <option value="">Sin asignar</option>
                                                            <?php if (!empty($data['users'])): ?>
                                                                <?php foreach ($data['users'] as $user): ?>
                                                                    <option value="<?= $user['id'] ?>"
                                                                        <?= (isset($data['task']['usuario_id']) && $data['task']['usuario_id'] == $user['id']) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($user['nombre_usuario']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="estado_tipo_id" class="form-label">Estado</label>
                                                        <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                                            <?php if (!empty($data['taskStates'])): ?>
                                                                <?php foreach ($data['taskStates'] as $state): ?>
                                                                    <option value="<?= $state['id'] ?>"
                                                                        <?= ((isset($data['task']['estado_tipo_id']) && $data['task']['estado_tipo_id'] == $state['id']) ||
                                                                            (!isset($data['task']) && $state['id'] == 2)) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($state['nombre']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Botones de Acción -->
                                            <div class="mt-4 text-end">
                                                <a href="/tasks" class="btn btn-secondary me-2">
                                                    <i class="bi bi-x-lg"></i> Cancelar
                                                </a>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="bi bi-check-lg"></i>
                                                    <?= $data['task_id'] ? 'Actualizar Tarea' : 'Crear Tarea' ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <script>
                                    // Validación de fechas
                                    document.getElementById('fecha_fin').addEventListener('change', function() {
                                        const fechaInicio = document.getElementById('fecha_inicio').value;
                                        const fechaFin = this.value;

                                        if (fechaInicio && fechaFin && fechaFin < fechaInicio) {
                                            alert('La fecha de fin no puede ser anterior a la fecha de inicio');
                                            this.value = '';
                                        }
                                    });

                                    // Validación del formulario
                                    document.getElementById('taskForm').addEventListener('submit', function(e) {
                                        const nombre = document.getElementById('nombre').value.trim();
                                        const proyectoId = document.getElementById('proyecto_id').value;
                                        const tareaTipoId = document.getElementById('tarea_tipo_id').value;

                                        if (!nombre) {
                                            e.preventDefault();
                                            alert('El nombre de la tarea es obligatorio');
                                            return;
                                        }

                                        if (!proyectoId) {
                                            e.preventDefault();
                                            alert('Debe seleccionar un proyecto');
                                            return;
                                        }

                                        if (!tareaTipoId) {
                                            e.preventDefault();
                                            alert('Debe seleccionar un tipo de tarea');
                                            return;
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
</body>

</html>