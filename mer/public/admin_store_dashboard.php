<?php
// Dashboard de la tienda
$totalProducts = count($products);
$activeProducts = count(array_filter($products, fn($p) => $p['active']));
$lowStockCount = count($lowStockProducts);
$totalStockValue = array_sum(array_map(fn($p) => $p['stock_quantity'] * $p['price'], $products));

// Estadísticas de disponibilidad
$totalAvailableSlots = array_sum(array_map(fn($p) => $p['available_slots'] ?? 0, $availabilityStats));
$avgAvailability = count($availabilityStats) > 0 ? round($totalAvailableSlots / count($availabilityStats)) : 0;

// Productos más vendidos (simulado)
$topProducts = array_slice($products, 0, 5);
?>

<div class="section-header">
  <h2 class="section-title">📊 Dashboard - <?= htmlspecialchars($store['name']) ?></h2>
</div>

<div class="section-content">
  <!-- Estadísticas principales -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-value"><?= $totalProducts ?></div>
      <div class="stat-label">Productos Totales</div>
      <div class="stat-change positive"><?= $activeProducts ?> activos</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-value"><?= $lowStockCount ?></div>
      <div class="stat-label">Productos con Stock Bajo</div>
      <div class="stat-change <?= $lowStockCount > 0 ? 'negative' : 'positive' ?>">
        <?= $lowStockCount > 0 ? 'Requiere atención' : 'Stock OK' ?>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-value">$<?= number_format($totalStockValue, 0) ?></div>
      <div class="stat-label">Valor en Stock</div>
      <div class="stat-change positive">Inventario total</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-value"><?= $avgAvailability ?></div>
      <div class="stat-label">Capacidad Promedio</div>
      <div class="stat-change positive">Disponibilidad diaria</div>
    </div>
  </div>

  <!-- Alertas de stock bajo -->
  <?php if (!empty($lowStockProducts)): ?>
  <div class="section">
    <div class="section-header">
      <h3 class="section-title">⚠️ Alertas de Stock Bajo</h3>
    </div>
    <div class="section-content">
      <div class="product-grid">
        <?php foreach ($lowStockProducts as $product): ?>
        <div class="product-item">
          <img src="<?= $productImages[$product['id']] ?? '' ?>" 
               alt="<?= htmlspecialchars($product['name']) ?>" 
               class="product-image"
               onerror="this.style.display='none'">
          
          <div class="product-info">
            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
            <div class="product-details">
              <span class="product-detail">Stock: <?= $product['stock_quantity'] ?></span>
              <span class="product-detail">Mínimo: <?= $product['stock_min_threshold'] ?></span>
              <span class="stock-indicator stock-low">Stock Crítico</span>
            </div>
          </div>
          
          <button class="btn btn-warning" onclick="updateStock(<?= $product['id'] ?>, <?= $product['stock_quantity'] ?>)">
            Actualizar Stock
          </button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Productos principales -->
  <div class="section">
    <div class="section-header">
      <h3 class="section-title">☕ Productos Principales</h3>
      <button class="btn btn-primary" onclick="openModal('productModal')">
        + Agregar Producto
      </button>
    </div>
    <div class="section-content">
      <div class="product-grid">
        <?php foreach ($topProducts as $product): ?>
        <?php
        $stockLevel = 'high';
        if ($product['stock_quantity'] <= $product['stock_min_threshold']) {
          $stockLevel = 'low';
        } elseif ($product['stock_quantity'] <= $product['stock_min_threshold'] * 2) {
          $stockLevel = 'medium';
        }
        ?>
        <div class="product-item">
          <img src="<?= $productImages[$product['id']] ?? '' ?>" 
               alt="<?= htmlspecialchars($product['name']) ?>" 
               class="product-image"
               onerror="this.style.display='none'">
          
          <div class="product-info">
            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
            <div class="product-details">
              <span class="product-detail">$<?= number_format($product['price'], 0) ?></span>
              <span class="product-detail">Stock: <?= $product['stock_quantity'] ?></span>
              <span class="product-detail">Disponibles: <?= $product['available_slots'] ?? 'N/A' ?></span>
              <span class="stock-indicator stock-<?= $stockLevel ?>">
                <?= ucfirst($stockLevel) ?> Stock
              </span>
            </div>
          </div>
          
          <div style="display: flex; gap: var(--space-xs);">
            <button class="btn btn-outline" onclick="updateStock(<?= $product['id'] ?>, <?= $product['stock_quantity'] ?>)">
              Stock
            </button>
            <button class="btn btn-outline" onclick="updateCapacity(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')">
              Capacidad
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Acciones rápidas -->
  <div class="section">
    <div class="section-header">
      <h3 class="section-title">🚀 Acciones Rápidas</h3>
    </div>
    <div class="section-content">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-lg);">
        <a href="?store_id=<?= $storeId ?>&action=products" class="btn btn-primary" style="text-decoration: none;">
          Gestionar Productos
        </a>
        <a href="?store_id=<?= $storeId ?>&action=stock" class="btn btn-warning" style="text-decoration: none;">
          Revisar Stock
        </a>
        <a href="?store_id=<?= $storeId ?>&action=capacity" class="btn btn-success" style="text-decoration: none;">
          Configurar Capacidades
        </a>
        <a href="?store_id=<?= $storeId ?>&action=appointments" class="btn btn-outline" style="text-decoration: none;">
          Ver Citas
        </a>
      </div>
    </div>
  </div>
</div>