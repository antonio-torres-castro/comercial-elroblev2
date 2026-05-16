<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Compliance - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body class="bg-light">
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <main>
            <div class="mb-3">
                <h2 class="mb-0"><i class="bi bi-clock-history"></i> Historia</h2>
                <div class="text-muted small">Trazabilidad de lecturas, aceptaciones, evaluaciones y logs.</div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label" for="fecha_inicio">Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($data['filters']['fecha_inicio']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="fecha_fin">Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($data['filters']['fecha_fin']) ?>">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-setap-primary" type="submit"><i class="bi bi-search"></i> Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header bg-white"><strong>Cumplimiento usuarios</strong></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Cumplimiento</th>
                                            <th>Aceptacion</th>
                                            <th>Puntaje</th>
                                            <th>Estado</th>
                                            <th>Vencimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['history'] as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nombre_completo'] ?? $row['nombre_usuario']) ?></td>
                                                <td><?= htmlspecialchars($row['cumplimiento']) ?> <span class="text-muted small">v<?= htmlspecialchars($row['version']) ?></span></td>
                                                <td><?= !empty($row['fecha_aceptacion']) ? date('d/m/Y H:i', strtotime($row['fecha_aceptacion'])) : '-' ?></td>
                                                <td><?= $row['puntaje_obtenido'] !== null ? htmlspecialchars($row['puntaje_obtenido']) . '%' : '-' ?></td>
                                                <td><span class="badge <?= !empty($row['aprobado']) ? 'bg-success' : 'bg-secondary' ?>"><?= !empty($row['aprobado']) ? 'Aprobado' : 'Pendiente/Reprobado' ?></span></td>
                                                <td><?= !empty($row['fecha_vencimiento']) ? date('d/m/Y', strtotime($row['fecha_vencimiento'])) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($data['history'])): ?>
                                            <tr><td colspan="6" class="text-center text-muted">Sin registros en el periodo</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header bg-white"><strong>Logs Compliance</strong></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>Fecha</th><th>Accion</th><th>Usuario</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($data['logs'] as $log): ?>
                                            <tr>
                                                <td class="small"><?= !empty($log['fecha']) ? date('d/m H:i', strtotime($log['fecha'])) : '-' ?></td>
                                                <td class="small"><?= htmlspecialchars($log['accion']) ?></td>
                                                <td class="small"><?= htmlspecialchars($log['nombre_completo'] ?? $log['nombre_usuario'] ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($data['logs'])): ?>
                                            <tr><td colspan="3" class="text-center text-muted">Sin logs</td></tr>
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

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
</body>

</html>
