<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/auth_functions.php';

init_secure_session();
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/config.php';
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$paymentId = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
$order = $orderId ? orderById($orderId) : null;
if (!$order) { http_response_code(404); echo 'Orden no encontrada'; exit; }
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Transbank</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<h1>Transbank</h1>
<p>Orden #<?= (int)$order['id'] ?>, Total: $<?= number_format((float)$order['total'], 2) ?></p>
<?php if (defined('TRANSBANK_MOCK') && TRANSBANK_MOCK): ?>
  <form method="post" style="display:flex; gap:8px;">
    <input type="hidden" name="payment_id" value="<?= (int)$paymentId ?>">
    <button name="simulate" value="success" type="submit">Simular éxito</button>
    <button name="simulate" value="fail" type="submit">Simular fallo</button>
  </form>
  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate'])): ?>
    <?php if ($_POST['simulate'] === 'success') { markPaymentPaid((int)$paymentId, 'MOCK-TBK-OK'); header('Location: /admin/order.php?id=' . (int)$order['id']); exit; } ?>
    <?php if ($_POST['simulate'] === 'fail') { markPaymentFailed((int)$paymentId, 'MOCK-TBK-FAIL'); header('Location: /admin/order.php?id=' . (int)$order['id']); exit; } ?>
  <?php endif; ?>
<?php else: ?>
  <p>Inicialice aquí la transacción Webpay usando el SDK oficial.</p>
  <p>CommerceCode: <?= htmlspecialchars((string)TRANSBANK_COMMERCE_CODE) ?> | Ambiente: <?= htmlspecialchars((string)TRANSBANK_ENV) ?></p>
  <p><em>Integración pendiente de SDK y credenciales.</em></p>
<?php endif; ?>
<p><a href="/admin/orders.php">Órdenes</a></p>
</body>
</html>