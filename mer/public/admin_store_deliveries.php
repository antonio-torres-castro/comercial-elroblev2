<?php

/**
 * Módulo de Gestión de Entregas y Envíos
 * Sistema completo para administrar entregas, métodos, repartidores y seguimiento
 * 
 * @author MiniMax Agent
 * @version 1.0
 */

if (!isset($store) || !isset($products)) {
    echo '<div class="alert alert-warning">Error: Datos de la tienda no disponibles.</div>';
    return;
}

// Verificar que el usuario tenga acceso a la tienda
// Los permisos específicos se manejan a nivel de funciones individuales

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        switch ($action) {
            case 'update_delivery_status':
                $deliveryId = (int)$_POST['delivery_id'];
                $newStatus = $_POST['status'];
                $reason = $_POST['reason'] ?? null;
                $notes = $_POST['notes'] ?? null;

                $result = updateDeliveryStatus($deliveryId, $newStatus, $reason, $notes);
                echo json_encode($result);
                exit;

            case 'assign_driver':
                $deliveryId = (int)$_POST['delivery_id'];
                $driverId = (int)$_POST['driver_id'];

                $result = assignDriverToDelivery($deliveryId, $driverId);
                echo json_encode($result);
                exit;

            case 'create_delivery_method':
                $methodData = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'base_cost' => (float)$_POST['base_cost'],
                    'delivery_time_days' => (int)$_POST['delivery_time_days'],
                    'active' => isset($_POST['active']) ? 1 : 0
                ];

                $result = createDeliveryMethod($store['id'], $methodData);
                echo json_encode($result);
                exit;

            case 'get_delivery_stats':
                $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_POST['end_date'] ?? date('Y-m-d');

                $stats = getDeliveryStatistics($store['id'], $startDate, $endDate);
                echo json_encode(['success' => true, 'data' => $stats]);
                exit;

            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Obtener datos iniciales
$deliveries = getStoreDeliveries($store['id']);
$deliveryMethods = getStoreDeliveryMethods($store['id']);
$deliveryDrivers = getStoreDrivers($store['id']);
$deliveryStats = getDeliveryStats($store['id']);
?>

<div class="section-header">
    <h2 class="section-title">🚚 Gestión de Entregas - <?= htmlspecialchars($store['name']) ?></h2>
    <div class="section-actions">
        <button type="button" class="btn btn-primary" onclick="showNewDeliveryModal()">
            <i class="icon-plus"></i> Nueva Entrega
        </button>
        <button type="button" class="btn btn-secondary" onclick="refreshDeliveries()">
            <i class="icon-refresh"></i> Actualizar
        </button>
    </div>
</div>

<div class="section-content">
    <!-- Estadísticas Rápidas -->
    <div class="stats-grid">
        <div class="stat-card pending">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <div class="stat-number"><?= $deliveryStats['pending'] ?? 0 ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
        </div>
        <div class="stat-card in-transit">
            <div class="stat-icon">🚛</div>
            <div class="stat-content">
                <div class="stat-number"><?= $deliveryStats['in_transit'] ?? 0 ?></div>
                <div class="stat-label">En Tránsito</div>
            </div>
        </div>
        <div class="stat-card delivered">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?= $deliveryStats['delivered'] ?? 0 ?></div>
                <div class="stat-label">Entregadas</div>
            </div>
        </div>
        <div class="stat-card failed">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <div class="stat-number"><?= $deliveryStats['failed'] ?? 0 ?></div>
                <div class="stat-label">Fallidas</div>
            </div>
        </div>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="tabs-navigation">
        <button class="tab-btn active" onclick="switchTab('deliveries')">
            📦 Entregas
        </button>
        <button class="tab-btn" onclick="switchTab('methods')">
            🚚 Métodos
        </button>
        <button class="tab-btn" onclick="switchTab('calendar')">
            📅 Calendario
        </button>
        <button class="tab-btn" onclick="switchTab('drivers')">
            👤 Repartidores
        </button>
        <button class="tab-btn" onclick="switchTab('reports')">
            📊 Reportes
        </button>
        <button class="tab-btn" onclick="switchTab('new-delivery')">
            ➕ Nueva Entrega
        </button>
    </div>

    <!-- Contenido de las Pestañas -->
    <div class="tab-content active" id="deliveries">
        <div class="tab-header">
            <h3>📦 Lista de Entregas</h3>
            <div class="filters">
                <select id="status-filter" onchange="filterDeliveries()">
                    <option value="">Todos los Estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="confirmado">Confirmado</option>
                    <option value="en_preparacion">En Preparación</option>
                    <option value="en_transito">En Tránsito</option>
                    <option value="entregada">Entregada</option>
                    <option value="fallida">Fallida</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <input type="date" id="date-filter" onchange="filterDeliveries()">
                <input type="text" id="search-filter" placeholder="Buscar por cliente o dirección..." onkeyup="filterDeliveries()">
            </div>
        </div>
        <div class="deliveries-list">
            <?php if (empty($deliveries)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h4>No hay entregas</h4>
                    <p>No se encontraron entregas para mostrar.</p>
                    <button class="btn btn-primary" onclick="showNewDeliveryModal()">Crear Primera Entrega</button>
                </div>
            <?php else: ?>
                <?php foreach ($deliveries as $delivery): ?>
                    <div class="delivery-card" data-status="<?= $delivery['status'] ?>"
                        data-date="<?= $delivery['created_at'] ?>"
                        data-search="<?= strtolower($delivery['customer_name'] . ' ' . $delivery['delivery_address']) ?>">
                        <div class="delivery-header">
                            <div class="delivery-info">
                                <span class="delivery-id">#<?= $delivery['id'] ?></span>
                                <span class="status-badge status-<?= $delivery['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $delivery['status'])) ?>
                                </span>
                                <?php if ($delivery['priority_level'] === 'urgent'): ?>
                                    <span class="priority-badge urgent">URGENTE</span>
                                <?php endif; ?>
                            </div>
                            <div class="delivery-actions">
                                <button class="btn-sm btn-outline" onclick="updateDeliveryStatus(<?= $delivery['id'] ?>)">
                                    <i class="icon-edit"></i>
                                </button>
                                <button class="btn-sm btn-outline" onclick="viewDeliveryDetails(<?= $delivery['id'] ?>)">
                                    <i class="icon-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="delivery-body">
                            <div class="delivery-details">
                                <div class="detail-item">
                                    <strong>Cliente:</strong> <?= htmlspecialchars($delivery['customer_name']) ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Teléfono:</strong> <?= htmlspecialchars($delivery['customer_phone']) ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Dirección:</strong> <?= htmlspecialchars($delivery['delivery_address']) ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Método:</strong> <?= htmlspecialchars($delivery['delivery_method_name'] ?? 'No especificado') ?>
                                </div>
                                <?php if ($delivery['scheduled_date']): ?>
                                    <div class="detail-item">
                                        <strong>Fecha Programada:</strong> <?= date('d/m/Y', strtotime($delivery['scheduled_date'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="delivery-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Creado:</span>
                                    <span class="meta-value"><?= date('d/m/Y H:i', strtotime($delivery['created_at'])) ?></span>
                                </div>
                                <?php if ($delivery['assigned_driver_name']): ?>
                                    <div class="meta-item">
                                        <span class="meta-label">Repartidor:</span>
                                        <span class="meta-value"><?= htmlspecialchars($delivery['assigned_driver_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($delivery['days_until_delivery'] !== null): ?>
                                    <div class="meta-item">
                                        <span class="meta-label">Días restantes:</span>
                                        <span class="meta-value <?= $delivery['is_overdue'] ? 'text-danger' : '' ?>">
                                            <?= $delivery['days_until_delivery'] ?> días
                                            <?php if ($delivery['is_overdue']): ?>
                                                <i class="icon-warning text-warning"></i>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-content" id="methods">
        <div class="tab-header">
            <h3>🚚 Métodos de Entrega</h3>
            <button class="btn btn-primary" onclick="showNewMethodModal()">
                <i class="icon-plus"></i> Nuevo Método
            </button>
        </div>
        <div class="methods-grid">
            <?php if (empty($deliveryMethods)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🚚</div>
                    <h4>No hay métodos de entrega</h4>
                    <p>Crea tu primer método de entrega para comenzar.</p>
                </div>
            <?php else: ?>
                <?php foreach ($deliveryMethods as $method): ?>
                    <div class="method-card">
                        <div class="method-header">
                            <h4><?= htmlspecialchars($method['name']) ?></h4>
                            <span class="status-badge <?= $method['active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $method['active'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                        <div class="method-body">
                            <p><?= htmlspecialchars($method['description']) ?></p>
                            <div class="method-details">
                                <div class="detail-item">
                                    <span class="detail-label">Costo Base:</span>
                                    <span class="detail-value">$<?= number_format($method['base_cost'], 0, ',', '.') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tiempo:</span>
                                    <span class="detail-value"><?= $method['delivery_time_days'] ?> día<?= $method['delivery_time_days'] != 1 ? 's' : '' ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="method-actions">
                            <button class="btn-sm btn-outline" onclick="editMethod(<?= $method['id'] ?>)">
                                <i class="icon-edit"></i> Editar
                            </button>
                            <button class="btn-sm btn-outline" onclick="toggleMethodStatus(<?= $method['id'] ?>, <?= $method['active'] ? 0 : 1 ?>)">
                                <?= $method['active'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-content" id="calendar">
        <div class="tab-header">
            <h3>📅 Calendario de Entregas</h3>
            <div class="calendar-controls">
                <button class="btn btn-outline" onclick="previousWeek()">◀ Semana Anterior</button>
                <span id="current-week" class="week-display">Semana Actual</span>
                <button class="btn btn-outline" onclick="nextWeek()">Siguiente Semana ▶</button>
            </div>
        </div>
        <div class="calendar-container">
            <div class="calendar-grid" id="delivery-calendar">
                <!-- Se llena dinámicamente con JavaScript -->
            </div>
        </div>
    </div>

    <div class="tab-content" id="drivers">
        <div class="tab-header">
            <h3>👤 Repartidores</h3>
            <button class="btn btn-primary" onclick="showNewDriverModal()">
                <i class="icon-plus"></i> Nuevo Repartidor
            </button>
        </div>
        <div class="drivers-grid">
            <?php if (empty($deliveryDrivers)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👤</div>
                    <h4>No hay repartidores registrados</h4>
                    <p>Agrega repartidores para asignar entregas.</p>
                </div>
            <?php else: ?>
                <?php foreach ($deliveryDrivers as $driver): ?>
                    <div class="driver-card">
                        <div class="driver-header">
                            <h4><?= htmlspecialchars($driver['name']) ?></h4>
                            <span class="status-badge <?= $driver['active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $driver['active'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                        <div class="driver-body">
                            <div class="driver-details">
                                <div class="detail-item">
                                    <span class="detail-label">Teléfono:</span>
                                    <span class="detail-value"><?= htmlspecialchars($driver['phone']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Vehículo:</span>
                                    <span class="detail-value"><?= htmlspecialchars($driver['vehicle_type']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Capacidad:</span>
                                    <span class="detail-value"><?= $driver['capacity_kg'] ?> kg</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Licencia:</span>
                                    <span class="detail-value"><?= htmlspecialchars($driver['license_number']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Rating:</span>
                                    <span class="detail-value">
                                        <?= str_repeat('⭐', (int)$driver['rating']) ?>
                                        <?= number_format($driver['rating'], 1) ?>/5
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="driver-actions">
                            <button class="btn-sm btn-outline" onclick="editDriver(<?= $driver['id'] ?>)">
                                <i class="icon-edit"></i> Editar
                            </button>
                            <button class="btn-sm btn-outline" onclick="toggleDriverStatus(<?= $driver['id'] ?>, <?= $driver['active'] ? 0 : 1 ?>)">
                                <?= $driver['active'] ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-content" id="reports">
        <div class="tab-header">
            <h3>📊 Reportes y Estadísticas</h3>
            <div class="report-controls">
                <input type="date" id="report-start-date" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                <span>hasta</span>
                <input type="date" id="report-end-date" value="<?= date('Y-m-d') ?>">
                <button class="btn btn-primary" onclick="generateReport()">Generar Reporte</button>
            </div>
        </div>
        <div class="reports-container" id="reports-content">
            <!-- Se llena dinámicamente -->
        </div>
    </div>

    <div class="tab-content" id="new-delivery">
        <div class="tab-header">
            <h3>➕ Crear Nueva Entrega</h3>
        </div>
        <form class="delivery-form" id="new-delivery-form" onsubmit="createDelivery(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label for="customer_name">Nombre del Cliente *</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label for="customer_phone">Teléfono *</label>
                    <input type="tel" id="customer_phone" name="customer_phone" required>
                </div>
                <div class="form-group">
                    <label for="customer_email">Email</label>
                    <input type="email" id="customer_email" name="customer_email">
                </div>
                <div class="form-group">
                    <label for="delivery_method_id">Método de Entrega *</label>
                    <select id="delivery_method_id" name="delivery_method_id" required>
                        <option value="">Seleccionar método...</option>
                        <?php foreach ($deliveryMethods as $method): ?>
                            <option value="<?= $method['id'] ?>">
                                <?= htmlspecialchars($method['name']) ?> - $<?= number_format($method['base_cost'], 0, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label for="delivery_address">Dirección de Entrega *</label>
                    <textarea id="delivery_address" name="delivery_address" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="delivery_city">Ciudad *</label>
                    <input type="text" id="delivery_city" name="delivery_city" required>
                </div>
                <div class="form-group">
                    <label for="scheduled_date">Fecha Programada</label>
                    <input type="date" id="scheduled_date" name="scheduled_date"
                        min="<?= date('Y-m-d') ?>"
                        value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <div class="form-group">
                    <label for="priority_level">Prioridad</label>
                    <select id="priority_level" name="priority_level">
                        <option value="normal">Normal</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label for="special_instructions">Instrucciones Especiales</label>
                    <textarea id="special_instructions" name="special_instructions" rows="2"></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">Limpiar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="icon-check"></i> Crear Entrega
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modales -->
<div class="modal-overlay" id="modal-overlay" style="display: none;"></div>

<!-- Modal de Estados -->
<div class="modal" id="status-modal" style="display: none;">
    <div class="modal-header">
        <h3>Actualizar Estado de Entrega</h3>
        <button class="modal-close" onclick="closeModal('status-modal')">&times;</button>
    </div>
    <div class="modal-body">
        <form id="status-form">
            <input type="hidden" id="status-delivery-id">
            <div class="form-group">
                <label for="new-status">Nuevo Estado *</label>
                <select id="new-status" name="status" required>
                    <option value="pendiente">Pendiente</option>
                    <option value="confirmado">Confirmado</option>
                    <option value="en_preparacion">En Preparación</option>
                    <option value="en_transito">En Tránsito</option>
                    <option value="entregada">Entregada</option>
                    <option value="fallida">Fallida</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status-reason">Razón del Cambio</label>
                <input type="text" id="status-reason" name="reason" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label for="status-notes">Notas Adicionales</label>
                <textarea id="status-notes" name="notes" rows="3"></textarea>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('status-modal')">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveStatusChange()">Actualizar Estado</button>
    </div>
</div>

<!-- Modal de Método -->
<div class="modal" id="method-modal" style="display: none;">
    <div class="modal-header">
        <h3 id="method-modal-title">Nuevo Método de Entrega</h3>
        <button class="modal-close" onclick="closeModal('method-modal')">&times;</button>
    </div>
    <div class="modal-body">
        <form id="method-form">
            <input type="hidden" id="method-id">
            <div class="form-group">
                <label for="method-name">Nombre *</label>
                <input type="text" id="method-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="method-description">Descripción</label>
                <textarea id="method-description" name="description" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="method-cost">Costo Base *</label>
                <input type="number" id="method-cost" name="base_cost" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="method-days">Tiempo de Entrega (días) *</label>
                <input type="number" id="method-days" name="delivery_time_days" min="0" required>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="method-active" name="active" checked>
                    <span class="checkbox-custom"></span>
                    Método activo
                </label>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('method-modal')">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveMethod()">Guardar Método</button>
    </div>
</div>

<style>
    /* Estilos específicos para el módulo de entregas */

    /* Estadísticas Rápidas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        border-left: 4px solid #e0e0e0;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stat-card.pending {
        border-left-color: #ff9800;
    }

    .stat-card.in-transit {
        border-left-color: #2196f3;
    }

    .stat-card.delivered {
        border-left-color: #4caf50;
    }

    .stat-card.failed {
        border-left-color: #f44336;
    }

    .stat-icon {
        font-size: 2rem;
        opacity: 0.7;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }

    /* Pestañas */
    .tabs-navigation {
        display: flex;
        gap: 0;
        border-bottom: 1px solid #ddd;
        margin-bottom: 1.5rem;
    }

    .tab-btn {
        padding: 0.75rem 1.5rem;
        border: none;
        background: transparent;
        color: #666;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
    }

    .tab-btn:hover {
        background: #f5f5f5;
        color: #333;
    }

    .tab-btn.active {
        color: #007bff;
        border-bottom-color: #007bff;
        font-weight: 500;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .tab-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .tab-header h3 {
        margin: 0;
        color: #333;
    }

    .filters {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .filters select,
    .filters input {
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    /* Lista de Entregas */
    .deliveries-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .delivery-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #e0e0e0;
    }

    .delivery-card[data-status="pendiente"] {
        border-left-color: #ff9800;
    }

    .delivery-card[data-status="confirmado"] {
        border-left-color: #2196f3;
    }

    .delivery-card[data-status="en_preparacion"] {
        border-left-color: #9c27b0;
    }

    .delivery-card[data-status="en_transito"] {
        border-left-color: #607d8b;
    }

    .delivery-card[data-status="entregada"] {
        border-left-color: #4caf50;
    }

    .delivery-card[data-status="fallida"] {
        border-left-color: #f44336;
    }

    .delivery-card[data-status="cancelado"] {
        border-left-color: #9e9e9e;
    }

    .delivery-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .delivery-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .delivery-id {
        font-weight: bold;
        color: #666;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .status-pendiente {
        background: #fff3e0;
        color: #f57c00;
    }

    .status-confirmado {
        background: #e3f2fd;
        color: #1976d2;
    }

    .status-en_preparacion {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .status-en_transito {
        background: #eceff1;
        color: #455a64;
    }

    .status-entregada {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .status-fallida {
        background: #ffebee;
        color: #d32f2f;
    }

    .status-cancelado {
        background: #f5f5f5;
        color: #616161;
    }

    .status-active {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .status-inactive {
        background: #f5f5f5;
        color: #616161;
    }

    .priority-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: bold;
        background: #ffebee;
        color: #c62828;
    }

    .delivery-actions {
        display: flex;
        gap: 0.5rem;
    }

    .delivery-body {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1rem;
    }

    .delivery-details {
        display: grid;
        gap: 0.5rem;
    }

    .detail-item {
        font-size: 0.9rem;
    }

    .detail-item strong {
        color: #333;
    }

    .delivery-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .meta-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
    }

    .meta-label {
        color: #666;
    }

    .meta-value {
        font-weight: 500;
    }

    .text-danger {
        color: #d32f2f;
    }

    .text-warning {
        color: #f57c00;
    }

    /* Grid de Métodos */
    .methods-grid,
    .drivers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }

    .method-card,
    .driver-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .method-header,
    .driver-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .method-header h4,
    .driver-header h4 {
        margin: 0;
        color: #333;
    }

    .method-body,
    .driver-body {
        margin-bottom: 1rem;
    }

    .method-details,
    .driver-details {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .detail-label {
        color: #666;
        font-size: 0.9rem;
    }

    .detail-value {
        font-weight: 500;
        color: #333;
    }

    .method-actions,
    .driver-actions {
        display: flex;
        gap: 0.5rem;
    }

    /* Estado Vacío */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #666;
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state h4 {
        margin-bottom: 0.5rem;
        color: #333;
    }

    /* Formulario */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    /* Checkbox personalizado */
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        margin: 0;
    }

    .checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid #ddd;
        border-radius: 3px;
        position: relative;
        background: white;
    }

    .checkbox-custom::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        color: white;
        font-weight: bold;
        font-size: 12px;
        transition: transform 0.2s ease;
    }

    input[type="checkbox"]:checked+.checkbox-custom {
        background: #007bff;
        border-color: #007bff;
    }

    input[type="checkbox"]:checked+.checkbox-custom::after {
        transform: translate(-50%, -50%) scale(1);
    }

    input[type="checkbox"] {
        display: none;
    }

    /* Modal */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal {
        background: white;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
    }

    .modal-header h3 {
        margin: 0;
        color: #333;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        padding: 1.5rem;
        border-top: 1px solid #eee;
    }

    /* Botones */
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background: #0056b3;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #545b62;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid #ddd;
        color: #333;
    }

    .btn-outline:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    /* Utilidades */
    .icon-plus::before {
        content: '+';
    }

    .icon-refresh::before {
        content: '↻';
    }

    .icon-edit::before {
        content: '✏';
    }

    .icon-eye::before {
        content: '👁';
    }

    .icon-check::before {
        content: '✓';
    }

    .icon-warning::before {
        content: '⚠';
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .delivery-body {
            grid-template-columns: 1fr;
        }

        .filters {
            flex-wrap: wrap;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .tab-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .tabs-navigation {
            flex-wrap: wrap;
        }
    }
</style>

<script>
    // Variables globales
    let currentWeek = new Date();
    let editingMethodId = null;
    let editingDriverId = null;

    // Función para cambiar pestañas
    function switchTab(tabName) {
        // Ocultar todas las pestañas
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });

        // Quitar clase active de todos los botones
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Mostrar la pestaña seleccionada
        document.getElementById(tabName).classList.add('active');

        // Activar el botón correspondiente
        event.target.classList.add('active');

        // Cargar contenido específico de la pestaña
        if (tabName === 'calendar') {
            loadDeliveryCalendar();
        } else if (tabName === 'reports') {
            generateReport();
        }
    }

    // Filtrar entregas
    function filterDeliveries() {
        const statusFilter = document.getElementById('status-filter').value;
        const dateFilter = document.getElementById('date-filter').value;
        const searchFilter = document.getElementById('search-filter').value.toLowerCase();

        document.querySelectorAll('.delivery-card').forEach(card => {
            const status = card.dataset.status;
            const date = card.dataset.date;
            const search = card.dataset.search;

            let show = true;

            if (statusFilter && status !== statusFilter) {
                show = false;
            }

            if (dateFilter && !date.startsWith(dateFilter)) {
                show = false;
            }

            if (searchFilter && !search.includes(searchFilter)) {
                show = false;
            }

            card.style.display = show ? 'block' : 'none';
        });
    }

    // Actualizar estado de entrega
    function updateDeliveryStatus(deliveryId) {
        document.getElementById('status-delivery-id').value = deliveryId;
        showModal('status-modal');
    }

    // Guardar cambio de estado
    async function saveStatusChange() {
        const form = document.getElementById('status-form');
        const formData = new FormData(form);
        formData.append('action', 'update_delivery_status');
        formData.append('delivery_id', document.getElementById('status-delivery-id').value);

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Estado actualizado exitosamente');
                closeModal('status-modal');
                location.reload();
            } else {
                showAlert('error', result.error || 'Error al actualizar estado');
            }
        } catch (error) {
            showAlert('error', 'Error de conexión');
        }
    }

    // Mostrar modal de nuevo método
    function showNewMethodModal() {
        editingMethodId = null;
        document.getElementById('method-modal-title').textContent = 'Nuevo Método de Entrega';
        document.getElementById('method-form').reset();
        document.getElementById('method-id').value = '';
        showModal('method-modal');
    }

    // Guardar método
    async function saveMethod() {
        const form = document.getElementById('method-form');
        const formData = new FormData(form);
        formData.append('action', 'create_delivery_method');

        if (editingMethodId) {
            formData.append('method_id', editingMethodId);
        }

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Método guardado exitosamente');
                closeModal('method-modal');
                location.reload();
            } else {
                showAlert('error', result.error || 'Error al guardar método');
            }
        } catch (error) {
            showAlert('error', 'Error de conexión');
        }
    }

    // Mostrar/ocultar modal
    function showModal(modalId) {
        document.getElementById('modal-overlay').style.display = 'flex';
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById('modal-overlay').style.display = 'none';
        document.getElementById(modalId).style.display = 'none';
    }

    // Crear nueva entrega
    async function createDelivery(event) {
        event.preventDefault();

        const form = document.getElementById('new-delivery-form');
        const formData = new FormData(form);
        formData.append('action', 'create_delivery');

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', 'Entrega creada exitosamente');
                resetForm();
                // Opcional: cambiar a la pestaña de entregas
            } else {
                showAlert('error', result.error || 'Error al crear entrega');
            }
        } catch (error) {
            showAlert('error', 'Error de conexión');
        }
    }

    // Resetear formulario
    function resetForm() {
        document.getElementById('new-delivery-form').reset();
    }

    // Cargar calendario de entregas
    function loadDeliveryCalendar() {
        const container = document.getElementById('delivery-calendar');
        const startOfWeek = new Date(currentWeek);
        startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());

        let calendarHTML = '<div class="calendar-row">';
        calendarHTML += '<div class="calendar-header">Hora</div>';

        // Días de la semana
        for (let i = 0; i < 7; i++) {
            const day = new Date(startOfWeek);
            day.setDate(day.getDate() + i);
            const dayName = day.toLocaleDateString('es-ES', {
                weekday: 'short'
            });
            const dayNum = day.getDate();
            calendarHTML += `<div class="calendar-day-header">${dayName} ${dayNum}</div>`;
        }
        calendarHTML += '</div>';

        // Horarios
        const timeSlots = ['09:00', '11:00', '14:00', '16:00'];
        timeSlots.forEach(time => {
            calendarHTML += `<div class="calendar-row">`;
            calendarHTML += `<div class="calendar-time">${time}</div>`;

            for (let i = 0; i < 7; i++) {
                const day = new Date(startOfWeek);
                day.setDate(day.getDate() + i);
                const dateStr = day.toISOString().split('T')[0];

                calendarHTML += `<div class="calendar-cell" data-date="${dateStr}" data-time="${time}">`;
                calendarHTML += '<div class="cell-content">';
                // Aquí se pueden agregar entregas programadas
                calendarHTML += '</div>';
                calendarHTML += '</div>';
            }

            calendarHTML += '</div>';
        });

        container.innerHTML = calendarHTML;
    }

    // Generar reporte
    async function generateReport() {
        const startDate = document.getElementById('report-start-date').value;
        const endDate = document.getElementById('report-end-date').value;

        const formData = new FormData();
        formData.append('action', 'get_delivery_stats');
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                displayReport(result.data);
            } else {
                showAlert('error', 'Error al generar reporte');
            }
        } catch (error) {
            showAlert('error', 'Error de conexión');
        }
    }

    // Mostrar reporte
    function displayReport(stats) {
        const container = document.getElementById('reports-content');

        let reportHTML = '<div class="report-grid">';
        reportHTML += '<div class="report-card">';
        reportHTML += '<h4>Total de Entregas</h4>';
        reportHTML += `<div class="report-number">${stats.total_deliveries || 0}</div>`;
        reportHTML += '</div>';

        reportHTML += '<div class="report-card">';
        reportHTML += '<h4>Tasa de Entrega</h4>';
        reportHTML += `<div class="report-number">${(stats.delivery_rate * 100).toFixed(1)}%</div>`;
        reportHTML += '</div>';

        reportHTML += '<div class="report-card">';
        reportHTML += '<h4>Promedio por Entrega</h4>';
        reportHTML += `<div class="report-number">$${Number(stats.avg_delivery_cost || 0).toLocaleString()}</div>`;
        reportHTML += '</div>';

        reportHTML += '<div class="report-card">';
        reportHTML += '<h4>Ingresos por Envío</h4>';
        reportHTML += `<div class="report-number">$${Number(stats.total_delivery_revenue || 0).toLocaleString()}</div>`;
        reportHTML += '</div>';
        reportHTML += '</div>';

        container.innerHTML = reportHTML;
    }

    // Mostrar alertas
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 2000;
        padding: 1rem 1.5rem;
        border-radius: 4px;
        color: white;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
    `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }

    // Funciones de navegación del calendario
    function previousWeek() {
        currentWeek.setDate(currentWeek.getDate() - 7);
        loadDeliveryCalendar();
        updateWeekDisplay();
    }

    function nextWeek() {
        currentWeek.setDate(currentWeek.getDate() + 7);
        loadDeliveryCalendar();
        updateWeekDisplay();
    }

    function updateWeekDisplay() {
        const startOfWeek = new Date(currentWeek);
        startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(endOfWeek.getDate() + 6);

        document.getElementById('current-week').textContent =
            `${startOfWeek.toLocaleDateString('es-ES')} - ${endOfWeek.toLocaleDateString('es-ES')}`;
    }

    // Funciones de actualización
    function refreshDeliveries() {
        location.reload();
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        updateWeekDisplay();
    });
</script>