<?php
// Gestión de citas y reservas
// Este módulo está pendiente de implementación

if (!isset($store) || !isset($products)) {
    echo '<div class="alert alert-warning">Error: Datos de la tienda no disponibles.</div>';
    return;
}
?>

<div class="section-header">
  <h2 class="section-title">📅 Gestión de Citas - <?= htmlspecialchars($store['name']) ?></h2>
</div>

<div class="section-content">
  <div class="info-card">
    <div class="card-header">
      <h3>⚠️ Funcionalidad en Desarrollo</h3>
    </div>
    <div class="card-body">
      <p>La gestión de citas y reservas está pendiente de implementación.</p>
      <p>Esta sección permitirá:</p>
      <ul>
        <li>Crear y gestionar citas de servicio</li>
        <li>Configurar disponibilidad horaria</li>
        <li>Administrar calendario de reservas</li>
        <li>Enviar recordatorios automáticos</li>
        <li>Gestionar cancelaciones y reprogramaciones</li>
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
  background: #d1ecf1;
  border-bottom: 1px solid #bee5eb;
  border-radius: 8px 8px 0 0;
}

.card-header h3 {
  margin: 0;
  color: #0c5460;
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