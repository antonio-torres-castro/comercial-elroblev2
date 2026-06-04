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
                <a href="<?= AppConstants::ROUTE_SERVICES_CATALOG ?>" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>

            <?php if (!empty($data['errors'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($data['errors'] as $error): ?><li><?= htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <form id="serviceForm" method="POST" action="<?= AppConstants::ROUTE_SERVICES ?>/store">
                        <?= Security::renderCsrfField() ?>
                        <input type="hidden" name="service_processes_json" id="serviceProcessesJson" value="[]">
                        <input type="hidden" name="activo" value="1">

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Definicion comercial</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <?php if ($isAdmin): ?>
                                        <div class="col-md-4">
                                            <label class="form-label" for="proveedor_id">Proveedor<span class="text-danger">*</span></label>
                                            <select class="form-select" id="proveedor_id" name="proveedor_id" required>
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
                                        <label class="form-label" for="servicio_tipo_id">Tipo<span class="text-danger">*</span></label>
                                        <select class="form-select" id="servicio_tipo_id" name="servicio_tipo_id" required>
                                            <option value="">Seleccione</option>
                                            <?php foreach ($data['types'] as $type): ?>
                                                <option value="<?= $type['id']; ?>"><?= htmlspecialchars($type['nombre']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="codigo">Codigo</label>
                                        <input class="form-control" id="codigo" name="codigo" maxlength="50">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="nombre">Servicio<span class="text-danger">*</span></label>
                                        <input class="form-control" id="nombre" name="nombre" maxlength="150" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="descripcion">Descripcion</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-layers"></i> Version inicial</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" for="nombre_version">Nombre version</label>
                                        <input class="form-control" id="nombre_version" name="nombre_version" value="Version 1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="precio_base">Precio base</label>
                                        <input class="form-control" id="precio_base" name="precio_base" type="number" min="0" step="0.01">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="tiempo_estimado_dias">Dias requeridos<span class="text-danger">*</span></label>
                                        <input class="form-control" id="tiempo_estimado_dias" name="tiempo_estimado_dias" type="number" min="1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="vigente_desde">Vigente desde</label>
                                        <input class="form-control" id="vigente_desde" name="vigente_desde" type="date" value="<?= date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="vigente_hasta">Vigente hasta</label>
                                        <input class="form-control" id="vigente_hasta" name="vigente_hasta" type="date">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Procesos del servicio</h5>
                                <span class="badge bg-primary" id="processCount">0 procesos</span>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label" for="processSelect">Proceso</label>
                                        <select class="form-select" id="processSelect">
                                            <option value="">Seleccione</option>
                                            <?php foreach ($data['processes'] as $process): ?>
                                                <option value="<?= $process['id']; ?>"><?= htmlspecialchars($process['nombre']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" for="processOffset">Dia inicio</label>
                                        <input class="form-control" id="processOffset" type="number" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" for="processRequired">Obligatorio</label>
                                        <select class="form-select" id="processRequired">
                                            <option value="1">Si</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-setap-primary" id="addProcessBtn"><i class="bi bi-plus-circle"></i> Agregar</button>
                                    </div>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Orden</th>
                                                <th>Proceso</th>
                                                <th>Dia inicio</th>
                                                <th>Obligatorio</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="processBody"></tbody>
                                    </table>
                                </div>
                                <div class="text-muted small">Insumos y activos se pueden cargar como texto simple por proceso usando los botones de recursos.</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-secondary" href="<?= AppConstants::ROUTE_SERVICES_CATALOG ?>"><i class="bi bi-x-lg"></i> Cancelar</a>
                            <button class="btn btn-setap-primary" type="submit"><i class="bi bi-check-circle"></i> Guardar Servicio</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script>
        const processes = [];
        const providerSelect = document.getElementById('proveedor_id');
        const typeSelect = document.getElementById('servicio_tipo_id');
        const processSelect = document.getElementById('processSelect');
        const body = document.getElementById('processBody');
        const hidden = document.getElementById('serviceProcessesJson');

        function renderProcesses() {
            body.innerHTML = '';
            processes.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${index + 1}</td><td>${item.nombre}</td><td>${item.dias_desde_inicio}</td><td>${item.obligatorio ? 'Si' : 'No'}</td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-index="${index}"><i class="bi bi-trash"></i></button></td>`;
                body.appendChild(row);
            });
            document.getElementById('processCount').textContent = `${processes.length} procesos`;
            hidden.value = JSON.stringify(processes.map((item, index) => ({
                proveedor_proceso_id: item.proveedor_proceso_id,
                orden_ejecucion: index + 1,
                dias_desde_inicio: item.dias_desde_inicio,
                obligatorio: item.obligatorio,
                insumos: [],
                activos: []
            })));
        }

        document.getElementById('addProcessBtn').addEventListener('click', () => {
            const option = processSelect.selectedOptions[0];
            if (!option || !option.value) return;
            processes.push({
                proveedor_proceso_id: Number(option.value),
                nombre: option.textContent.trim(),
                dias_desde_inicio: Number(document.getElementById('processOffset').value || 0),
                obligatorio: document.getElementById('processRequired').value === '1'
            });
            renderProcesses();
        });

        body.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-index]');
            if (!button) return;
            processes.splice(Number(button.dataset.index), 1);
            renderProcesses();
        });

        const serviceBaseRoute = '<?= AppConstants::ROUTE_SERVICES ?>';
        if (providerSelect && providerSelect.tagName === 'SELECT') {
            providerSelect.addEventListener('change', async () => {
                if (!providerSelect.value) return;
                const response = await fetch(`${serviceBaseRoute}/provider-data?proveedor_id=${providerSelect.value}`);
                const json = await response.json();
                const payload = json.data || {};
                typeSelect.innerHTML = '<option value="">Seleccione</option>';
                (payload.types || []).forEach(type => typeSelect.add(new Option(type.nombre, type.id)));
                processSelect.innerHTML = '<option value="">Seleccione</option>';
                (payload.processes || []).forEach(process => processSelect.add(new Option(process.nombre, process.id)));
            });
        }
    </script>
</body>

</html>