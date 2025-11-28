<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/auth_functions.php';

init_secure_session();

// Obtener totales del carrito
$t = totals();

// Verificar si el carrito está vacío
if (empty($t['items'])) {
    header('Location: index.php');
    exit;
}

// Procesar formulario de checkout
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validaciones
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));
    
    // Validar campos requeridos
    if (empty($name)) {
        $errors[] = 'El nombre es requerido';
    }
    
    if (empty($email)) {
        $errors[] = 'El email es requerido';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido';
    }
    
    if (empty($address)) {
        $errors[] = 'La dirección es requerida';
    }
    
    if (empty($city)) {
        $errors[] = 'La ciudad es requerida';
    }
    
    // Si no hay errores, crear la orden
    if (empty($errors)) {
        $result = createOrder([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'notes' => $notes,
        ]);
        
        if ($result['ok']) {
            $success = true;
            $orderId = (int)$result['order_id'];
            header('Location: pay.php?order_id=' . $orderId);
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
        <h1 class="header-title">Mall Virtual</h1>
      </a>
    </div>
    
    <nav class="header-nav">
      <a href="cart.php" class="btn btn-outline">← Volver al Carrito</a>
      <span style="color: var(--neutral-600); font-weight: 500;">
        <?= $cartItemsCount ?> producto<?= $cartItemsCount !== 1 ? 's' : '' ?>
      </span>
    </nav>
  </div>
</header>

<!-- Hero Section -->
<section class="hero-section" style="background: linear-gradient(135deg, var(--primary-100) 0%, var(--neutral-0) 100%);">
  <div class="container">
    <h1>Finalizar Compra</h1>
    <p style="font-size: 18px; margin-top: var(--space-md); color: var(--neutral-600);">
      Revisa tu pedido y completa la información de envío
    </p>
    
    <!-- Indicador de pasos -->
    <div style="display: flex; justify-content: center; gap: var(--space-lg); margin-top: var(--space-xl); flex-wrap: wrap;">
      <div style="display: flex; align-items: center; gap: var(--space-sm); color: var(--success);">
        <div style="width: 32px; height: 32px; background: var(--success); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">1</div>
        <span>Carrito</span>
      </div>
      <div style="color: var(--neutral-400);">→</div>
      <div style="display: flex; align-items: center; gap: var(--space-sm); color: var(--primary-500);">
        <div style="width: 32px; height: 32px; background: var(--primary-500); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">2</div>
        <span>Información</span>
      </div>
      <div style="color: var(--neutral-400);">→</div>
      <div style="display: flex; align-items: center; gap: var(--space-sm); color: var(--neutral-400);">
        <div style="width: 32px; height: 32px; background: var(--neutral-200); color: var(--neutral-600); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">3</div>
        <span>Pago</span>
      </div>
    </div>
  </div>
</section>

<!-- Contenido del Checkout -->
<section class="section">
  <div class="container">
    <div style="display: grid; grid-template-columns: 1fr 400px; gap: var(--space-xxl); align-items: start;">
      
      <!-- Formulario de Información -->
      <div>
        <?php if (!empty($errors)): ?>
          <div style="background: var(--error); color: white; padding: var(--space-md); border-radius: var(--radius-sm); margin-bottom: var(--space-lg);">
            <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-sm);">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
              <strong>Errores en el formulario:</strong>
            </div>
            <ul style="margin: 0; padding-left: var(--space-lg);">
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        
        <div class="card">
          <h2 style="margin-bottom: var(--space-xl);">Información de Envío</h2>
          
          <form method="post" id="checkoutForm" novalidate>
            <!-- Información Personal -->
            <div style="margin-bottom: var(--space-xl);">
              <h3 style="margin-bottom: var(--space-lg); color: var(--neutral-900);">Datos Personales</h3>
              
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg);">
                <div class="form-group">
                  <label for="name" class="form-label">Nombre Completo *</label>
                  <input type="text" 
                         id="name" 
                         name="name" 
                         value="<?= htmlspecialchars((string)($_POST['name'] ?? '')) ?>"
                         class="form-input" 
                         required
                         placeholder="Juan Pérez"
                         onblur="validateField(this, 'nombre')">
                </div>
                
                <div class="form-group">
                  <label for="email" class="form-label">Email *</label>
                  <input type="email" 
                         id="email" 
                         name="email" 
                         value="<?= htmlspecialchars((string)($_POST['email'] ?? '')) ?>"
                         class="form-input" 
                         required
                         placeholder="juan@email.com"
                         onblur="validateField(this, 'email')">
                </div>
              </div>
              
              <div class="form-group" style="max-width: 300px;">
                <label for="phone" class="form-label">Teléfono</label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       value="<?= htmlspecialchars((string)($_POST['phone'] ?? '')) ?>"
                       class="form-input" 
                       placeholder="+56 9 1234 5678"
                       pattern="[+]?[0-9\s\-\(\)]+"
                       onblur="validateField(this, 'teléfono')">
              </div>
            </div>
            
            <!-- Dirección de Entrega -->
            <div style="margin-bottom: var(--space-xl);">
              <h3 style="margin-bottom: var(--space-lg); color: var(--neutral-900);">Dirección de Entrega</h3>
              
              <div class="form-group">
                <label for="address" class="form-label">Dirección *</label>
                <input type="text" 
                       id="address" 
                       name="address" 
                       value="<?= htmlspecialchars((string)($_POST['address'] ?? '')) ?>"
                       class="form-input" 
                       required
                       placeholder="Av. Principal 123, Depto 45"
                       onblur="validateField(this, 'dirección')">
              </div>
              
              <div style="display: grid; grid-template-columns: 1fr 200px; gap: var(--space-lg);">
                <div class="form-group">
                  <label for="city" class="form-label">Ciudad *</label>
                  <select id="city" name="city" class="form-select" required onblur="validateField(this, 'ciudad')">
                    <option value="">Seleccionar ciudad</option>
                    <option value="Santiago" <?= ($_POST['city'] ?? '') === 'Santiago' ? 'selected' : '' ?>>Santiago</option>
                    <option value="Valparaíso" <?= ($_POST['city'] ?? '') === 'Valparaíso' ? 'selected' : '' ?>>Valparaíso</option>
                    <option value="Concepción" <?= ($_POST['city'] ?? '') === 'Concepción' ? 'selected' : '' ?>>Concepción</option>
                    <option value="La Serena" <?= ($_POST['city'] ?? '') === 'La Serena' ? 'selected' : '' ?>>La Serena</option>
                    <option value="Antofagasta" <?= ($_POST['city'] ?? '') === 'Antofagasta' ? 'selected' : '' ?>>Antofagasta</option>
                    <option value="Temuco" <?= ($_POST['city'] ?? '') === 'Temuco' ? 'selected' : '' ?>>Temuco</option>
                    <option value="Otra" <?= ($_POST['city'] ?? '') === 'Otra' ? 'selected' : '' ?>>Otra</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="region" class="form-label">Región</label>
                  <input type="text" value="Metropolitana" readonly class="form-input" style="background: var(--neutral-100);">
                </div>
              </div>
            </div>
            
            <!-- Notas Adicionales -->
            <div style="margin-bottom: var(--space-xl);">
              <h3 style="margin-bottom: var(--space-lg); color: var(--neutral-900);">Notas Adicionales</h3>
              
              <div class="form-group">
                <label for="notes" class="form-label">Comentarios para el pedido</label>
                <textarea id="notes" 
                          name="notes" 
                          class="form-input" 
                          rows="3"
                          placeholder="Instrucciones especiales de entrega, horarios, etc."
                          onblur="validateField(this, 'notas')"><?= htmlspecialchars((string)($_POST['notes'] ?? '')) ?></textarea>
              </div>
            </div>
            
            <!-- Términos y Condiciones -->
            <div style="margin-bottom: var(--space-xl);">
              <div style="display: flex; align-items: flex-start; gap: var(--space-sm);">
                <input type="checkbox" id="terms" required style="margin-top: 4px;">
                <label for="terms" style="font-size: 14px; color: var(--neutral-600); line-height: 1.5;">
                  Acepto los <a href="#" style="color: var(--primary-500);">términos y condiciones</a> y la 
                  <a href="#" style="color: var(--primary-500);">política de privacidad</a> del mall virtual.
                </label>
              </div>
            </div>
            
            <!-- Botón de Envío -->
            <button type="submit" class="btn btn-primary btn-icon" style="width: 100%; font-size: 18px; padding: 16px 24px;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 3h2l.4 2M7 13h10l4-8H5.4"></path>
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
              </svg>
              Continuar al Pago - $<?= number_format((float)$t['total'], 2) ?>
            </button>
          </form>
        </div>
      </div>
      
      <!-- Resumen del Pedido -->
      <div style="position: sticky; top: 120px;">
        <div class="card">
          <h3 style="margin-bottom: var(--space-lg);">Resumen del Pedido</h3>
          
          <!-- Productos -->
          <div style="display: flex; flex-direction: column; gap: var(--space-md); margin-bottom: var(--space-lg); max-height: 300px; overflow-y: auto;">
            <?php foreach ($t['items'] as $item): 
              $product = $item['product'];
              $imageUrl = $productImages[$product['id']] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0yMCAyMEg0NFY0NEgyMFYyMFoiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+';
              $store = storeById((int)$product['store_id']);
            ?>
              <div style="display: flex; gap: var(--space-md); align-items: center; padding: var(--space-sm); border: 1px solid var(--neutral-200); border-radius: var(--radius-sm);">
                <img src="<?= htmlspecialchars($imageUrl) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     style="width: 48px; height: 48px; border-radius: var(--radius-sm); object-fit: cover;"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIHZpZXdCb3g9IjAgMCA0OCA0OCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMiAxMkgzNlYzNkgxMlYxMloiIGZpbGw9IiNFNUU3RUIiLz4KPC9zdmc+';">
                
                <div style="flex: 1;">
                  <h4 style="margin: 0 0 4px 0; font-size: 14px;"><?= htmlspecialchars($product['name']) ?></h4>
                  <p style="margin: 0; color: var(--neutral-600); font-size: 12px;"><?= htmlspecialchars((string)$store['name']) ?></p>
                  <p style="margin: 4px 0 0 0; font-weight: 600; color: var(--primary-500);">
                    $<?= number_format((float)$product['price'], 2) ?> × <?= (int)$item['qty'] ?>
                  </p>
                </div>
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
              <span>$<?= number_format((float)$t['shipping'], 2) ?></span>
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
          
          <!-- Información de Seguridad -->
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

<!-- Scripts -->
<script src="assets/js/modern-app.js"></script>
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
    } else if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        isValid = emailRegex.test(value);
    } else if (field.name === 'phone' && value) {
        const phoneRegex = /^[+]?[0-9\s\-\(\)]+$/;
        isValid = phoneRegex.test(value);
    }
    
    // Aplicar clases de validación
    if (value || isRequired) {
        field.classList.add(isValid ? 'field-valid' : 'field-invalid');
    }
    
    return isValid;
}

// Validar checkbox de términos
document.getElementById('terms').addEventListener('change', function() {
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.disabled = !this.checked;
    submitBtn.style.opacity = this.checked ? '1' : '0.6';
});

// Validar formulario completo antes del envío
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    const termsCheckbox = document.getElementById('terms');
    if (!termsCheckbox.checked) {
        alert('Debes aceptar los términos y condiciones');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        // Scroll al primer campo inválido
        const firstInvalid = this.querySelector('.field-invalid, input:not(:checked)#terms');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
        return;
    }
    
    // Feedback visual
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin"><circle cx="12" cy="12" r="10"></circle></svg> Procesando...';
    submitBtn.disabled = true;
});

// Estilos para validación de campos
const style = document.createElement('style');
style.textContent = `
    .field-valid {
        border-color: var(--success) !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }
    
    .field-invalid {
        border-color: var(--error) !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Auto-llenar ciudad según región
document.addEventListener('DOMContentLoaded', function() {
    // Scroll suave para navegación
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
    
    // Enfocar primer campo
    const firstInput = document.querySelector('input[required]');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 500);
    }
    
    // Actualizar botón de términos
    const termsCheckbox = document.getElementById('terms');
    const submitBtn = document.querySelector('button[type="submit"]');
    if (termsCheckbox && submitBtn) {
        submitBtn.disabled = !termsCheckbox.checked;
        submitBtn.style.opacity = termsCheckbox.checked ? '1' : '0.6';
    }
});

// Detectar cambios en tiempo real
document.querySelectorAll('input, select, textarea').forEach(field => {
    field.addEventListener('input', function() {
        if (this.hasAttribute('required')) {
            setTimeout(() => validateField(this), 100);
        }
    });
});
</script>

<!-- Schema.org para SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CheckoutPage",
  "name": "Checkout - Mall Virtual",
  "description": "Página de checkout para completar la compra",
  "url": "<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>",
  "totalValue": "<?= $t['total'] ?>",
  "priceCurrency": "CLP",
  "numberOfItems": <?= $cartItemsCount ?>,
  "seller": {
    "@type": "Organization",
    "name": "Mall Virtual"
  }
}
</script>
</body>
</html>