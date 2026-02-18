<?php

use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
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
                        <a href="javascript:history.back()" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK ?>
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?= $data['subtitle']; ?></h5>
                            </div>
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <h6><?= htmlspecialchars($data['task']['tarea_descripcion'] ?? '') ?></h6>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" id="proyecto_nombre" placeholder="" value="<?= $data['task']['proyecto_nombre'] ?>" readonly>
                                                    <label for="proyecto_nombre" class="form-label">Proyecto</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" id="tipo_tarea" placeholder="" value="<?= $data['task']['tipo_tarea'] ?>" readonly>
                                                    <label for="tipo_tarea" class="form-label">Tipo Tarea</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" id="fecha_inicio" name="fecha_inicio" readonly
                                                        value="<?= date('Y-m-d', strtotime($data['task']['fecha_inicio'])) ?>">
                                                    <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" id="fecha_fin" name="fecha_fin" readonly
                                                        value="<?= date('Y-m-d', strtotime($data['task']['fecha_fin'])) ?>">
                                                    <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" id="estado" placeholder="" value="<?= $data['task']['estado'] ?>" readonly>
                                                    <label for="estado" class="form-label">Estado</label>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" id="ejecutor_nombre" placeholder="" value="<?= $data['task']['ejecutor_nombre'] ?>" readonly>
                                                    <label for="ejecutor_nombre" class="form-label">Ejecutor</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" id="supervisor_nombre" placeholder="" value="<?= $data['task']['supervisor_nombre'] ?>" readonly>
                                                    <label for="supervisor_nombre" class="form-label">Supervisor</label>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial de Cambios -->
                <?php if (!empty($data['task_history'])): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Cambios</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Fecha Evento</th>
                                                <th>Usuario</th>
                                                <th>Cambio de Estado</th>
                                                <th>Supervisor</th>
                                                <th class="text-center">Detalle</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data['task_history'] as $index => $history): ?>
                                            <tr class="history-row">
                                                <td class="ps-4">
                                                    <?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($history['fecha_evento']))) ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($history['usuario_email'] ?? 'N/A') ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $estadoAnterior = $history['estado_anterior'] ?? 'N/A';
                                                        $estadoNuevo = $history['estado_nuevo'] ?? 'N/A';
                                                        echo htmlspecialchars($estadoAnterior) . ' <i class="bi bi-arrow-right text-primary"></i> ' . htmlspecialchars($estadoNuevo);
                                                    ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($history['supervisor_email'] ?? 'N/A') ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                        $hasComment = !empty(trim($history['comentario'] ?? ''));
                                                        $photos = $history['fotos'] ?? [];
                                                        $hasPhotos = !empty($photos);
                                                    ?>
                                                    <?php if ($hasComment): ?>
                                                    <button class="btn btn-sm btn-outline-primary toggle-comment mb-1" type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#comment-<?= $index ?>"
                                                            aria-expanded="false"
                                                            aria-controls="comment-<?= $index ?>">
                                                        <i class="bi bi-chat-text"></i> Comentario
                                                    </button>
                                                    <?php endif; ?>

                                                    <?php if ($hasPhotos): ?>
                                                    <button class="btn btn-sm btn-outline-success toggle-photos mb-1" type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#photos-<?= $index ?>"
                                                            aria-expanded="false"
                                                            aria-controls="photos-<?= $index ?>">
                                                        <i class="bi bi-images"></i> Evidencias (<?= count($photos) ?>)
                                                    </button>
                                                    <?php endif; ?>

                                                    <?php if (!$hasComment && !$hasPhotos): ?>
                                                    <span class="text-muted small">Sin detalle</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php if ($hasComment): ?>
                                            <tr class="history-comment-row">
                                                <td colspan="5" class="p-0 border-0">
                                                    <div class="collapse" id="comment-<?= $index ?>">
                                                        <div class="p-3 bg-light border-start border-primary border-3">
                                                            <strong class="text-muted">Comentario:</strong>
                                                            <p class="mb-0 mt-1"><?= htmlspecialchars($history['comentario']) ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>

                                            <?php if ($hasPhotos): ?>
                                            <tr class="history-photos-row">
                                                <td colspan="5" class="p-0 border-0">
                                                    <div class="collapse" id="photos-<?= $index ?>">
                                                        <div class="p-3 bg-light border-start border-success border-3">
                                                            <strong class="text-muted d-block mb-2">Evidencia fotográfica:</strong>
                                                            <div class="row g-3">
                                                                <?php foreach ($photos as $photo): ?>
                                                                <div class="col-sm-6 col-md-4 col-lg-3">
                                                                    <div class="card h-100 shadow-sm">
                                                                        <a href="<?= htmlspecialchars($photo['url_foto']) ?>" target="_blank" rel="noopener noreferrer">
                                                                            <img src="<?= htmlspecialchars($photo['url_foto']) ?>"
                                                                                class="card-img-top"
                                                                                alt="Evidencia de cambio de estado"
                                                                                style="height: 180px; object-fit: cover;">
                                                                        </a>
                                                                        <div class="card-body p-2">
                                                                            <small class="text-muted d-block">
                                                                                <?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($photo['fecha_Creado'] ?? 'now'))) ?>
                                                                            </small>
                                                                            <?php if (!empty($photo['estado_nombre'])): ?>
                                                                            <small class="badge text-bg-secondary mt-1">
                                                                                <?= htmlspecialchars($photo['estado_nombre']) ?>
                                                                            </small>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
</body>

</html>
