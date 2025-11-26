<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/advanced_store_functions.php';

$storeSlug = isset($_GET['slug']) ? (string)$_GET['slug'] : '';
$store = storeBySlug($storeSlug);

if (!$store) {
    http_response_code(404);
    echo 'Tienda no encontrada';
    exit;
}

// Obtener productos con información de stock y disponibilidad
$products = getStoreProductsWithStock($store['id']);

// Configuración de la tienda
$settings = getStoreSettings($store['id']);

// Imágenes de productos (por ahora usando las existentes, pero se pueden personalizar por tienda)
$productImages = [
    1 => 'assets/images/cafe-arab.webp',
    2 => 'assets/images/te-hierbas.jpg', 
    3 => 'assets/images/instalacion-purificador.jpg',
    4 => 'assets/images/cafe-colombia.jpg',
    5 => 'assets/images/filtro-agua.jpg'
];

// Colores de la tienda (personalizable)
$primaryColor = $store['primary_color'] ?? '#5E422E';
$secondaryColor = $settings['secondary_color']['value'] ?? '#926D50';
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($store['name']) ?> - Mall Virtual Viña del Mar</title>
<meta name="description" content="Compra en <?= htmlspecialchars($store['name']) ?>. Productos <?= htmlspecialchars($store['name']) ?> con delivery a Viña del Mar.">
<link rel="stylesheet" href="assets/css/modern.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* Variables específicas de la tienda */
:root {
  --store-primary: <?= $primaryColor ?>;
  --store-secondary: <?= $secondaryColor ?>;
  --store-accent: <?= $settings['accent_color']['value'] ?? '#3CE0C9' ?>;
}

/* Hero Banner de la Tienda */
.store-hero {
  background: linear-gradient(135deg, <?= $primaryColor ?>20, <?= $primaryColor ?>05);
  padding: var(--space-xxxxl) 0;
  margin-bottom: var(--space-xxxl);
  text-align: center;
  position: relative;
  overflow: hidden;
}

.store-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23<?= str_replace('#', '', $primaryColor) ?>" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  opacity: 0.3;
}

.store-hero-content {
  position: relative;
  z-index: 2;
}

.store-logo {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid var(--store-primary);
  box-shadow: var(--shadow-lg);
  margin: 0 auto var(--space-lg);
  display: block;
}

.store-title {
  font-size: 3rem;
  font-weight: 700;
  color: var(--store-primary);
  margin-bottom: var(--space-sm);
  text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.store-subtitle {
  font-size: 1.25rem;
  color: var(--neutral-700);
  margin-bottom: var(--space-xl);
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.store-info {
  display: flex;
  justify-content: center;
  gap: var(--space-xl);
  flex-wrap: wrap;
  margin-top: var(--space-xl);
}

.info-item {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  background: var(--neutral-0);
  padding: var(--space-sm) var(--space-lg);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
}

.info-icon {
  width: 20px;
  height: 20px;
  color: var(--store-primary);
}

/* Productos */
.products-section {
  padding: var(--space-xxxl) 0;
}

.section-title {
  text-align: center;
  font-size: 2.5rem;
  font-weight: 600;
  color: var(--store-primary);
  margin-bottom: var(--space-xl);
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--space-xl);
  margin-top: var(--space-xl);
}

.product-card {
  background: var(--neutral-0);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-md);
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--store-primary);
}

.product-image {
  width: 100%;
  height: 200px;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.product-card:hover .product-image {
  transform: scale(1.05);
}

.product-info {
  padding: var(--space-lg);
}

.product-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--store-primary);
  margin-bottom: var(--space-sm);
}

.product-description {
  color: var(--neutral-700);
  margin-bottom: var(--space-lg);
  line-height: 1.6;
}

.product-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-lg);
}

.product-price {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--store-secondary);
}

.stock-badge {
  padding: var(--space-xs) var(--space-sm);
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
}

.stock-available {
  background: var(--success);
  color: white;
}

.stock-low {
  background: var(--warning);
  color: white;
}

.stock-unavailable {
  background: var(--error);
  color: white;
}

/* Selector de fecha y cantidad */
.product-actions {
  border-top: 1px solid var(--neutral-200);
  padding-top: var(--space-lg);
}

.quantity-selector {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.quantity-btn {
  width: 32px;
  height: 32px;
  border: 2px solid var(--neutral-200);
  background: var(--neutral-0);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quantity-btn:hover {
  border-color: var(--store-primary);
  background: var(--store-primary);
  color: white;
}

.quantity-input {
  width: 60px;
  text-align: center;
  border: 2px solid var(--neutral-200);
  border-radius: var(--radius-sm);
  padding: var(--space-xs);
  font-weight: 600;
}

.date-selector {
  margin-bottom: var(--space-md);
}

.date-selector label {
  display: block;
  margin-bottom: var(--space-xs);
  font-weight: 600;
  color: var(--store-primary);
}

.date-input {
  width: 100%;
  padding: var(--space-sm);
  border: 2px solid var(--neutral-200);
  border-radius: var(--radius-sm);
  font-size: 1rem;
}

.service-options {
  margin-bottom: var(--space-md);
}

.service-option {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-xs);
}

.add-to-cart-btn {
  width: 100%;
  padding: var(--space-md);
  background: var(--store-primary);
  color: white;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.add-to-cart-btn:hover {
  background: var(--store-secondary);
  transform: translateY(-1px);
}

.add-to-cart-btn:disabled {
  background: var(--neutral-300);
  cursor: not-allowed;
  transform: none;
}

/* Responsive */
@media (max-width: 768px) {
  .store-title {
    font-size: 2rem;
  }
  
  .store-info {
    flex-direction: column;
    gap: var(--space-md);
  }
  
  .product-grid {
    grid-template-columns: 1fr;
    gap: var(--space-lg);
  }
}
</style>
</head>
<body>
<!-- Header Principal -->
<header class="main-header">
  <div class="header-content">
    <div class="header-logo">
      <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: var(--space-md);">
        <div style="width: 40px; height: 40px; background: var(--primary-500); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">M</div>
        <span style="font-size: 1.25rem; font-weight: 600; color: var(--primary-500);">Mall Virtual</span>
      </a>
    </div>
    
    <nav class="header-nav">
      <a href="index.php" class="nav-link">Tiendas</a>
      <a href="cart.php" class="cart-link">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="m1 1 4 4 14 14-4-4L1 1z"></path>
        </svg>
        Carrito
        <span class="cart-count" id="cartCount">0</span>
      </a>
    </nav>
  </div>
</header>

<!-- Hero de la Tienda -->
<section class="store-hero">
  <div class="container">
    <div class="store-hero-content">
      <img src="<?= htmlspecialchars($store['logo_url']) ?>" 
           alt="Logo de <?= htmlspecialchars($store['name']) ?>" 
           class="store-logo"
           onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjNUY0MjJFIi8+Cjx0ZXh0IHg9IjYwIiB5PSI2NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjM0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+4oCcPC90ZXh0Pgo8L3N2Zz4K';">
      
      <h1 class="store-title"><?= htmlspecialchars($store['name']) ?></h1>
      <p class="store-subtitle">
        <?= htmlspecialchars($settings['store_description']['value'] ?? 'Productos y servicios de calidad en Viña del Mar') ?>
      </p>
      
      <div class="store-info">
        <div class="info-item">
          <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 8l7.89 4.26c.67.36 1.45.36 2.12 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          <span><?= htmlspecialchars($store['contact_email'] ?? 'No disponible') ?></span>
        </div>
        
        <div class="info-item">
          <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 8l7.89 4.26c.67.36 1.45.36 2.12 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          <span>Delivery <?= (int)($settings['delivery_radius_km']['value'] ?? 25) ?>km</span>
        </div>
        
        <div class="info-item">
          <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span>Entrega en <?= (int)($settings['delivery_days_min']['value'] ?? 1) ?>-<?= (int)($settings['delivery_days_max']['value'] ?? 3) ?> días</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Productos -->
<section class="products-section">
  <div class="container">
    <h2 class="section-title">Nuestros Productos y Servicios</h2>
    
    <?php if (empty($products)): ?>
      <div style="text-align: center; padding: var(--space-xxxl);">
        <p style="color: var(--neutral-600); font-size: 1.125rem;">No hay productos disponibles en este momento.</p>
      </div>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($products as $product): ?>
          <?php 
          $imageUrl = $productImages[$product['id']] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjhGOUYwIi8+CjxwYXRoIGQ9Ik0xMjAgNzBMMjIwIDEyMEgxMjBWNzBaIiBmaWxsPSIjRTVFN0VCIi8+Cjwvc3ZnPgo=';
          
          $stockClass = 'stock-unavailable';
          $stockText = 'Sin Stock';
          
          if ($product['stock_quantity'] > $product['stock_min_threshold']) {
            $stockClass = 'stock-available';
            $stockText = 'Stock: ' . $product['stock_quantity'];
          } elseif ($product['stock_quantity'] > 0) {
            $stockClass = 'stock-low';
            $stockText = 'Poco Stock: ' . $product['stock_quantity'];
          }
          
          $isAvailable = $product['stock_quantity'] > 0;
          $serviceMode = $product['service_mode'] ?? 'standard';
          ?>
          
          <div class="product-card" data-product-id="<?= (int)$product['id'] ?>">
            <img src="<?= htmlspecialchars($imageUrl) ?>" 
                 alt="<?= htmlspecialchars($product['name']) ?>" 
                 class="product-image"
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjhGOUYwIi8+CjxwYXRoIGQ9Ik0xMjAgNzBMMjIwIDEyMEgxMjBWNzBaIiBmaWxsPSIjRTVFN0VCIi8+Cjwvc3ZnPgo=';">
            
            <div class="product-info">
              <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
              <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
              
              <div class="product-meta">
                <span class="product-price">$<?= number_format((float)$product['price'], 2) ?></span>
                <span class="stock-badge <?= $stockClass ?>"><?= $stockText ?></span>
              </div>
              
              <?php if ($isAvailable): ?>
              <div class="product-actions">
                <div class="quantity-selector">
                  <button type="button" class="quantity-btn" onclick="changeQuantity(this, -1)">−</button>
                  <input type="number" class="quantity-input" value="1" min="1" max="<?= (int)$product['stock_quantity'] ?>">
                  <button type="button" class="quantity-btn" onclick="changeQuantity(this, 1)">+</button>
                </div>
                
                <?php if ($serviceMode === 'requires_appointment' || $serviceMode === 'hybrid'): ?>
                <div class="date-selector">
                  <label for="date-<?= (int)$product['id'] ?>">Fecha estimada:</label>
                  <input type="date" 
                         id="date-<?= (int)$product['id'] ?>" 
                         class="date-input" 
                         min="<?= date('Y-m-d') ?>"
                         max="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
                
                <?php if ($serviceMode === 'requires_appointment'): ?>
                <div class="service-options">
                  <div class="service-option">
                    <input type="radio" id="delivery-<?= (int)$product['id'] ?>" name="service-<?= (int)$product['id'] ?>" value="delivery" checked>
                    <label for="delivery-<?= (int)$product['id'] ?>">Entrega a domicilio</label>
                  </div>
                  <div class="service-option">
                    <input type="radio" id="pickup-<?= (int)$product['id'] ?>" name="service-<?= (int)$product['id'] ?>" value="pickup">
                    <label for="pickup-<?= (int)$product['id'] ?>">Retiro en tienda</label>
                  </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <button type="button" 
                        class="add-to-cart-btn" 
                        onclick="addToCartAdvanced(<?= (int)$product['id'] ?>)"
                        <?= !$isAvailable ? 'disabled' : '' ?>>
                  <?php
                  switch ($serviceMode) {
                    case 'requires_appointment':
                      echo 'Agendar Servicio';
                      break;
                    case 'hybrid':
                      echo 'Agregar al Carrito';
                      break;
                    default:
                      echo 'Agregar al Carrito';
                  }
                  ?>
                </button>
              </div>
              <?php else: ?>
              <div class="product-actions">
                <button type="button" class="add-to-cart-btn" disabled>
                  Producto Agotado
                </button>
              </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Footer -->
<footer style="background: var(--neutral-900); color: var(--neutral-0); padding: var(--space-xxxl) 0;">
  <div class="container">
    <div style="text-align: center;">
      <h3 style="color: var(--neutral-0); margin-bottom: var(--space-md);"><?= htmlspecialchars($store['name']) ?></h3>
      <p style="color: var(--neutral-600); margin-bottom: var(--space-xl);">
        <?= htmlspecialchars($store['address']) ?>
      </p>
      <div style="display: flex; justify-content: center; gap: var(--space-lg); flex-wrap: wrap;">
        <a href="index.php" style="color: var(--neutral-600); text-decoration: none;">Mall Virtual</a>
        <a href="#" style="color: var(--neutral-600); text-decoration: none;">Contacto</a>
        <a href="#" style="color: var(--neutral-600); text-decoration: none;">Términos</a>
        <a href="#" style="color: var(--neutral-600); text-decoration: none;">Privacidad</a>
      </div>
      <p style="color: var(--neutral-600); margin-top: var(--space-xl); font-size: 14px;">
        © 2025 <?= htmlspecialchars($store['name']) ?>. Todos los derechos reservados.
      </p>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="assets/js/modern-app.js"></script>
<script>
function changeQuantity(button, change) {
    const input = button.parentNode.querySelector('.quantity-input');
    const currentValue = parseInt(input.value);
    const maxValue = parseInt(input.getAttribute('max'));
    const minValue = parseInt(input.getAttribute('min')) || 1;
    
    const newValue = Math.max(minValue, Math.min(maxValue, currentValue + change));
    input.value = newValue;
}

function addToCartAdvanced(productId) {
    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    const quantityInput = productCard.querySelector('.quantity-input');
    const dateInput = productCard.querySelector('.date-input');
    const serviceOptions = productCard.querySelectorAll('input[name^="service-"]');
    
    const quantity = parseInt(quantityInput.value);
    const selectedDate = dateInput ? dateInput.value : null;
    let serviceType = 'standard';
    
    // Determinar tipo de servicio
    if (serviceOptions.length > 0) {
        const selectedService = Array.from(serviceOptions).find(radio => radio.checked);
        serviceType = selectedService ? selectedService.value : 'standard';
    }
    
    // Validaciones
    if (quantity < 1) {
        showNotification('La cantidad debe ser mayor a 0', 'error');
        return;
    }
    
    if (dateInput && !selectedDate) {
        showNotification('Debe seleccionar una fecha para este servicio', 'error');
        return;
    }
    
    // Preparar datos
    const productData = {
        product_id: productId,
        quantity: quantity,
        service_type: serviceType,
        preferred_date: selectedDate,
        store_id: <?= (int)$store['id'] ?>
    };
    
    // Agregar al carrito (usar la función existente)
    addToCart(productId, quantity);
    
    // Mostrar confirmación
    showNotification(`Producto agregado al carrito. Cantidad: ${quantity}`, 'success');
    
    // Resetear formulario
    quantityInput.value = 1;
    if (dateInput) {
        dateInput.value = '';
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Actualizar contador de carrito al cargar
document.addEventListener('DOMContentLoaded', function() {
    // La función para actualizar el contador se maneja en modern-app.js
});
</script>
</body>
</html>