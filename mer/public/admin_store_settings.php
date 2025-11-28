<?php
// Configuración avanzada de la tienda
// Este módulo está pendiente de implementación

if (!isset($store) || !isset($products)) {
    echo '<div class="alert alert-warning">Error: Datos de la tienda no disponibles.</div>';
    return;
}
?>

<div class="section-header">
  <h2 class="section-title">⚙️ Configuración de Tienda - <?= htmlspecialchars($store['name']) ?></h2>
</div>

<div class="section-content">
  <div class="info-card">
    <div class="card-header">
      <h3>⚠️ Funcionalidad en Desarrollo</h3>
    </div>
    <div class="card-body">
      <p>La configuración avanzada de la tienda está pendiente de implementación.</p>
      <p>Esta sección permitirá:</p>
      <ul>
        <li>Configurar métodos de pago disponibles</li>
        <li>Gestionar políticas de envío</li>
        <li>Personalizar diseño y temas</li>
        <li>Configurar notificaciones automáticas</li>
        <li>Administrar usuarios y permisos</li>
        <li>Configurar integraciones con terceros</li>
      </ul>
    </div>
  </div>
</div>

<style>
.info-card {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  margin: 20px 0;
}

.card-header {
  padding: 15px;
  background: #e2e3e5;
  border-bottom: 1px solid #d6d8db;
  border-radius: 8px 8px 0 0;
}

.card-header h3 {
  margin: 0;
  color: #383d41;
}

.card-body {
  padding: 15px;
}

.card-body ul {
  margin: 10px 0;
  padding-left: 20px;
}

.card-body li {
  margin: 5px 0;
}
</style>