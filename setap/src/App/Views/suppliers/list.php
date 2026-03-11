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
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= htmlspecialchars($data['title']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= AppConstants::ROUTE_SUPPLIERS_CREATE ?>" class="btn btn-sm btn-setap-primary">
                            <i class="bi bi-plus-circle"></i> <?= AppConstants::UI_NEW_SUPPLIER ?>
                        </a>
                    </div>
                </div>

                <!-- Alertas de mensajes -->
                <?php if (isset($_GET['success']) && !empty($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        $messages = [
                            'created' => AppConstants::SUCCESS_SUPPLIER_CREATED,
                            'updated' => AppConstants::SUCCESS_SUPPLIER_UPDATED,
                            'deleted' => AppConstants::SUCCESS_SUPPLIER_DELETED
                        ]; ?>
                        <?= $messages[$_GET['success']]; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros de busqueda -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros de Busqueda</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?= AppConstants::ROUTE_SUPPLIERS ?>" class="row g-3">
                            <div class="col-md-3">
                                <label for="rut" class="form-label">RUT</label>
                                <input type="text" class="form-control" id="rut" name="rut"
                                    value="<?= htmlspecialchars($data['filters']['rut']); ?>"
                                    placeholder="Buscar por RUT">
                            </div>
                            <div class="col-md-4">
                                <label for="razon_social" class="form-label">Razon Social</label>
                                <input type="text" class="form-control" id="razon_social" name="razon_social"
                                    value="<?= htmlspecialchars($data['filters']['razon_social']); ?>"
                                    placeholder="Buscar por razon social">
                            </div>
                            <div class="col-md-3">
                                <label for="estado_tipo_id" class="form-label">Estado</label>
                                <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                    <option value="">Todos los estados</option>
                                    <?php foreach ($data['statusTypes'] as $status): ?>
                                        <option value="<?= $status['id']; ?>"
                                            <?= $data['filters']['estado_tipo_id'] == $status['id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($status['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-setap-primary">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    <a href="<?= AppConstants::ROUTE_SUPPLIERS ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de proveedores -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?= htmlspecialchars($data['subtitle']); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['suppliers'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                No se encontraron proveedores con los filtros aplicados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="suppliersTable">
                                    <thead class="table-setap-primary">
                                        <tr>
                                            <th>RUT</th>
                                            <th>Razon Social</th>
                                            <th>Email</th>
                                            <th>Telefono</th>
                                            <th>Estado</th>
                                            <th>Creado</th>
                                            <th width="150">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['suppliers'] as $supplier): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($supplier['rut']): ?>
                                                        <code><?= htmlspecialchars($supplier['rut']); ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($supplier['razon_social']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($supplier['email']): ?>
                                                        <a href="mailto:<?= htmlspecialchars($supplier['email']); ?>">
                                                            <?= htmlspecialchars($supplier['email']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($supplier['telefono']): ?>
                                                        <a href="tel:<?= htmlspecialchars($supplier['telefono']); ?>">
                                                            <?= htmlspecialchars($supplier['telefono']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClasses = [
                                                        0 => 'bg-warning',
                                                        1 => 'bg-success',
                                                        2 => 'bg-secondary'
                                                    ];
                                                    $statusClass = $statusClasses[$supplier['estado_tipo_id']] ?? 'bg-dark';
                                                    ?>
                                                    <span class="badge <?= $statusClass; ?>">
                                                        <?= htmlspecialchars($supplier['estado_nombre']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($supplier['fecha_Creado'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?= AppConstants::ROUTE_SUPPLIERS_EDIT . '/' ?><?= $supplier['id'] ?>"
                                                            class="btn btn-outline-setap-primary" title="Editar" id="edit<?= $supplier['id']; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger"
                                                            onclick="confirmDelete(<?= $supplier['id']; ?>, '<?= addslashes($supplier['razon_social']); ?>')"
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

    <!-- Modal de confirmacion de eliminacion -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminacion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Estas seguro de que deseas eliminar al proveedor:</p>
                    <p><strong id="supplierName"></strong></p>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Esta accion no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="<?= AppConstants::ROUTE_SUPPLIERS ?>/delete" style="display: inline;" id="deleteForm">
                        <?= Security::renderCsrfField() ?>
                        <input type="hidden" name="id" id="deleteSupplierId">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php $scripts = ['jquery', 'datatables'];
    include __DIR__ . '/../layouts/scripts-advanced.php';
    include __DIR__ . '/../layouts/scripts-base.php'; ?>

    <script>
        $(document).ready(function() {
            $('#suppliersTable').DataTable({
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

        function confirmDelete(id, name) {
            const supplierNameElement = document.getElementById('supplierName');
            if (supplierNameElement) {
                supplierNameElement.textContent = name;
            }
            document.getElementById('deleteSupplierId').value = id;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>

</html>

