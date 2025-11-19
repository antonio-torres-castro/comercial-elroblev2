<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function stores(): array {
  $stmt = db()->query("SELECT id, name, slug, logo_url, primary_color, address, delivery_time_days_min, delivery_time_days_max, contact_email, payout_delay_days, commission_rate_percent, commission_min_amount, tax_rate_percent FROM stores ORDER BY id");
  return $stmt->fetchAll();
}

function storeBySlug(?string $slug): ?array {
  if (!$slug) return null;
  $stmt = db()->prepare("SELECT id, name, slug, logo_url, primary_color, address, delivery_time_days_min, delivery_time_days_max, contact_email, payout_delay_days, commission_rate_percent, commission_min_amount, tax_rate_percent FROM stores WHERE slug = ?");
  $stmt->execute([$slug]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function storeById(int $id): ?array {
  $stmt = db()->prepare("SELECT id, name, slug, logo_url, primary_color, address, delivery_time_days_min, delivery_time_days_max, contact_email, payout_delay_days, commission_rate_percent, commission_min_amount, tax_rate_percent FROM stores WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function products(?int $storeId = null): array {
  if ($storeId) {
    $stmt = db()->prepare("SELECT p.id, p.store_id, p.name, p.description, p.price, p.group_id FROM products p WHERE p.active = 1 AND p.store_id = ? ORDER BY p.id");
    $stmt->execute([$storeId]);
    return $stmt->fetchAll();
  }
  $stmt = db()->query("SELECT p.id, p.store_id, p.name, p.description, p.price, p.group_id FROM products p WHERE p.active = 1 ORDER BY p.id");
  return $stmt->fetchAll();
}

function productById(int $id): ?array {
  $stmt = db()->prepare("SELECT id, store_id, name, price, group_id FROM products WHERE id = ? AND active = 1");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function shippingMethodsForProduct(int $productId): array {
  $stmt = db()->prepare("SELECT sm.id, sm.name, sm.cost FROM product_shipping_methods psm JOIN shipping_methods sm ON sm.id = psm.shipping_method_id WHERE psm.product_id = ? AND sm.active = 1 ORDER BY sm.id");
  $stmt->execute([$productId]);
  $rows = $stmt->fetchAll();
  if ($rows) return $rows;
  $stmt2 = db()->prepare("SELECT sm.id, sm.name, sm.cost FROM products p JOIN group_shipping_methods gsm ON gsm.group_id = p.group_id JOIN shipping_methods sm ON sm.id = gsm.shipping_method_id WHERE p.id = ? AND sm.active = 1 ORDER BY sm.id");
  $stmt2->execute([$productId]);
  return $stmt2->fetchAll();
}

function couponByCode(string $code): ?array {
  $stmt = db()->prepare("SELECT id, code, type, value, expires_at FROM coupons WHERE code = ? AND active = 1");
  $stmt->execute([$code]);
  $row = $stmt->fetch();
  if (!$row) return null;
  $expires = strtotime($row['expires_at']);
  if ($expires < time()) return null;
  return $row;
}

function cartGet(): array {
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
  return $_SESSION['cart'];
}

function cartAdd(int $productId, int $qty): void {
  $p = productById($productId);
  if (!$p) return;
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
  if (!isset($_SESSION['cart'][$productId])) $_SESSION['cart'][$productId] = 0;
  $_SESSION['cart'][$productId] += max(1, $qty);
}

function cartUpdate(int $productId, int $qty): void {
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
  if ($qty <= 0) unset($_SESSION['cart'][$productId]); else $_SESSION['cart'][$productId] = $qty;
}

function cartClear(): void {
  $_SESSION['cart'] = [];
  $_SESSION['shipping'] = [];
  $_SESSION['coupon'] = null;
}

function shippingSelectionSet(int $productId, int $methodId): void {
  $methods = shippingMethodsForProduct($productId);
  $ok = false;
  foreach ($methods as $m) if ((int)$m['id'] === $methodId) { $ok = true; break; }
  if (!$ok) return;
  if (!isset($_SESSION['shipping'])) $_SESSION['shipping'] = [];
  $_SESSION['shipping'][$productId] = $methodId;
}

function shippingSelectionGet(): array {
  if (!isset($_SESSION['shipping'])) $_SESSION['shipping'] = [];
  return $_SESSION['shipping'];
}

function deliveryAddressesGet(): array {
  if (!isset($_SESSION['delivery'])) $_SESSION['delivery'] = [];
  return $_SESSION['delivery'];
}

function deliveryAddressSet(int $productId, string $addr, string $city): void {
  if (!isset($_SESSION['delivery'])) $_SESSION['delivery'] = [];
  $_SESSION['delivery'][$productId] = ['address'=>trim($addr), 'city'=>trim($city)];
}

function couponApply(string $code): bool {
  $c = couponByCode($code);
  if (!$c) return false;
  $_SESSION['coupon'] = $c;
  return true;
}

function couponClear(): void {
  $_SESSION['coupon'] = null;
}

function totals(): array {
  $cart = cartGet();
  $shipSel = shippingSelectionGet();
  $deliv = deliveryAddressesGet();
  $coupon = $_SESSION['coupon'] ?? null;
  $items = [];
  $subtotal = 0.0;
  $shipping = 0.0;
  $perStore = [];
  foreach ($cart as $pid => $qty) {
    $p = productById((int)$pid);
    if (!$p) continue;
    $methods = shippingMethodsForProduct((int)$pid);
    $selectedId = $shipSel[$pid] ?? ($methods[0]['id'] ?? null);
    $selected = null;
    foreach ($methods as $m) if ((int)$m['id'] === (int)$selectedId) { $selected = $m; break; }
    if ($selected) {
      $shipping += ((float)$selected['cost']) * $qty;
      $sid = (int)$p['store_id'];
      if (!isset($perStore[$sid])) $perStore[$sid] = ['subtotal'=>0.0,'shipping'=>0.0,'items'=>[]];
      $perStore[$sid]['shipping'] += ((float)$selected['cost']) * $qty;
    }
    $line = ((float)$p['price']) * $qty;
    $subtotal += $line;
    $sid2 = (int)$p['store_id'];
    if (!isset($perStore[$sid2])) $perStore[$sid2] = ['subtotal'=>0.0,'shipping'=>0.0,'items'=>[]];
    $perStore[$sid2]['subtotal'] += $line;
    $perStore[$sid2]['items'][] = [
      'product' => $p,
      'qty' => $qty,
      'shipping_methods' => $methods,
      'selected_shipping_id' => $selectedId,
    ];
    $items[] = [
      'product' => $p,
      'qty' => $qty,
      'shipping_methods' => $methods,
      'selected_shipping_id' => $selectedId,
      'delivery' => $deliv[$pid] ?? null,
    ];
  }
  $discount = 0.0;
  $shippingDiscounted = $shipping;
  if ($coupon) {
    if ($coupon['type'] === 'free_shipping') {
      $shippingDiscounted = 0.0;
    } elseif ($coupon['type'] === 'percent') {
      $discount = min($subtotal, $subtotal * ((float)$coupon['value'] / 100.0));
    } elseif ($coupon['type'] === 'amount') {
      $discount = min($subtotal, (float)$coupon['value']);
    }
  }
  $total = max(0.0, $subtotal - $discount) + $shippingDiscounted;
  $perStoreTotals = [];
  foreach ($perStore as $sid => $data) {
    $dAlloc = $subtotal > 0 ? $discount * ($data['subtotal'] / $subtotal) : 0.0;
    $shipAlloc = $coupon && $coupon['type'] === 'free_shipping' ? 0.0 : $data['shipping'];
    $perStoreTotals[$sid] = [
      'store' => storeById((int)$sid),
      'subtotal' => round($data['subtotal'], 2),
      'shipping' => round($shipAlloc, 2),
      'discount' => round($dAlloc, 2),
      'total' => round(max(0.0, $data['subtotal'] - $dAlloc) + $shipAlloc, 2),
      'items' => $data['items'],
    ];
  }
  return [
    'items' => $items,
    'subtotal' => round($subtotal, 2),
    'shipping' => round($shippingDiscounted, 2),
    'discount' => round($discount, 2),
    'total' => round($total, 2),
    'coupon' => $coupon,
    'per_store' => $perStoreTotals,
  ];
}

function createOrder(array $customer): array {
  $t = totals();
  if (empty($t['items'])) return ['ok'=>false];
  $pdo = db();
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, email, phone, address, city, notes, coupon_id, subtotal, discount, shipping, total) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
      trim((string)($customer['name'] ?? '')), trim((string)($customer['email'] ?? '')), trim((string)($customer['phone'] ?? '')),
      trim((string)($customer['address'] ?? '')), trim((string)($customer['city'] ?? '')), trim((string)($customer['notes'] ?? '')),
      $t['coupon']['id'] ?? null, $t['subtotal'], $t['discount'], $t['shipping'], $t['total']
    ]);
    $orderId = (int)$pdo->lastInsertId();
    $insItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, store_id, qty, unit_price, shipping_method_id, shipping_cost_per_unit, line_subtotal, line_shipping, line_total, delivery_address, delivery_city) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($t['items'] as $it) {
      $p = $it['product'];
      $qty = (int)$it['qty'];
      $unit = (float)$p['price'];
      $shipId = $it['selected_shipping_id'] ? (int)$it['selected_shipping_id'] : null;
      $shipCostUnit = 0.0;
      foreach ($it['shipping_methods'] as $m) if ((int)$m['id'] === (int)$shipId) { $shipCostUnit = (float)$m['cost']; break; }
      $lineSub = round($unit * $qty, 2);
      $lineShip = round($shipCostUnit * $qty, 2);
      $addr = $it['delivery']['address'] ?? ($customer['address'] ?? null);
      $city = $it['delivery']['city'] ?? ($customer['city'] ?? null);
      $insItem->execute([$orderId, (int)$p['id'], (int)$p['store_id'], $qty, $unit, $shipId, $shipCostUnit, $lineSub, $lineShip, round($lineSub + $lineShip, 2), $addr, $city]);
    }
    $insStore = $pdo->prepare("INSERT INTO order_store_totals (order_id, store_id, subtotal, discount, shipping, total) VALUES (?,?,?,?,?,?)");
    foreach ($t['per_store'] as $sid => $ps) {
      $insStore->execute([$orderId, (int)$sid, $ps['subtotal'], $ps['discount'], $ps['shipping'], $ps['total']]);
    }
    $insPayout = $pdo->prepare("INSERT INTO store_payouts (order_id, store_id, amount, status, scheduled_at, commission_percent, commission_min, commission_amount, commission_vat_percent, commission_vat_amount, commission_gross_amount, net_amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($t['per_store'] as $sid => $ps) {
      $s = storeById((int)$sid);
      $delay = (int)($s['payout_delay_days'] ?? 0);
      $scheduled = date('Y-m-d H:i:s', time() + ($delay > 0 ? $delay * 86400 : 0));
      $rate = (float)($s['commission_rate_percent'] ?? 0.0);
      $min = (float)($s['commission_min_amount'] ?? 0.0);
      $tax = (float)($s['tax_rate_percent'] ?? 0.0);
      $productsNet = max(0.0, (float)$ps['subtotal'] - (float)$ps['discount']);
      $base = $productsNet * (1.0 - ($tax > 0 ? ($tax/100.0) : 0.0));
      $commission = max(round($base * $rate / 100, 2), $min);
      $cVatRate = defined('COMMISSION_VAT_PERCENT') ? (float)COMMISSION_VAT_PERCENT : 0.0;
      $cVatAmount = round($commission * $cVatRate / 100, 2);
      $cGross = round($commission + $cVatAmount, 2);
      $net = round($ps['total'] - $cGross, 2);
      $insPayout->execute([$orderId, (int)$sid, $ps['total'], 'scheduled', $scheduled, $rate, $min, $commission, $cVatRate, $cVatAmount, $cGross, $net]);
    }
    foreach ($t['per_store'] as $sid => $ps) {
      $store = storeById((int)$sid);
      $subject = 'Nueva orden #' . $orderId . ' para ' . ($store['name'] ?? ('Tienda ' . (int)$sid));
      $content = 'Orden #' . $orderId . "\n" .
                 'Tienda: ' . ($store['name'] ?? '') . "\n" .
                 'Total tienda: $' . number_format((float)$ps['total'], 2) . "\n" .
                 'Subtotal: $' . number_format((float)$ps['subtotal'], 2) . "\n" .
                 'Descuento: $' . number_format((float)$ps['discount'], 2) . "\n" .
                 'Despacho: $' . number_format((float)$ps['shipping'], 2) . "\n";
      $logStmt = $pdo->prepare("INSERT INTO order_notifications (order_id, store_id, channel, content) VALUES (?,?,?,?)");
      $channel = 'log';
      if (defined('NOTIFY_EMAIL_ENABLED') && NOTIFY_EMAIL_ENABLED && !empty($store['contact_email'])) {
        $channel = 'email';
        @mail($store['contact_email'], $subject, $content, 'From: ' . MAIL_FROM);
      }
      $logStmt->execute([$orderId, (int)$sid, $channel, $content]);
    }
    $pdo->commit();
    $_SESSION['last_order_id'] = $orderId;
    cartClear();
    return ['ok'=>true,'order_id'=>$orderId,'summary'=>$t];
  } catch (Throwable $e) {
    $pdo->rollBack();
    return ['ok'=>false];
  }
}

function payoutsList(): array {
  $stmt = db()->query("SELECT sp.*, s.name AS store_name, o.created_at AS order_date FROM store_payouts sp JOIN stores s ON s.id = sp.store_id JOIN orders o ON o.id = sp.order_id ORDER BY sp.id DESC");
  return $stmt->fetchAll();
}

function payoutById(int $id): ?array {
  $stmt = db()->prepare("SELECT sp.*, s.name AS store_name FROM store_payouts sp JOIN stores s ON s.id = sp.store_id WHERE sp.id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function markPayoutPaid(int $id, ?string $method, ?string $reference): bool {
  $stmt = db()->prepare("UPDATE store_payouts SET status = 'paid', paid_at = NOW(), method = ?, reference = ?, updated_at = NOW() WHERE id = ?");
  return $stmt->execute([$method, $reference, $id]);
}

function ordersList(): array {
  $stmt = db()->query("SELECT id, created_at, customer_name, total FROM orders ORDER BY id DESC");
  return $stmt->fetchAll();
}

function orderById(int $orderId): ?array {
  $stmt = db()->prepare("SELECT o.*, c.code AS coupon_code FROM orders o LEFT JOIN coupons c ON c.id = o.coupon_id WHERE o.id = ?");
  $stmt->execute([$orderId]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function orderItems(int $orderId): array {
  $sql = "SELECT oi.*, p.name AS product_name, sm.name AS shipping_name, s.name AS store_name 
          FROM order_items oi 
          LEFT JOIN products p ON p.id = oi.product_id 
          LEFT JOIN shipping_methods sm ON sm.id = oi.shipping_method_id 
          LEFT JOIN stores s ON s.id = oi.store_id 
          WHERE oi.order_id = ? ORDER BY oi.id";
  $stmt = db()->prepare($sql);
  $stmt->execute([$orderId]);
  return $stmt->fetchAll();
}

function orderStoreTotals(int $orderId): array {
  $stmt = db()->prepare("SELECT ost.*, s.name AS store_name FROM order_store_totals ost LEFT JOIN stores s ON s.id = ost.store_id WHERE ost.order_id = ? ORDER BY ost.id");
  $stmt->execute([$orderId]);
  return $stmt->fetchAll();
}

function orderNotifications(int $orderId): array {
  $stmt = db()->prepare("SELECT onf.*, s.name AS store_name FROM order_notifications onf LEFT JOIN stores s ON s.id = onf.store_id WHERE onf.order_id = ? ORDER BY onf.id");
  $stmt->execute([$orderId]);
  return $stmt->fetchAll();
}

function createPayment(int $orderId, string $method, float $amount, array $extra = []): ?int {
  $allowed = ['transbank','transfer','cash'];
  if (!in_array($method, $allowed, true)) return null;
  $stmt = db()->prepare("INSERT INTO payments (order_id, method, amount, status, transaction_id, transfer_code, pickup_location_id) VALUES (?,?,?,?,?,?,?)");
  $tx = $extra['transaction_id'] ?? null;
  $code = $extra['transfer_code'] ?? null;
  $pickup = $extra['pickup_location_id'] ?? null;
  $stmt->execute([$orderId, $method, $amount, 'pending', $tx, $code, $pickup]);
  $pid = (int)db()->lastInsertId();
  $upd = db()->prepare("UPDATE orders SET payment_method = ?, payment_reference = ? WHERE id = ?");
  $ref = $code ?: $tx ?: ('PAY-' . $pid);
  $upd->execute([$method, $ref, $orderId]);
  return $pid;
}

function markPaymentPaid(int $paymentId, ?string $transactionId = null): bool {
  $upd = db()->prepare("UPDATE payments SET status = 'paid', paid_at = NOW(), transaction_id = COALESCE(?, transaction_id) WHERE id = ?");
  $ok = $upd->execute([$transactionId, $paymentId]);
  if ($ok) {
    $row = db()->prepare("SELECT order_id FROM payments WHERE id = ?");
    $row->execute([$paymentId]);
    $o = $row->fetch();
    if ($o) {
      $updo = db()->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
      $updo->execute([(int)$o['order_id']]);
    }
  }
  return $ok;
}

function markPaymentFailed(int $paymentId, ?string $transactionId = null): bool {
  $upd = db()->prepare("UPDATE payments SET status = 'failed', transaction_id = COALESCE(?, transaction_id) WHERE id = ?");
  $ok = $upd->execute([$transactionId, $paymentId]);
  if ($ok) {
    $row = db()->prepare("SELECT order_id FROM payments WHERE id = ?");
    $row->execute([$paymentId]);
    $o = $row->fetch();
    if ($o) {
      $updo = db()->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
      $updo->execute([(int)$o['order_id']]);
    }
  }
  return $ok;
}

function paymentsList(): array {
  $stmt = db()->query("SELECT p.*, o.customer_name, o.total FROM payments p JOIN orders o ON o.id = p.order_id ORDER BY p.id DESC");
  return $stmt->fetchAll();
}