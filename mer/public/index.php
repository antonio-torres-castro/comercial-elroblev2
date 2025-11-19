<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../src/functions.php';
$stores = stores();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_id'], $_POST['qty'])) {
  cartAdd((int)$_POST['add_product_id'], max(1, (int)$_POST['qty']));
  header('Location: cart.php');
  exit;
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Portal de tiendas</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sakura.css/css/sakura.css">
</head>
<body>
<header style="padding:10px; display:flex; align-items:center; gap:16px; background:#f5f5f5">
  <h1 style="margin:0;">Portal de tiendas</h1>
  <div style="margin-left:auto">
    <a href="cart.php">Ver carrito</a>
  </div>
</header>
<div>
<div style="display:grid; grid-template-columns: repeat(auto-fill,minmax(240px,1fr)); gap:16px;">
<?php foreach ($stores as $s): ?>
  <a href="/stores/<?= htmlspecialchars((string)$s['slug']) ?>/" style="display:block; text-decoration:none;">
    <section style="border:1px solid #ddd; border-radius:8px; padding:12px; background:<?= htmlspecialchars((string)($s['primary_color'] ?? '#ffffff')) ?>10;">
      <?php if (!empty($s['logo_url'])): ?>
        <img src="<?= htmlspecialchars((string)$s['logo_url']) ?>" alt="logo" style="height:60px; object-fit:contain; display:block; margin-bottom:8px;">
      <?php endif; ?>
      <h3 style="color:<?= htmlspecialchars((string)($s['primary_color'] ?? '#333')) ?>; margin:0 0 6px;"><?= htmlspecialchars($s['name']) ?></h3>
      <?php if (!empty($s['address'])): ?>
        <p style="margin:0; color:#555;"><?= htmlspecialchars((string)$s['address']) ?></p>
      <?php endif; ?>
    </section>
  </a>
<?php endforeach; ?>
<?php if (!$stores): ?>
  <p>Sin tiendas</p>
<?php endif; ?>
</div>
</body>
</html>