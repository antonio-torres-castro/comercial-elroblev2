<?php

use App\Helpers\Security;

$success = $data['success'] ?? '';
$error = $data['error'] ?? '';

function compliance_state_badge(int $stateId, string $name): string
{
    $class = match ($stateId) {
        1 => 'bg-secondary',
        2 => 'bg-success',
        3 => 'bg-warning text-dark',
        4 => 'bg-danger',
        default => 'bg-light text-dark',
    };
    return '<span class="badge ' . $class . '">' . htmlspecialchars($name) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Cumplimientos - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body class="bg-light">
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="mb-0"><i class="bi bi-person-check"></i> Administrar Cumplimientos</h2>
                    <div class="text-muted small">Documentos, versiones, evaluaciones y trazabilidad operacional.</div>
                </div>
                <?php if (Security::hasPermission('Create')): ?>
                    <button type="button" class="btn btn-setap-primary" data-bs-toggle="modal" data-bs-target="#createDocumentModal">
                        <i class="bi bi-plus-lg"></i> Nuevo Cumplimiento
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label" for="search">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Nombre, codigo o descripcion">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="estado_tipo_id">Estado</label>
                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id">
                                <option value="">Todos</option>
                                <option value="1" <?= ($_GET['estado_tipo_id'] ?? '') == '1' ? 'selected' : '' ?>>Creado</option>
                                <option value="2" <?= ($_GET['estado_tipo_id'] ?? '') == '2' ? 'selected' : '' ?>>Activo</option>
                                <option value="3" <?= ($_GET['estado_tipo_id'] ?? '') == '3' ? 'selected' : '' ?>>Inactivo</option>
                                <option value="4" <?= ($_GET['estado_tipo_id'] ?? '') == '4' ? 'selected' : '' ?>>Eliminado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_deleted" name="include_deleted" value="1" <?= isset($_GET['include_deleted']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="include_deleted">Incluir eliminados</label>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-outline-setap-primary" type="submit"><i class="bi bi-search"></i></button>
                            <a class="btn btn-outline-secondary" href="/setap/compliance"><i class="bi bi-x-lg"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-setap-primary">
                                        <tr>
                                            <th>Cumplimiento</th>
                                            <th>Version vigente</th>
                                            <th>Evaluacion</th>
                                            <th>Estado</th>
                                            <th>Usuarios</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['documents'])): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No hay cumplimientos registrados</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($data['documents'] as $document): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($document['nombre']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($document['codigo'] ?? '') ?> <?= htmlspecialchars($document['descripcion'] ?? '') ?></div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($document['version_id'])): ?>
                                                        <span class="badge bg-info text-dark">v<?= htmlspecialchars($document['version']) ?></span>
                                                        <div class="small"><?= htmlspecialchars($document['titulo']) ?></div>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Sin version publicada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small"><?= !empty($document['requiere_evaluacion']) ? 'Requerida' : 'No requerida' ?></div>
                                                    <div class="text-muted small"><?= (int)$document['preguntas_activas'] ?>/<?= (int)$document['cantidad_preguntas'] ?> preguntas, minimo <?= htmlspecialchars($document['puntaje_minimo']) ?>%</div>
                                                </td>
                                                <td><?= compliance_state_badge((int)$document['estado_tipo_id'], $document['estado_nombre']) ?></td>
                                                <td><span class="badge bg-secondary"><?= (int)$document['lecturas_count'] ?></span></td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if (!empty($document['version_id'])): ?>
                                                            <a href="/setap/compliance/document/<?= (int)$document['version_id'] ?>" class="btn btn-outline-info" title="Previsualizar"><i class="bi bi-eye"></i></a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-outline-setap-primary" title="Editar" onclick='fillEditForm(<?= json_encode($document, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
                                                        <button type="button" class="btn btn-outline-secondary" title="Nueva version" onclick="fillVersionForm(<?= (int)$document['id'] ?>, '<?= htmlspecialchars($document['nombre'], ENT_QUOTES) ?>')"><i class="bi bi-files"></i></button>
                                                        <?php if (!empty($document['version_id'])): ?>
                                                            <a class="btn btn-outline-warning" href="/setap/compliance/assessments?version_id=<?= (int)$document['version_id'] ?>" title="Preguntas"><i class="bi bi-ui-checks"></i></a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-outline-dark" title="Estado" onclick="fillStatusForm(<?= (int)$document['id'] ?>, '<?= htmlspecialchars($document['nombre'], ENT_QUOTES) ?>', <?= (int)$document['estado_tipo_id'] ?>)"><i class="bi bi-arrow-repeat"></i></button>
                                                        <button type="button" class="btn btn-outline-danger" title="Limpiar flujo usuarios" onclick="fillCleanupForm(<?= (int)$document['id'] ?>, '<?= htmlspecialchars($document['nombre'], ENT_QUOTES) ?>')"><i class="bi bi-eraser"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <strong><i class="bi bi-people"></i> Avance de usuarios</strong>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Cumplimiento</th>
                                            <th>Lectura</th>
                                            <th>Aceptacion</th>
                                            <th>Evaluacion</th>
                                            <th>Vencimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($data['assignments'], 0, 100) as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nombre_completo'] ?? $row['nombre_usuario']) ?></td>
                                                <td><?= htmlspecialchars($row['cumplimiento']) ?></td>
                                                <td><?= !empty($row['fecha_inicio_lectura']) ? date('d/m/Y H:i', strtotime($row['fecha_inicio_lectura'])) : '<span class="text-muted">Pendiente</span>' ?></td>
                                                <td><?= !empty($row['fecha_aceptacion']) ? date('d/m/Y H:i', strtotime($row['fecha_aceptacion'])) : '<span class="text-muted">Pendiente</span>' ?></td>
                                                <td>
                                                    <?php if ($row['puntaje_obtenido'] !== null): ?>
                                                        <span class="badge <?= !empty($row['aprobado']) ? 'bg-success' : 'bg-danger' ?>"><?= htmlspecialchars($row['puntaje_obtenido']) ?>%</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= !empty($row['fecha_vencimiento']) ? date('d/m/Y', strtotime($row['fecha_vencimiento'])) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($data['assignments'])): ?>
                                            <tr><td colspan="6" class="text-center text-muted">Sin usuarios asociados al proveedor</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="createDocumentModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="/setap/compliance/store">
                <div class="modal-header"><h5 class="modal-title">Nuevo Cumplimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= Security::renderCsrfField() ?>
                    <?php include __DIR__ . '/partials/document-form.php'; ?>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-setap-primary">Guardar</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editDocumentModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="/setap/compliance/update">
                <div class="modal-header"><h5 class="modal-title">Editar Cumplimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= Security::renderCsrfField() ?>
                    <input type="hidden" name="document_id" id="edit_document_id">
                    <div class="alert alert-info small">Solo se guardan cambios si el cumplimiento esta en estado creado y sin datos de usuarios asociados.</div>
                    <?php $prefix = 'edit_'; include __DIR__ . '/partials/document-basic-form.php'; unset($prefix); ?>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-setap-primary">Guardar cambios</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="versionModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="/setap/compliance/version">
                <div class="modal-header"><h5 class="modal-title">Nueva version</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= Security::renderCsrfField() ?>
                    <input type="hidden" name="document_id" id="version_document_id">
                    <div class="alert alert-secondary small" id="version_document_name"></div>
                    <?php include __DIR__ . '/partials/version-form.php'; ?>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-setap-primary">Crear version</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="/setap/compliance/status">
                <div class="modal-header"><h5 class="modal-title">Cambiar estado</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= Security::renderCsrfField() ?>
                    <input type="hidden" name="document_id" id="status_document_id">
                    <div class="mb-3 fw-semibold" id="status_document_name"></div>
                    <label class="form-label" for="status_estado_tipo_id">Nuevo estado</label>
                    <select class="form-select" id="status_estado_tipo_id" name="estado_tipo_id" required>
                        <option value="1">Creado</option>
                        <option value="2">Activo</option>
                        <option value="3">Inactivo</option>
                        <option value="4">Eliminado</option>
                    </select>
                    <div class="form-text">El cambio de estado se guarda como transaccion independiente.</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-setap-primary">Cambiar estado</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="cleanupModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="/setap/compliance/cleanup-flow">
                <div class="modal-header"><h5 class="modal-title">Limpiar datos de usuarios</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= Security::renderCsrfField() ?>
                    <input type="hidden" name="document_id" id="cleanup_document_id">
                    <p>Se eliminaran lecturas y respuestas de usuario asociadas a <strong id="cleanup_document_name"></strong>. La traza quedara en usuario_logs.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Eliminar flujo</button></div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script>
        function fillEditForm(documentData) {
            document.getElementById('edit_document_id').value = documentData.id;
            ['nombre', 'codigo', 'descripcion', 'puntaje_minimo', 'cantidad_preguntas', 'vigencia_dias'].forEach((field) => {
                const input = document.getElementById('edit_' + field);
                if (input) input.value = documentData[field] ?? '';
            });
            document.getElementById('edit_requiere_evaluacion').checked = Number(documentData.requiere_evaluacion) === 1;
            new bootstrap.Modal(document.getElementById('editDocumentModal')).show();
        }

        function fillVersionForm(id, name) {
            document.getElementById('version_document_id').value = id;
            document.getElementById('version_document_name').textContent = name;
            new bootstrap.Modal(document.getElementById('versionModal')).show();
        }

        function fillStatusForm(id, name, stateId) {
            document.getElementById('status_document_id').value = id;
            document.getElementById('status_document_name').textContent = name;
            document.getElementById('status_estado_tipo_id').value = stateId;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        function fillCleanupForm(id, name) {
            document.getElementById('cleanup_document_id').value = id;
            document.getElementById('cleanup_document_name').textContent = name;
            new bootstrap.Modal(document.getElementById('cleanupModal')).show();
        }
    </script>
</body>

</html>
