<?php

use App\Constants\AppConstants;
use App\Helpers\Security;

$tipoBadgeMap = [
    1 => 'success',
    2 => 'warning',
    3 => 'info',
    4 => 'secondary',
    5 => 'danger',
    6 => 'primary',
];

$dayNames = [
    'Mon' => 'Lunes',
    'Tue' => 'Martes',
    'Wed' => 'Miercoles',
    'Thu' => 'Jueves',
    'Fri' => 'Viernes',
    'Sat' => 'Sabado',
    'Sun' => 'Domingo',
];

$projectEndDate = $project['fecha_fin'] ?: $project['fecha_inicio'];
$selectedDefaultHh = 9;
$selectedUserName = '';
if (!empty($executors) && !empty($selected_user_id)) {
    foreach ($executors as $executor) {
        if ((int)$executor['usuario_id'] === (int)$selected_user_id) {
            $selectedDefaultHh = isset($executor['hh_default']) ? (float)$executor['hh_default'] : 9;
            $selectedUserName = $executor['nombre_completo'] ?? $executor['nombre_usuario'] ?? '';
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Colaboradores - SETAP</title>
    <link rel="icon" type="image/x-icon" href="/setap/public/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/setap/public/favicon.svg">
    <link rel="apple-touch-icon" href="/setap/public/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/setap/public/css/setap-theme.css">
</head>

<body class="bg-light">
    <?php include __DIR__ . '/../layouts/navigation.php'; ?>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">Gestion de Colaboradores</h2>
                        <p class="text-muted mb-0">Administrar disponibilidad por proyecto</p>
                    </div>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> <?= AppConstants::UI_BACK ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-lg-4 mb-3 mb-lg-0">
                                <label class="form-label">Proyecto</label>
                                <select class="form-select" id="projectSelect">
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$project['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nombre'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-8">
                                <h5 class="mb-1"><?= htmlspecialchars($project['cliente_nombre'] ?? '') ?></h5>
                                <div class="text-muted">
                                    <span class="me-3"><strong>Direccion:</strong> <?= htmlspecialchars($project['direccion'] ?? 'No especificada') ?></span>
                                    <span><strong>Periodo:</strong> <?= date('d/m/Y', strtotime($project['fecha_inicio'])) ?><?= $project['fecha_fin'] ? ' - ' . date('d/m/Y', strtotime($project['fecha_fin'])) : '' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Agregar ejecutor</h5>
                    </div>
                    <div class="card-body">
                        <form id="form-add-executor">
                            <?= Security::renderCsrfField() ?>
                            <input type="hidden" name="proyecto_id" value="<?= (int)$project['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Ejecutor</label>
                                <select class="form-select" name="usuario_id" required>
                                    <option value="">Seleccione un ejecutor</option>
                                    <?php foreach ($available_executors as $executor): ?>
                                        <option value="<?= (int)$executor['id'] ?>">
                                            <?= htmlspecialchars($executor['nombre_completo'] ?? $executor['nombre_usuario']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">HH base diario</label>
                                <input type="number" class="form-control" name="hh_default" value="9" step="1" min="0" max="24">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Agregar ejecutor
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Ejecutores del proyecto</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($executors)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>HH base</th>
                                            <th>Estado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($executors as $executor):
                                            $uid = (int)$executor['usuario_id'];
                                            $isOver = $overassigned_map[$uid] ?? false;
                                        ?>
                                            <tr class="<?= $isOver ? 'table-danger' : '' ?>">
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($executor['nombre_completo'] ?? $executor['nombre_usuario']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($executor['nombre_usuario'] ?? '') ?></div>
                                                </td>
                                                <td><?= number_format((float)($executor['hh_default'] ?? 9), 0, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($isOver): ?>
                                                        <span class="badge bg-danger">Sobre asignado</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">OK</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a class="btn btn-sm btn-outline-primary btn-view-calendar" href="?proyecto_id=<?= (int)$project['id'] ?>&usuario_id=<?= $uid ?>&fecha_inicio=<?= htmlspecialchars($fecha_inicio) ?>&fecha_fin=<?= htmlspecialchars($fecha_fin) ?>">
                                                        Ver calendario
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">No hay ejecutores asociados.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card" id="calendarCard">
                    <div class="card-header">
                        <h5 class="mb-0">Calendario de disponibilidad<?= $selectedUserName ? ' - ' . htmlspecialchars($selectedUserName) : '' ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($selected_user_id > 0): ?>
                            <form class="row g-3 mb-3" method="GET">
                                <input type="hidden" name="proyecto_id" value="<?= (int)$project['id'] ?>">
                                <input type="hidden" name="usuario_id" value="<?= (int)$selected_user_id ?>">
                                <div class="col-md-4">
                                    <label class="form-label">Fecha inicio</label>
                                    <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha fin</label>
                                    <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-outline-primary w-100" type="submit">
                                        <i class="bi bi-funnel"></i> Filtrar
                                    </button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Tipos de fecha</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <?php foreach ($tipos_fecha as $tipo):
                                            $tipoId = (int)$tipo['id'];
                                            $checked = empty($selected_tipos) || in_array($tipoId, $selected_tipos, true);
                                        ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="tipos[]" value="<?= $tipoId ?>" id="tipo_<?= $tipoId ?>" <?= $checked ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="tipo_<?= $tipoId ?>"><?= htmlspecialchars($tipo['nombre']) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </form>

                            <?php if (!$calendar_exists): ?>
                                <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                    <div>
                                        <strong>Calendario no guardado.</strong>
                                        Este ejecutor aun no tiene un calendario persistido para este proyecto.
                                    </div>
                                    <form id="form-save-calendar" class="d-flex">
                                        <?= Security::renderCsrfField() ?>
                                        <input type="hidden" name="proyecto_id" value="<?= (int)$project['id'] ?>">
                                        <input type="hidden" name="usuario_id" value="<?= (int)$selected_user_id ?>">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-save"></i> Guardar calendario base
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <?php if ($calendar_exists): ?>
                                <div class="alert alert-light border d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                    <div>
                                        Calendario guardado. Puede eliminarlo para regenerar desde cero.
                                    </div>
                                    <form id="form-delete-calendar" class="d-flex">
                                        <?= Security::renderCsrfField() ?>
                                        <input type="hidden" name="proyecto_id" value="<?= (int)$project['id'] ?>">
                                        <input type="hidden" name="usuario_id" value="<?= (int)$selected_user_id ?>">
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i> Eliminar calendario
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <form id="form-add-date" class="row g-2 align-items-end mb-3">
                                <?= Security::renderCsrfField() ?>
                                <input type="hidden" name="proyecto_id" value="<?= (int)$project['id'] ?>">
                                <input type="hidden" name="usuario_id" value="<?= (int)$selected_user_id ?>">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Agregar fecha</label>
                                    <input type="date" class="form-control" name="fecha" min="<?= htmlspecialchars($project['fecha_inicio']) ?>" max="<?= htmlspecialchars($projectEndDate) ?>" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Tipo</label>
                                    <select class="form-select" name="tipo_fecha_id">
                                        <?php foreach ($tipos_fecha as $tipo):
                                            $tipoId = (int)$tipo['id']; ?>
                                            <option value="<?= $tipoId ?>" <?= $tipoId === 6 ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tipo['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label">HH</label>
                                    <input type="number" class="form-control" name="hh" value="<?= htmlspecialchars((string)$selectedDefaultHh) ?>" step="1" min="0" max="24">
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-plus-circle"></i> Agregar
                                    </button>
                                </div>
                            </form>

                            <div id="holidaySection">
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Feriados del proyecto</h6>
                                    <span class="text-muted small"><?= (int)$holiday_total_rows ?> total</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Dia</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($project_holidays_page)): ?>
                                                    <?php foreach ($project_holidays_page as $holiday):
                                                        $hDate = DateTime::createFromFormat('Y-m-d', $holiday);
                                                        $label = $hDate ? $hDate->format('d/m/Y') : $holiday;
                                                        $hDay = $hDate ? ($dayNames[$hDate->format('D')] ?? $hDate->format('D')) : '';
                                                    ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($label) ?></td>
                                                            <td><?= htmlspecialchars($hDay) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted">No hay feriados registrados.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <?php if ($holiday_total_pages > 1): ?>
                            <div class="holiday-pagination">
                                <?php
                                $holidayQuery = $_GET;
                                unset($holidayQuery['holiday_page']);
                                $holidayBaseUrl = '?' . http_build_query($holidayQuery);
                                ?>
                                <nav aria-label="Paginacion de feriados" class="mb-3">
                                    <ul class="pagination pagination-sm justify-content-center">
                                        <li class="page-item <?= $holiday_current_page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $holidayBaseUrl . '&holiday_page=' . ($holiday_current_page - 1) ?>">&laquo;</a>
                                        </li>
                                        <?php
                                        $hStart = max(1, $holiday_current_page - 1);
                                        $hEnd = min($holiday_total_pages, $holiday_current_page + 1);
                                        for ($i = $hStart; $i <= $hEnd; $i++): ?>
                                            <li class="page-item <?= $i == $holiday_current_page ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= $holidayBaseUrl . '&holiday_page=' . $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= $holiday_current_page >= $holiday_total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $holidayBaseUrl . '&holiday_page=' . ($holiday_current_page + 1) ?>">&raquo;</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Dia</th>
                                            <th>Tipo</th>
                                            <th>HH proyecto</th>
                                            <th>HH en OP</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($calendar_rows)): ?>
                                            <?php foreach ($calendar_rows as $row):
                                                $dateObj = DateTime::createFromFormat('Y-m-d', $row['fecha']);
                                                $dayName = $dateObj ? ($dayNames[$dateObj->format('D')] ?? $dateObj->format('D')) : '';
                                                $isOver = ($row['total'] ?? 0) > 9;
                                                $badgeClass = $tipoBadgeMap[$row['tipo_fecha_id']] ?? 'secondary';
                                            ?>
                                                <tr class="<?= $isOver ? 'table-danger' : '' ?>">
                                                    <td><?= $dateObj ? $dateObj->format('d/m/Y') : htmlspecialchars($row['fecha']) ?></td>
                                                    <td><?= htmlspecialchars($dayName) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $badgeClass ?>">
                                                            <?= htmlspecialchars($row['tipo_fecha_nombre'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td><?= number_format((float)$row['hh'], 0, ',', '.') ?></td>
                                                    <td><?= number_format((float)$row['hh_op'], 0, ',', '.') ?></td>
                                                    <td><?= number_format((float)$row['total'], 0, ',', '.') ?></td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary btn-edit-day"
                                                            data-fecha="<?= htmlspecialchars($row['fecha']) ?>"
                                                            data-hh="<?= htmlspecialchars($row['hh']) ?>"
                                                            data-tipo-id="<?= (int)$row['tipo_fecha_id'] ?>">
                                                            Editar
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No hay fechas para el rango seleccionado.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($calendar_total_pages > 1): ?>
                                <div class="calendar-pagination">
                                <?php
                                $queryString = $_GET;
                                unset($queryString['page']);
                                $baseUrl = '?' . http_build_query($queryString);
                                ?>
                                <nav aria-label="Paginacion de calendario" class="mt-3">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $calendar_current_page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $baseUrl . '&page=' . ($calendar_current_page - 1) ?>">&laquo;</a>
                                        </li>
                                        <?php
                                        $start = max(1, $calendar_current_page - 1);
                                        $end = min($calendar_total_pages, $calendar_current_page + 1);
                                        for ($i = $start; $i <= $end; $i++): ?>
                                            <li class="page-item <?= $i == $calendar_current_page ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= $baseUrl . '&page=' . $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= $calendar_current_page >= $calendar_total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="<?= $baseUrl . '&page=' . ($calendar_current_page + 1) ?>">&raquo;</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                            </div>
                        <?php endif; ?>
                        <?php else: ?>
                            <div class="text-muted">Seleccione un ejecutor para administrar su disponibilidad.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editDayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-edit-day">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar disponibilidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?= Security::renderCsrfField() ?>
                        <input type="hidden" name="proyecto_id" value="<?= (int)$project['id'] ?>">
                        <input type="hidden" name="usuario_id" value="<?= (int)$selected_user_id ?>">
                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input type="text" class="form-control" name="fecha" id="editFecha" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de fecha</label>
                            <select class="form-select" name="tipo_fecha_id" id="editTipoFecha">
                                <?php foreach ($tipos_fecha as $tipo): ?>
                                    <option value="<?= (int)$tipo['id'] ?>"><?= htmlspecialchars($tipo['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Horas laborales</label>
                            <input type="number" class="form-control" name="hh" id="editHh" step="1" min="0" max="24">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/scripts-base.php'; ?>
    <script src="/setap/public/js/proyecto-colaboradores.js"></script>
</body>

</html>