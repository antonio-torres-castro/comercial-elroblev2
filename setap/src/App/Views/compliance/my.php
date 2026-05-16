<?php
$success = $data['success'] ?? '';
$error = $data['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cumplimientos - SETAP</title>
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
                <h2 class="mb-0"><i class="bi bi-clipboard-check"></i> Cumplimientos</h2>
                <div class="text-muted small">Lecturas vigentes, aceptaciones y evaluaciones pendientes.</div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="row g-3">
                <?php foreach ($data['items'] as $item): ?>
                    <?php
                    $accepted = !empty($item['fecha_aceptacion']);
                    $approved = !empty($item['aprobado']);
                    $hasScore = $item['puntaje_obtenido'] !== null;
                    $expired = !empty($item['fecha_vencimiento']) && strtotime($item['fecha_vencimiento']) < strtotime(date('Y-m-d'));
                    ?>
                    <div class="col-12 col-xl-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($item['nombre']) ?></h5>
                                        <div class="text-muted small"><?= htmlspecialchars($item['titulo']) ?> · v<?= htmlspecialchars($item['version']) ?></div>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($approved && !$expired): ?>
                                            <span class="badge bg-success">Aprobado</span>
                                        <?php elseif ($hasScore && !$approved): ?>
                                            <span class="badge bg-danger">Reprobado</span>
                                        <?php elseif ($accepted): ?>
                                            <span class="badge bg-warning text-dark">Evaluacion pendiente</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Lectura pendiente</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row mt-3 g-2 small">
                                    <div class="col-md-4"><span class="text-muted">Aceptacion</span><br><?= $accepted ? date('d/m/Y H:i', strtotime($item['fecha_aceptacion'])) : 'Pendiente' ?></div>
                                    <div class="col-md-4"><span class="text-muted">Puntaje</span><br><?= $hasScore ? htmlspecialchars($item['puntaje_obtenido']) . '%' : 'Pendiente' ?></div>
                                    <div class="col-md-4"><span class="text-muted">Vence</span><br><?= !empty($item['fecha_vencimiento']) ? date('d/m/Y', strtotime($item['fecha_vencimiento'])) : '-' ?></div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <a class="btn btn-outline-info btn-sm" href="/setap/compliance/document/<?= (int)$item['version_id'] ?>">
                                        <i class="bi bi-book"></i> Leer
                                    </a>
                                    <?php if ($accepted && !empty($item['requiere_evaluacion']) && !$approved): ?>
                                        <a class="btn btn-setap-primary btn-sm" href="/setap/compliance/evaluation/<?= (int)$item['lectura_id'] ?>">
                                            <i class="bi bi-ui-checks"></i> Iniciar evaluacion
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($data['items'])): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                <i class="bi bi-inbox display-4"></i>
                                <h5 class="mt-3">No hay cumplimientos vigentes</h5>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
</body>

</html>
