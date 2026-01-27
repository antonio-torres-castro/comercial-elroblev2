<?php

use App\Helpers\Security;
use App\Constants\AppConstants;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= AppConstants::UI_PROJECT_MANAGEMENT ?> - SETAP</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body class="bg-light">
    <!-- Navegación Unificada -->
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">Gestión de Feriados</h2>
                    </div>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?= AppConstants::UI_BACK ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Project Info Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="card-title"><?= htmlspecialchars($project['cliente_nombre']) ?></h5>
                                <p class="card-text">
                                    <strong>Dirección:</strong> <?= htmlspecialchars($project['direccion'] ?? 'No especificada') ?><br>
                                    <strong>Período:</strong> <?= date('d/m/Y', strtotime($project['fecha_inicio'])) ?>
                                    <?= $project['fecha_fin'] ? ' - ' . date('d/m/Y', strtotime($project['fecha_fin'])) : '' ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h6 class="text-muted">Total Feriados</h6>
                                        <h4 class="text-primary"><?= $stats['total_feriados'] ?? 0 ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <h6 class="text-muted">Recurrentes</h6>
                                        <h4 class="text-info"><?= $stats['feriados_recurrentes'] ?? 0 ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <h6 class="text-muted">Irrenunciables</h6>
                                        <h4 class="text-warning"><?= $stats['feriados_irrenunciables'] ?? 0 ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Creation Forms -->
        <div class="row mb-4">
            <div class="col-12">

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Crear Feriados</h5>
                    </div>
                    <div class="card-body">
                        <!-- Tabs for different creation methods -->
                        <ul class="nav nav-tabs" id="feriadoTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="masivo-tab" data-bs-toggle="tab" data-bs-target="#masivo" type="button" role="tab">
                                    <i class="fas fa-calendar-week"></i> Creación Masiva
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="especifico-tab" data-bs-toggle="tab" data-bs-target="#especifico" type="button" role="tab">
                                    <i class="fas fa-calendar-day"></i> Fecha Específica
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="rango-tab" data-bs-toggle="tab" data-bs-target="#rango" type="button" role="tab">
                                    <i class="fas fa-calendar-alt"></i> Rango de Fechas
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="feriadoTabContent">
                            <!-- Creación Masiva Tab -->
                            <div class="tab-pane fade show active" id="masivo" role="tabpanel">
                                <form id="form-masivo" class="mt-3">
                                    <?= \App\Helpers\Security::renderCsrfField() ?>
                                    <input type="hidden" name="proyecto_id" value="<?= $project['id'] ?>">

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="fecha_inicio_masivo" class="form-label">Fecha Inicio *</label>
                                            <input type="date" class="form-control" id="fecha_inicio_masivo" name="fecha_inicio"
                                                value="<?= $project['fecha_inicio'] ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fecha_fin_masivo" class="form-label">Fecha Fin *</label>
                                            <input type="date" class="form-control" id="fecha_fin_masivo" name="fecha_fin"
                                                value="<?= $project['fecha_fin'] ?? '' ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label">Días de la semana a marcar como feriados *</label>
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
                                                        <input class="form-check-input" type="checkbox" name="dias[]" value="6" id="sabado" checked>
                                                        <label class="form-check-label" for="sabado">Sábado</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="dias[]" value="0" id="domingo" checked>
                                                        <label class="form-check-label" for="domingo">Domingo</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tipo de Feriado</label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="irrenunciable" id="renunciable_masivo" value="0" checked>
                                                    <label class="form-check-label" for="renunciable_masivo">Renunciable</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="irrenunciable" id="irrenunciable_masivo" value="1">
                                                    <label class="form-check-label" for="irrenunciable_masivo">Irrenunciable</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="observaciones_masivo" class="form-label">Observaciones</label>
                                            <input type="text" class="form-control" id="observaciones_masivo" name="observaciones"
                                                placeholder="Ej: Fines de semana regulares" maxlength="100">
                                        </div>
                                    </div>
                                    <!-- ********Boton Submit *********************-->
                                    <button type="submit" class="btn btn-primary" id="btn-create-masivo">
                                        <i class="fas fa-plus"></i> Crear Feriados Masivamente
                                    </button>
                                </form>
                            </div>

                            <!-- Fecha Específica Tab -->
                            <div class="tab-pane fade" id="especifico" role="tabpanel">
                                <form id="form-especifico" class="mt-3">
                                    <?= \App\Helpers\Security::renderCsrfField() ?>
                                    <input type="hidden" name="proyecto_id" value="<?= $project['id'] ?>">

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="fecha_especifica" class="form-label">Fecha *</label>
                                            <input type="date" class="form-control" id="fecha_especifica" name="fecha" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tipo de Feriado</label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="irrenunciable" id="renunciable_especifico" value="0" checked>
                                                    <label class="form-check-label" for="renunciable_especifico">Renunciable</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="irrenunciable" id="irrenunciable_especifico" value="1">
                                                    <label class="form-check-label" for="irrenunciable_especifico">Irrenunciable</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label for="observaciones_especifico" class="form-label">Observaciones</label>
                                            <input type="text" class="form-control" id="observaciones_especifico" name="observaciones"
                                                placeholder="Ej: Feriado Nacional" maxlength="100">
                                        </div>
                                    </div>
                                    <!-- ********Boton Submit *********************-->
                                    <button type="submit" class="btn btn-primary" id="btn-create-especifico">
                                        <i class="fas fa-plus"></i> Crear Feriado
                                    </button>
                                </form>
                            </div>

                            <!-- Rango de Fechas Tab -->
                            <div class="tab-pane fade" id="rango" role="tabpanel">
                                <form id="form-rango" class="mt-3">
                                    <?= \App\Helpers\Security::renderCsrfField() ?>
                                    <input type="hidden" name="proyecto_id" value="<?= $project['id'] ?>">

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="fecha_inicio_rango" class="form-label">Fecha Inicio *</label>
                                            <input type="date" class="form-control" id="fecha_inicio_rango" name="fecha_inicio" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fecha_fin_rango" class="form-label">Fecha Fin *</label>
                                            <input type="date" class="form-control" id="fecha_fin_rango" name="fecha_fin" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tipo de Feriado</label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="irrenunciable" id="renunciable_rango" value="0" checked>
                                                    <label class="form-check-label" for="renunciable_rango">Renunciable</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="irrenunciable" id="irrenunciable_rango" value="1">
                                                    <label class="form-check-label" for="irrenunciable_rango">Irrenunciable</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="observaciones_rango" class="form-label">Observaciones</label>
                                            <input type="text" class="form-control" id="observaciones_rango" name="observaciones"
                                                placeholder="Ej: Vacaciones de verano" maxlength="100">
                                        </div>
                                    </div>
                                    <!-- ********Boton Submit *********************-->
                                    <button type="submit" class="btn btn-primary" id="btn-create-rango">
                                        <i class="fas fa-plus"></i> Crear Feriados en Rango
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holidays Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Feriados del Proyecto</h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshHolidaysTable()">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="holidays-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Día de la Semana</th>
                                        <th>Tipo</th>
                                        <th>Irrenunciable</th>
                                        <th>Observaciones</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="holidays-tbody">
                                    <?php foreach ($feriados as $feriado): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($feriado['fecha'])) ?></td>
                                            <td><?= htmlspecialchars($feriado['dia_semana']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $feriado['tipo_feriado'] === 'recurrente' ? 'info' : 'primary' ?>">
                                                    <?= ucfirst($feriado['tipo_feriado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($feriado['ind_irrenunciable']): ?>
                                                    <span class="badge bg-warning">Irrenunciable</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Renunciable</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($feriado['observaciones'] ?? '') ?></td>
                                            <td>
                                                <span class="badge bg-<?= $feriado['estado_tipo_id'] == 2 ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars($feriado['estado_nombre']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editHoliday(<?= $feriado['id'] ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteHoliday(<?= $feriado['id'] ?>)" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Navegación de páginas" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <!-- Botón Anterior -->
                                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link ajax-page" href="#" data-page="<?= $currentPage - 1 ?>">&laquo;</a>
                                    </li>
                                    <!-- Números -->
                                    <?php
                                    $start = max(1, $currentPage - 1);
                                    $end = min($totalPages, $currentPage + 1);
                                    for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                            <a class="page-link ajax-page" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <!-- Botón Siguiente -->
                                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link ajax-page" href="#" data-page="<?= $currentPage + 1 ?>">&raquo;</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflict Modal -->
    <div class="modal fade" id="conflictModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Conflictos con Tareas Detectados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Se han detectado tareas programadas en las fechas marcadas como feriados:</p>
                    <div id="conflict-list"></div>
                    <hr>
                    <p><strong>¿Qué desea hacer?</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Solo Marcar Feriados</button>
                    <button type="button" class="btn btn-primary" id="move-tasks-btn">Mover Tareas al Siguiente Día Hábil</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Holiday Modal -->
    <div class="modal fade" id="editHolidayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Feriado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="edit-holiday-form">
                    <div class="modal-body">
                        <?= Security::renderCsrfField() ?>
                        <input type="hidden" name="id" id="edit-holiday-id">

                        <div class="mb-3">
                            <label class="form-label">Tipo de Feriado</label>
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="irrenunciable" id="edit-renunciable" value="0">
                                    <label class="form-check-label" for="edit-renunciable">Renunciable</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="irrenunciable" id="edit-irrenunciable" value="1">
                                    <label class="form-check-label" for="edit-irrenunciable">Irrenunciable</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit-observaciones" class="form-label">Observaciones</label>
                            <input type="text" class="form-control" id="edit-observaciones" name="observaciones" maxlength="100">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <!-- ********Boton Submit *********************-->
                        <button type="submit" class="btn btn-primary" id="btn-actualizar-editHolidayModal">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts Optimizados de SETAP -->
    <?php include __DIR__ . "/../layouts/scripts-base.php"; ?>
    <script src="/setap/public/js/proyecto-feriados.js"></script>

</body>

</html>