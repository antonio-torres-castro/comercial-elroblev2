<!DOCTYPE html>
<html lang="es">
<?php

use App\Helpers\Security;
use App\Constants\AppConstants;
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']); ?> - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>
    <div class="container-fluid mt-4">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($data['title']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                        <a href="<?= AppConstants::ROUTE_SERVICES_CATALOG ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                        <button type="button" class="btn btn-sm btn-setap-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                            <i class="bi bi-plus-circle"></i> Nueva Categoria
                        </button>
                    </div>
                </div>

                <!-- Alertas -->
                <?php if (isset($_GET['success']) && !empty($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros de Busqueda</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?= AppConstants::ROUTE_SERVICES ?>/categories" class="row g-3">
                            <div class="col-md-4">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                    value="<?= htmlspecialchars($data['filters']['nombre'] ?? ''); ?>"
                                    placeholder="Buscar por nombre">
                            </div>
                            <div class="col-md-4">
                                <label for="parent_id" class="form-label">Padre</label>
                                <select class="form-select" id="parent_id" name="parent_id">
                                    <option value="">Todos</option>
                                    <?php foreach ($data['parent_categories'] as $parent): ?>
                                        <option value="<?= $parent['id']; ?>" <?= ($data['filters']['parent_id'] ?? '') == $parent['id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($parent['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-setap-primary">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    <a href="<?= AppConstants::ROUTE_SERVICES ?>/categories" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-tags"></i> Categorias</h5>
                        <span class="badge bg-primary"><?= count($data['categories']); ?> categorias</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['categories'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No se encontraron categorias.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="categoriesTable">
                                    <thead class="table-setap-primary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Padre</th>
                                            <th width="120">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['categories'] as $cat): ?>
                                            <tr>
                                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($cat['id']); ?></span></td>
                                                <td>
                                                    <?php if ($cat['ind_padre'] > 0): ?>
                                                        <strong><?= htmlspecialchars($cat['nombre']); ?></strong>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($cat['nombre']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($cat['parent_nombre']): ?>

                                                        <?php if ($cat['ind_padre'] > 0): ?>
                                                            <span class="badge bg-setap-primary"><?= htmlspecialchars($cat['parent_nombre']); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-setap-secondary"><?= htmlspecialchars($cat['parent_nombre']); ?></span>
                                                        <?php endif; ?>

                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-danger"
                                                            onclick="confirmDeleteCategory(<?= $cat['id']; ?>, '<?= addslashes($cat['nombre']); ?>')"
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
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Crear Categoria -->
    <div class="modal fade" id="createCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-tags"></i> Crear Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/category" id="createCategoryForm">
                        <?= Security::renderCsrfField() ?>
                        <label class="form-label" for="modal_parent_id">Padre</label>
                        <select class="form-select mb-3" id="modal_parent_id" name="parent_id">
                            <option value="">Sin padre</option>
                            <?php foreach ($data['all_categories'] as $category): ?>
                                <option value="<?= $category['id']; ?>" <?= $category['ind_padre'] > 0 ? 'style="font-weight: bold;"' : 'style="font-weight: normal;"'; ?>>
                                    <?= htmlspecialchars($category['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label class="form-label" for="modal_categoria_nombre">Nombre</label>
                        <input class="form-control mb-3" id="modal_categoria_nombre" name="nombre" required>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-setap-primary" form="createCategoryForm">
                        <i class="bi bi-plus-circle"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminacion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Estas seguro de que deseas eliminar la categoria:</p>
                    <p><strong id="deleteCategoryName"></strong></p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Esta accion no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/delete-category" style="display: inline;" id="deleteForm">
                        <?= Security::renderCsrfField() ?>
                        <input type="hidden" name="id" id="deleteCategoryId">
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php $scripts = ['jquery', 'datatables'];
    include __DIR__ . '/../layouts/scripts-advanced.php';
    include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script>
        $(document).ready(function() {
            $('#categoriesTable').DataTable({
                language: {
                    url: 'https://cdn.jsdelivr.net/npm/datatables.net-plugins@1.13.6/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [
                    [1, 'asc']
                ],
                columnDefs: [{
                    targets: [-1],
                    orderable: false,
                    searchable: false
                }]
            });
        });

        function confirmDeleteCategory(id, name) {
            document.getElementById('deleteCategoryName').textContent = name;
            document.getElementById('deleteCategoryId').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>

</html>