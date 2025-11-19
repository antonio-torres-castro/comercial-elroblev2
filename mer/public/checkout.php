<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../src/functions.php';
$t = totals();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = createOrder([
    'name' => $_POST['name'] ?? '',
    'email' => $_POST['email'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'address' => $_POST['address'] ?? '',
    'city' => $_POST['city'] ?? '',
    'notes' => $_POST['notes'] ?? '',
  ]);
  if ($result['ok']) {
    $oid = (int)$result['order_id'];
    header('Location: /pay.php?order_id=' . $oid);
    exit;
  }
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<h1>Checkout</h1>
<p><a href="cart.php">Volver al carrito</a></p>
<?php if (true): ?>
  <?php if (!$t['items']): ?>
    <p>Carrito vacío</p>
  <?php else: ?>
  <form method="post">
    <label>Nombre
      <input type="text" name="name" required>
    </label>
    <label>Email
      <input type="email" name="email">
    </label>
    <label>Teléfono
      <input type="text" name="phone">
    </label>
    <label>Dirección
      <input type="text" name="address">
    </label>
    <label>Ciudad
      <input type="text" name="city">
    </label>
    <label>Notas
      <textarea name="notes"></textarea>
    </label>
    <p>Total: $<?= number_format((float)$t['total'], 2) ?></p>
    <button type="submit">Confirmar compra</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
</body>
</html>