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
            <!-- Main content -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= $data['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?= AppConstants::ROUTE_TASKS ?>" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK ?>
                        </a>
                    </div>
                </div>

                <!-- Mensajes de error -->
                <?php if (isset($data['error']) && !empty($data['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6><i class="bi bi-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
                        <p class="mb-0"><?= htmlspecialchars($data['error']); ?></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">

                        <form id="createTaskForm" method="POST" action="<?= AppConstants::ROUTE_TASKS ?>/store">
                            <?= Security::renderCsrfField() ?>
                            <!-- Definicion tarea catalogo:inicio-->
                            <!-- Tarea Catálogo -->
                            <div class="col-md-12">
                                <select class="form-select" id="tarea_id" name="tarea_id" required>
                                    <option value="">Seleccionar tarea existente...</option>
                                    <?php foreach ($data['tasks'] as $taskType): ?>
                                        <option value="<?= $taskType['id']; ?>"
                                            <?= (isset($_POST['tarea_id']) && $_POST['tarea_id'] == $taskType['id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($taskType['nombre']); ?> - <?= htmlspecialchars($taskType['descripcion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="nueva" <?= (isset($_POST['tarea_id']) && $_POST['tarea_id'] == 'nueva') ? 'selected' : ''; ?>>
                                        ➕ Crear nueva tarea
                                    </option>
                                </select>
                                <div class="form-text mb-3">Seleccione del catálogo o cree una nueva.</div>
                            </div>
                            <!-- Campos para nueva tarea (ocultos por defecto) -->
                            <div class="col-12" id="nueva-tarea-fields" style="display: none;">
                                <div class="card border-primary mb-2">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva tarea en estado activo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="nueva_tarea_nombre" class="form-label">
                                                    Nombre llave <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="nueva_tarea_nombre" name="nueva_tarea_nombre"
                                                    placeholder="Nombre descriptivo de la tarea"
                                                    value="<?= htmlspecialchars($_POST['nueva_tarea_nombre'] ?? ''); ?>"
                                                    maxlength="150">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="nueva_tarea_descripcion" class="form-label">Descripción</label>
                                                <textarea class="form-control" id="nueva_tarea_descripcion" name="nueva_tarea_descripcion"
                                                    placeholder="Descripción detallada de la tarea" rows="3"><?= htmlspecialchars($_POST['nueva_tarea_descripcion'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Definicion tarea catalogo:fin-->

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> <?= $data['subtitle']; ?></h5>
                                </div>
                                <div class="card-body">

                                    <div class="row g-3">
                                        <!-- Proyecto -->
                                        <div class="col-md-12">
                                            <label for="proyecto_id" class="form-label">
                                                Proyecto <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="proyecto_id" name="proyecto_id" required>
                                                <option value="">Seleccionar proyecto...</option>
                                                <?php foreach ($data['projects'] as $project): ?>
                                                    <option value="<?= $project['id']; ?>"
                                                        <?= ($data['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($project['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Asignaciones usuario:inicio -->
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="text-muted"><i class="bi bi-people"></i> Asignación de Usuarios</h6>
                                        </div>

                                        <!-- Ejecutor -->
                                        <div class="col-md-6">
                                            <label for="ejecutor_id" class="form-label">Ejecutor</label>
                                            <select class="form-select" id="ejecutor_id" name="ejecutor_id" required>
                                                <option value="">Sin asignar</option>
                                                <?php foreach ($data['executor_users'] as $user): ?>
                                                    <option value="<?= $user['id']; ?>"
                                                        <?= (isset($_POST['ejecutor_id']) && $_POST['ejecutor_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($user['nombre_completo'] . ' (' . $user['nombre_usuario'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Inicia/Termina tarea.</div>
                                        </div>

                                        <!-- Supervisor -->
                                        <div class="col-md-6">
                                            <label for="supervisor_id" class="form-label">Supervisor</label>
                                            <select class="form-select" id="supervisor_id" name="supervisor_id" required>
                                                <option value="">Sin supervisor</option>
                                                <?php foreach ($data['supervisor_users'] as $user): ?>
                                                    <option value="<?= $user['id']; ?>"
                                                        <?= (isset($_POST['supervisor_id']) && $_POST['supervisor_id'] == $user['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($user['nombre_completo'] . ' (' . $user['nombre_usuario'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Aprueba/Rechaza tarea.</div>
                                        </div>
                                        <!-- Asignaciones usuario:fin -->

                                        <!-- Programación -->
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="text-muted"><i class="bi bi-calendar"></i> Programación</h6>
                                        </div>

                                        <!-- Tarea Tipo -->
                                        <div class="col-md-2">
                                            <label for="tarea_tipo_id" class="form-label">Tipo<span class="text-danger">*</span></label>
                                            <select class="form-select" id="tarea_tipo_id" name="tarea_tipo_id" required>
                                                <option value="">Seleccionar tipo</option>
                                                <?php foreach ($data['taskTypes'] as $type): ?>
                                                    <option value="<?= $type['id']; ?>"
                                                        <?= (isset($data['task']['tarea_tipo_id']) && $data['task']['tarea_tipo_id'] == $type['id']) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($type['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Estado -->
                                        <div class="col-md-2">
                                            <label for="estado_tipo_id" class="form-label">Estado<span class="text-danger">*</span></label>
                                            <select class="form-select" id="estado_tipo_id" name="estado_tipo_id" required>
                                                <?php foreach ($data['taskStates'] as $state): ?>
                                                    <option value="<?= $state['id']; ?>"
                                                        <?= (!empty($_POST['estado_tipo_id']) && $_POST['estado_tipo_id'] == $state['id']) || $state['id'] == 1 ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($state['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Duración -->
                                        <div class="col-md-2">
                                            <label for="duracion_horas" class="form-label">Duración(horas)</label>
                                            <input type="number" class="form-control" id="duracion_horas" name="duracion_horas" step="0.5" min="0.5" max="24" required
                                                value="<?= htmlspecialchars($_POST['duracion_horas'] ?? '1.0'); ?>">
                                        </div>

                                        <!-- Prioridad -->
                                        <div class="col-md-2">
                                            <label for="prioridad" class="form-label">Prioridad</label>
                                            <select class="form-select" id="prioridad" name="prioridad" required>
                                                <option value="0" <?= ($_POST['prioridad'] ?? '') === '0' ? 'selected' : ''; ?>>0 - Baja</option>
                                                <option value="3" <?= ($_POST['prioridad'] ?? '') === '3' ? 'selected' : ''; ?>>3 - Normal</option>
                                                <option value="5" <?= (!isset($_POST['prioridad']) || $_POST['prioridad'] == '5') ? 'selected' : ''; ?>>5 - Media</option>
                                                <option value="7" <?= ($_POST['prioridad'] ?? '') === '7' ? 'selected' : ''; ?>>7 - Alta</option>
                                                <option value="10" <?= ($_POST['prioridad'] ?? '') === '10' ? 'selected' : ''; ?>>10 - Crítica</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <input class="text" id="idTipoOcurrencia" value="masivo" name="idTipoOcurrencia" hidden>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" onclick="selOpt(event, 'masivo')"
                                                    name="optionOcurrencia" id="iorMasivo" value="1" checked>
                                                <label class="form-check-label" for="iorMasivo">Masivo</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" onclick="selOpt(event, 'especifico')"
                                                    name="optionOcurrencia" id="iorEspecifico" value="2">
                                                <label class="form-check-label" for="iorEspecifico">Especifico</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" onclick="selOpt(event, 'rango')"
                                                    name="optionOcurrencia" id="iorRango" value="3">
                                                <label class="form-check-label" for="iorRango">Rango</label>
                                            </div>
                                        </div>



                                    </div>

                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-list-task"></i> Cuando ocurre</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Tabs for different creation methods -->
                                    <ul class="nav nav-tabs" id="ocurrenciaTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="masivo-tab" onclick="openTab(event, 'Masivo')"
                                                data-bs-toggle="tab" data-bs-target="#masivo" type="button" role="tab" name="button-tab">
                                                <i class="fas fa-calendar-week"></i> Todos los...
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="especifico-tab" onclick="openTab(event, 'Especifico')"
                                                data-bs-toggle="tab" data-bs-target="#especifico" type="button" role="tab" name="button-tab">
                                                <i class="fas fa-calendar-day"></i> Solo el...
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="rango-tab" onclick="openTab(event, 'Rango')"
                                                data-bs-toggle="tab" data-bs-target="#rango" type="button" role="tab" name="button-tab">
                                                <i class="fas fa-calendar-alt"></i> Todos los dias entre...
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="ocurrenciaTabContent">
                                        <!-- Creación Masiva Tab -->
                                        <div class="tab-pane fade show active" id="masivo" role="tabpanel" name="tabpane">

                                            <input type="hidden" name="proyecto_id" value="<?= $project['id'] ?>">

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="fecha_inicio_masivo" class="form-label">Inicio *</label>
                                                    <input type="date" class="form-control" id="fecha_inicio_masivo" name="fecha_inicio_masivo"
                                                        value="<?= htmlspecialchars($_POST['fecha_inicio'] ?? date('Y-m-d')); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="fecha_fin_masivo" class="form-label">Fin *</label>
                                                    <input type="date" class="form-control" id="fecha_fin_masivo" name="fecha_fin_masivo"
                                                        value="<?= htmlspecialchars($_POST['fecha_fin'] ?? date('Y-m-d')); ?>" required>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <label class="form-label">Días de la semana *</label>
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias[]" value="1" id="lunes">
                                                                <label class="form-check-label" for="lunes">Lunes</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias[]" value="2" id="martes">
                                                                <label class="form-check-label" for="martes">Martes</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias[]" value="3" id="miercoles">
                                                                <label class="form-check-label" for="miercoles">Miércoles</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias[]" value="4" id="jueves">
                                                                <label class="form-check-label" for="jueves">Jueves</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias[]" value="5" id="viernes">
                                                                <label class="form-check-label" for="viernes">Viernes</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias[]" value="6" id="sabado">
                                                                <label class="form-check-label" for="sabado">Sábado</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="dias[]" value="0" id="domingo">
                                                                <label class="form-check-label" for="domingo">Domingo</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <!-- Fecha Específica Tab -->
                                        <div class="tab-pane fade" id="especifico" role="tabpanel" name="tabpane">
                                            <input type="hidden" name="proyecto_id" value="<?= $project['id'] ?>">

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="fecha_especifica_inicio" class="form-label">Inicio *</label>
                                                    <input type="date" class="form-control" id="fecha_especifica_inicio" name="fecha_especifica_inicio"
                                                        value="<?= htmlspecialchars($_POST['fecha_inicio'] ?? date('Y-m-d')); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="fecha_especifica_fin" class="form-label">Fin *</label>
                                                    <input type="date" class="form-control" id="fecha_especifica_fin" name="fecha_especifica_fin"
                                                        value="<?= htmlspecialchars($_POST['fecha_fin'] ?? date('Y-m-d')); ?>" required disabled>
                                                </div>
                                            </div>

                                        </div>

                                        <!-- Rango de Fechas Tab -->
                                        <div class="tab-pane fade" id="rango" role="tabpanel" name="tabpane">
                                            <input type="hidden" name="proyecto_id" value="<?= $project['id'] ?>">

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="fecha_inicio_rango" class="form-label">Inicio *</label>
                                                    <input type="date" class="form-control" id="fecha_inicio_rango" name="fecha_inicio_rango"
                                                        value="<?= htmlspecialchars($_POST['fecha_inicio'] ?? date('Y-m-d')); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="fecha_fin_rango" class="form-label">Fin *</label>
                                                    <input type="date" class="form-control" id="fecha_fin_rango" name="fecha_fin_rango"
                                                        value="<?= htmlspecialchars($_POST['fecha_fin'] ?? date('Y-m-d')); ?>" required>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="col-12 mt-3">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= AppConstants::ROUTE_TASKS ?>" class="btn btn-secondary">
                                        <i class="bi bi-x-lg"></i> <?= AppConstants::UI_BTN_CANCEL ?>
                                    </a>
                                    <button id="createBtn" type="submit" class="btn btn-setap-primary">
                                        <i class="bi bi-plus-circle"></i> Asignar
                                    </button>
                                </div>
                                <br>
                                <br>
                            </div>

                        </form>

                    </div>
                </div>

            </main>

        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <!-- UX -->
    <script src="/setap/public/js/create-project-task.js"></script>
    <!-- GAP 5: Task State Validation Utilities -->
    <script src="/setap/public/js/task-state-utils.js"></script>
</body>

</html>