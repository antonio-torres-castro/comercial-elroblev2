<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title']); ?> - SETAP</title>
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
    <?php

    use App\Constants\AppConstants;

    include __DIR__ . '/../layouts/navigation.php';
    ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo htmlspecialchars($data['title']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= AppConstants::ROUTE_CLIENTS_CREATE ?>" class="btn btn-sm btn-setap-primary">
                            <i class="bi bi-plus-circle"></i> <?= AppConstants::UI_NEW_CLIENT ?>
                        </a>
                    </div>
                </div>

                <!-- Alertas de mensajes -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        $messages = [
                            'created' => AppConstants::SUCCESS_CLIENT_CREATED,
                            'updated' => AppConstants::SUCCESS_CLIENT_UPDATED,
                            'deleted' => AppConstants::SUCCESS_CLIENT_DELETED
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

                <!-- Filtros de búsqueda -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?= AppConstants::ROUTE_CLIENTS ?>" class="row g-3">
                            <div class="col-md-3">
                                <label for="rut" class="form-label">RUT</label>
                                <input type="text" class="form-control" id="rut" name="rut"
                                    value="<?php echo htmlspecialchars($data['filters']['rut']); ?>"
                                    placeholder="Buscar por RUT">
                            </div>
                            <div class="col-md-4">
                                <label for="razon_social" class="form-label">Razón Social</label>
                                <input type="text" class="form-control" id="razon_social" name="razon_social"
                                    value="<?php echo htmlspecialchars($data['filters']['razon_social']); ?>"
                                    placeholder="Buscar por razón social">
                            </div>
                            <div class="col-md-3">
                                <label for="estado_tipo_id" class="form-label">Estado</label>
                                <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                    <option value="">Todos los estados</option>
                                    <?php foreach ($data['statusTypes'] as $status): ?>
                                        <option value="<?php echo $status['id']; ?>"
                                            <?php echo $data['filters']['estado_tipo_id'] == $status['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status['nombre']); ?>
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
                                    <a href="<?= AppConstants::ROUTE_CLIENTS ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de clientes -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($data['subtitle']); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['clients'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                No se encontraron clientes con los filtros aplicados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="clientsTable">
                                    <thead class="table-setap-primary">
                                        <tr>
                                            <th>RUT</th>
                                            <th>Razón Social</th>
                                            <th>Email</th>
                                            <th>Teléfono</th>
                                            <th>Contrapartes</th>
                                            <th>Estado</th>
                                            <th>Creado</th>
                                            <th width="150">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['clients'] as $client): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($client['rut']): ?>
                                                        <code><?php echo htmlspecialchars($client['rut']); ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($client['razon_social']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($client['email']): ?>
                                                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                                                            <?php echo htmlspecialchars($client['email']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($client['telefono']): ?>
                                                        <a href="tel:<?php echo htmlspecialchars($client['telefono']); ?>">
                                                            <?php echo htmlspecialchars($client['telefono']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo $client['total_contrapartes']; ?> contacto(s)
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClasses = [
                                                        0 => 'bg-warning',   // Creado
                                                        1 => 'bg-success',   // Activo
                                                        2 => 'bg-secondary'  // Inactivo
                                                    ];
                                                    $statusClass = $statusClasses[$client['estado_tipo_id']] ?? 'bg-dark';
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php echo htmlspecialchars($client['estado_nombre']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y', strtotime($client['fecha_Creado'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="/client/<?php echo $client['id']; ?>"
                                                            class="btn btn-outline-setap-primary" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger"
                                                            onclick="confirmDelete(<?php echo $client['id']; ?>, '<?php echo addslashes($client['razon_social']); ?>')"
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

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar al cliente:</p>
                    <p><strong id="clientName"></strong></p>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="<?= AppConstants::ROUTE_CLIENTS ?>/delete" style="display: inline;" id="deleteForm">
                        <?= \App\Helpers\Security::renderCsrfField() ?>
                        <input type="hidden" name="id" id="deleteClientId">
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
    include __DIR__ . '/../layouts/scripts-advanced.php'; ?>
    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>

    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#clientsTable').DataTable({
                language: {
                    url: 'https://cdn.jsdelivr.net/npm/datatables.net-plugins@1.13.6/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [
                    [1, 'asc']
                ], // Ordenar por razón social
                columnDefs: [{
                    targets: [-1], // Última columna (acciones)
                    orderable: false,
                    searchable: false
                }]
            });
        });

        function confirmDelete(id, name) {
            const clientNameElement = document.getElementById('clientName');
            if (clientNameElement) {
                clientNameElement.textContent = name;
            }
            document.getElementById('deleteClientId').value = id;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>

</html>