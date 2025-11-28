<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/auth_functions.php';

init_secure_session();
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/advanced_store_functions.php';
require_once __DIR__ . '/../src/router.php';

// Obtener totales del carrito
$t = totals();

// Verificar si el carrito está vacío
if (empty($t['items'])) {
    header('Location: ' . Router::url());
    exit;
}

// Procesar formulario de checkout
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validaciones
    $customerName = trim((string)($_POST['customer_name'] ?? ''));
    $customerEmail = trim((string)($_POST['customer_email'] ?? ''));
    $customerPhone = trim((string)($_POST['customer_phone'] ?? ''));
    
    // Información de entrega
    $deliveryAddress = trim((string)($_POST['delivery_address'] ?? ''));
    $deliveryCity = trim((string)($_POST['delivery_city'] ?? ''));
    $deliveryContactName = trim((string)($_POST['delivery_contact_name'] ?? ''));
    $deliveryContactPhone = trim((string)($_POST['delivery_contact_phone'] ?? ''));
    $deliveryContactEmail = trim((string)($_POST['delivery_contact_email'] ?? ''));
    $pickupLocationId = isset($_POST['pickup_location_id']) ? (int)$_POST['pickup_location_id'] : null;
    $deliveryDate = trim((string)($_POST['delivery_date'] ?? ''));
    $deliveryTimeSlot = trim((string)($_POST['delivery_time_slot'] ?? ''));
    $deliveryNotes = trim((string)($_POST['delivery_notes'] ?? ''));
    
    // Validar campos requeridos
    if (empty($customerName)) {
        $errors[] = 'El nombre es requerido';
    }
    
    if (empty($customerEmail)) {
        $errors[] = 'El email es requerido';
    } elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido';
    }
    
    if (empty($customerPhone)) {
        $errors[] = 'El teléfono es requerido';
    }
    
    if (empty($deliveryAddress)) {
        $errors[] = 'La dirección de entrega es requerida';
    }
    
    if (empty($deliveryCity)) {
        $errors[] = 'La ciudad de entrega es requerida';
    }
    
    if (empty($deliveryContactName)) {
        $errors[] = 'El nombre del contacto de entrega es requerido';
    }
    
    if (empty($deliveryContactPhone)) {
        $errors[] = 'El teléfono del contacto de entrega es requerido';
    }
    
    // Si no hay errores, crear la orden
    if (empty($errors)) {
        $orderData = [
            'name' => $customerName,
            'email' => $customerEmail,
            'phone' => $customerPhone,
            'address' => $deliveryAddress,
            'city' => $deliveryCity,
            'notes' => $deliveryNotes,
            // Información de entrega expandida
            'delivery_address' => $deliveryAddress,
            'delivery_city' => $deliveryCity,
            'delivery_contact_name' => $deliveryContactName,
            'delivery_contact_phone' => $deliveryContactPhone,
            'delivery_contact_email' => $deliveryContactEmail,
            'pickup_location_id' => $pickupLocationId,
            'delivery_date' => $deliveryDate ?: null,
            'delivery_time_slot' => $deliveryTimeSlot ?: null,
        ];
        
        $result = createOrder($orderData);
        
        if ($result['ok']) {
            $orderId = (int)$result['order_id'];
            
            // Si hay múltiples tiendas, crear grupos de despacho
            $storesInOrder = [];
            foreach ($t['items'] as $item) {
                if (!in_array($item['store_id'], $storesInOrder)) {
                    $storesInOrder[] = $item['store_id'];
                }
            }
            
            if (count($storesInOrder) > 1) {
                // Múltiples tiendas - crear grupos por tienda
                foreach ($storesInOrder as $storeId) {
                    $groupData = [
                        'order_id' => $orderId,
                        'group_name' => 'Despacho - ' . storeById($storeId)['name'],
                        'delivery_address' => $deliveryAddress,
                        'delivery_city' => $deliveryCity,
                        'delivery_contact_name' => $deliveryContactName,
                        'delivery_contact_phone' => $deliveryContactPhone,
                        'delivery_contact_email' => $deliveryContactEmail,
                        'pickup_location_id' => $pickupLocationId,
                        'delivery_date' => $deliveryDate,
                        'delivery_time_slot' => $deliveryTimeSlot,
                        'delivery_notes' => $deliveryNotes,
                        'shipping_cost' => 3000.00 // Costo fijo por grupo
                    ];
                    
                    $groupResult = createDeliveryGroup($groupData);
                    if ($groupResult['success']) {
                        // Agregar items de esta tienda al grupo
                        foreach ($t['items'] as $item) {
                            if ($item['store_id'] == $storeId) {
                                addItemToDeliveryGroup($groupResult['group_id'], $item['order_item_id'], $item['qty']);
                            }
                        }
                    }
                }
            } else {
                // Una sola tienda - crear un grupo único
                $groupData = [
                    'order_id' => $orderId,
                    'group_name' => 'Despacho General',
                    'delivery_address' => $deliveryAddress,
                    'delivery_city' => $deliveryCity,
                    'delivery_contact_name' => $deliveryContactName,
                    'delivery_contact_phone' => $deliveryContactPhone,
                    'delivery_contact_email' => $deliveryContactEmail,
                    'pickup_location_id' => $pickupLocationId,
                    'delivery_date' => $deliveryDate,
                    'delivery_time_slot' => $deliveryTimeSlot,
                    'delivery_notes' => $deliveryNotes,
                    'shipping_cost' => 0.00 // Gratis para una sola tienda
                ];
                
                $groupResult = createDeliveryGroup($groupData);
                if ($groupResult['success']) {
                    // Agregar todos los items al grupo
                    foreach ($t['items'] as $item) {
                        addItemToDeliveryGroup($groupResult['group_id'], $item['order_item_id'], $item['qty']);
                    }
                }
            }
            
            header('Location: pay_transbank.php?order_id=' . $orderId);
            exit;
        } else {
            $errors[] = 'Error al crear la orden. Por favor inténtalo de nuevo.';
        }
    }
}

// Contar items en carrito
$cartItemsCount = 0;
foreach ($t['items'] as $item) {
    $cartItemsCount += $item['qty'];
}

// Productos con imágenes
$productImages = [
    1 => 'assets/images/cafe-arab.webp',
    2 => 'assets/images/te-hierbas.jpg',
    3 => 'assets/images/instalacion-purificador.jpg',
    4 => 'assets/images/cafe-colombia.jpg',
    5 => 'assets/images/filtro-agua.jpg'
];

// Obtener ubicaciones de recojo disponibles
$pickupLocations = [];
foreach (array_unique(array_column($t['items'], 'store_id')) as $storeId) {
    $locations = getStorePickupLocations($storeId);
    $pickupLocations = array_merge($pickupLocations, $locations);
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout - Mall Virtual</title>
<meta name="description" content="Completa tu compra en el mall virtual. Información de envío y pago.">
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
        <span class="cart-count" id="cartCount"><?= $cartItemsCount ?></span>
      </a>
    </nav>
  </div>
</header>

<!-- Contenido Principal -->
<section style="padding: var(--space-xxxl) 0;">
  <div class="container">
    <h1 style="text-align: center; font-size: 2.5rem; font-weight: 600; color: var(--primary-500); margin-bottom: var(--space-xl);">
      Finalizar Compra
    </h1>

    <?php if (!empty($errors)): ?>
    <div style="background: #FEE2E2; border: 1px solid #FECACA; color: #991B1B; padding: var(--space-lg); border-radius: var(--radius-sm); margin-bottom: var(--space-xl);">
      <h3 style="margin: 0 0 var(--space-sm); color: #991B1B;">Errores en el formulario:</h3>
      <ul style="margin: 0; padding-left: var(--space-lg);">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="checkout-form" onsubmit="return validateCheckoutForm(this)">
      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-xxxl);">
        
        <!-- Información del Cliente y Entrega -->
        <div style="display: flex; flex-direction: column; gap: var(--space-xl);">
          
          <!-- Datos Personales -->
          <div style="background: var(--neutral-0); padding: var(--space-xl); border-radius: var(--radius-md); box-shadow: var(--shadow-md);">
            <h2 style="color: var(--primary-500); margin-bottom: var(--space-lg);">👤 Información Personal</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg); margin-bottom: var(--space-lg);">
              <div>
                <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                  Nombre Completo *
                </label>
                <input type="text" 
                       name="customer_name" 
                       value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>"
                       class="form-input" 
                       required
                       style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
              </div>
              
              <div>
                <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                  Teléfono *
                </label>
                <input type="tel" 
                       name="customer_phone" 
                       value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>"
                       class="form-input" 
                       required
                       style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
              </div>
            </div>
            
            <div>
              <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                Email *
              </label>
              <input type="email" 
                     name="customer_email" 
                     value="<?= htmlspecialchars($_POST['customer_email'] ?? '') ?>"
                     class="form-input" 
                     required
                     style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
            </div>
          </div>
          
          <!-- Información de Entrega -->
          <div style="background: var(--neutral-0); padding: var(--space-xl); border-radius: var(--radius-md); box-shadow: var(--shadow-md);">
            <h2 style="color: var(--primary-500); margin-bottom: var(--space-lg);">📍 Información de Entrega</h2>
            
            <div style="margin-bottom: var(--space-lg);">
              <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                Dirección de Entrega *
              </label>
              <input type="text" 
                     name="delivery_address" 
                     value="<?= htmlspecialchars($_POST['delivery_address'] ?? '') ?>"
                     class="form-input" 
                     required
                     placeholder="Calle, número, departamento..."
                     style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
            </div>
            
            <div style="margin-bottom: var(--space-lg);">
              <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                Ciudad *
              </label>
              <input type="text" 
                     name="delivery_city" 
                     value="<?= htmlspecialchars($_POST['delivery_city'] ?? 'Viña del Mar') ?>"
                     class="form-input" 
                     required
                     style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg); margin-bottom: var(--space-lg);">
              <div>
                <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                  Persona que Recibe *
                </label>
                <input type="text" 
                       name="delivery_contact_name" 
                       value="<?= htmlspecialchars($_POST['delivery_contact_name'] ?? '') ?>"
                       class="form-input" 
                       required
                       style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
              </div>
              
              <div>
                <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                  Teléfono de Contacto *
                </label>
                <input type="tel" 
                       name="delivery_contact_phone" 
                       value="<?= htmlspecialchars($_POST['delivery_contact_phone'] ?? '') ?>"
                       class="form-input" 
                       required
                       style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
              </div>
            </div>
            
            <div style="margin-bottom: var(--space-lg);">
              <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                Email de Contacto
              </label>
              <input type="email" 
                     name="delivery_contact_email" 
                     value="<?= htmlspecialchars($_POST['delivery_contact_email'] ?? '') ?>"
                     class="form-input" 
                     style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
            </div>
            
            <?php if (!empty($pickupLocations)): ?>
            <div style="margin-bottom: var(--space-lg);">
              <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                Opción de Entrega
              </label>
              <div style="display: flex; gap: var(--space-md); margin-bottom: var(--space-md);">
                <label style="display: flex; align-items: center; gap: var(--space-sm); cursor: pointer;">
                  <input type="radio" name="delivery_option" value="delivery" checked style="accent-color: var(--primary-500);">
                  🚚 Entrega a domicilio
                </label>
                <label style="display: flex; align-items: center; gap: var(--space-sm); cursor: pointer;">
                  <input type="radio" name="delivery_option" value="pickup" style="accent-color: var(--primary-500);">
                  🏪 Retiro en tienda
                </label>
              </div>
              
              <div id="deliveryOptions" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg);">
                <div>
                  <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                    Fecha Estimada
                  </label>
                  <input type="date" 
                         name="delivery_date" 
                         value="<?= htmlspecialchars($_POST['delivery_date'] ?? '') ?>"
                         class="form-input"
                         min="<?= date('Y-m-d') ?>"
                         max="<?= date('Y-m-d', strtotime('+14 days')) ?>"
                         style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
                </div>
                
                <div>
                  <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                    Horario Preferido
                  </label>
                  <select name="delivery_time_slot" class="form-select" style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
                    <option value="">Seleccionar horario</option>
                    <option value="09:00">09:00 - 11:00</option>
                    <option value="11:00">11:00 - 13:00</option>
                    <option value="13:00">13:00 - 15:00</option>
                    <option value="15:00">15:00 - 17:00</option>
                    <option value="17:00">17:00 - 19:00</option>
                  </select>
                </div>
              </div>
              
              <div id="pickupOptions" style="display: none;">
                <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                  Ubicación de Retiro
                </label>
                <select name="pickup_location_id" class="form-select" style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem;">
                  <option value="">Seleccionar ubicación</option>
                  <?php foreach ($pickupLocations as $location): ?>
                    <option value="<?= $location['id'] ?>">
                      <?= htmlspecialchars($location['name']) ?> - <?= htmlspecialchars($location['address']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <?php endif; ?>
            
            <div>
              <label style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--primary-500);">
                Notas para la Entrega
              </label>
              <textarea name="delivery_notes" 
                        class="form-textarea"
                        placeholder="Instrucciones especiales, referencias, etc."
                        style="width: 100%; padding: var(--space-md); border: 2px solid var(--neutral-200); border-radius: var(--radius-sm); font-size: 1rem; resize: vertical; min-height: 80px;"><?= htmlspecialchars($_POST['delivery_notes'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
        
        <!-- Resumen de la Orden -->
        <div>
          <div style="background: var(--neutral-0); padding: var(--space-xl); border-radius: var(--radius-md); box-shadow: var(--shadow-md); position: sticky; top: var(--space-xl);">
            <h2 style="color: var(--primary-500); margin-bottom: var(--space-lg);">🛒 Resumen de Orden</h2>
            
            <!-- Items del carrito -->
            <div style="margin-bottom: var(--space-lg);">
              <?php 
              $storesInOrder = [];
              foreach ($t['items'] as $item): 
                if (!in_array($item['store_id'], $storesInOrder)) {
                  $storesInOrder[] = $item['store_id'];
                }
              endforeach;
              
              foreach ($storesInOrder as $storeId):
                $store = storeById($storeId);
              ?>
              <div style="margin-bottom: var(--space-lg);">
                <h3 style="color: var(--primary-500); margin-bottom: var(--space-sm); font-size: 1.125rem;">
                  🏪 <?= htmlspecialchars($store['name']) ?>
                </h3>
                
                <?php foreach ($t['items'] as $item): ?>
                  <?php if ($item['store_id'] == $storeId): ?>
                    <?php 
                    $imageUrl = $productImages[$item['product_id']] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0NFY0NEgyMFYyMFoiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+';
                    ?>
                    <div style="display: flex; gap: var(--space-md); align-items: center; padding: var(--space-sm); border: 1px solid var(--neutral-200); border-radius: var(--radius-sm); margin-bottom: var(--space-sm);">
                      <img src="<?= htmlspecialchars($imageUrl) ?>" 
                           alt="<?= htmlspecialchars($item['name']) ?>" 
                           style="width: 48px; height: 48px; border-radius: var(--radius-sm); object-fit: cover;"
                           onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIHZpZXdCb3g9IjAgMCA0OCA0OCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMiAxMkgzNlYzNkgxMlYxMloiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+';">
                      
                      <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px 0; font-size: 14px;"><?= htmlspecialchars($item['name']) ?></h4>
                        <p style="margin: 0; color: var(--neutral-600); font-size: 12px;">
                          Cantidad: <?= (int)$item['qty'] ?>
                        </p>
                        <p style="margin: 4px 0 0 0; font-weight: 600; color: var(--primary-500);">
                          $<?= number_format((float)$item['price'], 2) ?> × <?= (int)$item['qty'] ?>
                        </p>
                      </div>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
              <?php endforeach; ?>
            </div>
            
            <!-- Totales -->
            <div style="border-top: 2px solid var(--neutral-200); padding-top: var(--space-lg);">
              <div style="display: flex; justify-content: space-between; margin-bottom: var(--space-sm);">
                <span>Subtotal (<?= $cartItemsCount ?> productos):</span>
                <span>$<?= number_format((float)$t['subtotal'], 2) ?></span>
              </div>
              
              <div style="display: flex; justify-content: space-between; margin-bottom: var(--space-sm);">
                <span>Envío:</span>
                <span>
                  <?php if (count($storesInOrder) > 1): ?>
                    $<?= number_format(3000.00 * count($storesInOrder), 2) ?> 
                    <small style="color: var(--neutral-600);"><?= count($storesInOrder) ?> grupos</small>
                  <?php else: ?>
                    Gratis
                  <?php endif; ?>
                </span>
              </div>
              
              <?php if ($t['discount'] > 0): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: var(--space-sm); color: var(--success);">
                  <span>Descuento:</span>
                  <span>-$<?= number_format((float)$t['discount'], 2) ?></span>
                </div>
              <?php endif; ?>
              
              <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; color: var(--primary-500); border-top: 1px solid var(--neutral-200); padding-top: var(--space-md); margin-top: var(--space-md);">
                <span>Total:</span>
                <span>$<?= number_format((float)$t['total'], 2) ?></span>
              </div>
            </div>
            
            <!-- Botón de pago -->
            <button type="submit" 
                    style="width: 100%; margin-top: var(--space-xl); padding: var(--space-lg); background: var(--primary-500); color: white; border: none; border-radius: var(--radius-sm); font-size: 1.125rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
              💳 Proceder al Pago
            </button>
            
            <!-- Información de seguridad -->
            <div style="margin-top: var(--space-lg); padding: var(--space-md); background: var(--neutral-100); border-radius: var(--radius-sm); text-align: center;">
              <div style="display: flex; align-items: center; justify-content: center; gap: var(--space-sm); margin-bottom: var(--space-xs);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--success);">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span style="font-weight: 600; color: var(--success);">Compra 100% Segura</span>
              </div>
              <p style="margin: 0; color: var(--neutral-600); font-size: 12px;">
                Tu información está protegida con encriptación SSL
              </p>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>

<!-- Footer -->
<footer style="background: var(--neutral-900); color: var(--neutral-0); padding: var(--space-xxxl) 0;">
  <div class="container">
    <div style="text-align: center;">
      <h3 style="color: var(--neutral-0); margin-bottom: var(--space-md);">Mall Virtual</h3>
      <p style="color: var(--neutral-600); margin-bottom: var(--space-xl);">
        Proceso de checkout seguro y confiable.
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

<script>
// Validación de formularios en tiempo real
function validateField(field, fieldName) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    
    // Remover clases de validación previas
    field.classList.remove('field-valid', 'field-invalid');
    
    // Validaciones específicas
    let isValid = true;
    
    if (isRequired && !value) {
        isValid = false;
    } else if (fieldName === 'email' && value && !isValidEmail(value)) {
        isValid = false;
    } else if (fieldName === 'phone' && value && !isValidPhone(value)) {
        isValid = false;
    }
    
    // Aplicar clases de validación
    if (isRequired || value) {
        field.classList.add(isValid ? 'field-valid' : 'field-invalid');
    }
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[+]?[\d\s\-\(\)]{9,}$/;
    return phoneRegex.test(phone);
}

function validateCheckoutForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field, field.name)) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        alert('Por favor completa todos los campos requeridos correctamente.');
        return false;
    }
    
    return true;
}

// Cambiar entre opciones de entrega
document.addEventListener('DOMContentLoaded', function() {
    const deliveryOptions = document.querySelectorAll('input[name="delivery_option"]');
    const deliverySection = document.getElementById('deliveryOptions');
    const pickupSection = document.getElementById('pickupOptions');
    
    deliveryOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.value === 'delivery') {
                deliverySection.style.display = 'grid';
                pickupSection.style.display = 'none';
            } else {
                deliverySection.style.display = 'none';
                pickupSection.style.display = 'block';
            }
        });
    });
    
    // Validación en tiempo real
    const form = document.querySelector('.checkout-form');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this, this.name);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('field-invalid') && this.hasAttribute('required')) {
                validateField(this, this.name);
            }
        });
    });
});

// Auto-completar información de contacto
document.querySelector('input[name="customer_name"]').addEventListener('blur', function() {
    const contactName = document.querySelector('input[name="delivery_contact_name"]');
    if (!contactName.value) {
        contactName.value = this.value;
    }
});

document.querySelector('input[name="customer_phone"]').addEventListener('blur', function() {
    const contactPhone = document.querySelector('input[name="delivery_contact_phone"]');
    if (!contactPhone.value) {
        contactPhone.value = this.value;
    }
});

document.querySelector('input[name="customer_email"]').addEventListener('blur', function() {
    const contactEmail = document.querySelector('input[name="delivery_contact_email"]');
    if (!contactEmail.value) {
        contactEmail.value = this.value;
    }
});
</script>

<style>
.field-valid {
    border-color: var(--success) !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.field-invalid {
    border-color: var(--error) !important;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}

.checkout-form input:focus,
.checkout-form select:focus,
.checkout-form textarea:focus {
    border-color: var(--primary-500);
    box-shadow: 0 0 0 2px rgba(94, 66, 46, 0.2);
}
</style>
</body>
</html>