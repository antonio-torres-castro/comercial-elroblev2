<?php
declare(strict_types=1);
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/auth_functions.php';

init_secure_session();

// Headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'get_cart':
            echo json_encode(getCartData());
            break;
            
        case 'update_quantity':
            $productId = (int)($input['product_id'] ?? 0);
            $change = (int)($input['change'] ?? 0);
            
            if ($productId <= 0) {
                throw new Exception('ID de producto inválido');
            }
            
            $cart = cartGet();
            $currentQty = $cart[$productId] ?? 0;
            $newQty = max(0, $currentQty + $change);
            
            if ($newQty === 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId] = $newQty;
            }
            
            $_SESSION['cart'] = $cart;
            
            echo json_encode([
                'success' => true,
                'message' => 'Cantidad actualizada',
                'data' => getCartData()
            ]);
            break;
            
        case 'remove_item':
            $productId = (int)($input['product_id'] ?? 0);
            
            if ($productId <= 0) {
                throw new Exception('ID de producto inválido');
            }
            
            $cart = cartGet();
            unset($cart[$productId]);
            $_SESSION['cart'] = $cart;
            
            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado',
                'data' => getCartData()
            ]);
            break;
            
        case 'add_to_cart':
            $productId = (int)($input['product_id'] ?? 0);
            $qty = max(1, (int)($input['quantity'] ?? 1));
            
            if ($productId <= 0) {
                throw new Exception('ID de producto inválido');
            }
            
            $product = productById($productId);
            if (!$product) {
                throw new Exception('Producto no encontrado');
            }
            
            cartAdd($productId, $qty);
            
            echo json_encode([
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'data' => getCartData()
            ]);
            break;
            
        case 'apply_coupon':
            $code = trim((string)($input['code'] ?? ''));
            
            if (empty($code)) {
                couponClear();
                echo json_encode([
                    'success' => true,
                    'message' => 'Cupón removido',
                    'data' => getCartData()
                ]);
                break;
            }
            
            if (couponApply($code)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cupón aplicado correctamente',
                    'data' => getCartData()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Código de cupón inválido o expirado'
                ]);
            }
            break;
            
        case 'clear_cart':
            cartClear();
            echo json_encode([
                'success' => true,
                'message' => 'Carrito vaciado',
                'data' => getCartData()
            ]);
            break;
            
        default:
            throw new Exception('Acción no reconocida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getCartData(): array {
    $t = totals();
    $items = [];
    
    // Productos con imágenes
    $productImages = [
        1 => 'assets/images/cafe-arab.webp',
        2 => 'assets/images/te-hierbas.jpg',
        3 => 'assets/images/instalacion-purificador.jpg',
        4 => 'assets/images/cafe-colombia.jpg',
        5 => 'assets/images/filtro-agua.jpg'
    ];
    
    foreach ($t['items'] as $item) {
        $product = $item['product'];
        $store = storeById((int)$product['store_id']);
        
        $items[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'description' => $product['description'] ?? '',
            'price' => (float)$product['price'],
            'qty' => (int)$item['qty'],
            'image' => $productImages[$product['id']] ?? null,
            'store_id' => (int)$product['store_id'],
            'store_name' => $store['name'] ?? 'Tienda desconocida',
            'store_color' => $store['primary_color'] ?? '#0055D4'
        ];
    }
    
    $itemsCount = 0;
    foreach ($items as $item) {
        $itemsCount += $item['qty'];
    }
    
    return [
        'items' => $items,
        'items_count' => $itemsCount,
        'subtotal' => (float)$t['subtotal'],
        'shipping' => (float)$t['shipping'],
        'discount' => (float)$t['discount'],
        'total' => (float)$t['total'],
        'coupon' => $t['coupon'],
        'per_store' => array_map(function($storeData) {
            return [
                'store' => $storeData['store'],
                'subtotal' => (float)$storeData['subtotal'],
                'shipping' => (float)$storeData['shipping'],
                'discount' => (float)$storeData['discount'],
                'total' => (float)$storeData['total'],
                'items_count' => count($storeData['items'])
            ];
        }, $t['per_store'])
    ];
}
?>