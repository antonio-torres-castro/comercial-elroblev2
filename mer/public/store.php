<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../src/functions.php';

// Obtener tienda por slug
$slug = $_GET['store'] ?? null;
$store = storeBySlug($slug);

if (!$store) { 
    http_response_code(404); 
    echo 'Tienda no encontrada';
    exit; 
}

// Obtener productos de la tienda
$products = products((int)$store['id']);

// Procesar agregar producto al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_id'], $_POST['qty'])) {
    cartAdd((int)$_POST['add_product_id'], max(1, (int)$_POST['qty']));
    header('Location: cart.php');
    exit;
}

// Obtener total de items en carrito
$cartItemsCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartItemsCount += $item['qty'];
    }
}

// Assets específicos de la tienda
$storeAssets = [
    'tienda-a' => [
        'banner' => 'assets/images/banner-tienda-a.jpg',
        'logo' => 'assets/images/logo-tienda-a.jpg'
    ],
    'tienda-b' => [
        'banner' => 'assets/images/banner-tienda-b.png', 
        'logo' => 'assets/images/logo-tienda-b.jpg'
    ]
];

$assets = $storeAssets[$store['slug']] ?? ['banner' => '', 'logo' => ''];

// Productos con sus imágenes correspondientes
$productImages = [
    1 => 'assets/images/cafe-arab.webp',      // Cafe Arabe
    2 => 'assets/images/te-hierbas.jpg',      // Te de Hierbas
    3 => 'assets/images/instalacion-purificador.jpg', // Instalacion de Purificador
    4 => 'assets/images/cafe-colombia.jpg',   // Cafe Colombia
    5 => 'assets/images/filtro-agua.jpg'      // Filtro de Agua
];
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($store['name']) ?> - Mall Virtual</title>
<meta name="description" content="Descubre los productos de <?= htmlspecialchars($store['name']) ?>. <?= htmlspecialchars((string)$store['address'] ?? '') ?>">
<link rel="stylesheet" href="../assets/css/modern.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
<!-- Header Principal -->
<header class="main-header">
  <div class="header-content">
    <div class="header-logo">
      <a href="../index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: var(--space-md);">
        <div style="width: 40px; height: 40px; background: var(--primary-500); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">M</div>
        <h1 class="header-title">Mall Virtual</h1>
      </a>
    </div>
    
    <nav class="header-nav">
      <a href="../index.php" class="btn btn-outline">Volver al Mall</a>
      <button class="cart-toggle" onclick="openCartModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <span>Carrito</span>
        <?php if ($cartItemsCount > 0): ?>
          <span class="cart-count"><?= htmlspecialchars((string)$cartItemsCount) ?></span>
        <?php else: ?>
          <span class="cart-count" style="display: none;">0</span>
        <?php endif; ?>
      </button>
    </nav>
  </div>
</header>

<!-- Hero Section de la Tienda -->
<section class="hero-section" style="background: linear-gradient(135deg, <?= htmlspecialchars((string)($store['primary_color'] ?? '#0055D4')) ?>20 0%, var(--neutral-0) 100%);">
  <div class="container">
    <div style="display: flex; align-items: center; gap: var(--space-xl); justify-content: center; flex-wrap: wrap;">
      <img src="<?= htmlspecialchars((string)($assets['logo'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRTVFN0VCIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNTAiIHI9IjMwIiBmaWxsPSIjOTk5Ii8+Cjx0ZXh0IHg9IjUwIiB5PSI1NSIgZm9udC1zaXplPSIxNiIgZm9udC13ZWlnaHQ9IjYwMCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPvCfkqc8L3RleHQ+Cjwvc3ZnPg==')) ?>" 
           alt="Logo <?= htmlspecialchars($store['name']) ?>" 
           style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid var(--neutral-0); box-shadow: var(--shadow-md);"
           onerror="this.style.display='none';">
      
      <div style="text-align: center;">
        <h1 style="color: <?= htmlspecialchars((string)($store['primary_color'] ?? '#0055D4')) ?>; margin-bottom: var(--space-md);">
          <?= htmlspecialchars($store['name']) ?>
        </h1>
        <?php if (!empty($store['address'])): ?>
          <p style="font-size: 18px; color: var(--neutral-600); margin-bottom: var(--space-md);">
            📍 <?= htmlspecialchars((string)$store['address']) ?>
          </p>
        <?php endif; ?>
        <?php if (!empty($store['delivery_time_days_min']) && !empty($store['delivery_time_days_max'])): ?>
          <p style="color: var(--neutral-600);">
            🚚 Tiempo de entrega: <?= (int)$store['delivery_time_days_min'] ?>–<?= (int)$store['delivery_time_days_max'] ?> días
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Productos de la Tienda -->
<section class="section">
  <div class="container">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-xl); flex-wrap: wrap; gap: var(--space-md);">
      <h2 style="margin: 0;">Nuestros Productos</h2>
      <div style="color: var(--neutral-600);">
        <?= count($products) ?> producto<?= count($products) !== 1 ? 's' : '' ?> disponible<?= count($products) !== 1 ? 's' : '' ?>
      </div>
    </div>
    
    <?php if (!empty($products)): ?>
      <div class="products-grid">
        <?php foreach ($products as $product): 
          $imageUrl = $productImages[$product['id']] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik01MCA1MEgxNTBWMTUwSDUwVjUwWiIgZmlsbD0iI0U1RTdFQSIvPgo8dGV4dCB4PSIxMDAiIHk9IjEyMCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzlDQTNBRiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+UHJvZHVjdG88L3RleHQ+Cjwvc3ZnPg==';
        ?>
          <div class="card product-card fade-in">
            <img src="<?= htmlspecialchars($imageUrl) ?>" 
                 alt="<?= htmlspecialchars($product['name']) ?>" 
                 class="product-image"
                 loading="lazy"
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik01MCA1MEgxNTBWMTUwSDUwVjUwWiIgZmlsbD0iI0U1RTdFQSIvPgo8dGV4dCB4PSIxMDAiIHk9IjEyMCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzlDQTNBRiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+UHJvZHVjdG88L3RleHQ+Cjwvc3ZnPg==';">
            
            <div class="product-info">
              <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
              
              <?php if (!empty($product['description'])): ?>
                <p class="product-description"><?= htmlspecialchars((string)$product['description']) ?></p>
              <?php else: ?>
                <p class="product-description">Producto de calidad en nuestra tienda.</p>
              <?php endif; ?>
              
              <div class="product-price">
                $<?= number_format((float)$product['price'], 2) ?>
              </div>
              
              <form method="post" class="product-form" onsubmit="return validateProductForm(this)">
                <input type="hidden" name="add_product_id" value="<?= (int)$product['id'] ?>">
                
                <div style="display: flex; gap: var(--space-md); align-items: center; flex-wrap: wrap;">
                  <div style="flex: 1; min-width: 120px;">
                    <label style="display: block; margin-bottom: var(--space-xs); font-weight: 500; color: var(--neutral-900); font-size: 14px;">
                      Cantidad
                    </label>
                    <input type="number" 
                           name="qty" 
                           value="1" 
                           min="1" 
                           max="99" 
                           style="width: 100%; padding: var(--space-sm); border: 1px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 16px;"
                           onchange="validateQuantity(this)">
                  </div>
                  
                  <button type="submit" class="btn btn-primary btn-icon" style="flex: 1; min-width: 150px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="9" cy="21" r="1"></circle>
                      <circle cx="20" cy="21" r="1"></circle>
                      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    Agregar al Carrito
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-center" style="padding: var(--space-xxxl);">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color: var(--neutral-400); margin-bottom: var(--space-md);">
          <path d="M20 6L9 17l-5-5"></path>
        </svg>
        <h3>No hay productos disponibles</h3>
        <p style="color: var(--neutral-600); margin-top: var(--space-md);">
          Esta tienda aún no tiene productos publicados.
        </p>
        <a href="../index.php" class="btn btn-primary" style="margin-top: var(--space-lg);">
          Ver Otras Tiendas
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Modal del Carrito -->
<div class="cart-modal" id="cartModal">
  <div class="cart-content">
    <div class="cart-header">
      <h3>Tu Carrito de Compras</h3>
      <button class="cart-close" onclick="closeCartModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--neutral-600);">&times;</button>
    </div>
    
    <div class="cart-body">
      <div class="text-center" style="padding: 2rem;">
        <p>Cargando carrito...</p>
      </div>
    </div>
    
    <div class="cart-footer">
      <div class="cart-summary">
        <div class="summary-row">
          <span>Subtotal:</span>
          <span id="cartSubtotal">$0</span>
        </div>
        <div class="summary-row">
          <span>Envío:</span>
          <span id="cartShipping">$0</span>
        </div>
        <div class="summary-row" style="border-top: 1px solid var(--neutral-200); padding-top: var(--space-sm);">
          <span class="summary-total">Total:</span>
          <span class="summary-total" id="cartTotal">$0</span>
        </div>
      </div>
      <a href="../checkout.php" class="btn btn-primary btn-icon" style="width: 100%; justify-content: center;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 3h2l.4 2M7 13h10l4-8H5.4"></path>
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
        </svg>
        Finalizar Compra
      </a>
    </div>
  </div>
</div>

<!-- Footer -->
<footer style="background: var(--neutral-900); color: var(--neutral-0); padding: var(--space-xxxl) 0; margin-top: var(--space-xxxxl);">
  <div class="container">
    <div style="text-align: center;">
      <h3 style="color: var(--neutral-0); margin-bottom: var(--space-md);"><?= htmlspecialchars($store['name']) ?></h3>
      <p style="color: var(--neutral-600); margin-bottom: var(--space-xl);">
        <?= htmlspecialchars((string)$store['address'] ?? 'Tienda parte del Mall Virtual') ?>
      </p>
      <div style="display: flex; justify-content: center; gap: var(--space-lg); flex-wrap: wrap; margin-bottom: var(--space-xl);">
        <a href="../index.php" style="color: var(--neutral-600); text-decoration: none;">Mall Virtual</a>
        <a href="#" style="color: var(--neutral-600); text-decoration: none;">Contacto</a>
        <a href="#" style="color: var(--neutral-600); text-decoration: none;">Términos</a>
        <a href="#" style="color: var(--neutral-600); text-decoration: none;">Privacidad</a>
      </div>
      <p style="color: var(--neutral-600); font-size: 14px;">
        © 2025 <?= htmlspecialchars($store['name']) ?>. Parte del Mall Virtual.
      </p>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="../assets/js/modern-app.js"></script>
<script>
// Funciones específicas para esta página
function validateProductForm(form) {
    const qtyInput = form.querySelector('input[name="qty"]');
    const qty = parseInt(qtyInput.value);
    
    if (qty < 1) {
        showNotification('La cantidad mínima es 1', 'warning');
        qtyInput.value = 1;
        return false;
    }
    
    if (qty > 99) {
        showNotification('La cantidad máxima es 99', 'warning');
        qtyInput.value = 99;
        return false;
    }
    
    // Mostrar feedback visual
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin"><circle cx="12" cy="12" r="10"></circle></svg> Agregando...';
    btn.disabled = true;
    
    return true;
}

function validateQuantity(input) {
    const qty = parseInt(input.value);
    
    if (isNaN(qty) || qty < 1) {
        input.value = 1;
    } else if (qty > 99) {
        input.value = 99;
    }
}

// Funciones del carrito (reutilizar las del index.php)
function openCartModal() {
    const modal = document.getElementById('cartModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        loadCartData();
    }
}

function closeCartModal() {
    const modal = document.getElementById('cartModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function loadCartData() {
    const cartBody = document.querySelector('.cart-body');
    if (!cartBody) return;
    
    fetch('../cart_ajax.php')
        .then(response => response.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                renderCartItems(data.items);
                updateCartSummary(data);
            } else {
                showEmptyCart();
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            showEmptyCart();
        });
}

function renderCartItems(items) {
    const cartBody = document.querySelector('.cart-body');
    if (!cartBody) return;
    
    let html = '';
    items.forEach(item => {
        html += `
            <div class="cart-item">
                <img src="../${item.image || 'assets/images/product-placeholder.jpg'}" 
                     alt="${item.name}" 
                     class="cart-item-image"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0NFY0NEgyMFYyMFoiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+';">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-store">${item.store_name}</div>
                    <div class="cart-item-price">$${parseFloat(item.price).toFixed(2)}</div>
                </div>
                <div class="cart-item-controls">
                    <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span class="qty-display">${item.qty}</span>
                    <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                    <button class="qty-btn" onclick="removeFromCart(${item.id})" style="margin-left: 8px; color: var(--error);">×</button>
                </div>
            </div>
        `;
    });
    
    cartBody.innerHTML = html;
}

function updateCartSummary(data) {
    document.getElementById('cartSubtotal').textContent = `$${parseFloat(data.subtotal).toFixed(2)}`;
    document.getElementById('cartShipping').textContent = `$${parseFloat(data.shipping).toFixed(2)}`;
    document.getElementById('cartTotal').textContent = `$${parseFloat(data.total).toFixed(2)}`;
    
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = data.items_count || 0;
        cartCount.style.display = (data.items_count || 0) > 0 ? 'flex' : 'none';
    }
}

function showEmptyCart() {
    const cartBody = document.querySelector('.cart-body');
    if (cartBody) {
        cartBody.innerHTML = `
            <div class="text-center" style="padding: 2rem;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color: var(--neutral-400); margin-bottom: var(--space-md);">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <p style="color: var(--neutral-600); margin-bottom: var(--space-lg);">Tu carrito está vacío</p>
                <a href="#products" class="btn btn-primary" onclick="closeCartModal()">Agregar Productos</a>
            </div>
        `;
    }
    
    updateCartSummary({subtotal: 0, shipping: 0, total: 0, items_count: 0});
}

function updateQuantity(productId, change) {
    fetch('../cart_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'update_quantity', product_id: productId, change: change})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCartData();
            showNotification('Cantidad actualizada', 'success');
        } else {
            showNotification(data.message || 'Error al actualizar', 'error');
        }
    });
}

function removeFromCart(productId) {
    if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
        fetch('../cart_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'remove_item', product_id: productId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCartData();
                showNotification('Producto eliminado', 'success');
            } else {
                showNotification(data.message || 'Error al eliminar', 'error');
            }
        });
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '12px 24px',
        borderRadius: '8px',
        color: 'white',
        fontWeight: '500',
        zIndex: '10000',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease',
        maxWidth: '300px'
    });
    
    const colors = {
        success: 'var(--success)',
        error: 'var(--error)',
        warning: 'var(--warning)',
        info: 'var(--primary-500)'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Animación de entrada para productos
document.addEventListener('DOMContentLoaded', function() {
    const products = document.querySelectorAll('.product-card');
    products.forEach((product, index) => {
        setTimeout(() => {
            product.classList.add('fade-in');
        }, index * 100);
    });
});

// Smooth scroll para anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<!-- Schema.org para SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Store",
  "name": "<?= htmlspecialchars($store['name']) ?>",
  "description": "<?= htmlspecialchars((string)$store['address'] ?? 'Tienda en Mall Virtual') ?>",
  "url": "<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= htmlspecialchars((string)$store['address'] ?? '') ?>",
    "addressCountry": "CL"
  },
  "telephone": "<?= htmlspecialchars((string)$store['contact_email'] ?? '') ?>",
  "openingHours": "Mo-Su 09:00-18:00",
  "priceRange": "$$"
}
</script>
</body>
</html>