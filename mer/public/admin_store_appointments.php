<?php

/**
 * Sistema de Gestión de Citas y Reservas
 * Vista completa para administrar citas, servicios y políticas
 * Implementa todas las funcionalidades especificadas:
 * - Duración en días (mínimo 0.5 días)
 * - Servicios recurrentes 
 * - Múltiples citas simultáneas permitidas
 * - Generación de calendario automático
 * - Políticas de cancelación configurables
 */

if (!isset($store) || !isset($products)) {
    echo '<div class="alert alert-warning">Error: Datos de la tienda no disponibles.</div>';
    return;
}

$storeId = (int)$store['id'];

// Obtener datos iniciales
$appointments = getStoreAppointments($storeId, ['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d', strtotime('+30 days'))]);
$services = getAppointmentServices($storeId);
$statistics = getAppointmentStatistics($storeId, date('Y-m-01'), date('Y-m-t'));
$policies = getStoreCancellationPolicies($storeId);

function getStatusBadgeColor(string $status): string
{
    $colors = [
        'programada' => 'warning',
        'confirmada' => 'info',
        'en_proceso' => 'primary',
        'completada' => 'success',
        'cancelada' => 'danger',
        'no_asistio' => 'secondary'
    ];
    return $colors[$status] || 'secondary';
}

?>

<div class="appointments-container">
    <!-- Header con título y estadísticas -->
    <div class="section-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="section-title">
                    <i class="bi bi-calendar-event"></i>
                    Gestión de Citas - <?= htmlspecialchars($store['name']) ?>
                </h2>
                <p class="text-muted mb-0">Administra citas, servicios y políticas de cancelación</p>
            </div>
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?= $statistics['general']['total_appointments'] ?? 0 ?></div>
                    <div class="stat-label">Total Citas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number text-success"><?= $statistics['general']['completed'] ?? 0 ?></div>
                    <div class="stat-label">Completadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number text-warning"><?= $statistics['general']['scheduled'] ?? 0 ?></div>
                    <div class="stat-label">Programadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number text-danger"><?= $statistics['general']['cancelled'] ?? 0 ?></div>
                    <div class="stat-label">Canceladas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de navegación -->
    <ul class="nav nav-tabs" id="appointmentsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments-panel" type="button" role="tab">
                <i class="bi bi-calendar"></i> Citas del Día
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services-panel" type="button" role="tab">
                <i class="bi bi-gear"></i> Servicios
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-panel" type="button" role="tab">
                <i class="bi bi-calendar-range"></i> Calendario Automático
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="policies-tab" data-bs-toggle="tab" data-bs-target="#policies-panel" type="button" role="tab">
                <i class="bi bi-shield-check"></i> Políticas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="new-appointment-tab" data-bs-toggle="tab" data-bs-target="#new-appointment-panel" type="button" role="tab">
                <i class="bi bi-plus-circle"></i> Nueva Cita
            </button>
        </li>
    </ul>

    <!-- Contenido de tabs -->
    <div class="tab-content" id="appointmentsTabContent">

        <!-- Panel de Citas del Día -->
        <div class="tab-pane fade show active" id="appointments-panel" role="tabpanel">
            <div class="tab-content-inner">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Citas Programadas</h4>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control form-control-sm" id="filterDate" value="<?= date('Y-m-d') ?>">
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">Todos los estados</option>
                            <option value="programada">Programada</option>
                            <option value="confirmada">Confirmada</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="completada">Completada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                        <button class="btn btn-primary btn-sm" onclick="refreshAppointments()">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                    </div>
                </div>

                <div id="appointments-list">
                    <?php if (empty($appointments)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x display-1 text-muted"></i>
                            <h5 class="text-muted mt-3">No hay citas programadas</h5>
                            <p class="text-muted">Crea una nueva cita o genera el calendario automático</p>
                            <button class="btn btn-primary" onclick="showTab('new-appointment-tab')">
                                <i class="bi bi-plus"></i> Nueva Cita
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($appointments as $appointment): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="appointment-card status-<?= $appointment['status'] ?>">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-1"><?= htmlspecialchars($appointment['customer_name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($appointment['service_name']) ?></small>
                                                </div>
                                                <span class="badge bg-<?= getStatusBadgeColor($appointment['status']) ?>">
                                                    <?= ucfirst($appointment['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="appointment-info">
                                                <div class="info-item">
                                                    <i class="bi bi-telephone"></i>
                                                    <span><?= htmlspecialchars($appointment['customer_phone']) ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="bi bi-calendar"></i>
                                                    <span><?= date('d/m/Y H:i', strtotime($appointment['appointment_date'])) ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="bi bi-clock"></i>
                                                    <span><?= $appointment['duration_hours'] ?> horas</span>
                                                </div>
                                            </div>
                                            <?php if ($appointment['notes']): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-chat-left-text"></i>
                                                        <?= htmlspecialchars($appointment['notes']) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer">
                                            <div class="btn-group w-100" role="group">
                                                <?php if ($appointment['status'] === 'programada'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="confirmAppointment(<?= $appointment['id'] ?>)">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" onclick="startAppointment(<?= $appointment['id'] ?>)">
                                                        <i class="bi bi-play-circle"></i>
                                                    </button>
                                                <?php elseif ($appointment['status'] === 'confirmada' || $appointment['status'] === 'en_proceso'): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="completeAppointment(<?= $appointment['id'] ?>)">
                                                        <i class="bi bi-check2"></i> Completar
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="editAppointment(<?= $appointment['id'] ?>)">
                                                            <i class="bi bi-pencil"></i> Editar
                                                        </a></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="cancelAppointment(<?= $appointment['id'] ?>)">
                                                            <i class="bi bi-x-circle"></i> Cancelar
                                                        </a></li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item" href="#" onclick="markNoShow(<?= $appointment['id'] ?>)">
                                                            <i class="bi bi-person-x"></i> No Asistió
                                                        </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Panel de Servicios -->
        <div class="tab-pane fade" id="services-panel" role="tabpanel">
            <div class="tab-content-inner">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Servicios Disponibles</h4>
                    <button class="btn btn-primary" onclick="openServiceModal()">
                        <i class="bi bi-plus"></i> Nuevo Servicio
                    </button>
                </div>

                <div id="services-list">
                    <?php if (empty($services)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-gear display-1 text-muted"></i>
                            <h5 class="text-muted mt-3">No hay servicios configurados</h5>
                            <p class="text-muted">Crea servicios para poder programar citas</p>
                            <button class="btn btn-primary" onclick="openServiceModal()">
                                <i class="bi bi-plus"></i> Crear Primer Servicio
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($services as $service): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="service-card">
                                        <div class="card-header">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-1"><?= htmlspecialchars($service['name']) ?></h6>
                                                    <?php if ($service['is_recurring']): ?>
                                                        <span class="badge bg-info">
                                                            <i class="bi bi-arrow-repeat"></i> Recurrente
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#" onclick="editService(<?= $service['id'] ?>)">
                                                                <i class="bi bi-pencil"></i> Editar
                                                            </a></li>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteService(<?= $service['id'] ?>)">
                                                                <i class="bi bi-trash"></i> Eliminar
                                                            </a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?= htmlspecialchars($service['description']) ?></p>
                                            <div class="service-meta">
                                                <div class="meta-item">
                                                    <i class="bi bi-clock"></i>
                                                    <span><?= $service['default_duration_hours'] ?> horas</span>
                                                </div>
                                                <?php if ($service['price']): ?>
                                                    <div class="meta-item">
                                                        <i class="bi bi-currency-dollar"></i>
                                                        <span>$<?= number_format($service['price']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Panel de Calendario Automático -->
        <div class="tab-pane fade" id="calendar-panel" role="tabpanel">
            <div class="tab-content-inner">
                <h4>Generación Automática de Calendario</h4>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Función Automática:</strong> Esta herramienta genera automáticamente citas para servicios recurrentes basándose en la configuración de horarios de la tienda.
                </div>

                <form id="calendar-generator-form" onsubmit="generateCalendar(event)">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="gen-date-from" class="form-label">Fecha de Inicio</label>
                                <input type="date" class="form-control" id="gen-date-from" name="date_from" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="gen-date-to" class="form-label">Fecha de Fin</label>
                                <input type="date" class="form-control" id="gen-date-to" name="date_to" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-magic"></i> Generar Calendario
                            </button>
                        </div>
                    </div>
                </form>

                <div id="calendar-generator-results" class="mt-4"></div>

                <!-- Horarios de la tienda -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-clock"></i> Configuración de Horarios</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Horario de Inicio</label>
                                    <input type="time" class="form-control" id="store-start-time" value="09:00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Horario de Término</label>
                                    <input type="time" class="form-control" id="store-end-time" value="18:00">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Intervalo entre Citas (minutos)</label>
                                    <input type="number" class="form-control" id="appointment-interval" value="30" min="15" max="240">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button class="btn btn-outline-primary" onclick="updateScheduleConfig()">
                                    <i class="bi bi-save"></i> Actualizar Horarios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Políticas -->
        <div class="tab-pane fade" id="policies-panel" role="tabpanel">
            <div class="tab-content-inner">
                <h4>Políticas de Cancelación y Gestión</h4>

                <form id="policies-form" onsubmit="updatePolicies(event)">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-x-circle"></i> Políticas de Cancelación</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="hours-before-cancellation" class="form-label">Horas mínimas de anticipación</label>
                                        <input type="number" class="form-control" id="hours-before-cancellation"
                                            value="<?= $policies['hours_before_cancellation'] ?? 24 ?>" min="1" max="168">
                                        <div class="form-text">Tiempo mínimo para cancelar sin penalización</div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="require-cancellation-reason"
                                            <?= ($policies['require_cancellation_reason'] ?? true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="require-cancellation-reason">
                                            Requerir razón para cancelación
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-gear"></i> Configuración General</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="auto-confirm-appointments"
                                            <?= ($policies['auto_confirm_appointments'] ?? true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="auto-confirm-appointments">
                                            Confirmar citas automáticamente
                                        </label>
                                    </div>

                                    <div class="mb-3">
                                        <label for="max-daily-appointments" class="form-label">Máximo citas por día</label>
                                        <input type="number" class="form-control" id="max-daily-appointments"
                                            value="<?= $policies['max_daily_appointments'] ?? 20 ?>" min="1" max="100">
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="allow-double-booking"
                                            <?= ($policies['allow_double_booking'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="allow-double-booking">
                                            Permitir múltiples citas simultáneas
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Políticas
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel de Nueva Cita -->
        <div class="tab-pane fade" id="new-appointment-panel" role="tabpanel">
            <div class="tab-content-inner">
                <h4>Crear Nueva Cita</h4>

                <form id="new-appointment-form" onsubmit="createAppointment(event)">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer-name" class="form-label">Nombre del Cliente *</label>
                                <input type="text" class="form-control" id="customer-name" name="customer_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer-phone" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="customer-phone" name="customer_phone" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer-email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="customer-email" name="customer_email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="service-id" class="form-label">Servicio *</label>
                                <select class="form-select" id="service-id" name="service_id" required>
                                    <option value="">Seleccionar servicio...</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?= $service['id'] ?>" data-duration="<?= $service['default_duration_hours'] ?>">
                                            <?= htmlspecialchars($service['name']) ?> (<?= $service['default_duration_hours'] ?>h)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="appointment-date" class="form-label">Fecha y Hora *</label>
                                <input type="datetime-local" class="form-control" id="appointment-date" name="appointment_date" required>
                                <div class="form-text">La fecha no puede ser pasada</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duration-hours" class="form-label">Duración (horas) *</label>
                                <input type="number" class="form-control" id="duration-hours" name="duration_hours"
                                    min="0.5" step="0.5" value="1" required>
                                <div class="form-text">Duración mínima: 0.5 horas</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="appointment-notes" class="form-label">Notas</label>
                        <textarea class="form-control" id="appointment-notes" name="notes" rows="3"
                            placeholder="Observaciones adicionales para la cita..."></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="allow-multiple" name="allow_multiple">
                        <label class="form-check-label" for="allow-multiple">
                            Permitir múltiples citas simultáneas
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is-recurring" name="is_recurring">
                        <label class="form-check-label" for="is-recurring">
                            Servicio recurrente (crear recordatorios automáticos)
                        </label>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Validación automática:</strong> El sistema verificará conflictos de horarios y la disponibilidad antes de crear la cita.
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-calendar-plus"></i> Crear Cita
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar Formulario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
<!-- Modal para Servicio -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalTitle">Nuevo Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="service-form">
                    <input type="hidden" id="service-id-edit" name="service_id">
                    <div class="mb-3">
                        <label for="service-name" class="form-label">Nombre del Servicio *</label>
                        <input type="text" class="form-control" id="service-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="service-description" class="form-label">Descripción *</label>
                        <textarea class="form-control" id="service-description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="service-duration" class="form-label">Duración (horas) *</label>
                                <input type="number" class="form-control" id="service-duration" name="duration_hours"
                                    min="0.5" step="0.5" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="service-price" class="form-label">Precio</label>
                                <input type="number" class="form-control" id="service-price" name="price" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="service-recurring" name="is_recurring">
                        <label class="form-check-label" for="service-recurring">
                            Servicio recurrente
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveService()">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Cancelación -->
<div class="modal fade" id="cancellationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    ¿Estás seguro de que deseas cancelar esta cita?
                </div>
                <div class="mb-3">
                    <label for="cancellation-reason" class="form-label">Motivo de la cancelación *</label>
                    <textarea class="form-control" id="cancellation-reason" rows="3" required
                        placeholder="Especifique el motivo de la cancelación..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, mantener cita</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancellation()">
                    <i class="bi bi-x-circle"></i> Sí, cancelar cita
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .appointments-container {
        padding: 20px;
    }

    .section-header {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .stats-cards {
        display: flex;
        gap: 15px;
    }

    .stat-card {
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        min-width: 80px;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: bold;
        color: #495057;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 2px;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
    }

    .nav-tabs .nav-link.active {
        color: #007bff;
        border-bottom: 2px solid #007bff;
    }

    .tab-content-inner {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        min-height: 400px;
    }

    .appointment-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.2s;
    }

    .appointment-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .appointment-card.status-completada {
        border-left: 4px solid #28a745;
    }

    .appointment-card.status-cancelada {
        border-left: 4px solid #dc3545;
        opacity: 0.7;
    }

    .appointment-card.status-programada {
        border-left: 4px solid #ffc107;
    }

    .appointment-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.875rem;
    }

    .service-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        height: 100%;
    }

    .service-meta {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-top: 10px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.875rem;
        color: #6c757d;
    }

    @media (max-width: 768px) {
        .stats-cards {
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .stat-card {
            min-width: 60px;
            padding: 8px;
        }

        .section-header .d-flex {
            flex-direction: column;
            align-items: stretch !important;
        }
    }
</style>

<script>
    // Variables globales
    let currentAppointmentId = null;

    // Utilidades
    function showTab(tabId) {
        const tab = new bootstrap.Tab(document.getElementById(tabId));
        tab.show();
    }

    // Funciones de gestión de citas
    function refreshAppointments() {
        const date = document.getElementById('filterDate').value;
        const status = document.getElementById('filterStatus').value;

        // Simular actualización (en implementación real haría AJAX)
        location.reload();
    }

    function confirmAppointment(id) {
        if (confirm('¿Confirmar esta cita?')) {
            updateAppointmentStatus(id, 'confirmada');
        }
    }

    function startAppointment(id) {
        if (confirm('¿Iniciar esta cita?')) {
            updateAppointmentStatus(id, 'en_proceso');
        }
    }

    function completeAppointment(id) {
        if (confirm('¿Marcar como completada?')) {
            updateAppointmentStatus(id, 'completada');
        }
    }

    function cancelAppointment(id) {
        currentAppointmentId = id;
        document.getElementById('cancellation-reason').value = '';
        new bootstrap.Modal(document.getElementById('cancellationModal')).show();
    }

    function confirmCancellation() {
        const reason = document.getElementById('cancellation-reason').value;
        if (!reason.trim()) {
            alert('Debe especificar un motivo para la cancelación');
            return;
        }

        if (currentAppointmentId) {
            updateAppointmentStatus(currentAppointmentId, 'cancelada', reason);
            bootstrap.Modal.getInstance(document.getElementById('cancellationModal')).hide();
        }
    }

    function markNoShow(id) {
        if (confirm('¿Marcar como no asistió?')) {
            updateAppointmentStatus(id, 'no_asistio', 'Cliente no se presentó');
        }
    }

    function editAppointment(id) {
        alert('Función de edición pendiente de implementación');
    }

    function updateAppointmentStatus(id, status, reason = null) {
        // Implementación AJAX real
        fetch('ajax/appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'updateStatus',
                    appointment_id: id,
                    status: status,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el estado de la cita');
            });
    }

    // Gestión de servicios
    function openServiceModal(serviceId = null) {
        document.getElementById('service-form').reset();
        document.getElementById('service-id-edit').value = serviceId || '';

        if (serviceId) {
            // Cargar datos del servicio para edición
            document.getElementById('serviceModalTitle').textContent = 'Editar Servicio';
            // TODO: Implementar carga de datos
        } else {
            document.getElementById('serviceModalTitle').textContent = 'Nuevo Servicio';
        }

        new bootstrap.Modal(document.getElementById('serviceModal')).show();
    }

    function saveService() {
        const form = document.getElementById('service-form');
        const formData = new FormData(form);
        formData.append('action', 'saveService');

        fetch('ajax/appointments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('serviceModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el servicio');
            });
    }

    function editService(id) {
        openServiceModal(id);
    }

    function deleteService(id) {
        if (confirm('¿Estás seguro de que deseas eliminar este servicio?')) {
            fetch('ajax/appointments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'deleteService',
                        service_id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el servicio');
                });
        }
    }

    // Calendario automático
    function generateCalendar(event) {
        event.preventDefault();

        const form = document.getElementById('calendar-generator-form');
        const formData = new FormData(form);
        formData.append('action', 'generateCalendar');

        const resultsDiv = document.getElementById('calendar-generator-results');
        resultsDiv.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Generando calendario...</div>';

        fetch('ajax/appointments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultsDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <strong>¡Calendario generado exitosamente!</strong><br>
                    Citas generadas: ${data.generated}<br>
                    Período: ${data.period.from} a ${data.period.to}
                    ${data.errors && data.errors.length ? `<br><strong>Errores:</strong> ${data.errors.length}` : ''}
                </div>
            `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultsDiv.innerHTML = '<div class="alert alert-danger">Error al generar el calendario</div>';
            });
    }

    function updateScheduleConfig() {
        const startTime = document.getElementById('store-start-time').value;
        const endTime = document.getElementById('store-end-time').value;
        const interval = document.getElementById('appointment-interval').value;

        fetch('ajax/appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'updateScheduleConfig',
                    start_time: startTime,
                    end_time: endTime,
                    appointment_interval: interval
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Configuración actualizada exitosamente');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar la configuración');
            });
    }

    // Políticas
    function updatePolicies(event) {
        event.preventDefault();

        const form = document.getElementById('policies-form');
        const formData = new FormData(form);
        formData.append('action', 'updatePolicies');

        fetch('ajax/appointments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Políticas actualizadas exitosamente');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar las políticas');
            });
    }

    // Nueva cita
    function createAppointment(event) {
        event.preventDefault();

        const form = document.getElementById('new-appointment-form');
        const formData = new FormData(form);
        formData.append('action', 'createAppointment');

        fetch('ajax/appointments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cita creada exitosamente');
                    form.reset();
                    refreshAppointments();
                    showTab('appointments-tab');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear la cita');
            });
    }

    function resetForm() {
        document.getElementById('new-appointment-form').reset();
    }

    // Eventos del DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-completar duración cuando se selecciona un servicio
        document.getElementById('service-id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.dataset.duration) {
                document.getElementById('duration-hours').value = selectedOption.dataset.duration;
            }
        });

        // Validar fecha mínima para citas
        const appointmentDateInput = document.getElementById('appointment-date');
        const now = new Date();
        const minDate = now.toISOString().slice(0, 16);
        appointmentDateInput.min = minDate;
    });
</script>