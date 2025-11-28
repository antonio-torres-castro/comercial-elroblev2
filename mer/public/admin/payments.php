<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/functions.php';
require_once __DIR__ . '/../../src/auth_functions.php';

init_secure_session();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
  markPaymentPaid((int)$_POST['payment_id'], $_POST['transaction_id'] ?? null);
  header('Location: payments.php');
  exit;
}
$payments = paymentsList();
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pagos</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<h1>Pagos</h1>
<p><a href="/admin/orders.php">Órdenes</a> | <a href="/admin/payouts.php">Liquidaciones</a></p>
<table>
<thead>
  <tr><th>ID</th><th>Orden</th><th>Cliente</th><th>Método</th><th>Monto</th><th>Estado</th><th>Ref/Transacción</th><th>Acciones</th></tr>
</thead>
<tbody>
<?php foreach ($payments as $p): ?>
  <tr>
    <td><?= (int)$p['id'] ?></td>
    <td><?= (int)$p['order_id'] ?></td>
    <td><?= htmlspecialchars((string)$p['customer_name']) ?></td>
    <td><?= htmlspecialchars((string)$p['method']) ?></td>
    <td>$<?= number_format((float)$p['amount'], 2) ?></td>
    <td><?= htmlspecialchars((string)$p['status']) ?></td>
    <td><?= htmlspecialchars((string)($p['transaction_id'] ?? $p['transfer_code'] ?? '')) ?></td>
    <td>
      <?php if ($p['status'] !== 'paid'): ?>
        <form method="post" style="display:flex; gap:6px; align-items:center;">
          <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
          <input type="text" name="transaction_id" placeholder="ID transacción" style="width:160px">
          <button type="submit">Marcar pagado</button>
        </form>
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
<?php if (!$payments): ?>
  <tr><td colspan="8">Sin pagos</td></tr>
<?php endif; ?>
</tbody>
</table>
</body>
</html>