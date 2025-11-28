<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/functions.php';
require_once __DIR__ . '/../../src/auth_functions.php';

init_secure_session();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = orderById($id);
if (!$order) { http_response_code(404); echo 'Orden no encontrada'; exit; }
$items = orderItems($id);
$stores = orderStoreTotals($id);
$notes = orderNotifications($id);
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orden #<?= (int)$order['id'] ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<h1>Orden #<?= (int)$order['id'] ?></h1>
<p><a href="orders.php">Volver</a></p>
<section>
  <p>Fecha: <?= htmlspecialchars((string)$order['created_at']) ?></p>
  <p>Cliente: <?= htmlspecialchars((string)$order['customer_name']) ?>, Email: <?= htmlspecialchars((string)$order['email']) ?></p>
  <p>Teléfono: <?= htmlspecialchars((string)$order['phone']) ?></p>
  <p>Dirección: <?= htmlspecialchars((string)$order['address']) ?>, Ciudad: <?= htmlspecialchars((string)$order['city']) ?></p>
  <p>Cupón: <?= htmlspecialchars((string)($order['coupon_code'] ?? '')) ?></p>
  <p>Subtotal: $<?= number_format((float)$order['subtotal'], 2) ?>, Descuento: $<?= number_format((float)$order['discount'], 2) ?>, Despacho: $<?= number_format((float)$order['shipping'], 2) ?>, Total: $<?= number_format((float)$order['total'], 2) ?></p>
  <p>Pago: <?= htmlspecialchars((string)($order['payment_method'] ?? 'pendiente')) ?>, Estado: <?= htmlspecialchars((string)($order['payment_status'] ?? 'pending')) ?>, Ref: <?= htmlspecialchars((string)($order['payment_reference'] ?? '')) ?></p>
</section>

<h3>Ítems</h3>
<table>
<thead>
  <tr><th>Producto</th><th>Tienda</th><th>Cantidad</th><th>Precio</th><th>Despacho</th><th>Costos</th><th>Total línea</th></tr>
</thead>
<tbody>
<?php foreach ($items as $it): ?>
  <tr>
    <td><?= htmlspecialchars((string)$it['product_name']) ?></td>
    <td><?= htmlspecialchars((string)$it['store_name']) ?></td>
    <td><?= (int)$it['qty'] ?></td>
    <td>$<?= number_format((float)$it['unit_price'], 2) ?></td>
    <td><?= htmlspecialchars((string)($it['shipping_name'] ?? '')) ?></td>
    <td>Sub: $<?= number_format((float)$it['line_subtotal'], 2) ?> / Env: $<?= number_format((float)$it['line_shipping'], 2) ?></td>
    <td>$<?= number_format((float)$it['line_total'], 2) ?></td>
  </tr>
<?php endforeach; ?>
<?php if (!$items): ?>
  <tr><td colspan="7">Sin ítems</td></tr>
<?php endif; ?>
</tbody>
</table>

<h3>Totales por tienda</h3>
<ul>
<?php foreach ($stores as $ps): ?>
  <li><?= htmlspecialchars((string)$ps['store_name']) ?>: Sub $<?= number_format((float)$ps['subtotal'], 2) ?>, Desc $<?= number_format((float)$ps['discount'], 2) ?>, Env $<?= number_format((float)$ps['shipping'], 2) ?>, Total $<?= number_format((float)$ps['total'], 2) ?></li>
<?php endforeach; ?>
<?php if (!$stores): ?>
  <li>Sin desglose por tienda</li>
<?php endif; ?>
</ul>

<h3>Notificaciones</h3>
<ul>
<?php foreach ($notes as $n): ?>
  <li>[<?= htmlspecialchars((string)$n['channel']) ?>] <?= htmlspecialchars((string)$n['store_name']) ?>: <?= nl2br(htmlspecialchars((string)$n['content'])) ?></li>
<?php endforeach; ?>
<?php if (!$notes): ?>
  <li>Sin notificaciones</li>
<?php endif; ?>
</ul>

</body>
</html>