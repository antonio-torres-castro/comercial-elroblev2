<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/functions.php';
require_once __DIR__ . '/../../src/auth_functions.php';

init_secure_session();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payout_id'])) {
  markPayoutPaid((int)$_POST['payout_id'], $_POST['method'] ?? null, $_POST['reference'] ?? null);
  header('Location: payouts.php');
  exit;
}
$payouts = payoutsList();
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Liquidaciones</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<h1>Liquidaciones</h1>
<p><a href="/admin/orders.php">Órdenes</a> | <a href="/">Portal</a></p>
<table>
<thead>
  <tr><th>ID</th><th>Tienda</th><th>Bruto</th><th>Comisión neta</th><th>IVA comisión</th><th>Comisión total</th><th>Neto</th><th>Estado</th><th>Programada</th><th>Pagada</th><th>Método</th><th>Ref</th><th>Acciones</th></tr>
</thead>
<tbody>
<?php foreach ($payouts as $p): ?>
  <tr>
    <td><?= (int)$p['id'] ?></td>
    <td><?= htmlspecialchars((string)$p['store_name']) ?></td>
    <td>$<?= number_format((float)$p['amount'], 2) ?></td>
    <td>$<?= number_format((float)$p['commission_amount'], 2) ?> (<?= number_format((float)$p['commission_percent'], 2) ?>%, min $<?= number_format((float)$p['commission_min'], 2) ?>)</td>
    <td>$<?= number_format((float)$p['commission_vat_amount'], 2) ?> (<?= number_format((float)$p['commission_vat_percent'], 2) ?>%)</td>
    <td>$<?= number_format((float)$p['commission_gross_amount'], 2) ?></td>
    <td>$<?= number_format((float)$p['net_amount'], 2) ?></td>
    <td><?= htmlspecialchars((string)$p['status']) ?></td>
    <td><?= htmlspecialchars((string)$p['scheduled_at']) ?></td>
    <td><?= htmlspecialchars((string)$p['paid_at'] ?? '') ?></td>
    <td><?= htmlspecialchars((string)$p['method'] ?? '') ?></td>
    <td><?= htmlspecialchars((string)$p['reference'] ?? '') ?></td>
    <td>
      <?php if ($p['status'] !== 'paid'): ?>
        <form method="post" style="display:flex; gap:6px; align-items:center;">
          <input type="hidden" name="payout_id" value="<?= (int)$p['id'] ?>">
          <input type="text" name="method" placeholder="Método" style="width:120px">
          <input type="text" name="reference" placeholder="Referencia" style="width:140px">
          <button type="submit">Marcar pagada</button>
        </form>
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
<?php if (!$payouts): ?>
  <tr><td colspan="9">Sin liquidaciones</td></tr>
<?php endif; ?>
</tbody>
</table>
</body>
</html>