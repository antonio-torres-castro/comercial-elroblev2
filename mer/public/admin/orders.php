<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../src/functions.php';
$orders = ordersList();
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Órdenes</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<h1>Órdenes</h1>
<p><a href="/index.php">Ir al portal</a> | <a href="/admin/payouts.php">Liquidaciones</a> | <a href="/admin/payments.php">Pagos</a></p>
<table>
<thead>
  <tr><th>ID</th><th>Fecha</th><th>Cliente</th><th>Total</th><th></th></tr>
</thead>
<tbody>
<?php foreach ($orders as $o): ?>
  <tr>
    <td><?= (int)$o['id'] ?></td>
    <td><?= htmlspecialchars((string)$o['created_at']) ?></td>
    <td><?= htmlspecialchars((string)$o['customer_name']) ?></td>
    <td>$<?= number_format((float)$o['total'], 2) ?></td>
    <td><a href="order.php?id=<?= (int)$o['id'] ?>">Ver detalle</a></td>
  </tr>
<?php endforeach; ?>
<?php if (!$orders): ?>
  <tr><td colspan="5">Sin órdenes</td></tr>
<?php endif; ?>
</tbody>
</table>
</body>
</html>