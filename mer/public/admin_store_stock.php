<?php
// Gestión de stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_stock_update'])) {
  $updates = $_POST['stock_updates'] ?? [];

  foreach ($updates as $productId => $data) {
    $newStock = (int)($data['quantity'] ?? 0);
    $reason = $data['reason'] ?? 'Actualización masiva';

    if ($newStock >= 0) {
      updateProductStock((int)$productId, $newStock, $reason);
    }
  }

  $_SESSION['message'] = 'Stock actualizado masivamente';
  header("Location: admin_store.php?store_id=$storeId&action=stock");
  exit;
}

// Obtener movimientos de stock recientes
$stockMovements = [];
foreach (array_slice($products, 0, 10) as $product) {
  $movements = getStockMovements($product['id'], 5);
  foreach ($movements as $movement) {
    $stockMovements[] = $movement;
  }
}
usort($stockMovements, function ($a, $b) {
  return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$stockMovements = array_slice($stockMovements, 0, 20);
?>

<div class="section-header">
  <h2 class="section-title">📦 Gestión de Stock</h2>
  <button class="btn btn-primary" onclick="openModal('stockModal')">
    + Movimiento Manual
  </button>
</div>

<div class="section-content">
  <!-- Resumen de stock -->
  <div class="stats-grid" style="margin-bottom: var(--space-xl);">
    <div class="stat-card">
      <div class="stat-value"><?= array_sum(array_column($products, 'stock_quantity')) ?></div>
      <div class="stat-label">Unidades Totales</div>
      <div class="stat-change positive">Inventario general</div>
    </div>

    <div class="stat-card">
      <div class="stat-value"><?= count(array_filter($products, fn($p) => $p['stock_quantity'] <= $p['stock_min_threshold'])) ?></div>
      <div class="stat-label">Stock Crítico</div>
      <div class="stat-change negative">Requiere reposición</div>
    </div>

    <div class="stat-card">
      <div class="stat-value"><?= count(array_filter($products, fn($p) => $p['stock_quantity'] > $p['stock_min_threshold'] && $p['stock_quantity'] <= $p['stock_min_threshold'] * 2)) ?></div>
      <div class="stat-label">Stock Medio</div>
      <div class="stat-change warning">Monitorear</div>
    </div>

    <div class="stat-card">
      <div class="stat-value">$<?= number_format(array_sum(array_map(fn($p) => $p['stock_quantity'] * $p['price'], $products)), 0) ?></div>
      <div class="stat-label">Valor Inventario</div>
      <div class="stat-change positive">Capital de trabajo</div>
    </div>
  </div>

  <!-- Lista de productos con stock -->
  <div class="section">
    <div class="section-header">
      <h3 class="section-title">Inventario Actual</h3>
      <form method="POST" style="display: inline;">
        <input type="hidden" name="bulk_stock_update" value="1">
        <button type="submit" class="btn btn-warning" onclick="return confirm('¿Actualizar todos los stocks mostrados?')">
          💾 Guardar Cambios
        </button>
      </form>
    </div>
    <div class="section-content">
      <table class="table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Stock Actual</th>
            <th>Stock Mínimo</th>
            <th>Nuevo Stock</th>
            <th>Movimiento</th>
            <th>Última Actualización</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $product): ?>
            <?php
            $stockLevel = 'high';
            if ($product['stock_quantity'] <= $product['stock_min_threshold']) {
              $stockLevel = 'low';
            } elseif ($product['stock_quantity'] <= $product['stock_min_threshold'] * 2) {
              $stockLevel = 'medium';
            }

            // Obtener último movimiento
            $lastMovement = getStockMovements($product['id'], 1)[0] ?? null;
            ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: var(--space-sm);">
                  <img src="<?= $productImages[$product['id']] ?? '' ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>"
                    style="width: 30px; height: 30px; border-radius: 4px; object-fit: cover;"
                    onerror="this.style.display='none'">
                  <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--neutral-600);">
                      $<?= number_format($product['price'], 0) ?>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <span style="font-weight: 600; color: <?= ($stockLevel === 'low') ? 'var(--admin-danger)' : (($stockLevel === 'medium') ? 'var(--admin-warning)' : 'var(--admin-success)') ?>;">
                  <?= $product['stock_quantity'] ?>
                </span>
                <span class="stock-indicator stock-<?= $stockLevel ?>" style="margin-left: var(--space-xs);">
                  <?= strtoupper($stockLevel) ?>
                </span>
              </td>
              <td><?= $product['stock_min_threshold'] ?></td>
              <td>
                <input type="number"
                  name="stock_updates[<?= $product['id'] ?>][quantity]"
                  value="<?= $product['stock_quantity'] ?>"
                  min="0"
                  class="form-input"
                  style="width: 80px;">
              </td>
              <td>
                <input type="text"
                  name="stock_updates[<?= $product['id'] ?>][reason]"
                  placeholder="Motivo"
                  class="form-input"
                  style="width: 120px; font-size: 0.875rem;">
              </td>
              <td style="font-size: 0.875rem; color: var(--neutral-600);">
                <?= $lastMovement ? date('d/m/Y H:i', strtotime($lastMovement['created_at'])) : 'N/A' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Historial de movimientos -->
  <div class="section">
    <div class="section-header">
      <h3 class="section-title">📈 Historial de Movimientos Recientes</h3>
    </div>
    <div class="section-content">
      <?php if (empty($stockMovements)): ?>
        <p style="text-align: center; color: var(--neutral-600);">
          No hay movimientos de stock registrados.
        </p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Fecha/Hora</th>
              <th>Producto</th>
              <th>Tipo</th>
              <th>Cantidad</th>
              <th>Referencia</th>
              <th>Notas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stockMovements as $movement): ?>
              <tr>
                <td style="font-size: 0.875rem;">
                  <?= date('d/m/Y H:i', strtotime($movement['created_at'])) ?>
                </td>
                <td><?= htmlspecialchars($movement['product_name']) ?></td>
                <td>
                  <?php
                  $typeColor = match ($movement['movement_type']) {
                    'in' => 'var(--admin-success)',
                    'out' => 'var(--admin-danger)',
                    'adjustment' => 'var(--admin-warning)',
                    default => 'var(--neutral-600)'
                  };
                  ?>
                  <span style="color: <?= $typeColor ?>; font-weight: 500;">
                    <?= strtoupper($movement['movement_type']) ?>
                  </span>
                </td>
                <td style="font-weight: 600;"><?= $movement['quantity'] ?></td>
                <td style="font-size: 0.875rem;">
                  <?= strtoupper($movement['reference_type']) ?>
                </td>
                <td style="font-size: 0.875rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                  <?= htmlspecialchars($movement['notes']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal para movimiento manual -->
<div id="stockModal" class="modal">
  <div class="modal-content">
    <h3>Registrar Movimiento de Stock</h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="add_stock_movement">

      <div class="form-group">
        <label class="form-label">Producto</label>
        <select name="product_id" class="form-select" required>
          <option value="">Seleccionar producto</option>
          <?php foreach ($products as $product): ?>
            <option value="<?= $product['id'] ?>">
              <?= htmlspecialchars($product['name']) ?> (Stock actual: <?= $product['stock_quantity'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Tipo de Movimiento</label>
          <select name="movement_type" class="form-select" required>
            <option value="in">Entrada (+)</option>
            <option value="out">Salida (-)</option>
            <option value="adjustment">Ajuste</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Cantidad</label>
          <input type="number" name="quantity" class="form-input" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Motivo</label>
        <select name="reference_type" class="form-select" required>
          <option value="purchase">Compra</option>
          <option value="sale">Venta</option>
          <option value="adjustment">Ajuste</option>
          <option value="return">Devolución</option>
          <option value="damage">Daño/Pérdida</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Notas</label>
        <textarea name="notes" class="form-textarea" placeholder="Detalles adicionales..."></textarea>
      </div>

      <div style="display: flex; gap: var(--space-md); justify-content: flex-end;">
        <button type="button" class="btn btn-outline" onclick="closeModal('stockModal')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Registrar Movimiento</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Auto-calcular stock mínimo para productos nuevos
  function suggestMinStock(currentStock) {
    return Math.max(1, Math.ceil(currentStock * 0.2));
  }

  // Validación de formularios
  document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('change', function() {
      if (this.value < 0) {
        this.value = 0;
        alert('El stock no puede ser negativo');
      }
    });
  });
</script>