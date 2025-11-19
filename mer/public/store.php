<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../src/functions.php';
$slug = $_GET['store'] ?? null;
$store = storeBySlug($slug);
if (!$store) { http_response_code(404); echo 'Tienda no encontrada'; exit; }
$list = products((int)$store['id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_id'], $_POST['qty'])) {
  cartAdd((int)$_POST['add_product_id'], max(1, (int)$_POST['qty']));
  header('Location: /cart.php');
  exit;
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($store['name']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<header style="padding:10px; display:flex; align-items:center; gap:16px; background:<?= htmlspecialchars((string)($store['primary_color'] ?? '#f5f5f5')) ?>20">
  <?php if (!empty($store['logo_url'])): ?>
    <img src="<?= htmlspecialchars((string)$store['logo_url']) ?>" alt="logo" style="height:40px">
  <?php endif; ?>
  <h1 style="color:<?= htmlspecialchars((string)($store['primary_color'] ?? '#333')) ?>; margin:0;"><?= htmlspecialchars($store['name']) ?></h1>
  <div style="margin-left:auto">
    <a href="/cart.php">Ver carrito</a>
  </div>
</header>
<main>
<?php foreach ($list as $p): ?>
  <section>
    <h3><?= htmlspecialchars($p['name']) ?></h3>
    <p><?= htmlspecialchars((string)$p['description']) ?></p>
    <p>$<?= number_format((float)$p['price'], 2) ?></p>
    <form method="post">
      <input type="hidden" name="add_product_id" value="<?= (int)$p['id'] ?>">
      <input type="number" name="qty" value="1" min="1" style="width:80px">
      <button type="submit">Agregar al carrito</button>
    </form>
  </section>
<?php endforeach; ?>
<?php if (!$list): ?>
  <p>Sin productos</p>
<?php endif; ?>
</main>
</body>
</html>