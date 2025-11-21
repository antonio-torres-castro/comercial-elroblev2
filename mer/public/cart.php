<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../src/functions.php';

// Procesar formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Actualizar cantidades
  if (isset($_POST['update']) && isset($_POST['qty']) && is_array($_POST['qty'])) {
    foreach ($_POST['qty'] as $pid => $q) {
      cartUpdate((int)$pid, max(0, (int)$q));
    }
    header('Location: cart.php');
    exit;
  }
  
  // Vaciar carrito
  if (isset($_POST['clear'])) {
    cartClear();
    header('Location: cart.php');
    exit;
  }
  
  // Seleccionar métodos de envío
  if (isset($_POST['shipping']) && is_array($_POST['shipping'])) {
    foreach ($_POST['shipping'] as $pid => $mid) {
      shippingSelectionSet((int)$pid, (int)$mid);
    }
  }
  
  // Direcciones de entrega
  if (isset($_POST['addr']) && is_array($_POST['addr'])) {
    foreach ($_POST['addr'] as $pid => $addr) {
      $city = $_POST['city'][$pid] ?? '';
      deliveryAddressSet((int)$pid, (string)$addr, (string)$city);
    }
  }
  
  // Aplicar cupón
  if (isset($_POST['coupon_code'])) {
    $code = trim((string)$_POST['coupon_code']);
    if (empty($code)) {
      couponClear();
    } else {
      $ok = couponApply($code);
      if (!$ok) {
        // El cupón no es válido, pero no redirigimos, solo mostramos mensaje
        $_SESSION['coupon_error'] = 'Código de cupón inválido o expirado';
      }
    }
  }
  
  // Redireccionar para evitar reenvío de formulario
  if (!isset($_SESSION['coupon_error'])) {
    header('Location: cart.php');
    exit;
  }
}

// Obtener totales del carrito
$t = totals();

// Verificar error de cupón
$couponError = $_SESSION['coupon_error'] ?? null;
if ($couponError) {
  unset($_SESSION['coupon_error']);
}

// Contar items en carrito
$cartItemsCount = 0;
foreach ($t['items'] as $item) {
  $cartItemsCount += $item['qty'];
}

// Mapping de imágenes para productos
$productImages = [
    1 => '../assets/images/cafe-arab.webp',      // Cafe Arabe
    2 => '../assets/images/te-hierbas.jpg',      // Te de Hierbas  
    3 => '../assets/images/instalacion-purificador.jpg', // Instalacion de Purificador
    4 => '../assets/images/cafe-colombia.jpg',   // Cafe Colombia
    5 => '../assets/images/filtro-agua.jpg'      // Filtro de Agua
];
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Carrito de Compras - Mall Virtual</title>
<meta name="description" content="Revisa y gestiona los productos en tu carrito de compras del mall virtual.">
<link rel="stylesheet" href="assets/css/modern.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
<!-- Header Principal -->
<header class="main-header">
  <div class="header-content">
    <div class="header-logo">
      <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: var(--space-md);">
        <div style="width: 40px; height: 40px; background: var(--primary-500); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">M</div>
        <h1 class="header-title">Mall Virtual</h1>
      </a>
    </div>
    
    <nav class="header-nav">
      <a href="index.php" class="btn btn-outline">Volver al Mall</a>
      <?php if (!empty($t['items'])): ?>
        <span style="color: var(--neutral-600); font-weight: 500;">
          <?= $cartItemsCount ?> producto<?= $cartItemsCount !== 1 ? 's' : '' ?>
        </span>
      <?php endif; ?>
    </nav>
  </div>
</header>

<!-- Hero Section -->
<section class="hero-section" style="background: linear-gradient(135deg, var(--primary-100) 0%, var(--neutral-0) 100%);">
  <div class="container">
    <h1>Tu Carrito de Compras</h1>
    <p style="font-size: 18px; margin-top: var(--space-md); color: var(--neutral-600);">
      <?php if (!empty($t['items'])): ?>
        Tienes <?= $cartItemsCount ?> producto<?= $cartItemsCount !== 1 ? 's' : '' ?> en tu carrito de <?= count($t['per_store']) ?> tienda<?= count($t['per_store']) !== 1 ? 's' : '' ?>
      <?php else: ?>
        Tu carrito está vacío. ¡Explora nuestras tiendas!
      <?php endif; ?>
    </p>
  </div>
</section>

<?php if (!empty($t['items'])): ?>
<!-- Contenido del Carrito -->
<section class="section">
  <div class="container">
    <form method="post" id="cartForm">
      <!-- Alerta de error de cupón -->
      <?php if ($couponError): ?>
        <div style="background: var(--error); color: white; padding: var(--space-md); border-radius: var(--radius-sm); margin-bottom: var(--space-lg); display: flex; align-items: center; gap: var(--space-sm);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          <?= htmlspecialchars($couponError) ?>
        </div>
      <?php endif; ?>
      
      <!-- Productos agrupados por tienda -->
      <?php foreach ($t['per_store'] as $sid => $storeData): 
        $store = $storeData['store'];
      ?>
        <div class="card" style="margin-bottom: var(--space-xl);">
          <div style="border-bottom: 1px solid var(--neutral-200); padding-bottom: var(--space-lg); margin-bottom: var(--space-lg);">
            <div style="display: flex; align-items: center; gap: var(--space-md);">
              <?php if (!empty($store['logo_url'])): ?>
                <img src="<?= htmlspecialchars((string)$store['logo_url']) ?>" 
                     alt="Logo <?= htmlspecialchars((string)$store['name']) ?>" 
                     style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
              <?php endif; ?>
              <div>
                <h3 style="margin: 0; color: <?= htmlspecialchars((string)($store['primary_color'] ?? '#0055D4')) ?>;">
                  <?= htmlspecialchars((string)$store['name']) ?>
                </h3>
                <?php if (!empty($store['address'])): ?>
                  <p style="margin: 0; color: var(--neutral-600); font-size: 14px;">
                    <?= htmlspecialchars((string)$store['address']) ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <!-- Productos de la tienda -->
          <div style="display: flex; flex-direction: column; gap: var(--space-lg);">
            <?php foreach ($storeData['items'] as $itemIndex => $item): 
              $product = $item['product'];
              $imageUrl = $productImages[$product['id']] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0NFY0NEgyMFYyMFoiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+';
            ?>
              <div style="display: grid; grid-template-columns: 80px 1fr auto auto auto; gap: var(--space-lg); align-items: start; padding: var(--space-md); border: 1px solid var(--neutral-200); border-radius: var(--radius-sm);">
                <!-- Imagen del producto -->
                <img src="<?= htmlspecialchars($imageUrl) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     style="width: 80px; height: 80px; border-radius: var(--radius-sm); object-fit: cover;"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg2MFY2MEgyMFYyMFoiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+';">
                
                <!-- Información del producto -->
                <div>
                  <h4 style="margin: 0 0 var(--space-xs) 0; font-size: 16px;"><?= htmlspecialchars($product['name']) ?></h4>
                  <p style="margin: 0; color: var(--neutral-600); font-size: 14px;"><?= htmlspecialchars((string)$product['description']) ?></p>
                  <p style="margin: var(--space-xs) 0 0 0; font-weight: 600; color: var(--primary-500);">
                    $<?= number_format((float)$product['price'], 2) ?> c/u
                  </p>
                </div>
                
                <!-- Cantidad -->
                <div>
                  <label style="display: block; margin-bottom: var(--space-xs); font-size: 12px; color: var(--neutral-600);">Cantidad</label>
                  <input type="number" 
                         name="qty[<?= (int)$product['id'] ?>]" 
                         value="<?= (int)$item['qty'] ?>" 
                         min="0" 
                         max="99"
                         style="width: 80px; padding: var(--space-sm); border: 1px solid var(--neutral-200); border-radius: var(--radius-sm);">
                </div>
                
                <!-- Método de envío -->
                <div>
                  <label style="display: block; margin-bottom: var(--space-xs); font-size: 12px; color: var(--neutral-600);">Envío</label>
                  <select name="shipping[<?= (int)$product['id'] ?>]" class="ship-select form-select" style="min-width: 150px;">
                    <?php foreach ($item['shipping_methods'] as $method): ?>
                      <option value="<?= (int)$method['id'] ?>" 
                              <?= (int)$item['selected_shipping_id'] === (int)$method['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($method['name']) ?> 
                        <?php if ((float)$method['cost'] > 0): ?>
                          (+$<?= number_format((float)$method['cost'], 2) ?>)
                        <?php else: ?>
                          (Gratis)
                        <?php endif; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <!-- Dirección de entrega -->
                <div>
                  <label style="display: block; margin-bottom: var(--space-xs); font-size: 12px; color: var(--neutral-600);">Dirección</label>
                  <div style="display: flex; flex-direction: column; gap: var(--space-xs); min-width: 250px;">
                    <input type="text" 
                           name="addr[<?= (int)$product['id'] ?>]" 
                           value="<?= htmlspecialchars((string)($item['delivery']['address'] ?? '')) ?>" 
                           placeholder="Dirección de entrega"
                           style="padding: var(--space-sm); border: 1px solid var(--neutral-200); border-radius: var(--radius-sm);">
                    <input type="text" 
                           name="city[<?= (int)$product['id'] ?>]" 
                           value="<?= htmlspecialchars((string)($item['delivery']['city'] ?? '')) ?>" 
                           placeholder="Ciudad"
                           style="padding: var(--space-sm); border: 1px solid var(--neutral-200); border-radius: var(--radius-sm);">
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <!-- Totales por tienda -->
          <div style="border-top: 1px solid var(--neutral-200); padding-top: var(--space-lg); margin-top: var(--space-lg);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="color: var(--neutral-600);">Subtotal tienda:</span>
              <span style="font-weight: 600;">$<?= number_format((float)$storeData['subtotal'], 2) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="color: var(--neutral-600);">Envío:</span>
              <span style="font-weight: 600;">$<?= number_format((float)$storeData['shipping'], 2) ?></span>
            </div>
            <?php if ($storeData['discount'] > 0): ?>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--success);">Descuento:</span>
                <span style="font-weight: 600; color: var(--success);">-$<?= number_format((float)$storeData['discount'], 2) ?></span>
              </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: 700; color: var(--primary-500); border-top: 1px solid var(--neutral-200); padding-top: var(--space-sm); margin-top: var(--space-sm);">
              <span>Total tienda:</span>
              <span>$<?= number_format((float)$storeData['total'], 2) ?></span>
            </div>
            
            <?php if (!empty($store['delivery_time_days_min']) && !empty($store['delivery_time_days_max'])): ?>
              <p style="margin: var(--space-sm) 0 0 0; color: var(--neutral-600); font-size: 14px;">
                🚚 Tiempo estimado: <?= (int)$store['delivery_time_days_min'] ?>–<?= (int)$store['delivery_time_days_max'] ?> días hábiles
              </p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      
      <!-- Acciones del carrito -->
      <div style="display: flex; gap: var(--space-md); justify-content: space-between; align-items: center; margin-top: var(--space-xl); flex-wrap: wrap;">
        <div style="display: flex; gap: var(--space-md);">
          <button type="submit" name="update" value="1" class="btn btn-outline">
            Actualizar Carrito
          </button>
          <button type="submit" name="clear" value="1" class="btn btn-secondary" onclick="return confirm('¿Estás seguro de que quieres vaciar el carrito?')">
            Vaciar Carrito
          </button>
        </div>
        
        <a href="checkout.php" class="btn btn-primary btn-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4"></path>
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
          </svg>
          Proceder al Checkout
        </a>
      </div>
    </form>
  </div>
</section>

<!-- Sección de Cupones y Totales -->
<section class="section" style="background: var(--neutral-100);">
  <div class="container">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-xxl); max-width: 800px; margin: 0 auto;">
      
      <!-- Aplicar Cupón -->
      <div class="card">
        <h3 style="margin-bottom: var(--space-lg);">Código de Cupón</h3>
        <form method="post">
          <div style="display: flex; gap: var(--space-md); align-items: end;">
            <div style="flex: 1;">
              <label class="form-label">Código de cupón</label>
              <input type="text" 
                     name="coupon_code" 
                     placeholder="Ingresa tu código"
                     value="<?= htmlspecialchars((string)($t['coupon']['code'] ?? '')) ?>"
                     class="form-input">
              <?php if ($t['coupon']): ?>
                <p style="margin: var(--space-xs) 0 0 0; color: var(--success); font-size: 14px;">
                  ✓ Cupón aplicado: <?= htmlspecialchars((string)$t['coupon']['code']) ?>
                </p>
              <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-outline" style="min-width: 100px;">
              <?php if ($t['coupon']): ?>
                Remover
              <?php else: ?>
                Aplicar
              <?php endif; ?>
            </button>
          </div>
        </form>
      </div>
      
      <!-- Resumen Total -->
      <div class="card">
        <h3 style="margin-bottom: var(--space-lg);">Resumen de Compra</h3>
        
        <div style="display: flex; flex-direction: column; gap: var(--space-md);">
          <div style="display: flex; justify-content: space-between;">
            <span>Subtotal (<?= $cartItemsCount ?> productos):</span>
            <span>$<?= number_format((float)$t['subtotal'], 2) ?></span>
          </div>
          
          <div style="display: flex; justify-content: space-between;">
            <span>Envío total:</span>
            <span>$<?= number_format((float)$t['shipping'], 2) ?></span>
          </div>
          
          <?php if ($t['discount'] > 0): ?>
            <div style="display: flex; justify-content: space-between; color: var(--success);">
              <span>Descuento:</span>
              <span>-$<?= number_format((float)$t['discount'], 2) ?></span>
            </div>
          <?php endif; ?>
          
          <div style="border-top: 2px solid var(--neutral-200); padding-top: var(--space-md); margin-top: var(--space-md);">
            <div style="display: flex; justify-content: space-between; font-size: 24px; font-weight: 700; color: var(--primary-500);">
              <span>Total a pagar:</span>
              <span>$<?= number_format((float)$t['total'], 2) ?></span>
            </div>
          </div>
        </div>
        
        <a href="checkout.php" class="btn btn-primary btn-icon" style="width: 100%; justify-content: center; margin-top: var(--space-lg);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4"></path>
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
          </svg>
          Proceder al Checkout
        </a>
        
        <p style="text-align: center; color: var(--neutral-600); font-size: 14px; margin-top: var(--space-md);">
          Pago seguro • Envío a todo el país
        </p>
      </div>
    </div>
  </div>
</section>

<?php else: ?>
<!-- Carrito Vacío -->
<section class="section">
  <div class="container">
    <div class="text-center" style="padding: var(--space-xxxl);">
      <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color: var(--neutral-400); margin-bottom: var(--space-xl);">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
      </svg>
      
      <h2 style="margin-bottom: var(--space-md);">Tu carrito está vacío</h2>
      <p style="color: var(--neutral-600); margin-bottom: var(--space-xl); max-width: 400px; margin-left: auto; margin-right: auto;">
        Explora nuestras tiendas y encuentra productos increíbles. ¡Agrega algunos productos para comenzar!
      </p>
      
      <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
        <a href="index.php" class="btn btn-primary btn-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9,22 9,12 15,12 15,22"></polyline>
          </svg>
          Explorar Tiendas
        </a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Footer -->
<footer style="background: var(--neutral-900); color: var(--neutral-0); padding: var(--space-xxxl) 0;">
  <div class="container">
    <div style="text-align: center;">
      <h3 style="color: var(--neutral-0); margin-bottom: var(--space-md);">Mall Virtual</h3>
      <p style="color: var(--neutral-600); margin-bottom: var(--space-xl);">
        Tu destino para compras en línea con múltiples tiendas y un solo carrito.
      </p>
      <div style="display: flex; justify-content: center; gap: var(--space-lg); flex-wrap: wrap;">
        <a href="index.php" style="color: var(--neutral-600); text-decoration: none;">Inicio</a>
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
// Auto-actualizar formulario al cambiar métodos de envío
document.querySelectorAll('.ship-select').forEach(select => {
    select.addEventListener('change', function() {
        const form = document.getElementById('cartForm');
        if (form) {
            const updateField = document.createElement('input');
            updateField.type = 'hidden';
            updateField.name = 'update';
            updateField.value = '1';
            form.appendChild(updateField);
            
            // Auto-submit después de un delay
            setTimeout(() => {
                form.submit();
            }, 300);
        }
    });
});

// Validación de cantidades en tiempo real
document.querySelectorAll('input[name^="qty"]').forEach(input => {
    input.addEventListener('input', function() {
        const value = parseInt(this.value);
        if (isNaN(value) || value < 0) {
            this.value = 0;
        } else if (value > 99) {
            this.value = 99;
        }
    });
});

// Mostrar notificaciones de éxito para actualizaciones automáticas
function showAutoUpdateMessage() {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--success);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    notification.textContent = 'Carrito actualizado automáticamente';
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 2000);
}

// Animación de entrada
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
});

// Manejo de formularios mejorado
document.getElementById('cartForm').addEventListener('submit', function(e) {
    const updateBtn = e.submitter;
    if (updateBtn && updateBtn.name === 'clear') {
        if (!confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
            e.preventDefault();
            return;
        }
    }
    
    // Feedback visual para botones
    if (updateBtn) {
        const originalText = updateBtn.textContent;
        updateBtn.textContent = 'Procesando...';
        updateBtn.disabled = true;
        
        setTimeout(() => {
            updateBtn.textContent = originalText;
            updateBtn.disabled = false;
        }, 1000);
    }
});
</script>

<!-- Schema.org para SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ShoppingCart",
  "name": "Carrito de Compras Mall Virtual",
  "description": "Carrito de compras con productos de múltiples tiendas",
  "url": "<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>",
  "totalValue": "<?= $t['total'] ?>",
  "numberOfItems": <?= $cartItemsCount ?>
}
</script>
</body>
</html>