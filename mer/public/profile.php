<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/auth_functions.php';
require_once __DIR__ . '/../src/functions.php';

init_secure_session();

// Verificar autenticación
requireAuth();

// Obtener datos del usuario
$user = getCurrentUser();
$addresses = getUserAddresses();

$orders_count = 0; // TODO: Implementar conteo de órdenes
$cart_items_count = 0; // TODO: Implementar conteo del carrito
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi Perfil - Mall Virtual</title>
<link rel="stylesheet" href="assets/css/modern.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --profile-primary: #3B82F6;
  --profile-secondary: #64748B;
  --profile-success: #10B981;
  --profile-danger: #EF4444;
  --profile-warning: #F59E0B;
  --profile-background: #F8FAFC;
  --profile-card: #FFFFFF;
  --profile-text: #1E293B;
  --profile-text-muted: #64748B;
  --profile-border: #E2E8F0;
  --profile-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--profile-background);
  color: var(--profile-text);
  line-height: 1.6;
}

.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.profile-header {
  background: var(--profile-card);
  border-radius: 1rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: var(--profile-shadow);
  display: flex;
  align-items: center;
  gap: 2rem;
}

.profile-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--profile-primary) 0%, #8B5CF6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
  font-weight: 700;
}

.profile-info h1 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--profile-text);
}

.profile-info p {
  color: var(--profile-text-muted);
  margin-bottom: 0.5rem;
}

.profile-actions {
  margin-left: auto;
  display: flex;
  gap: 1rem;
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
  background: var(--profile-primary);
  color: white;
}

.btn-primary:hover {
  background: #2563EB;
}

.btn-outline {
  background: transparent;
  color: var(--profile-primary);
  border: 1px solid var(--profile-primary);
}

.btn-outline:hover {
  background: var(--profile-primary);
  color: white;
}

.profile-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.profile-section {
  background: var(--profile-card);
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: var(--profile-shadow);
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: var(--profile-text);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  padding: 1rem;
  border: 1px solid var(--profile-border);
  border-radius: 0.5rem;
  text-align: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--profile-primary);
}

.stat-label {
  font-size: 0.75rem;
  color: var(--profile-text-muted);
  text-transform: uppercase;
  margin-top: 0.25rem;
}

.addresses-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.address-item {
  padding: 1rem;
  border: 1px solid var(--profile-border);
  border-radius: 0.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.address-info {
  flex: 1;
}

.address-type {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.address-details {
  color: var(--profile-text-muted);
  font-size: 0.875rem;
}

.address-default {
  background: var(--profile-success);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: var(--profile-text-muted);
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}

.quick-action {
  padding: 1rem;
  border: 1px solid var(--profile-border);
  border-radius: 0.5rem;
  text-align: center;
  text-decoration: none;
  color: var(--profile-text);
  transition: all 0.2s ease;
}

.quick-action:hover {
  border-color: var(--profile-primary);
  color: var(--profile-primary);
}

@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
  }
  
  .profile-actions {
    margin-left: 0;
    margin-top: 1rem;
  }
  
  .profile-content {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>
<div class="profile-container">
  <!-- Header del perfil -->
  <div class="profile-header">
    <div class="profile-avatar">
      <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
    </div>
    
    <div class="profile-info">
      <h1><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
      <p>📧 <?= htmlspecialchars($user['email']) ?></p>
      <?php if ($user['phone']): ?>
        <p>📞 <?= htmlspecialchars($user['phone']) ?></p>
      <?php endif; ?>
      <p>📅 Miembro desde <?= date('M Y', strtotime($user['created_at'])) ?></p>
    </div>
    
    <div class="profile-actions">
      <a href="edit-profile.php" class="btn btn-outline">✏️ Editar Perfil</a>
      <a href="addresses.php" class="btn btn-outline">📍 Direcciones</a>
      <?php if (getUserRole() === 'admin'): ?>
        <a href="/mer/public/admin/" class="btn btn-primary">⚙️ Panel Admin</a>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Contenido del perfil -->
  <div class="profile-content">
    <!-- Estadísticas -->
    <div class="profile-section">
      <h2 class="section-title">📊 Mis Estadísticas</h2>
      
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value"><?= $orders_count ?></div>
          <div class="stat-label">Órdenes</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= $cart_items_count ?></div>
          <div class="stat-label">En Carrito</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= count($addresses) ?></div>
          <div class="stat-label">Direcciones</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= $user['email_verified_at'] ? '✓' : '⏳' ?></div>
          <div class="stat-label">Email <?= $user['email_verified_at'] ? 'Verificado' : 'Pendiente' ?></div>
        </div>
      </div>
      
      <div class="quick-actions">
        <a href="/mer/public/cart.php" class="quick-action">
          🛒 Ver Carrito
        </a>
        <a href="/mer/public/" class="quick-action">
          🏠 Explorar Tiendas
        </a>
        <a href="orders.php" class="quick-action">
          📋 Mis Órdenes
        </a>
        <a href="wishlist.php" class="quick-action">
          ❤️ Lista de Deseos
        </a>
      </div>
    </div>
    
    <!-- Direcciones -->
    <div class="profile-section">
      <h2 class="section-title">📍 Mis Direcciones</h2>
      
      <?php if (empty($addresses)): ?>
        <div class="empty-state">
          <p>No tienes direcciones guardadas</p>
          <a href="addresses.php" class="btn btn-primary" style="margin-top: 1rem;">
            + Agregar Dirección
          </a>
        </div>
      <?php else: ?>
        <div class="addresses-list">
          <?php foreach (array_slice($addresses, 0, 3) as $address): ?>
            <div class="address-item">
              <div class="address-info">
                <div class="address-type">
                  <?php
                  $type_labels = [
                    'billing' => 'Facturación',
                    'shipping' => 'Despacho',
                    'both' => 'Ambas'
                  ];
                  echo $type_labels[$address['type']] ?? 'Dirección';
                  ?>
                  <?php if ($address['is_default']): ?>
                    <span class="address-default">Por defecto</span>
                  <?php endif; ?>
                </div>
                <div class="address-details">
                  <?= htmlspecialchars($address['label'] ? $address['label'] . ' - ' : '') ?>
                  <?= htmlspecialchars($address['street_address'] . ', ' . $address['city'] . ', ' . $address['state_province']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <?php if (count($addresses) > 3): ?>
          <a href="addresses.php" class="btn btn-outline" style="margin-top: 1rem;">
            Ver todas las direcciones (<?= count($addresses) ?>)
          </a>
        <?php else: ?>
          <a href="addresses.php" class="btn btn-outline" style="margin-top: 1rem;">
            Gestionar direcciones
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Animación de carga
document.addEventListener('DOMContentLoaded', function() {
  const header = document.querySelector('.profile-header');
  const sections = document.querySelectorAll('.profile-section');
  
  header.style.opacity = '0';
  header.style.transform = 'translateY(20px)';
  
  setTimeout(() => {
    header.style.transition = 'all 0.5s ease';
    header.style.opacity = '1';
    header.style.transform = 'translateY(0)';
  }, 100);
  
  sections.forEach((section, index) => {
    section.style.opacity = '0';
    section.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
      section.style.transition = 'all 0.5s ease';
      section.style.opacity = '1';
      section.style.transform = 'translateY(0)';
    }, 200 + (index * 100));
  });
});
</script>
</body>
</html>