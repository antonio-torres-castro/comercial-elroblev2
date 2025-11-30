<?php
// Gestión de capacidad y disponibilidad de la tienda
declare(strict_types=1);

// Verificar que tenemos los datos necesarios
if (!isset($store) || !isset($storeId)) {
    echo '<div class="alert alert-error">Error: Datos de la tienda no disponibles.</div>';
    return;
}

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_capacity':
            $productId = (int)($_POST['product_id'] ?? 0);
            $date = $_POST['capacity_date'] ?? '';
            $capacity = (int)($_POST['available_capacity'] ?? 0);
            $notes = $_POST['notes'] ?? '';
            
            if ($productId && $date && $capacity >= 0) {
                $success = updateProductDailyCapacity($productId, $storeId, $date, $capacity, $notes);
                if ($success) {
                    $_SESSION['message'] = 'Capacidad actualizada exitosamente';
                } else {
                    $_SESSION['error'] = 'Error al actualizar capacidad (verificar fecha)';
                }
            } else {
                $_SESSION['error'] = 'Datos incompletos para actualizar capacidad';
            }
            header("Location: admin_store.php?store_id=$storeId&action=capacity");
            exit;
            
        case 'bulk_capacity':
            $productId = (int)($_POST['product_id'] ?? 0);
            $daysAhead = (int)($_POST['days_ahead'] ?? 30);
            $defaultCapacity = (int)($_POST['default_capacity'] ?? 0);
            
            if ($productId && $daysAhead > 0 && $defaultCapacity >= 0) {
                $success = generateAutomaticCapacities($productId, $storeId, $daysAhead, $defaultCapacity);
                if ($success) {
                    $_SESSION['message'] = "Capacidades generadas automáticamente para los próximos $daysAhead días";
                } else {
                    $_SESSION['error'] = 'Error al generar capacidades automáticas';
                }
            } else {
                $_SESSION['error'] = 'Datos incompletos para generación masiva';
            }
            header("Location: admin_store.php?store_id=$storeId&action=capacity");
            exit;
            
        case 'delete_capacity':
            $productId = (int)($_POST['product_id'] ?? 0);
            $date = $_POST['capacity_date'] ?? '';
            
            if ($productId && $date) {
                $success = deleteProductDailyCapacity($productId, $date);
                if ($success) {
                    $_SESSION['message'] = 'Capacidad eliminada exitosamente';
                } else {
                    $_SESSION['error'] = 'Error al eliminar capacidad';
                }
            }
            header("Location: admin_store.php?store_id=$storeId&action=capacity");
            exit;
    }
}

// Obtener datos para la vista
$products = getStoreProductsWithStock($storeId, 100, 0);
$storeCapacities = getStoreProductCapacities($storeId);

// Obtener capacidades del producto seleccionado si se especifica
$selectedProductId = (int)($_GET['product_id'] ?? 0);
$productCapacities = [];
if ($selectedProductId) {
    $productCapacities = getProductDailyCapacities($selectedProductId, 30);
}

// Generar fechas futuras para formularios
$futureDates = [];
for ($i = 0; $i < 30; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $futureDates[] = $date;
}
?>

<div class="section-header">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <h2 class="section-title">📅 Gestión de Capacidad - <?= htmlspecialchars($store['name']) ?></h2>
    <div>
      <span class="badge badge-info"><?= count($storeCapacities) ?> productos configurados</span>
    </div>
  </div>
</div>

<div class="section-content">
  <!-- Vista de resumen de capacidades -->
  <div class="stats-grid" style="margin-bottom: var(--space-xl);">
    <div class="stat-card">
      <div class="stat-value"><?= array_sum(array_column($storeCapacities, 'configured_days')) ?></div>
      <div class="stat-label">Días Configurados</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= array_sum(array_column($storeCapacities, 'future_capacity')) ?></div>
      <div class="stat-label">Capacidad Futura Total</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= array_sum(array_column($storeCapacities, 'total_booked_capacity')) ?></div>
      <div class="stat-label">Reservas Confirmadas</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= array_sum(array_column($storeCapacities, 'future_capacity')) - array_sum(array_column($storeCapacities, 'total_booked_capacity')) ?></div>
      <div class="stat-label">Capacidad Disponible</div>
    </div>
  </div>

  <!-- Lista de productos con capacidades -->
  <div class="section" style="margin-bottom: var(--space-xl);">
    <div class="section-header">
      <h3 class="section-title">🎯 Productos y Sus Capacidades</h3>
      <div>
        <button type="button" class="btn btn-primary" onclick="showBulkCapacityForm()">
          ⚡ Configuración Masiva
        </button>
      </div>
    </div>
    
    <div class="section-content">
      <?php if (empty($storeCapacities)): ?>
        <div class="alert alert-info">
          <strong>📝 Sin productos configurados</strong><br>
          No hay productos con configuraciones de capacidad. Los productos pueden configurarse individualmente.
        </div>
      <?php else: ?>
        <div class="products-capacity-grid">
          <?php foreach ($storeCapacities as $product): ?>
            <div class="capacity-product-card" onclick="showProductDetails(<?= $product['product_id'] ?>)">
              <div class="capacity-header">
                <h4><?= htmlspecialchars($product['product_name']) ?></h4>
                <span class="service-type-badge <?= $product['service_type'] ?>">
                  <?= htmlspecialchars($product['service_type']) ?>
                </span>
              </div>
              
              <div class="capacity-stats">
                <div class="capacity-stat">
                  <span class="capacity-value"><?= $product['configured_days'] ?></span>
                  <span class="capacity-label">días configurados</span>
                </div>
                <div class="capacity-stat">
                  <span class="capacity-value"><?= $product['future_capacity'] ?></span>
                  <span class="capacity-label">capacidad futura</span>
                </div>
                <div class="capacity-stat">
                  <span class="capacity-value"><?= $product['total_booked_capacity'] ?></span>
                  <span class="capacity-label">reservas</span>
                </div>
                <div class="capacity-stat">
                  <span class="capacity-value capacity-remaining">
                    <?= ($product['future_capacity'] - $product['total_booked_capacity']) ?>
                  </span>
                  <span class="capacity-label">disponible</span>
                </div>
              </div>
              
              <div class="capacity-actions">
                <button type="button" class="btn btn-sm btn-primary" onclick="event.stopPropagation(); showCapacityForm(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_name']) ?>')">
                  ✏️ Configurar
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="event.stopPropagation(); generateCapacities(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_name']) ?>')">
                  ⚡ Auto-generar
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Vista detallada de capacidades por producto -->
  <?php if ($selectedProductId && !empty($productCapacities)): ?>
    <?php $productName = array_column($storeCapacities, 'product_name', 'product_id')[$selectedProductId] ?? 'Producto'; ?>
    <div class="section">
      <div class="section-header">
        <h3 class="section-title">📊 Detalle de Capacidades - <?= htmlspecialchars($productName) ?></h3>
        <div>
          <button type="button" class="btn btn-outline" onclick="hideProductDetails()">Ocultar Detalle</button>
        </div>
      </div>
      
      <div class="section-content">
        <div class="capacity-calendar">
          <?php foreach ($productCapacities as $capacity): ?>
            <div class="capacity-day <?= $capacity['remaining_capacity'] <= 0 ? 'fully-booked' : ($capacity['remaining_capacity'] <= 2 ? 'low-capacity' : '') ?>">
              <div class="date"><?= date('d/m', strtotime($capacity['date'])) ?></div>
              <div class="capacity-numbers">
                <span class="capacity-total"><?= $capacity['available_capacity'] ?></span>
                <span class="capacity-booked">/<?= $capacity['booked_capacity'] ?></span>
              </div>
              <div class="capacity-remaining">
                <?= $capacity['remaining_capacity'] ?> disp.
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modales para configuración -->

<!-- Modal para configurar capacidad individual -->
<div id="capacityModal" class="modal">
  <div class="modal-content">
    <h3 id="capacityModalTitle">Configurar Capacidad</h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="update_capacity">
      <input type="hidden" id="modalProductId" name="product_id">
      
      <div class="form-group">
        <label class="form-label">Fecha</label>
        <select name="capacity_date" class="form-select" required>
          <option value="">Seleccionar fecha</option>
          <?php foreach ($futureDates as $date): ?>
            <option value="<?= $date ?>"><?= date('d/m/Y', strtotime($date)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Capacidad Disponible</label>
          <input type="number" name="available_capacity" class="form-input" min="0" required placeholder="ej: 10">
        </div>
        
        <div class="form-group">
          <label class="form-label">Notas (opcional)</label>
          <input type="text" name="notes" class="form-input" placeholder="ej: Capacidad reducida por mantención">
        </div>
      </div>
      
      <div style="display: flex; gap: var(--space-md); justify-content: flex-end; margin-top: var(--space-lg);">
        <button type="button" class="btn btn-outline" onclick="closeModal('capacityModal')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Capacidad</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal para configuración masiva -->
<div id="bulkCapacityModal" class="modal">
  <div class="modal-content">
    <h3>⚡ Configuración Masiva de Capacidades</h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="bulk_capacity">
      
      <div class="form-group">
        <label class="form-label">Producto</label>
        <select name="product_id" class="form-select" required onchange="updateDefaultCapacity()">
          <option value="">Seleccionar producto</option>
          <?php foreach ($products as $product): ?>
            <option value="<?= $product['id'] ?>" data-stock="<?= $product['current_stock'] ?>">
              <?= htmlspecialchars($product['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Días hacia adelante</label>
          <input type="number" name="days_ahead" class="form-input" value="30" min="1" max="365" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Capacidad por día</label>
          <input type="number" name="default_capacity" class="form-input" value="10" min="0" required>
        </div>
      </div>
      
      <div class="alert alert-info" id="bulkInfo" style="display: none;">
        <strong>💡 Información:</strong> Se configurará la misma capacidad para todos los días seleccionados.
      </div>
      
      <div style="display: flex; gap: var(--space-md); justify-content: flex-end; margin-top: var(--space-lg);">
        <button type="button" class="btn btn-outline" onclick="closeModal('bulkCapacityModal')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Generar Capacidades</button>
      </div>
    </form>
  </div>
</div>

<script>
function showCapacityForm(productId, productName) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('capacityModalTitle').textContent = `Configurar Capacidad - ${productName}`;
    openModal('capacityModal');
}

function showBulkCapacityForm() {
    openModal('bulkCapacityModal');
}

function generateCapacities(productId, productName) {
    if (confirm(`¿Generar capacidades automáticas para "${productName}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="bulk_capacity">
            <input type="hidden" name="product_id" value="${productId}">
            <input type="hidden" name="days_ahead" value="30">
            <input type="hidden" name="default_capacity" value="10">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function updateDefaultCapacity() {
    const select = document.querySelector('select[name="product_id"]');
    const option = select.options[select.selectedIndex];
    const stock = option.getAttribute('data-stock');
    
    if (stock && stock !== 'null') {
        document.querySelector('input[name="default_capacity"]').value = stock;
    }
    
    document.getElementById('bulkInfo').style.display = 'block';
}

function showProductDetails(productId) {
    const url = new URL(window.location);
    url.searchParams.set('product_id', productId);
    window.location.href = url.toString();
}

function hideProductDetails() {
    const url = new URL(window.location);
    url.searchParams.delete('product_id');
    window.location.href = url.toString();
}
</script>

<style>
.products-capacity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--space-lg);
}

.capacity-product-card {
    background: white;
    border: 1px solid var(--admin-border);
    border-radius: var(--radius-md);
    padding: var(--space-lg);
    cursor: pointer;
    transition: all 0.2s ease;
}

.capacity-product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: var(--admin-accent);
}

.capacity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-md);
}

.capacity-header h4 {
    margin: 0;
    color: var(--admin-primary);
    font-size: 1.1rem;
}

.service-type-badge {
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.service-type-badge.producto {
    background: #DBEAFE;
    color: #1E40AF;
}

.service-type-badge.servicio {
    background: #FEF3C7;
    color: #92400E;
}

.service-type-badge.ambos {
    background: #D1FAE5;
    color: #065F46;
}

.capacity-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-sm);
    margin-bottom: var(--space-md);
}

.capacity-stat {
    text-align: center;
}

.capacity-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--admin-primary);
}

.capacity-remaining {
    color: var(--admin-success);
}

.capacity-label {
    display: block;
    font-size: 0.75rem;
    color: var(--neutral-600);
    text-transform: uppercase;
}

.capacity-actions {
    display: flex;
    gap: var(--space-sm);
}

.capacity-calendar {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: var(--space-sm);
    max-height: 400px;
    overflow-y: auto;
}

.capacity-day {
    background: white;
    border: 1px solid var(--admin-border);
    border-radius: var(--radius-sm);
    padding: var(--space-sm);
    text-align: center;
    transition: all 0.2s ease;
}

.capacity-day.fully-booked {
    background: #FEE2E2;
    border-color: #FECACA;
}

.capacity-day.low-capacity {
    background: #FEF3C7;
    border-color: #FDE68A;
}

.capacity-day.fully-booked .capacity-remaining {
    color: var(--admin-danger);
    font-weight: 600;
}

.capacity-day.low-capacity .capacity-remaining {
    color: var(--admin-warning);
    font-weight: 600;
}

.capacity-day .date {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--admin-primary);
    margin-bottom: var(--space-xs);
}

.capacity-day .capacity-numbers {
    margin-bottom: var(--space-xs);
}

.capacity-total {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--admin-primary);
}

.capacity-booked {
    font-size: 0.875rem;
    color: var(--neutral-600);
}

.capacity-remaining {
    font-size: 0.75rem;
    color: var(--admin-success);
}

.badge {
    display: inline-block;
    padding: var(--space-xs) var(--space-sm);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-info {
    background: #DBEAFE;
    color: #1E40AF;
}

.btn-sm {
    padding: var(--space-xs) var(--space-sm);
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .products-capacity-grid {
        grid-template-columns: 1fr;
    }
    
    .capacity-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .capacity-actions {
        flex-direction: column;
    }
    
    .capacity-calendar {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}
</style>