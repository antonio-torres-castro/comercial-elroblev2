<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/advanced_store_functions.php';
require_once __DIR__ . '/../src/auth_functions.php';

init_secure_session();

// Verificar autenticación de administrador
requireRole('admin');

$storeId = isset($_GET['store_id']) ? (int)$_GET['store_id'] : 1; // Por defecto tienda-a
$store = storeById($storeId);

if (!$store) {
    echo 'Tienda no encontrada';
    exit;
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : (isset($_GET['action']) ? (string)$_GET['action'] : 'dashboard');

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add_product':
            $productData = [
                'store_id' => $storeId,
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => (float)($_POST['price'] ?? 0),
                'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
                'stock_min_threshold' => (int)($_POST['stock_min_threshold'] ?? 5),
                'delivery_days_min' => (int)($_POST['delivery_days_min'] ?? 1),
                'delivery_days_max' => (int)($_POST['delivery_days_max'] ?? 3),
                'service_type' => $_POST['service_type'] ?? 'producto',
                'requires_appointment' => isset($_POST['requires_appointment']) ? 1 : 0,
                'image_url' => $_POST['image_url'] ?? '',
                'active' => 1
            ];
            
            $result = upsertProduct($productData);
            if ($result['success']) {
                $_SESSION['message'] = 'Producto agregado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al agregar producto: ' . $result['error'];
            }
            break;
            
        case 'update_stock':
            $productId = (int)($_POST['product_id'] ?? 0);
            $newStock = (int)($_POST['stock_quantity'] ?? 0);
            $reason = $_POST['reason'] ?? 'Ajuste manual';
            
            $result = updateProductStock($productId, $newStock, $reason);
            if ($result['success']) {
                $_SESSION['message'] = 'Stock actualizado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al actualizar stock';
            }
            break;
            
        case 'update_capacity':
            $productId = (int)($_POST['product_id'] ?? 0);
            $date = $_POST['capacity_date'] ?? '';
            $capacity = (int)($_POST['available_capacity'] ?? 0);
            
            if ($productId && $date && $capacity >= 0) {
                $stmt = db()->prepare("
                    INSERT INTO product_daily_capacity (product_id, store_id, capacity_date, available_capacity) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE available_capacity = VALUES(available_capacity)
                ");
                if ($stmt->execute([$productId, $storeId, $date, $capacity])) {
                    $_SESSION['message'] = 'Capacidad actualizada exitosamente';
                } else {
                    $_SESSION['error'] = 'Error al actualizar capacidad';
                }
            }
            break;
    }
    
    header("Location: admin_store.php?store_id=$storeId");
    exit;
}

$products = getStoreProductsWithStock($storeId, 100, 0);
$lowStockProducts = getLowStockProducts($storeId);
$availabilityStats = getProductAvailabilityStats($storeId);
$pickupLocations = getStorePickupLocations($storeId);
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin - <?= htmlspecialchars($store['name']) ?> - Mall Virtual</title>
<link rel="stylesheet" href="assets/css/modern.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --admin-primary: #1F2937;
  --admin-secondary: #374151;
  --admin-accent: #3B82F6;
  --admin-success: #10B981;
  --admin-warning: #F59E0B;
  --admin-danger: #EF4444;
  --admin-light: #F9FAFB;
  --admin-border: #E5E7EB;
}

.admin-container {
  display: flex;
  min-height: 100vh;
  background: var(--admin-light);
}

.admin-sidebar {
  width: 280px;
  background: var(--admin-primary);
  color: white;
  padding: 0;
}

.admin-main {
  flex: 1;
  padding: var(--space-xl);
}

.sidebar-header {
  padding: var(--space-lg);
  border-bottom: 1px solid var(--admin-secondary);
}

.store-name {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: var(--space-xs);
}

.store-subtitle {
  color: #9CA3AF;
  font-size: 0.875rem;
}

.nav-menu {
  padding: var(--space-lg) 0;
}

.nav-item {
  display: block;
  padding: var(--space-sm) var(--space-lg);
  color: #D1D5DB;
  text-decoration: none;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.nav-item:hover,
.nav-item.active {
  background: var(--admin-secondary);
  border-left-color: var(--admin-accent);
  color: white;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--space-lg);
  margin-bottom: var(--space-xxxl);
}

.stat-card {
  background: white;
  padding: var(--space-lg);
  border-radius: var(--radius-md);
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid var(--admin-border);
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--admin-primary);
  margin-bottom: var(--space-xs);
}

.stat-label {
  color: var(--neutral-600);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-change {
  margin-top: var(--space-sm);
  font-size: 0.875rem;
}

.stat-change.positive {
  color: var(--admin-success);
}

.stat-change.negative {
  color: var(--admin-danger);
}

.section {
  background: white;
  margin-bottom: var(--space-xl);
  border-radius: var(--radius-md);
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid var(--admin-border);
}

.section-header {
  padding: var(--space-lg);
  border-bottom: 1px solid var(--admin-border);
  display: flex;
  justify-content: between;
  align-items: center;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--admin-primary);
}

.section-content {
  padding: var(--space-lg);
}

.product-grid {
  display: grid;
  gap: var(--space-lg);
}

.product-item {
  padding: var(--space-lg);
  border: 1px solid var(--admin-border);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  gap: var(--space-lg);
}

.product-image {
  width: 80px;
  height: 80px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  background: var(--admin-light);
}

.product-info {
  flex: 1;
}

.product-name {
  font-weight: 600;
  color: var(--admin-primary);
  margin-bottom: var(--space-xs);
}

.product-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);
}

.product-detail {
  font-size: 0.875rem;
  color: var(--neutral-600);
}

.stock-indicator {
  padding: var(--space-xs) var(--space-sm);
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.stock-high {
  background: #D1FAE5;
  color: #065F46;
}

.stock-medium {
  background: #FEF3C7;
  color: #92400E;
}

.stock-low {
  background: #FEE2E2;
  color: #991B1B;
}

.btn {
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
}

.btn-primary {
  background: var(--admin-accent);
  color: white;
}

.btn-primary:hover {
  background: #2563EB;
}

.btn-success {
  background: var(--admin-success);
  color: white;
}

.btn-warning {
  background: var(--admin-warning);
  color: white;
}

.btn-danger {
  background: var(--admin-danger);
  color: white;
}

.btn-outline {
  background: transparent;
  color: var(--admin-primary);
  border: 1px solid var(--admin-border);
}

.btn-outline:hover {
  background: var(--admin-light);
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal.show {
  display: flex;
}

.modal-content {
  background: white;
  padding: var(--space-xl);
  border-radius: var(--radius-md);
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.form-group {
  margin-bottom: var(--space-lg);
}

.form-label {
  display: block;
  margin-bottom: var(--space-sm);
  font-weight: 500;
  color: var(--admin-primary);
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: var(--space-sm);
  border: 1px solid var(--admin-border);
  border-radius: var(--radius-sm);
  font-size: 1rem;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-md);
}

.alert {
  padding: var(--space-md);
  border-radius: var(--radius-sm);
  margin-bottom: var(--space-lg);
}

.alert-success {
  background: #D1FAE5;
  color: #065F46;
  border: 1px solid #A7F3D0;
}

.alert-error {
  background: #FEE2E2;
  color: #991B1B;
  border: 1px solid #FECACA;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: var(--space-sm);
  text-align: left;
  border-bottom: 1px solid var(--admin-border);
}

.table th {
  background: var(--admin-light);
  font-weight: 600;
  color: var(--admin-primary);
}

@media (max-width: 768px) {
  .admin-container {
    flex-direction: column;
  }
  
  .admin-sidebar {
    width: 100%;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .product-item {
    flex-direction: column;
    text-align: center;
  }
}
</style>
</head>
<body>
<div class="admin-container">
  <!-- Sidebar -->
  <div class="admin-sidebar">
    <div class="sidebar-header">
      <div class="store-name"><?= htmlspecialchars($store['name']) ?></div>
      <div class="store-subtitle">Panel de Administración</div>
    </div>
    
    <nav class="nav-menu">
      <a href="?store_id=<?= $storeId ?>&action=dashboard" 
         class="nav-item <?= $action === 'dashboard' ? 'active' : '' ?>">
        📊 Dashboard
      </a>
      <a href="?store_id=<?= $storeId ?>&action=products" 
         class="nav-item <?= $action === 'products' ? 'active' : '' ?>">
        ☕ Productos
      </a>
      <a href="?store_id=<?= $storeId ?>&action=stock" 
         class="nav-item <?= $action === 'stock' ? 'active' : '' ?>">
        📦 Gestión Stock
      </a>
      <a href="?store_id=<?= $storeId ?>&action=capacity" 
         class="nav-item <?= $action === 'capacity' ? 'active' : '' ?>">
        📅 Capacidades
      </a>
      <a href="?store_id=<?= $storeId ?>&action=appointments" 
         class="nav-item <?= $action === 'appointments' ? 'active' : '' ?>">
        ⏰ Citas
      </a>
      <a href="?store_id=<?= $storeId ?>&action=deliveries" 
         class="nav-item <?= $action === 'deliveries' ? 'active' : '' ?>">
        🚚 Despachos
      </a>
      <a href="?store_id=<?= $storeId ?>&action=settings" 
         class="nav-item <?= $action === 'settings' ? 'active' : '' ?>">
        ⚙️ Configuración
      </a>
      <a href="../index.php" class="nav-item">
        ← Volver al Admin Principal
      </a>
    </nav>
  </div>
  
  <!-- Main Content -->
  <div class="admin-main">
    <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['message']) ?>
      </div>
      <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php
    switch ($action) {
        case 'dashboard':
            include 'admin_store_dashboard.php';
            break;
        case 'products':
            include 'admin_store_products.php';
            break;
        case 'stock':
            include 'admin_store_stock.php';
            break;
        case 'capacity':
            include 'admin_store_capacity.php';
            break;
        case 'appointments':
            include 'admin_store_appointments.php';
            break;
        case 'deliveries':
            include 'admin_store_deliveries.php';
            break;
        case 'settings':
            include 'admin_store_settings.php';
            break;
        default:
            include 'admin_store_dashboard.php';
    }
    ?>
  </div>
</div>

<!-- Modales -->
<div id="productModal" class="modal">
  <div class="modal-content">
    <h3>Agregar/Editar Producto</h3>
    <form method="POST" action="">
      <input type="hidden" name="action" value="add_product">
      
      <div class="form-group">
        <label class="form-label">Nombre del Producto</label>
        <input type="text" name="name" class="form-input" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-textarea" required></textarea>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Precio ($)</label>
          <input type="number" name="price" step="0.01" class="form-input" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Stock Inicial</label>
          <input type="number" name="stock_quantity" class="form-input" required>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Stock Mínimo</label>
          <input type="number" name="stock_min_threshold" class="form-input" value="5" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Tipo de Servicio</label>
          <select name="service_type" class="form-select" required>
            <option value="producto">Producto</option>
            <option value="servicio">Servicio</option>
            <option value="ambos">Producto + Servicio</option>
          </select>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Días Mín. Entrega</label>
          <input type="number" name="delivery_days_min" class="form-input" value="1" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Días Máx. Entrega</label>
          <input type="number" name="delivery_days_max" class="form-input" value="3" required>
        </div>
      </div>
      
      <div class="form-group">
        <label>
          <input type="checkbox" name="requires_appointment" value="1">
          Requiere agendamiento
        </label>
      </div>
      
      <div class="form-group">
        <label class="form-label">URL de Imagen</label>
        <input type="url" name="image_url" class="form-input">
      </div>
      
      <div style="display: flex; gap: var(--space-md); justify-content: flex-end;">
        <button type="button" class="btn btn-outline" onclick="closeModal('productModal')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Producto</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(modalId) {
  document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove('show');
}

function updateStock(productId, currentStock) {
  const newStock = prompt('Ingrese el nuevo stock:', currentStock);
  if (newStock !== null && newStock !== '') {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="update_stock">
      <input type="hidden" name="product_id" value="${productId}">
      <input type="hidden" name="stock_quantity" value="${newStock}">
      <input type="hidden" name="reason" value="Actualización manual">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

function updateCapacity(productId, productName) {
  const date = prompt('Fecha (YYYY-MM-DD):');
  const capacity = prompt('Capacidad disponible:');
  
  if (date && capacity !== null && capacity !== '') {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="update_capacity">
      <input type="hidden" name="product_id" value="${productId}">
      <input type="hidden" name="capacity_date" value="${date}">
      <input type="hidden" name="available_capacity" value="${capacity}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal')) {
    e.target.classList.remove('show');
  }
});
</script>
</body>
</html>