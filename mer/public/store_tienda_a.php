<?php

declare(strict_types=1);
require_once __DIR__ . '/../src/auth_functions.php';

init_secure_session();
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/advanced_store_functions.php';
require_once __DIR__ . '/../src/router.php';

// Configuración específica para Tienda-A (Café Brew)
$storeSlug = 'tienda-a';
$store = storeBySlug($storeSlug);

if (!$store) {
  http_response_code(404);
  echo 'Tienda no encontrada';
  exit;
}

// Configuración personalizada para Café Brew
$coffeeSettings = [
  'store_description' => [
    'value' => 'Los mejores cafés de especialidad de Chile y el mundo. Granos seleccionados, tostados artesanalmente y servicio de preparación profesional.',
    'type' => 'text'
  ],
  'secondary_color' => [
    'value' => '#8B4513', // Marrón café
    'type' => 'text'
  ],
  'accent_color' => [
    'value' => '#D2691E', // Naranja café
    'type' => 'text'
  ],
  'business_hours_start' => ['value' => '08:00', 'type' => 'text'],
  'business_hours_end' => ['value' => '18:00', 'type' => 'text'],
  'max_daily_orders' => ['value' => '50', 'type' => 'number'],
  'min_order_amount' => ['value' => '8000', 'type' => 'number'],
  'delivery_radius_km' => ['value' => '30', 'type' => 'number'],
  'delivery_days_min' => ['value' => '1', 'type' => 'number'],
  'delivery_days_max' => ['value' => '2', 'type' => 'number']
];

// Actualizar configuración en la base de datos
foreach ($coffeeSettings as $key => $setting) {
  updateStoreSetting($store['id'], $key, $setting['value'], $setting['type'], '');
}

// Obtener productos con información de stock
$products = getStoreProductsWithStock($store['id']);

// Configurar imágenes específicas para café
$productImages = [
  1 => 'assets/images/cafe-arab.webp',
  2 => 'assets/images/te-hierbas.jpg',
  3 => 'assets/images/instalacion-purificador.jpg',
  4 => 'assets/images/cafe-colombia.jpg',
  5 => 'assets/images/filtro-agua.jpg'
];

// Colores específicos de Café Brew
$primaryColor = '#5F4032'; // Café oscuro
$secondaryColor = '#8B4513'; // Marrón café
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Café Brew - Mall Virtual Viña del Mar</title>
  <meta name="description" content="Café Brew - Los mejores cafés de especialidad de Chile y el mundo. Granos seleccionados, tostados artesanalmente y servicio de preparación profesional.">
  <link rel="stylesheet" href="<?= url('assets/css/modern.css') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    /* Variables específicas de Café Brew */
    :root {
      --store-primary: <?= $primaryColor ?>;
      --store-secondary: <?= $secondaryColor ?>;
      --store-accent: #D2691E;
      /* Naranja café */
      --coffee-beige: #F5E6D3;
      --coffee-cream: #FFF8F0;
    }

    /* Hero Banner con tema de café */
    .store-hero {
      background: linear-gradient(135deg, var(--coffee-beige), var(--coffee-cream), var(--store-primary));
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
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="coffee-grain" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="2" fill="%235F4032" opacity="0.15"/></pattern></defs><rect width="100" height="100" fill="url(%23coffee-grain)"/></svg>');
      opacity: 0.4;
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
      background: var(--coffee-cream);
    }

    .store-title {
      font-size: 3rem;
      font-weight: 700;
      color: var(--store-primary);
      margin-bottom: var(--space-sm);
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      font-family: 'Poppins', sans-serif;
    }

    .store-subtitle {
      font-size: 1.25rem;
      color: var(--secondaryColor);
      margin-bottom: var(--space-xl);
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
      font-style: italic;
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
      border-left: 4px solid var(--store-accent);
    }

    .info-icon {
      width: 20px;
      height: 20px;
      color: var(--store-primary);
    }

    /* Sección de especialidades */
    .specialties-section {
      background: var(--coffee-cream);
      padding: var(--space-xxxl) 0;
      margin: var(--space-xxxl) 0;
    }

    .specialty-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: var(--space-xl);
      margin-top: var(--space-xl);
    }

    .specialty-card {
      background: var(--neutral-0);
      padding: var(--space-lg);
      border-radius: var(--radius-md);
      text-align: center;
      border: 2px solid var(--coffee-beige);
      transition: all 0.3s ease;
    }

    .specialty-card:hover {
      border-color: var(--store-primary);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .specialty-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto var(--space-md);
      background: var(--store-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
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
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: var(--space-xl);
      margin-top: var(--space-xl);
    }

    .product-card {
      background: var(--neutral-0);
      border-radius: var(--radius-md);
      overflow: hidden;
      box-shadow: var(--shadow-md);
      transition: all 0.3s ease;
      border: 2px solid var(--coffee-beige);
    }

    .product-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
      border-color: var(--store-primary);
    }

    .product-image {
      width: 100%;
      height: 220px;
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
      background: #22C55E;
      color: white;
    }

    .stock-low {
      background: #F59E0B;
      color: white;
    }

    .stock-unavailable {
      background: #EF4444;
      color: white;
    }

    /* Botón con tema de café */
    .add-to-cart-btn {
      width: 100%;
      padding: var(--space-md);
      background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
      color: white;
      border: none;
      border-radius: var(--radius-sm);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .add-to-cart-btn:hover {
      background: linear-gradient(135deg, var(--store-secondary), var(--store-primary));
      transform: translateY(-1px);
      box-shadow: 0 8px 25px rgba(95, 64, 50, 0.3);
    }

    .add-to-cart-btn:disabled {
      background: var(--neutral-300);
      cursor: not-allowed;
      transform: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .store-title {
        font-size: 2.2rem;
      }

      .store-info {
        flex-direction: column;
        gap: var(--space-md);
      }

      .specialty-grid {
        grid-template-columns: 1fr;
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
        <a href="<?= url() ?>" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: var(--space-md);">
          <div style="width: 40px; height: 40px; background: var(--primary-500); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">M</div>
          <span style="font-size: 1.25rem; font-weight: 600; color: var(--primary-500);">Mall Virtual</span>
        </a>
      </div>

      <nav class="header-nav">
        <a href="<?= url() ?>" class="nav-link">Tiendas</a>
        <a href="<?= url('cart.php') ?>" class="cart-link">
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

  <!-- Hero de Café Brew -->
  <section class="store-hero">
    <div class="container">
      <div class="store-hero-content">
        <img src="<?= url('assets/images/logo-tienda-a.jpg') ?>"
          alt="Logo Café Brew"
          class="store-logo"
          onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjNUY0MDMyIi8+Cjx0ZXh0IHg9IjYwIiB5PSI2NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjI4IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+8J+PRjwvdGV4dD4KPC9zdmc+Cg==';">

        <h1 class="store-title">☕ Café Brew</h1>
        <p class="store-subtitle">
          "Despertamos tus sentidos con cada grano seleccionado. Los mejores cafés de especialidad de Chile y el mundo, tostados artesanalmente en Viña del Mar."
        </p>

        <div class="store-info">
          <div class="info-item">
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 8l7.89 4.26c.67.36 1.45.36 2.12 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span>hola@cafebrew.cl</span>
          </div>

          <div class="info-item">
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 8l7.89 4.26c.67.36 1.45.36 2.12 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span>Delivery 30km</span>
          </div>

          <div class="info-item">
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Entrega 1-2 días</span>
          </div>

          <div class="info-item">
            <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 8l7.89 4.26c.67.36 1.45.36 2.12 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span>Despacho gratis desde $25.000</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Especialidades -->
  <section class="specialties-section">
    <div class="container">
      <h2 style="text-align: center; font-size: 2.5rem; font-weight: 600; color: var(--store-primary); margin-bottom: var(--space-xl);">
        Nuestras Especialidades ☕
      </h2>

      <div class="specialty-grid">
        <div class="specialty-card">
          <div class="specialty-icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 19h16M4 19V5h16v14M8 19V5h8v14" />
            </svg>
          </div>
          <h3 style="color: var(--store-primary); margin-bottom: var(--space-sm);">Granos de Especialidad</h3>
          <p style="color: var(--neutral-700);">Café premium de las mejores regiones productoras de Chile y el mundo</p>
        </div>

        <div class="specialty-card">
          <div class="specialty-icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 style="color: var(--store-primary); margin-bottom: var(--space-sm);">Tostado Artesanal</h3>
          <p style="color: var(--neutral-700);">Proceso de tostado personalizado para realzar los sabores únicos</p>
        </div>

        <div class="specialty-card">
          <div class="specialty-icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M8 21h8M12 17v4M8 5h8l-2 7H10l-2-7z" />
            </svg>
          </div>
          <h3 style="color: var(--store-primary); margin-bottom: var(--space-sm);">Preparación Profesional</h3>
          <p style="color: var(--neutral-700);">Servicio de preparación y asesoría especializada en métodos de extracción</p>
        </div>

        <div class="specialty-card">
          <div class="specialty-icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
            </svg>
          </div>
          <h3 style="color: var(--store-primary); margin-bottom: var(--space-sm);">Frescura Garantizada</h3>
          <p style="color: var(--neutral-700);">Granos recién tostados y envasado al vacío para preservar su frescura</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Productos -->
  <section class="products-section">
    <div class="container">
      <h2 class="section-title">☕ Nuestros Cafés y Servicios</h2>

      <?php if (empty($products)): ?>
        <div style="text-align: center; padding: var(--space-xxxl);">
          <p style="color: var(--neutral-600); font-size: 1.125rem;">No hay productos disponibles en este momento.</p>
        </div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $product): ?>
            <?php
            $imageUrl = $productImages[$product['id']] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjIyMCIgdmlld0JveD0iMCAwIDMyMCAyMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMjAiIGhlaWdodD0iMjIwIiBmaWxsPSIjRjVFNkQzIi8+CjxwYXRoIGQ9Ik0xNjAgODBMMjQwIDEyMEgxNjBWODBaIiBmaWxsPSIjNWY0MDMyIi8+Cjwvc3ZnPgo=';

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
                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjIyMCIgdmlld0JveD0iMCAwIDMyMCAyMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMjAiIGhlaWdodD0iMjIwIiBmaWxsPSIjRjVFNkQzIi8+CjxwYXRoIGQ9Ik0xNjAgODBMMjQwIDEyMEgxNjBWODBaIiBmaWxsPSIjNWY0MDMyIi8+Cjwvc3ZnPgo=';">

              <div class="product-info">
                <h3 class="product-name">☕ <?= htmlspecialchars($product['name']) ?></h3>
                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>

                <div class="product-meta">
                  <span class="product-price">$<?= number_format((float)$product['price'], 2) ?></span>
                  <span class="stock-badge <?= $stockClass ?>"><?= $stockText ?></span>
                </div>

                <?php if ($isAvailable): ?>
                  <div class="product-actions">
                    <div class="quantity-selector" style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-md);">
                      <button type="button" class="quantity-btn" onclick="changeQuantity(this, -1)" style="width: 32px; height: 32px; border: 2px solid var(--coffee-beige); background: var(--coffee-cream); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">−</button>
                      <input type="number" class="quantity-input" value="1" min="1" max="<?= (int)$product['stock_quantity'] ?>" style="width: 60px; text-align: center; border: 2px solid var(--coffee-beige); border-radius: var(--radius-sm); padding: var(--space-xs); font-weight: 600;">
                      <button type="button" class="quantity-btn" onclick="changeQuantity(this, 1)" style="width: 32px; height: 32px; border: 2px solid var(--coffee-beige); background: var(--coffee-cream); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">+</button>
                    </div>

                    <?php if ($serviceMode === 'requires_appointment' || $serviceMode === 'hybrid'): ?>
                      <div class="date-selector" style="margin-bottom: var(--space-md);">
                        <label for="date-<?= (int)$product['id'] ?>" style="display: block; margin-bottom: var(--space-xs); font-weight: 600; color: var(--store-primary);">Fecha de preparación:</label>
                        <input type="date"
                          id="date-<?= (int)$product['id'] ?>"
                          style="width: 100%; padding: var(--space-sm); border: 2px solid var(--coffee-beige); border-radius: var(--radius-sm); font-size: 1rem;"
                          min="<?= date('Y-m-d') ?>"
                          max="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                      </div>

                      <?php if ($serviceMode === 'requires_appointment'): ?>
                        <div class="service-options" style="margin-bottom: var(--space-md);">
                          <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-xs);">
                            <input type="radio" id="delivery-<?= (int)$product['id'] ?>" name="service-<?= (int)$product['id'] ?>" value="delivery" checked style="accent-color: var(--store-primary);">
                            <label for="delivery-<?= (int)$product['id'] ?>">🚚 Entrega a domicilio</label>
                          </div>
                          <div style="display: flex; align-items: center; gap: var(--space-sm);">
                            <input type="radio" id="pickup-<?= (int)$product['id'] ?>" name="service-<?= (int)$product['id'] ?>" value="pickup" style="accent-color: var(--store-primary);">
                            <label for="pickup-<?= (int)$product['id'] ?>">🏪 Retiro en tienda</label>
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
                          echo '⏰ Agendar Preparación';
                          break;
                        case 'hybrid':
                          echo '🛒 Agregar al Carrito';
                          break;
                        default:
                          echo '🛒 Comprar Ahora';
                      }
                      ?>
                    </button>
                  </div>
                <?php else: ?>
                  <div class="product-actions">
                    <button type="button" class="add-to-cart-btn" disabled style="background: var(--neutral-300);">
                      ☕ Producto Agotado
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
        <h3 style="color: var(--neutral-0); margin-bottom: var(--space-md);">☕ Café Brew</h3>
        <p style="color: var(--neutral-600); margin-bottom: var(--space-xl);">
          Av. Libertad 1234, Viña del Mar<br>
          Horario: 08:00 - 18:00 hrs
        </p>
        <div style="display: flex; justify-content: center; gap: var(--space-lg); flex-wrap: wrap;">
          <a href="<?= url() ?>" style="color: var(--neutral-600); text-decoration: none;">Mall Virtual</a>
          <a href="mailto:hola@cafebrew.cl" style="color: var(--neutral-600); text-decoration: none;">Contacto</a>
          <a href="#" style="color: var(--neutral-600); text-decoration: none;">Términos</a>
          <a href="#" style="color: var(--neutral-600); text-decoration: none;">Privacidad</a>
        </div>
        <p style="color: var(--neutral-600); margin-top: var(--space-xl); font-size: 14px;">
          © 2025 Café Brew. Todos los derechos reservados. | Cuidando cada grano ☕
        </p>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="<?= url('assets/js/modern-app.js') ?>"></script>
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
      const dateInput = productCard.querySelector('input[type="date"]');
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
        showNotification('Debe seleccionar una fecha para el servicio de café', 'error');
        return;
      }

      // Agregar al carrito
      addToCart(productId, quantity);

      // Mensaje personalizado para café
      const message = selectedDate ?
        `☕ Preparación agendada para ${selectedDate}. Cantidad: ${quantity}` :
        `☕ Café agregado al carrito. Cantidad: ${quantity}`;

      showNotification(message, 'success');

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
        font-family: 'Inter', sans-serif;
        font-weight: 500;
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

    // Actualizar contador de carrito
    document.addEventListener('DOMContentLoaded', function() {
      // El contador se actualiza en modern-app.js
    });
  </script>
</body>

</html>