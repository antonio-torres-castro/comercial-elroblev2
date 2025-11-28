<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/auth_functions.php';
require_once __DIR__ . '/../src/functions.php';

init_secure_session();

// Verificar autenticación
requireAuth();

$errors = [];
$success = '';
$addresses = getUserAddresses();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
        case 'edit':
            $data = [
                'id' => $_POST['id'] ?? null,
                'type' => $_POST['type'] ?? 'both',
                'label' => trim($_POST['label'] ?? ''),
                'street_address' => trim($_POST['street_address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'state_province' => trim($_POST['state_province'] ?? ''),
                'postal_code' => trim($_POST['postal_code'] ?? ''),
                'country' => trim($_POST['country'] ?? 'Chile'),
                'is_default' => isset($_POST['is_default']) ? 1 : 0
            ];
            
            // Validaciones
            if (empty($data['street_address'])) {
                $errors[] = 'La dirección es requerida';
            }
            if (empty($data['city'])) {
                $errors[] = 'La ciudad es requerida';
            }
            if (empty($data['state_province'])) {
                $errors[] = 'La región/provincia es requerida';
            }
            if (empty($data['postal_code'])) {
                $errors[] = 'El código postal es requerido';
            }
            
            if (empty($errors)) {
                $result = saveAddress($data);
                if ($result['success']) {
                    $success = $result['message'];
                    $addresses = getUserAddresses(); // Recargar
                } else {
                    $errors[] = $result['message'];
                }
            }
            break;
            
        case 'delete':
            $address_id = (int)($_POST['address_id'] ?? 0);
            if ($address_id > 0) {
                $result = deleteAddress($address_id);
                if ($result['success']) {
                    $success = $result['message'];
                    $addresses = getUserAddresses(); // Recargar
                } else {
                    $errors[] = $result['message'];
                }
            }
            break;
    }
}

// Obtener dirección para editar
$editing_address = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    foreach ($addresses as $address) {
        if ($address['id'] == $edit_id) {
            $editing_address = $address;
            break;
        }
    }
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mis Direcciones - Mall Virtual</title>
<link rel="stylesheet" href="assets/css/modern.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --address-primary: #3B82F6;
  --address-secondary: #64748B;
  --address-success: #10B981;
  --address-danger: #EF4444;
  --address-warning: #F59E0B;
  --address-background: #F8FAFC;
  --address-card: #FFFFFF;
  --address-text: #1E293B;
  --address-text-muted: #64748B;
  --address-border: #E2E8F0;
  --address-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--address-background);
  color: var(--address-text);
  line-height: 1.6;
}

.address-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.page-header {
  background: var(--address-card);
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: var(--address-shadow);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--address-text);
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: var(--address-primary);
  color: white;
}

.btn-primary:hover {
  background: #2563EB;
}

.btn-outline {
  background: transparent;
  color: var(--address-primary);
  border: 1px solid var(--address-primary);
}

.btn-outline:hover {
  background: var(--address-primary);
  color: white;
}

.btn-danger {
  background: var(--address-danger);
  color: white;
}

.btn-danger:hover {
  background: #DC2626;
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.address-form {
  background: var(--address-card);
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: var(--address-shadow);
  height: fit-content;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: var(--address-text);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--address-text);
  font-size: 0.875rem;
}

.form-input,
.form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--address-border);
  border-radius: 0.5rem;
  font-size: 1rem;
  background: white;
  transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--address-primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 1rem 0;
}

.checkbox-group input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.addresses-list {
  background: var(--address-card);
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: var(--address-shadow);
}

.address-item {
  border: 1px solid var(--address-border);
  border-radius: 0.5rem;
  padding: 1.5rem;
  margin-bottom: 1rem;
  position: relative;
}

.address-header {
  display: flex;
  justify-content: between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.address-type {
  font-weight: 700;
  color: var(--address-text);
  margin-bottom: 0.25rem;
}

.address-default {
  background: var(--address-success);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.address-actions {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  gap: 0.5rem;
}

.address-details {
  color: var(--address-text-muted);
  line-height: 1.5;
}

.address-details strong {
  color: var(--address-text);
}

.alert {
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.alert-error {
  background: #FEF2F2;
  color: #991B1B;
  border: 1px solid #FECACA;
}

.alert-success {
  background: #F0FDF4;
  color: #166534;
  border: 1px solid #BBF7D0;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: var(--address-text-muted);
}

.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
  align-items: center;
  justify-content: center;
}

.modal.show {
  display: flex;
}

.modal-content {
  background: var(--address-card);
  border-radius: 1rem;
  padding: 2rem;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

@media (max-width: 768px) {
  .address-container {
    padding: 1rem;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .content-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
</style>
</head>
<body>
<div class="address-container">
  <!-- Header -->
  <div class="page-header">
    <h1 class="page-title">📍 Mis Direcciones</h1>
    <a href="profile.php" class="btn btn-outline">← Volver al Perfil</a>
  </div>
  
  <!-- Alertas -->
  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $error): ?>
        <div><?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <?php if ($success): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  
  <div class="content-grid">
    <!-- Formulario de dirección -->
    <div class="address-form">
      <h2 class="section-title">
        <?= $editing_address ? '✏️ Editar Dirección' : '➕ Nueva Dirección' ?>
      </h2>
      
      <form method="POST" action="">
        <input type="hidden" name="action" value="<?= $editing_address ? 'edit' : 'add' ?>">
        <?php if ($editing_address): ?>
          <input type="hidden" name="id" value="<?= $editing_address['id'] ?>">
        <?php endif; ?>
        
        <div class="form-group">
          <label class="form-label" for="type">Tipo de Dirección</label>
          <select name="type" id="type" class="form-select" required>
            <option value="billing" <?= ($editing_address['type'] ?? '') === 'billing' ? 'selected' : '' ?>>
              📄 Solo Facturación
            </option>
            <option value="shipping" <?= ($editing_address['type'] ?? '') === 'shipping' ? 'selected' : '' ?>>
              🚚 Solo Despacho
            </option>
            <option value="both" <?= ($editing_address['type'] ?? 'both') === 'both' ? 'selected' : '' ?>>
              🏠 Ambas (Facturación y Despacho)
            </option>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="label">Etiqueta (opcional)</label>
          <input 
            type="text" 
            id="label" 
            name="label" 
            class="form-input" 
            value="<?= htmlspecialchars($editing_address['label'] ?? '') ?>"
            placeholder="Casa, Oficina, Casa de mi mamá..."
          >
        </div>
        
        <div class="form-group">
          <label class="form-label" for="street_address">Dirección *</label>
          <input 
            type="text" 
            id="street_address" 
            name="street_address" 
            class="form-input" 
            value="<?= htmlspecialchars($editing_address['street_address'] ?? '') ?>"
            required
            placeholder="Av. Ejemplo 123, Depto 45..."
          >
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="city">Ciudad *</label>
            <input 
              type="text" 
              id="city" 
              name="city" 
              class="form-input" 
              value="<?= htmlspecialchars($editing_address['city'] ?? '') ?>"
              required
              placeholder="Santiago"
            >
          </div>
          
          <div class="form-group">
            <label class="form-label" for="state_province">Región *</label>
            <select name="state_province" id="state_province" class="form-select" required>
              <option value="">Selecciona región</option>
              <option value="Arica y Parinacota" <?= ($editing_address['state_province'] ?? '') === 'Arica y Parinacota' ? 'selected' : '' ?>>Arica y Parinacota</option>
              <option value="Tarapacá" <?= ($editing_address['state_province'] ?? '') === 'Tarapacá' ? 'selected' : '' ?>>Tarapacá</option>
              <option value="Antofagasta" <?= ($editing_address['state_province'] ?? '') === 'Antofagasta' ? 'selected' : '' ?>>Antofagasta</option>
              <option value="Atacama" <?= ($editing_address['state_province'] ?? '') === 'Atacama' ? 'selected' : '' ?>>Atacama</option>
              <option value="Coquimbo" <?= ($editing_address['state_province'] ?? '') === 'Coquimbo' ? 'selected' : '' ?>>Coquimbo</option>
              <option value="Valparaíso" <?= ($editing_address['state_province'] ?? '') === 'Valparaíso' ? 'selected' : '' ?>>Valparaíso</option>
              <option value="Metropolitana" <?= ($editing_address['state_province'] ?? '') === 'Metropolitana' ? 'selected' : '' ?>>Metropolitana</option>
              <option value="O'Higgins" <?= ($editing_address['state_province'] ?? '') === "O'Higgins" ? 'selected' : '' ?>>O'Higgins</option>
              <option value="Maule" <?= ($editing_address['state_province'] ?? '') === 'Maule' ? 'selected' : '' ?>>Maule</option>
              <option value="Ñuble" <?= ($editing_address['state_province'] ?? '') === 'Ñuble' ? 'selected' : '' ?>>Ñuble</option>
              <option value="Biobío" <?= ($editing_address['state_province'] ?? '') === 'Biobío' ? 'selected' : '' ?>>Biobío</option>
              <option value="Araucanía" <?= ($editing_address['state_province'] ?? '') === 'Araucanía' ? 'selected' : '' ?>>Araucanía</option>
              <option value="Los Ríos" <?= ($editing_address['state_province'] ?? '') === 'Los Ríos' ? 'selected' : '' ?>>Los Ríos</option>
              <option value="Los Lagos" <?= ($editing_address['state_province'] ?? '') === 'Los Lagos' ? 'selected' : '' ?>>Los Lagos</option>
              <option value="Aysén" <?= ($editing_address['state_province'] ?? '') === 'Aysén' ? 'selected' : '' ?>>Aysén</option>
              <option value="Magallanes" <?= ($editing_address['state_province'] ?? '') === 'Magallanes' ? 'selected' : '' ?>>Magallanes</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="postal_code">Código Postal *</label>
            <input 
              type="text" 
              id="postal_code" 
              name="postal_code" 
              class="form-input" 
              value="<?= htmlspecialchars($editing_address['postal_code'] ?? '') ?>"
              required
              placeholder="1234567"
            >
          </div>
          
          <div class="form-group">
            <label class="form-label" for="country">País</label>
            <select name="country" id="country" class="form-select">
              <option value="Chile" <?= ($editing_address['country'] ?? 'Chile') === 'Chile' ? 'selected' : '' ?>>🇨🇱 Chile</option>
            </select>
          </div>
        </div>
        
        <div class="checkbox-group">
          <input 
            type="checkbox" 
            id="is_default" 
            name="is_default" 
            value="1"
            <?= ($editing_address['is_default'] ?? 0) ? 'checked' : '' ?>
          >
          <label for="is_default">Establecer como dirección por defecto</label>
        </div>
        
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <?= $editing_address ? '💾 Guardar Cambios' : '➕ Agregar Dirección' ?>
          </button>
          <?php if ($editing_address): ?>
            <a href="addresses.php" class="btn btn-outline">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
    
    <!-- Lista de direcciones -->
    <div class="addresses-list">
      <h2 class="section-title">📋 Direcciones Guardadas</h2>
      
      <?php if (empty($addresses)): ?>
        <div class="empty-state">
          <p>No tienes direcciones guardadas</p>
          <p style="margin-top: 0.5rem;">Agrega tu primera dirección usando el formulario</p>
        </div>
      <?php else: ?>
        <?php foreach ($addresses as $address): ?>
          <div class="address-item">
            <div class="address-header">
              <div>
                <div class="address-type">
                  <?php
                  $type_labels = [
                    'billing' => '📄 Facturación',
                    'shipping' => '🚚 Despacho',
                    'both' => '🏠 Ambas'
                  ];
                  echo $type_labels[$address['type']] ?? 'Dirección';
                  ?>
                  <?php if ($address['is_default']): ?>
                    <span class="address-default">Por defecto</span>
                  <?php endif; ?>
                </div>
                <?php if ($address['label']): ?>
                  <div style="color: var(--address-text-muted); font-size: 0.875rem;">
                    <?= htmlspecialchars($address['label']) ?>
                  </div>
                <?php endif; ?>
              </div>
              
              <div class="address-actions">
                <a href="?edit=<?= $address['id'] ?>" class="btn btn-small btn-outline">✏️ Editar</a>
                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta dirección?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                  <button type="submit" class="btn btn-small btn-danger">🗑️</button>
                </form>
              </div>
            </div>
            
            <div class="address-details">
              <strong><?= htmlspecialchars($address['street_address']) ?></strong><br>
              <?= htmlspecialchars($address['city'] . ', ' . $address['state_province']) ?><br>
              <?= htmlspecialchars($address['postal_code'] . ', ' . $address['country']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Auto-focus en el primer campo del formulario
  const firstInput = document.querySelector('.address-form input');
  if (firstInput) {
    firstInput.focus();
  }
  
  // Validación básica del código postal chileno
  const postalCodeInput = document.getElementById('postal_code');
  if (postalCodeInput) {
    postalCodeInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, ''); // Solo números
      if (value.length > 7) {
        value = value.substring(0, 7);
      }
      e.target.value = value;
    });
  }
});
</script>
</body>
</html>