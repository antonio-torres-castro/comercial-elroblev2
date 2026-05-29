<?php
use App\Constants\AppConstants;
use App\Helpers\Security;
$isAdmin = $data['user']['id'] == 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title']); ?> - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>
<body>
<?php include __DIR__ . '/../layouts/navigation.php'; ?>
<div class="container-fluid mt-4">
    <main class="col-12 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><?= htmlspecialchars($data['title']); ?></h1>
            <a href="<?= AppConstants::ROUTE_SERVICES ?>" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/generate">
                    <?= Security::renderCsrfField() ?>
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-calendar-plus"></i> Datos de planificacion</h5></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php if ($isAdmin): ?>
                                    <div class="col-md-4">
                                        <label class="form-label" for="proveedor_id">Proveedor</label>
                                        <select class="form-select" id="proveedor_id" name="proveedor_id">
                                            <option value="">Seleccione</option>
                                            <?php foreach ($data['suppliers'] as $supplier): ?>
                                                <option value="<?= $supplier['id']; ?>"><?= htmlspecialchars($supplier['razon_social']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" id="proveedor_id" name="proveedor_id" value="<?= htmlspecialchars((string)($data['suppliers'][0]['id'] ?? '')); ?>">
                                <?php endif; ?>
                                <div class="col-md-4">
                                    <label class="form-label" for="cliente_id">Cliente servicio<span class="text-danger">*</span></label>
                                    <select class="form-select" id="cliente_id" name="cliente_id" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($data['clients'] as $client): ?>
                                            <option value="<?= $client['id']; ?>"><?= htmlspecialchars($client['razon_social']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="servicio_version_id">Servicio/version<span class="text-danger">*</span></label>
                                    <select class="form-select" id="servicio_version_id" name="servicio_version_id" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($data['versions'] as $version): ?>
                                            <option value="<?= $version['id']; ?>" data-days="<?= (int)($version['tiempo_estimado_dias'] ?? 0); ?>">
                                                <?= htmlspecialchars($version['servicio_nombre'] . ' v' . $version['version']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="nombre">Nombre instancia</label>
                                    <input class="form-control" id="nombre" name="nombre">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="fecha_inicio">Fecha inicio<span class="text-danger">*</span></label>
                                    <input class="form-control" id="fecha_inicio" name="fecha_inicio" type="date" value="<?= date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Dias requeridos</label>
                                    <input class="form-control" id="diasRequeridos" readonly value="0">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="observaciones">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-kanban"></i> Proyectos operacionales</h5>
                            <span class="badge bg-primary" id="diasDisponibles">0 dias habiles</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-dark"><tr><th></th><th>Proyecto</th><th>Rango</th><th>Dias habiles</th></tr></thead>
                                    <tbody id="projectBody">
                                    <?php foreach ($data['projects'] as $project): ?>
                                        <tr>
                                            <td><input class="form-check-input project-check" type="checkbox" name="proyecto_ids[]" value="<?= $project['id']; ?>" data-days="<?= (int)$project['dias_habiles']; ?>"></td>
                                            <td><?= htmlspecialchars($project['nombre']); ?></td>
                                            <td><?= htmlspecialchars($project['fecha_inicio']); ?> / <?= htmlspecialchars($project['fecha_fin']); ?></td>
                                            <td><?= (int)$project['dias_habiles']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Las tareas reales se crearan en el proyecto cuya fecha cubra cada tarea generada.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a class="btn btn-secondary" href="<?= AppConstants::ROUTE_SERVICES ?>"><i class="bi bi-x-lg"></i> Cancelar</a>
                        <button class="btn btn-setap-primary" type="submit"><i class="bi bi-magic"></i> Generar planificacion</button>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-person-plus"></i> Crear cliente de servicio</h5></div>
                    <div class="card-body">
                        <form method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/client">
                            <?= Security::renderCsrfField() ?>
                            <?php if ($isAdmin): ?>
                                <label class="form-label" for="client_proveedor_id">Proveedor</label>
                                <select class="form-select mb-3" id="client_proveedor_id" name="proveedor_id" required>
                                    <?php foreach ($data['suppliers'] as $supplier): ?>
                                        <option value="<?= $supplier['id']; ?>"><?= htmlspecialchars($supplier['razon_social']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="hidden" name="proveedor_id" value="<?= htmlspecialchars((string)($data['suppliers'][0]['id'] ?? '')); ?>">
                            <?php endif; ?>
                            <label class="form-label" for="razon_social">Razon social</label>
                            <input class="form-control mb-3" id="razon_social" name="razon_social" required>
                            <label class="form-label" for="rut">RUT</label>
                            <input class="form-control mb-3" id="rut" name="rut">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control mb-3" id="email" name="email" type="email">
                            <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-plus-circle"></i> Crear cliente</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
<script>
const baseRoute = '<?= AppConstants::ROUTE_SERVICES ?>';
const providerSelect = document.getElementById('proveedor_id');
const versionSelect = document.getElementById('servicio_version_id');
const clientSelect = document.getElementById('cliente_id');
const projectBody = document.getElementById('projectBody');
const daysInput = document.getElementById('diasRequeridos');
const availableBadge = document.getElementById('diasDisponibles');

function refreshDays() {
    const selected = versionSelect.selectedOptions[0];
    daysInput.value = selected ? (selected.dataset.days || 0) : 0;
    const days = [...document.querySelectorAll('.project-check:checked')].reduce((sum, item) => sum + Number(item.dataset.days || 0), 0);
    availableBadge.textContent = `${days} dias habiles`;
    availableBadge.className = `badge ${days >= Number(daysInput.value || 0) ? 'bg-success' : 'bg-warning'}`;
}

versionSelect.addEventListener('change', refreshDays);
document.addEventListener('change', event => {
    if (event.target.classList.contains('project-check')) refreshDays();
});

if (providerSelect && providerSelect.tagName === 'SELECT') {
    providerSelect.addEventListener('change', async () => {
        if (!providerSelect.value) return;
        const response = await fetch(`${baseRoute}/provider-data?proveedor_id=${providerSelect.value}`);
        const json = await response.json();
        const payload = json.data || {};
        versionSelect.innerHTML = '<option value="">Seleccione</option>';
        (payload.versions || []).forEach(item => {
            const option = new Option(`${item.servicio_nombre} v${item.version}`, item.id);
            option.dataset.days = item.tiempo_estimado_dias || 0;
            versionSelect.add(option);
        });
        clientSelect.innerHTML = '<option value="">Seleccione</option>';
        (payload.clients || []).forEach(item => clientSelect.add(new Option(item.razon_social, item.id)));
        projectBody.innerHTML = '';
        (payload.projects || []).forEach(project => {
            const row = document.createElement('tr');
            row.innerHTML = `<td><input class="form-check-input project-check" type="checkbox" name="proyecto_ids[]" value="${project.id}" data-days="${project.dias_habiles}"></td><td>${project.nombre}</td><td>${project.fecha_inicio} / ${project.fecha_fin}</td><td>${project.dias_habiles}</td>`;
            projectBody.appendChild(row);
        });
        refreshDays();
    });
}
refreshDays();
</script>
</body>
</html>
