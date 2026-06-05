<!DOCTYPE html>
<html lang="es">
<?php

use App\Helpers\Security;
use App\Constants\AppConstants;

$isAdmin = $data['user']['id'] == 1;
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
                        <button type="button" class="btn btn-sm btn-setap-primary" data-bs-toggle="modal" data-bs-target="#createTypeModal">
                            <i class="bi bi-plus-circle"></i> Nuevo Tipo
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
                        <form method="GET" action="<?= AppConstants::ROUTE_SERVICES ?>/types" class="row g-3">
                            <div class="col-md-4">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                    value="<?= htmlspecialchars($data['filters']['nombre'] ?? ''); ?>"
                                    placeholder="Buscar por nombre">
                            </div>
                            <div class="col-md-4">
                                <label for="servicio_categoria_id" class="form-label">Categoria</label>
                                <select class="form-select" id="servicio_categoria_id" name="servicio_categoria_id">
                                    <option value="">Todas</option>
                                    <?php foreach ($data['categories'] as $category): ?>
                                        <option value="<?= $category['id']; ?>" <?= ($data['filters']['servicio_categoria_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($category['nombre']); ?>
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
                                    <a href="<?= AppConstants::ROUTE_SERVICES ?>/types" class="btn btn-outline-secondary">
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
                        <h5 class="mb-0"><i class="bi bi-ui-checks-grid"></i> Tipos de Servicio</h5>
                        <span class="badge bg-primary"><?= count($data['types']); ?> tipos</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['types'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No se encontraron tipos de servicio.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="typesTable">
                                    <thead class="table-setap-primary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Categoria</th>
                                            <?php if ($isAdmin): ?><th>Proveedor</th><?php endif; ?>
                                            <th>Estado</th>
                                            <th width="120">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['types'] as $type): ?>
                                            <tr>
                                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($type['id']); ?></span></td>
                                                <td><strong><?= htmlspecialchars($type['nombre']); ?></strong></td>
                                                <td>
                                                    <?php if ($type['categoria_nombre']): ?>
                                                        <span class="badge bg-setap-primary-light"><?= htmlspecialchars($type['categoria_nombre']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($isAdmin): ?>
                                                    <td><?= htmlspecialchars($type['proveedor_nombre'] ?? '-'); ?></td>
                                                <?php endif; ?>
                                                <td>
                                                    <span class="badge bg-<?= ($type['estado_tipo_id'] ?? 0) == 2 ? 'success' : 'secondary'; ?>">
                                                        <?= htmlspecialchars($type['estado_nombre'] ?? 'Desconocido'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-danger"
                                                            onclick="confirmDeleteType(<?= $type['id']; ?>, '<?= addslashes($type['nombre']); ?>')"
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

    <!-- Modal Crear Tipo -->
    <div class="modal fade" id="createTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-ui-checks-grid"></i> Crear Tipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/type" id="createTypeForm">
                        <?= Security::renderCsrfField() ?>
                        <input type="hidden" name="estado_tipo_id" value="2">
                        <?php if ($isAdmin): ?>
                            <label class="form-label" for="modal_type_proveedor_id">Proveedor</label>
                            <select class="form-select mb-3" id="modal_type_proveedor_id" name="proveedor_id" required>
                                <?php foreach ($data['suppliers'] as $supplier): ?>
                                    <option value="<?= $supplier['id']; ?>"><?= htmlspecialchars($supplier['razon_social']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="proveedor_id" value="<?= htmlspecialchars((string)($data['suppliers'][0]['id'] ?? '')); ?>">
                        <?php endif; ?>

                        <label class="form-label" for="modal_filtro_parent_id">Padre</label>
                        <select class="form-select mb-3" id="modal_filtro_parent_id" name="parent_id">
                            <option value="">Sin padre</option>
                            <?php foreach ($data['parent_categories'] ?? [] as $parent_category): ?>
                                <option value="<?= $parent_category['id']; ?>"><?= htmlspecialchars($parent_category['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="form-label" for="modal_servicio_categoria_id">Categoria</label>
                        <select class="form-select mb-3" id="modal_servicio_categoria_id" name="servicio_categoria_id">
                            <option value="">Sin categoria</option>
                            <?php foreach ($data['categories'] as $category): ?>
                                <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="form-label" for="modal_tipo_nombre">Nombre</label>
                        <input class="form-control mb-3" id="modal_tipo_nombre" name="nombre" required>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-setap-primary" form="createTypeForm">
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
                    <p>Estas seguro de que deseas eliminar el tipo:</p>
                    <p><strong id="deleteTypeName"></strong></p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Esta accion no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/delete-type" style="display: inline;" id="deleteForm">
                        <?= Security::renderCsrfField() ?>
                        <input type="hidden" name="id" id="deleteTypeId">
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
            $('#typesTable').DataTable({
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

        function confirmDeleteType(id, name) {
            document.getElementById('deleteTypeName').textContent = name;
            document.getElementById('deleteTypeId').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Filtro de categorias por padre en el modal
        const serviceBaseRoute = '<?= AppConstants::ROUTE_SERVICES ?>';
        const modalFiltroParent = document.getElementById('modal_filtro_parent_id');
        const modalCategoriaSelect = document.getElementById('modal_servicio_categoria_id');
        const modalAllCategoriaOptions = Array.from(modalCategoriaSelect.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));

        if (modalFiltroParent && modalCategoriaSelect) {
            modalFiltroParent.addEventListener('change', async () => {
                const parentId = modalFiltroParent.value;
                modalCategoriaSelect.innerHTML = '<option value="">Sin categoria</option>';
                if (!parentId) {
                    modalAllCategoriaOptions.forEach(opt => {
                        if (opt.value !== '') {
                            modalCategoriaSelect.add(new Option(opt.text, opt.value));
                        }
                    });
                    return;
                }
                try {
                    const response = await fetch(`${serviceBaseRoute}/category-by-parent?parent_id=${parentId}`);
                    const json = await response.json();
                    const categories = json.data || [];
                    categories.forEach(cat => modalCategoriaSelect.add(new Option(cat.nombre, cat.id)));
                } catch (e) {
                    console.error('Error al cargar categorias:', e);
                }
            });
        }
    </script>
</body>

</html>