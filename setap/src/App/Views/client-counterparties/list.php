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
                        <a href="/client-counterpartie" class="btn btn-sm btn-setap-primary">
                            <i class="bi bi-plus-circle"></i> Nueva Contraparte
                        </a>
                    </div>
                </div>

                <!-- Mensajes de éxito/error -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        switch ($_GET['success']) {
                            case 'created':
                                echo 'Contraparte creada exitosamente.';
                                break;
                            case 'updated':
                                echo 'Contraparte actualizada exitosamente.';
                                break;
                            case 'deleted':
                                echo 'Contraparte eliminada exitosamente.';
                                break;
                            default:
                                echo 'Operación realizada exitosamente.';
                        }
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
                        <h5 class="mb-0">
                            <i class="bi bi-funnel"></i> Filtros de Búsqueda
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="/client-counterparties">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="cliente_id" class="form-label">Cliente</label>
                                    <select class="form-select" id="cliente_id" name="cliente_id">
                                        <option value="">Todos los clientes</option>
                                        <?php foreach ($data['clients'] as $client): ?>
                                            <option value="<?php echo $client['id']; ?>"
                                                <?php echo ($data['filters']['cliente_id'] == $client['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($client['razon_social']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="persona_nombre" class="form-label">Nombre de Persona</label>
                                    <input type="text" class="form-control" id="persona_nombre" name="persona_nombre"
                                        placeholder="Buscar por nombre..."
                                        value="<?php echo htmlspecialchars($data['filters']['persona_nombre']); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="cargo" class="form-label">Cargo</label>
                                    <input type="text" class="form-control" id="cargo" name="cargo"
                                        placeholder="Buscar cargo..."
                                        value="<?php echo htmlspecialchars($data['filters']['cargo']); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="estado_tipo_id" class="form-label">Estado</label>
                                    <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                        <option value="">Todos</option>
                                        <?php foreach ($data['statusTypes'] as $status): ?>
                                            <option value="<?php echo $status['id']; ?>"
                                                <?php echo ($data['filters']['estado_tipo_id'] == $status['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                    <a href="/client-counterparties" class="btn btn-secondary">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de contrapartes -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-people"></i> <?php echo $data['subtitle']; ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($data['counterparties']); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($data['counterparties'])): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <h5 class="mt-3 text-muted">No se encontraron contrapartes</h5>
                                <p class="text-muted">No hay contrapartes que coincidan con los filtros aplicados.</p>
                                <a href="/client-counterpartie" class="btn btn-setap-primary">
                                    <i class="bi bi-plus-circle"></i> Crear Primera Contraparte
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Persona</th>
                                            <th>RUT</th>
                                            <th>Cargo</th>
                                            <th>Teléfono</th>
                                            <th>Email</th>
                                            <th>Estado</th>
                                            <th>Fecha Creación</th>
                                            <th width="100">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['counterparties'] as $counterpartie): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($counterpartie['cliente_nombre']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($counterpartie['persona_nombre']); ?></td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($counterpartie['persona_rut']); ?></code>
                                                </td>
                                                <td>
                                                    <?php echo $counterpartie['cargo'] ? htmlspecialchars($counterpartie['cargo']) : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $telefono = $counterpartie['telefono'] ?: $counterpartie['persona_telefono'];
                                                    echo $telefono ? htmlspecialchars($telefono) : '<span class="text-muted">-</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php echo $counterpartie['email'] ? htmlspecialchars($counterpartie['email']) : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badgeClass = 'secondary';
                                                    switch ($counterpartie['estado_tipo_id']) {
                                                        case 1:
                                                            $badgeClass = 'warning';
                                                            break; // creado
                                                        case 2:
                                                            $badgeClass = 'success';
                                                            break; // activo
                                                        case 3:
                                                            $badgeClass = 'secondary';
                                                            break; // inactivo
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                                        <?php echo htmlspecialchars($counterpartie['estado_nombre']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y', strtotime($counterpartie['fecha_Creado'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="/client-counterpartie/<?php echo $counterpartie['id']; ?>"
                                                            class="btn btn-outline-primary" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger"
                                                            title="Eliminar" data-bs-toggle="modal"
                                                            data-bs-target="#deleteModal<?php echo $counterpartie['id']; ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Modal de eliminación -->
                                            <div class="modal fade" id="deleteModal<?php echo $counterpartie['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>¿Estás seguro de que deseas eliminar la contraparte?</p>
                                                            <div class="alert alert-warning">
                                                                <strong>Cliente:</strong> <?php echo htmlspecialchars($counterpartie['cliente_nombre']); ?><br>
                                                                <strong>Persona:</strong> <?php echo htmlspecialchars($counterpartie['persona_nombre']); ?><br>
                                                                <strong>Cargo:</strong> <?php echo htmlspecialchars($counterpartie['cargo'] ?: 'Sin cargo'); ?>
                                                            </div>
                                                            <p><small class="text-muted">Esta acción no se puede deshacer.</small></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                Cancelar
                                                            </button>
                                                            <form method="POST" action="/client-counterpartie/delete" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo \App\Helpers\Security::generateCsrfToken(); ?>">
                                                                <input type="hidden" name="id" value="<?php echo $counterpartie['id']; ?>">
                                                                <button type="submit" class="btn btn-danger">
                                                                    <i class="bi bi-trash"></i> Eliminar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
</body>

</html>