<?php

use App\Helpers\Security;
use App\Constants\AppConstants; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title']; ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="javascript:history.back()" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK ?>
                        </a>
                    </div>
                </div>

                <?php if (isset($data['error']) && !empty($data['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="bi bi-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
                        <p class="mb-0"><?= htmlspecialchars($data['error']); ?></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <form id="createTaskByProcessForm" method="POST" action="<?= AppConstants::ROUTE_TASKS ?>/storeByProcess">
                            <?= Security::renderCsrfField() ?>

                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-gear-wide-connected"></i> Selección de Proceso</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- Proveedor (Solo Admin) -->
                                        <?php if ($data['user']['usuario_tipo_id'] == 1): ?>
                                            <div class="col-md-4">
                                                <label for="proveedor_id" class="form-label">Proveedor</label>
                                                <select class="form-select" id="proveedor_id" name="proveedor_id">
                                                    <option value="">Seleccionar proveedor...</option>
                                                    <?php foreach ($data['suppliers'] as $supplier): ?>
                                                        <option value="<?= $supplier['id']; ?>" <?= ($data['provider_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($supplier['nombre']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php else: ?>
                                            <input type="hidden" id="proveedor_id" name="proveedor_id" value="<?= $data['provider_id']; ?>">
                                        <?php endif; ?>

                                        <!-- Proyecto -->
                                        <div class="<?= ($data['user']['usuario_tipo_id'] == 1) ? 'col-md-4' : 'col-md-6'; ?>">
                                            <label for="proyecto_id" class="form-label">Proyecto <span class="text-danger">*</span></label>
                                            <select class="form-select" id="proyecto_id" name="proyecto_id" required>
                                                <option value="">Seleccionar proyecto...</option>
                                                <?php foreach ($data['projects'] as $project): ?>
                                                    <option value="<?= $project['id']; ?>" <?= ($data['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($project['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="<?= ($data['user']['usuario_tipo_id'] == 1) ? 'col-md-4' : 'col-md-6'; ?>">
                                            <label for="direccion_id" class="form-label">Dirección <span class="text-danger">*</span></label>
                                            <select class="form-select" id="direccion_id" name="direccion_id" required>
                                                <option value="">Seleccionar dirección...</option>
                                                <?php foreach ($data['projectAdresses'] as $pa): ?>
                                                    <option value="<?= (int)$pa['id'] ?>"
                                                        <?= $pa['id'] == $data['task']['direccion_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($pa['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Proceso -->
                                        <div class="col-md-10">
                                            <label for="proceso_id" class="form-label">Proceso <span class="text-danger">*</span></label>
                                            <select class="form-select" id="proceso_id" name="proceso_id" required>
                                                <option value="">Seleccionar proceso...</option>
                                                <?php foreach ($data['processes'] as $process): ?>
                                                    <option value="<?= $process['id']; ?>">
                                                        <?= htmlspecialchars($process['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-info w-100" id="btnVerTareas" disabled data-bs-toggle="modal" data-bs-target="#tasksModal">
                                                <i class="bi bi-eye"></i> Ver Tareas
                                            </button>
                                        </div>

                                        <!-- Supervisor -->
                                        <div class="col-md-6">
                                            <label for="supervisor_id" class="form-label">Supervisor <span class="text-danger">*</span></label>
                                            <select class="form-select" id="supervisor_id" name="supervisor_id" required>
                                                <option value="">Seleccionar supervisor...</option>
                                                <?php foreach ($data['supervisor_users'] as $user): ?>
                                                    <option value="<?= $user['id']; ?>">
                                                        <?= htmlspecialchars($user['nombre_completo'] . ' (' . $user['nombre_usuario'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Estado -->
                                        <div class="col-md-6">
                                            <label for="estado_tipo_id" class="form-label">Estado Inicial <span class="text-danger">*</span></label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                                <?php foreach ($data['taskStates'] as $state): ?>
                                                    <option value="<?= $state['id']; ?>" <?= ($state['id'] == 2) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($state['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Programación</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-12">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" onclick="selOpt(event, 'masivo')" name="optionOcurrencia" id="iorMasivo" value="1" checked>
                                                <label class="form-check-label" for="iorMasivo">Masivo</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" onclick="selOpt(event, 'especifico')" name="optionOcurrencia" id="iorEspecifico" value="2">
                                                <label class="form-check-label" for="iorEspecifico">Específico</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" onclick="selOpt(event, 'rango')" name="optionOcurrencia" id="iorRango" value="3">
                                                <label class="form-check-label" for="iorRango">Rango</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" onclick="selOpt(event, 'intervalo')" name="optionOcurrencia" id="iorIntervalo" value="4">
                                                <label class="form-check-label" for="iorIntervalo">Intervalo</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recurrence Tabs (Hidden nav but controlled by radios) -->
                                    <ul class="nav nav-tabs d-none" id="ocurrenciaTabs" role="tablist">
                                        <li class="nav-item"><button class="nav-link active" id="masivo-tab" data-bs-toggle="tab" data-bs-target="#masivo" type="button"></button></li>
                                        <li class="nav-item"><button class="nav-link" id="especifico-tab" data-bs-toggle="tab" data-bs-target="#especifico" type="button"></button></li>
                                        <li class="nav-item"><button class="nav-link" id="rango-tab" data-bs-toggle="tab" data-bs-target="#rango" type="button"></button></li>
                                        <li class="nav-item"><button class="nav-link" id="intervalo-tab" data-bs-toggle="tab" data-bs-target="#intervalo" type="button"></button></li>
                                    </ul>

                                    <div class="tab-content" id="ocurrenciaTabContent">
                                        <!-- Masivo -->
                                        <div class="tab-pane fade show active" id="masivo" role="tabpanel">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="fecha_inicio_masivo" class="form-label">Fecha Inicio *</label>
                                                    <input type="date" class="form-control" id="fecha_inicio_masivo" name="fecha_inicio_masivo" value="<?= date('Y-m-d'); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="fecha_fin_masivo" class="form-label">Fecha Fin *</label>
                                                    <input type="date" class="form-control" id="fecha_fin_masivo" name="fecha_fin_masivo" value="<?= date('Y-12-31'); ?>" required>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Días de la semana *</label>
                                                    <div class="d-flex flex-wrap gap-3">
                                                        <?php $dias = ['Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3, 'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6, 'Domingo' => 0];
                                                        foreach ($dias as $nombre => $valor): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias[]" value="<?= $valor ?>" id="dia_<?= $valor ?>">
                                                                <label class="form-check-label" for="dia_<?= $valor ?>"><?= $nombre ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Especifico -->
                                        <div class="tab-pane fade" id="especifico" role="tabpanel">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="fecha_especifica_inicio" class="form-label">Fecha *</label>
                                                    <input type="date" class="form-control" id="fecha_especifica_inicio" name="fecha_especifica_inicio" value="<?= date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Rango -->
                                        <div class="tab-pane fade" id="rango" role="tabpanel">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="fecha_inicio_rango" class="form-label">Fecha Inicio *</label>
                                                    <input type="date" class="form-control" id="fecha_inicio_rango" name="fecha_inicio_rango" value="<?= date('Y-m-d'); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="fecha_fin_rango" class="form-label">Fecha Fin *</label>
                                                    <input type="date" class="form-control" id="fecha_fin_rango" name="fecha_fin_rango" value="<?= date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Intervalo -->
                                        <div class="tab-pane fade" id="intervalo" role="tabpanel">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="fecha_inicio_intervalo" class="form-label">Fecha Inicio *</label>
                                                    <input type="date" class="form-control" id="fecha_inicio_intervalo" name="fecha_inicio_intervalo" value="<?= date('Y-m-d'); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="fecha_fin_intervalo" class="form-label">Fecha Fin *</label>
                                                    <input type="date" class="form-control" id="fecha_fin_intervalo" name="fecha_fin_intervalo" value="<?= date('Y-12-31'); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="intervalo_dias" class="form-label">Cada N días *</label>
                                                    <input type="number" class="form-control" id="intervalo_dias" name="intervalo_dias" min="1" value="1">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="duracion_bloque_dias" class="form-label">Duración del bloque (días) *</label>
                                                    <input type="number" class="form-control" id="duracion_bloque_dias" name="duracion_bloque_dias" min="1" value="1">
                                                </div>
                                                <div class="col-md-4 d-flex align-items-end">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="ajustar_feriados" name="ajustar_feriados" value="1" checked>
                                                        <label class="form-check-label" for="ajustar_feriados">Ajustar a día hábil</label>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Días de la semana (Opcional)</label>
                                                    <div class="d-flex flex-wrap gap-3">
                                                        <?php foreach ($dias as $nombre => $valor): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias_intervalo[]" value="<?= $valor ?>" id="dia_int_<?= $valor ?>">
                                                                <label class="form-check-label" for="dia_int_<?= $valor ?>"><?= $nombre ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4 mb-5">
                                <a href="javascript:history.back()" class="btn btn-secondary">
                                    <i class="bi bi-x-lg"></i> <?= AppConstants::UI_BTN_CANCEL ?>
                                </a>
                                <button type="submit" class="btn btn-setap-primary">
                                    <i class="bi bi-plus-circle"></i> Asignar Proceso
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para ver tareas -->
    <div class="modal fade" id="tasksModal" tabindex="-1" aria-labelledby="tasksModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tasksModalLabel">Tareas del Proceso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group" id="taskList">
                        <li class="list-group-item text-center">Cargando tareas...</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script src="/setap/public/js/task-by-process.js"></script>
</body>

</html>