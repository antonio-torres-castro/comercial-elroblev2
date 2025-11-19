<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../src/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['update']) && isset($_POST['qty']) && is_array($_POST['qty'])) {
    foreach ($_POST['qty'] as $pid => $q) cartUpdate((int)$pid, max(0, (int)$q));
  }
  if (isset($_POST['clear'])) {
    cartClear();
  }
  if (isset($_POST['shipping']) && is_array($_POST['shipping'])) {
    foreach ($_POST['shipping'] as $pid => $mid) shippingSelectionSet((int)$pid, (int)$mid);
  }
  if (isset($_POST['addr']) && is_array($_POST['addr'])) {
    foreach ($_POST['addr'] as $pid => $addr) {
      $city = $_POST['city'][$pid] ?? '';
      deliveryAddressSet((int)$pid, (string)$addr, (string)$city);
    }
  }
  if (isset($_POST['coupon_code'])) {
    $ok = couponApply(trim((string)$_POST['coupon_code']));
    if (!$ok) couponClear();
  }
  header('Location: cart.php');
  exit;
}

$t = totals();
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Carrito</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
<script defer src="/assets/js/app.js"></script>
</head>
<body>
<h1>Carrito</h1>
<p><a href="index.php">Seguir comprando</a></p>
<form method="post">
<table>
<thead>
<tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Despacho</th><th>Dirección</th></tr>
</thead>
<tbody>
<?php foreach ($t['items'] as $it): $p = $it['product']; ?>
<tr>
  <td><?= htmlspecialchars($p['name']) ?></td>
  <td>$<?= number_format((float)$p['price'], 2) ?></td>
  <td><input type="number" name="qty[<?= (int)$p['id'] ?>]" value="<?= (int)$it['qty'] ?>" min="0" style="width:80px"></td>
  <td>
    <select name="shipping[<?= (int)$p['id'] ?>]" class="ship-select">
      <?php foreach ($it['shipping_methods'] as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= (int)$it['selected_shipping_id'] === (int)$m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> ($<?= number_format((float)$m['cost'], 2) ?>)</option>
      <?php endforeach; ?>
    </select>
  </td>
  <td></td>
  <td>
    <input type="text" name="addr[<?= (int)$p['id'] ?>]" value="<?= htmlspecialchars((string)($it['delivery']['address'] ?? '')) ?>" placeholder="Dirección de entrega">
    <input type="text" name="city[<?= (int)$p['id'] ?>]" value="<?= htmlspecialchars((string)($it['delivery']['city'] ?? '')) ?>" placeholder="Ciudad" style="width:140px">
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$t['items']): ?>
<tr><td colspan="5">Carrito vacío</td></tr>
<?php endif; ?>
</tbody>
</table>
<p><button type="submit" name="update" value="1">Actualizar cantidades</button> <button type="submit" name="clear" value="1">Vaciar carrito</button></p>
<h3>Cupones</h3>
<p>
  <input type="text" name="coupon_code" placeholder="Código de cupón" value="<?= htmlspecialchars((string)($t['coupon']['code'] ?? '')) ?>">
  <button type="submit">Aplicar cupón</button>
</p>
<h3>Totales</h3>
<p>Subtotal: $<?= number_format((float)$t['subtotal'], 2) ?></p>
<p>Descuento: $<?= number_format((float)$t['discount'], 2) ?></p>
<p>Despacho: $<?= number_format((float)$t['shipping'], 2) ?></p>
<p>Total: $<?= number_format((float)$t['total'], 2) ?></p>
<p><a href="checkout.php">Ir a checkout</a></p>

<h3>Desglose por tienda</h3>
<?php foreach ($t['per_store'] as $sid => $ps): ?>
  <section>
    <h4><?= htmlspecialchars($ps['store']['name'] ?? ('Tienda ' . (int)$sid)) ?></h4>
    <p>Subtotal: $<?= number_format((float)$ps['subtotal'], 2) ?></p>
    <p>Descuento asignado: $<?= number_format((float)$ps['discount'], 2) ?></p>
    <p>Despacho: $<?= number_format((float)$ps['shipping'], 2) ?></p>
    <p>Total tienda: $<?= number_format((float)$ps['total'], 2) ?></p>
    <?php if (!empty($ps['store']['address'])): ?>
      <p>Dirección de despacho tienda: <?= htmlspecialchars((string)$ps['store']['address']) ?></p>
      <p>Tiempo de entrega: <?= htmlspecialchars((string)$ps['store']['delivery_time_days_min']) ?>–<?= htmlspecialchars((string)$ps['store']['delivery_time_days_max']) ?> días</p>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
</form>
</body>
</html>
