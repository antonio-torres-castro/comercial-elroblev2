<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../src/functions.php';

// Obtener tiendas
$stores = stores();

// Procesar agregar producto al carrito (si viene de una tienda específica)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_id'], $_POST['qty'])) {
  cartAdd((int)$_POST['add_product_id'], max(1, (int)$_POST['qty']));
  header('Location: cart.php');
  exit;
}

// Obtener total de items en carrito para mostrar contador
$cartItemsCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
  foreach ($_SESSION['cart'] as $item) {
    $cartItemsCount += $item['qty'];
  }
}

// Mapping de banners y logos para las tiendas
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
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mall Virtual - Portal de Tiendas</title>
  <meta name="description" content="Descubre las mejores tiendas en nuestro mall virtual. Compra productos de múltiples tiendas con un solo carrito.">
  <link rel="stylesheet" href="assets/css/modern.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>

<body>
  <!-- Header Principal -->
  <header class="main-header">
    <div class="header-content">
      <div class="header-logo">
        <div style="width: 40px; height: 40px; background: var(--primary-500); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">M</div>
        <h1 class="header-title">Mall Virtual</h1>
      </div>

      <nav class="header-nav">
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

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <h1>Bienvenido a Nuestro Mall Virtual</h1>
      <p style="font-size: 20px; margin-top: var(--space-md); color: var(--neutral-600); max-width: 600px; margin-left: auto; margin-right: auto;">
        Explora nuestras tiendas y encuentra los mejores productos. Compra de múltiples tiendas con un solo carrito.
      </p>
      <div style="margin-top: var(--space-xl);">
        <a href="#stores" class="btn btn-primary">Explorar Tiendas</a>
      </div>
    </div>
  </section>

  <!-- Sección de Tiendas -->
  <section class="section" id="stores">
    <div class="container">
      <h2 class="text-center" style="margin-bottom: var(--space-xxxl);">Nuestras Tiendas</h2>

      <?php if (!empty($stores)): ?>
        <div class="stores-grid">
          <?php foreach ($stores as $store):
            $assets = $storeAssets[$store['slug']] ?? ['banner' => '', 'logo' => ''];
            $productCount = count(products((int)$store['id']));
          ?>
            <div class="card store-card fade-in" onclick="location.href='/stores/<?= htmlspecialchars((string)$store['slug']) ?>/'">
              <img src="<?= htmlspecialchars((string)($assets['banner'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjE2OSIgdmlld0JveD0iMCAwIDMwMCAxNjkiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMTY5IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMDAgMjBIMjAwVjE0MEgxMDBWMjBaIiBmaWxsPSIjRTVFN0VCIi8+Cjwvc3ZnPg==')) ?>"
                alt="<?= htmlspecialchars($store['name']) ?>"
                class="store-banner"
                onerror="this.style.display='none';">

              <div class="store-overlay">
                <img src="<?= htmlspecialchars((string)($assets['logo'] ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjRTVFN0VCIi8+CjxwYXRoIGQ9Ik0yMCAyMEg0NFY0NEgyMFYyMFoiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPg==')) ?>"
                  alt="Logo <?= htmlspecialchars($store['name']) ?>"
                  class="store-logo"
                  onerror="this.style.display='none';">

                <h3 class="store-name"><?= htmlspecialchars($store['name']) ?></h3>
                <?php if (!empty($store['address'])): ?>
                  <p class="store-description">
                    <?= htmlspecialchars((string)$store['address']) ?>
                    <?php if ($productCount > 0): ?>
                      • <?= $productCount ?> producto<?= $productCount !== 1 ? 's' : '' ?>
                    <?php endif; ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center" style="padding: var(--space-xxxl);">
          <h3>No hay tiendas disponibles</h3>
          <p style="color: var(--neutral-600); margin-top: var(--space-md);">
            Pronto encontrarás las mejores tiendas en nuestro mall virtual.
          </p>
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
        <a href="checkout.php" class="btn btn-primary btn-icon" style="width: 100%; justify-content: center;">
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
        <h3 style="color: var(--neutral-0); margin-bottom: var(--space-md);">Mall Virtual</h3>
        <p style="color: var(--neutral-600); margin-bottom: var(--space-xl);">
          Tu destino para compras en línea con múltiples tiendas y un solo carrito.
        </p>
        <div style="display: flex; justify-content: center; gap: var(--space-lg); flex-wrap: wrap;">
          <a href="#" style="color: var(--neutral-600); text-decoration: none;">Sobre Nosotros</a>
          <a href="#" style="color: var(--neutral-600); text-decoration: none;">Contacto</a>
          <a href="#" style="color: var(--neutral-600); text-decoration: none;">Términos</a>
          <a href="#" style="color: var(--neutral-600); text-decoration: none;">Privacidad</a>
        </div>
        <p style="color: var(--neutral-600); margin-top: var(--space-xl); font-size: 14px;">
          © 2025 Mall Virtual. Todos los derechos reservados.
        </p>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="assets/js/modern-app.js"></script>
  <script>
    // Función para abrir el carrito
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

    // Cargar datos del carrito
    function loadCartData() {
      const cartBody = document.querySelector('.cart-body');
      if (!cartBody) return;

      fetch('cart_ajax.php')
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
                <img src="${item.image || 'assets/images/product-placeholder.jpg'}" 
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

      // Actualizar contador en header
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
                <a href="#stores" class="btn btn-primary" onclick="closeCartModal()">Explorar Tiendas</a>
            </div>
        `;
      }

      updateCartSummary({
        subtotal: 0,
        shipping: 0,
        total: 0,
        items_count: 0
      });
    }

    // Funciones auxiliares
    function updateQuantity(productId, change) {
      fetch('cart_ajax.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'update_quantity',
            product_id: productId,
            change: change
          })
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
        fetch('cart_ajax.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              action: 'remove_item',
              product_id: productId
            })
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
  </script>

  <!-- SEO y Analytics -->
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Mall Virtual",
      "description": "Mall virtual con múltiples tiendas y carrito unificado",
      "url": "<?= $_SERVER['HTTP_HOST'] ?? '' ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= $_SERVER['HTTP_HOST'] ?? '' ?>/search?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
  </script>
</body>

</html>