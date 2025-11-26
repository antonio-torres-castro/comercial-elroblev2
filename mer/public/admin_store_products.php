<?php
// Gestión de productos
if (isset($_GET['delete_product'])) {
    $productId = (int)$_GET['delete_product'];
    $stmt = db()->prepare("UPDATE products SET active = 0 WHERE id = ? AND store_id = ?");
    if ($stmt->execute([$productId, $storeId])) {
        $_SESSION['message'] = 'Producto desactivado exitosamente';
    } else {
        $_SESSION['error'] = 'Error al desactivar producto';
    }
    header("Location: admin_store.php?store_id=$storeId&action=products");
    exit;
}

if (isset($_GET['activate_product'])) {
    $productId = (int)$_GET['activate_product'];
    $stmt = db()->prepare("UPDATE products SET active = 1 WHERE id = ? AND store_id = ?");
    if ($stmt->execute([$productId, $storeId])) {
        $_SESSION['message'] = 'Producto activado exitosamente';
    } else {
        $_SESSION['error'] = 'Error al activar producto';
    }
    header("Location: admin_store.php?store_id=$storeId&action=products");
    exit;
}
?>

<div class="section-header">
  <h2 class="section-title">☕ Gestión de Productos</h2>
  <button class="btn btn-primary" onclick="openModal('productModal')">
    + Agregar Producto
  </button>
</div>

<div class="section-content">
  <!-- Filtros y búsqueda -->
  <div style="display: flex; gap: var(--space-md); margin-bottom: var(--space-lg); align-items: center;">
    <input type="text" id="productSearch" placeholder="Buscar productos..." class="form-input" style="flex: 1;">
    <select id="statusFilter" class="form-select" style="width: auto;">
      <option value="">Todos los estados</option>
      <option value="active">Activos</option>
      <option value="inactive">Inactivos</option>
      <option value="low_stock">Stock Bajo</option>
    </select>
    <button class="btn btn-outline" onclick="clearFilters()">Limpiar</button>
  </div>

  <!-- Lista de productos -->
  <div class="table-container">
    <table class="table" id="productsTable">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Tipo</th>
          <th>Entrega</th>
          <th>Estado</th>
          <th>Acciones</th>
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
        
        $serviceType = match($product['service_type']) {
          'producto' => 'Producto',
          'servicio' => 'Servicio',
          'ambos' => 'Producto + Servicio',
          default => 'Producto'
        };
        
        $status = $product['active'] ? 'active' : 'inactive';
        if ($stockLevel === 'low' && $product['active']) {
          $status = 'low_stock';
        }
        ?>
        <tr data-status="<?= $status ?>" data-name="<?= strtolower($product['name']) ?>">
          <td>
            <div style="display: flex; align-items: center; gap: var(--space-sm);">
              <img src="<?= $productImages[$product['id']] ?? '' ?>" 
                   alt="<?= htmlspecialchars($product['name']) ?>" 
                   style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;"
                   onerror="this.style.display='none'">
              <div>
                <div style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></div>
                <div style="font-size: 0.875rem; color: var(--neutral-600); max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                  <?= htmlspecialchars($product['description']) ?>
                </div>
              </div>
            </div>
          </td>
          <td style="font-weight: 600;">$<?= number_format($product['price'], 0) ?></td>
          <td>
            <div style="display: flex; align-items: center; gap: var(--space-xs);">
              <span><?= $product['stock_quantity'] ?></span>
              <span class="stock-indicator stock-<?= $stockLevel ?>" style="font-size: 0.75rem;">
                <?= strtoupper($stockLevel) ?>
              </span>
            </div>
          </td>
          <td>
            <span style="padding: 2px 8px; background: var(--admin-light); border-radius: 4px; font-size: 0.75rem;">
              <?= $serviceType ?>
            </span>
          </td>
          <td>
            <?= $product['delivery_days_min'] ?>-<?= $product['delivery_days_max'] ?> días
          </td>
          <td>
            <?php if ($product['active']): ?>
              <span style="color: var(--admin-success); font-weight: 500;">● Activo</span>
            <?php else: ?>
              <span style="color: var(--admin-danger); font-weight: 500;">● Inactivo</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display: flex; gap: var(--space-xs);">
              <button class="btn btn-outline" onclick="updateStock(<?= $product['id'] ?>, <?= $product['stock_quantity'] ?>)" title="Actualizar Stock">
                📦
              </button>
              <button class="btn btn-outline" onclick="updateCapacity(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')" title="Configurar Capacidad">
                📅
              </button>
              
              <?php if ($product['active']): ?>
                <a href="?store_id=<?= $storeId ?>&action=products&deactivate_product=<?= $product['id'] ?>" 
                   class="btn btn-warning" title="Desactivar"
                   onclick="return confirm('¿Desactivar este producto?')">
                  ⏸️
                </a>
              <?php else: ?>
                <a href="?store_id=<?= $storeId ?>&action=products&activate_product=<?= $product['id'] ?>" 
                   class="btn btn-success" title="Activar">
                  ▶️
                </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (empty($products)): ?>
    <div style="text-align: center; padding: var(--space-xxxl);">
      <p style="color: var(--neutral-600); margin-bottom: var(--space-lg);">
        No hay productos registrados para esta tienda.
      </p>
      <button class="btn btn-primary" onclick="openModal('productModal')">
        + Agregar Primer Producto
      </button>
    </div>
  <?php endif; ?>
</div>

<script>
// Funciones de filtrado
function filterProducts() {
  const searchTerm = document.getElementById('productSearch').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const rows = document.querySelectorAll('#productsTable tbody tr');
  
  rows.forEach(row => {
    const name = row.dataset.name;
    const status = row.dataset.status;
    
    const matchesSearch = name.includes(searchTerm);
    const matchesStatus = !statusFilter || status === statusFilter;
    
    if (matchesSearch && matchesStatus) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

function clearFilters() {
  document.getElementById('productSearch').value = '';
  document.getElementById('statusFilter').value = '';
  filterProducts();
}

// Event listeners
document.getElementById('productSearch').addEventListener('input', filterProducts);
document.getElementById('statusFilter').addEventListener('change', filterProducts);

// Inicializar filtros
filterProducts();
</script>