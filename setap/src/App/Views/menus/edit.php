<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title']); ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/setap-theme.css">
</head>

<body>
    <?php

    use App\Helpers\Security; ?>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo htmlspecialchars($data['title']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/menus" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Lista
                        </a>
                    </div>
                </div>

                <!-- Mostrar errores de validación -->
                <?php if (!empty($data['errors'])): ?>
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle"></i> Errores de Validación:</h6>
                        <ul class="mb-0">
                            <?php foreach ($data['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo htmlspecialchars($data['subtitle']); ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="/menus/update">
                                    <?php Security::renderCsrfField(); ?>
                                    <input type="hidden" name="id" value="<?php echo $data['menu_id']; ?>">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="nombre" name="nombre"
                                                    value="<?php echo htmlspecialchars($data['menu']['nombre'] ?? ''); ?>"
                                                    placeholder="Ej: Gestión de Usuarios" required>
                                                <div class="form-text">Nombre interno del menú para identificación.</div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="url" class="form-label">URL <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="url" name="url"
                                                    value="<?php echo htmlspecialchars($data['menu']['url'] ?? ''); ?>"
                                                    placeholder="Ej: /users" required>
                                                <div class="form-text">URL de destino del menú. Debe comenzar con "/"</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="icono" class="form-label">Icono</label>
                                                <input type="text" class="form-control" id="icono" name="icono"
                                                    value="<?php echo htmlspecialchars($data['menu']['icono'] ?? ''); ?>"
                                                    placeholder="Ej: bi-people">
                                                <div class="form-text">Clase de icono Bootstrap Icons (opcional).</div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="orden" class="form-label">Orden <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="orden" name="orden"
                                                    value="<?php echo htmlspecialchars($data['menu']['orden'] ?? 1); ?>"
                                                    min="1" required>
                                                <div class="form-text">Orden de visualización en el menú.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="estado_tipo_id" class="form-label">Estado</label>
                                                <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                                    <?php
                                                    $selectedStatus = $data['menu']['estado_tipo_id'] ?? 1;
                                                    foreach ($data['statusTypes'] as $status):
                                                    ?>
                                                        <option value="<?php echo $status['id']; ?>"
                                                            <?php echo ($status['id'] == $selectedStatus) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($status['nombre']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="display" class="form-label">Título de visualización <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="display" name="display"
                                                    value="<?php echo htmlspecialchars($data['menu']['display'] ?? ''); ?>"
                                                    required maxlength="150" placeholder="Ej: Usuarios, Clientes, Proyectos">
                                                <div class="form-text">Texto que verá el usuario en el menú (ej: "Usuarios" para nombre interno "manage_users")</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Descripción</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                            placeholder="Descripción opcional del menú"><?php echo htmlspecialchars($data['menu']['descripcion'] ?? ''); ?></textarea>
                                        <div class="form-text">Descripción opcional para documentación interna.</div>
                                    </div>

                                    <!-- Información adicional -->
                                    <?php if (!empty($data['menu']['fecha_creacion'])): ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Fecha de Creación</label>
                                                    <input type="text" class="form-control"
                                                        value="<?php echo htmlspecialchars($data['menu']['fecha_creacion']); ?>" readonly>
                                                </div>
                                            </div>
                                            <?php if (!empty($data['menu']['fecha_modificacion'])): ?>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Última Modificación</label>
                                                        <input type="text" class="form-control"
                                                            value="<?php echo htmlspecialchars($data['menu']['fecha_modificacion']); ?>" readonly>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <a href="/menus" class="btn btn-secondary">
                                                <i class="bi bi-arrow-left"></i> Cancelar
                                            </a>
                                        </div>
                                        <div>
                                            <!-- Botón eliminar -->
                                            <button type="button" class="btn btn-outline-danger me-2"
                                                onclick="confirmarEliminacion(<?php echo $data['menu_id']; ?>)">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                            <!-- Botón actualizar -->
                                            <button type="submit" class="btn btn-setap-primary">
                                                <i class="bi bi-check-circle"></i> Actualizar Menú
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <!-- Formulario oculto para eliminar -->
                                <form id="formEliminar" method="POST" action="/menus/delete" style="display: none;">
                                    <?php Security::renderCsrfField(); ?>
                                    <input type="hidden" name="id" value="<?php echo $data['menu_id']; ?>">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
    <script>
        function confirmarEliminacion(id) {
            if (confirm('¿Está seguro de que desea eliminar este menú? Esta acción no se puede deshacer.')) {
                document.getElementById('formEliminar').submit();
            }
        }

        // Validación del formulario
        document.querySelector('form:not(#formEliminar)').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const display = document.getElementById('display').value.trim();

            if (!nombre) {
                e.preventDefault();
                alert('El nombre interno es requerido');
                return;
            }

            if (!display) {
                e.preventDefault();
                alert('El título de visualización es requerido');
                return;
            }
        });
    </script>
</body>

</html>