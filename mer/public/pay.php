<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/auth_functions.php';

init_secure_session();
require_once __DIR__ . '/../src/functions.php';
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : (int)($_SESSION['last_order_id'] ?? 0);
$order = $orderId ? orderById($orderId) : null;
if (!$order) { http_response_code(404); echo 'Orden no encontrada'; exit; }
$amount = (float)$order['total'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method'])) {
  $m = $_POST['method'];
  if ($m === 'transfer') {
    $code = 'TRX-' . $orderId . '-' . substr(bin2hex(random_bytes(3)),0,6);
    createPayment($orderId, 'transfer', $amount, ['transfer_code'=>$code]);
    header('Location: pay.php?order_id=' . $orderId . '&transfer=1');
    exit;
  } elseif ($m === 'cash') {
    $pickup = isset($_POST['pickup_location_id']) ? (int)$_POST['pickup_location_id'] : null;
    createPayment($orderId, 'cash', $amount, ['pickup_location_id'=>$pickup]);
    header('Location: pay.php?order_id=' . $orderId . '&cash=1');
    exit;
  } elseif ($m === 'transbank') {
    $pid = createPayment($orderId, 'transbank', $amount, []);
    header('Location: pay_transbank.php?order_id=' . $orderId . '&payment_id=' . (int)$pid);
    exit;
  }
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pago</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<h1>Pago de orden #<?= (int)$order['id'] ?></h1>
<p>Total: $<?= number_format($amount, 2) ?></p>
<form method="post">
  <label><input type="radio" name="method" value="transbank" required> Transbank Webpay</label>
  <br>
  <label><input type="radio" name="method" value="transfer" required> Transferencia bancaria</label>
  <br>
  <label><input type="radio" name="method" value="cash" required> Pago en efectivo al retirar</label>
  <div>
    <?php $pls = db()->query("SELECT id, name, address FROM pickup_locations ORDER BY id")->fetchAll(); ?>
    <select name="pickup_location_id">
      <option value="">Seleccionar central de retiro</option>
      <?php foreach ($pls as $pl): ?>
        <option value="<?= (int)$pl['id'] ?>"><?= htmlspecialchars((string)$pl['name']) ?> - <?= htmlspecialchars((string)$pl['address']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit">Continuar</button>
  <p><a href="/admin/orders.php">Ver órdenes</a></p>
</form>
<?php if (isset($_GET['transfer'])): ?>
  <?php $p = db()->prepare("SELECT * FROM payments WHERE order_id = ? AND method = 'transfer' ORDER BY id DESC LIMIT 1"); $p->execute([$orderId]); $pay = $p->fetch(); ?>
  <h3>Datos para transferencia</h3>
  <p>Monto: $<?= number_format($amount, 2) ?></p>
  <p>Código de referencia: <strong><?= htmlspecialchars((string)$pay['transfer_code']) ?></strong></p>
  <p>Banco: XXXXXX, Cuenta: 00-000000-0, RUT: 00.000.000-0, Email: pagos@example.com</p>
  <p>Use el código de referencia en el asunto o mensaje de la transferencia.</p>
<?php endif; ?>
<?php if (isset($_GET['cash'])): ?>
  <?php $p = db()->prepare("SELECT * FROM payments WHERE order_id = ? AND method = 'cash' ORDER BY id DESC LIMIT 1"); $p->execute([$orderId]); $pay = $p->fetch(); ?>
  <h3>Pago en efectivo</h3>
  <p>Presente su orden #<?= (int)$order['id'] ?> en la central seleccionada para pagar y retirar.</p>
<?php endif; ?>
</body>
</html>