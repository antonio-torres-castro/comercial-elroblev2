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
            </main>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
</body>

</html>