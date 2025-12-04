<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/auth_functions.php';

init_secure_session();
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/config.php';

// Cargar Transbank SDK si está disponible
$composer_autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

// Configuración Transbank CORREGIDA con clases reales
require_once __DIR__ . '/../src/transbank_real.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$paymentId = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
$transactionToken = isset($_POST['TBK_TOKEN']) ? (string)$_POST['TBK_TOKEN'] : null;

// Obtener orden
$order = $orderId ? orderById($orderId) : null;
if (!$order) {
    http_response_code(404);
    echo '<h1>Orden no encontrada</h1><p>La orden solicitada no existe.</p>';
    exit;
}

// Obtener items de la orden
$orderItems = orderItems($orderId);
$orderStoreTotals = orderStoreTotals($orderId);

// Si es un retorno de Transbank
if ($transactionToken) {
    handleTransbankReturn($transactionToken, $orderId);
    exit;
}

// Verificar si Transbank está disponible
if (!transbank_available()) {
    showMockPaymentPage($order, $orderItems, $orderStoreTotals);
    exit;
}

// Configurar Transbank SDK
try {
    if (!transbank_available()) {
        // Modo mock - mostrar página de pago simulado
        showMockPaymentPage($order, $orderItems, $orderStoreTotals);
        exit;
    }
    
    $configuration = transbank_config();
    if (!$configuration) {
        throw new Exception('No se pudo configurar Transbank');
    }
    
    // Crear transacción
    $transaction = createTransbankTransaction($order, $orderItems);
    
    // Si no hay transaction token, crear uno nuevo
    if (!$transaction['token']) {
        $newTransaction = initTransbankTransaction($order, $transaction['payment_id']);
        if ($newTransaction['success']) {
            $transaction['token'] = $newTransaction['token'];
            $transaction['url'] = $newTransaction['url'];
        }
    }
    
    // Redirigir a Transbank
    if ($transaction['token'] && $transaction['url']) {
        header('Location: ' . $transaction['url'] . $transaction['token']);
        exit;
    }
    
} catch (Exception $e) {
    logPaymentError($e, $orderId);
    showPaymentError('Error al inicializar el pago. Inténtalo de nuevo.', $orderId);
}

/**
 * Manejar el retorno de Transbank
 */
function handleTransbankReturn(string $token, int $orderId): void {
    // Verificar si el SDK está disponible
    if (!transbank_available()) {
        // Modo mock - simular respuesta exitosa
        $mockResult = [
            'status' => 'AUTHORIZED',
            'transaction' => [
                'id' => 'TXN-MOCK-' . time(),
                'amount' => 1000
            ]
        ];
        processTransbankResult($mockResult, $orderId);
        return;
    }
    
    try {
        $configuration = transbank_config();
        if (!$configuration) {
            throw new Exception('No se pudo configurar Transbank');
        }
        
        // Usar función helper para resolver problema de tipo undefined
        $webpay = transbank_transaction($configuration);
        
        // Obtener resultado de la transacción
        $result = $webpay->getTransactionResult($token);
        
        if ($result) {
            // Verificar si la transacción fue exitosa
            if ($result['status'] === 'AUTHORIZED') {
                $transactionId = $result['transaction']['id'];
                
                // Marcar pago como exitoso
                $paymentId = getPaymentIdByOrder($orderId);
                if (markPaymentPaid($paymentId, $transactionId)) {
                    // Enviar confirmación por email
                    sendOrderConfirmationEmail($orderId);
                    
                    // Mostrar página de éxito
                    showSuccessPage($orderId, $transactionId);
                } else {
                    throw new Exception('Error al marcar el pago como exitoso');
                }
            } else {
                throw new Exception('Transacción rechazada: ' . $result['status']);
            }
        } else {
            throw new Exception('No se pudo obtener el resultado de la transacción');
        }
        
    } catch (Exception $e) {
        logPaymentError($e, $orderId);
        showPaymentError('Error al procesar el pago. Contacta al soporte.', $orderId);
    }
}

/**
 * Crear transacción en Transbank
 */
function createTransbankTransaction(array $order, array $orderItems): array {
    // Buscar pago existente para esta orden
    $existingPayment = getExistingPayment($order['id']);
    
    if (!$existingPayment) {
        // Crear nuevo pago
        $paymentId = createPayment(
            $order['id'],
            'transbank',
            (float)$order['total']
        );
        
        if (!$paymentId) {
            throw new Exception('No se pudo crear el pago');
        }
    } else {
        $paymentId = (int)$existingPayment['id'];
    }
    
    return [
        'payment_id' => $paymentId,
        'token' => null, // Se obtendrá al inicializar
        'url' => null
    ];
}

/**
 * Inicializar transacción en Transbank
 */
function initTransbankTransaction(array $order, int $paymentId): array {
    // Verificar si el SDK está disponible
    if (!transbank_available()) {
        // Modo mock - generar token simulado
        return [
            'success' => true,
            'token' => 'TKN-MOCK-' . bin2hex(random_bytes(16)),
            'url' => 'https://webpay3g.transbank.cl/webpayserver/bankpay?TBK_TOKEN='
        ];
    }
    
    try {
        $configuration = transbank_config();
        if (!$configuration) {
            throw new Exception('No se pudo configurar Transbank');
        }
        
        // Usar función helper para resolver problema de tipo undefined
        $webpay = transbank_transaction($configuration);
        
        // Preparar datos de la transacción
        $transactionData = [
            'buy_order' => 'ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
            'session_id' => session_id(),
            'amount' => (int)($order['total'] * 100), // Centavos
            'return_url' => getBaseUrl() . '/pay_transbank.php?order_id=' . $order['id'],
            'final_url' => getBaseUrl() . '/pay_transbank.php?order_id=' . $order['id']
        ];
        
        // Crear transacción
        $initTransaction = $webpay->getInitTransaction();
        $result = $initTransaction->initTransaction($transactionData);
        
        if ($result['token']) {
            return [
                'success' => true,
                'token' => $result['token'],
                'url' => $result['url'] . '?TBK_TOKEN='
            ];
        } else {
            throw new Exception('No se recibió token de Transbank');
        }
        
    } catch (Exception $e) {
        logPaymentError($e, $order['id']);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Obtener URL base del sistema
 */
function getBaseUrl(): string {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . '://' . $host . $path;
}

/**
 * Buscar pago existente para una orden
 */
function getExistingPayment(int $orderId): ?array {
    $stmt = db()->prepare("SELECT * FROM payments WHERE order_id = ? AND method = 'transbank'");
    $stmt->execute([$orderId]);
    return $stmt->fetch() ?: null;
}

/**
 * Obtener ID del pago por orden
 */
function getPaymentIdByOrder(int $orderId): int {
    $stmt = db()->prepare("SELECT id FROM payments WHERE order_id = ? AND method = 'transbank'");
    $stmt->execute([$orderId]);
    $result = $stmt->fetch();
    return (int)$result['id'];
}

/**
 * Enviar email de confirmación
 */
function sendOrderConfirmationEmail(int $orderId): void {
    // Implementar envío de email
    // Usar PHPMailer o similar
    
    $order = orderById($orderId);
    $subject = 'Confirmación de tu compra - Mall Virtual';
    
    // Contenido del email
    $message = "
    <h2>¡Gracias por tu compra!</h2>
    <p>Hola {$order['customer_name']},</p>
    <p>Tu pedido #{$orderId} ha sido confirmado exitosamente.</p>
    <p>Total: $" . number_format((float)$order['total'], 2) . "</p>
    <p>Te enviaremos un email cuando tu pedido esté listo para envío.</p>
    <br>
    <p>Mall Virtual - Viña del Mar</p>
    ";
    
    // Aquí se enviaría el email real
    error_log("Email de confirmación enviado para orden: $orderId");
}

/**
 * Mostrar página de éxito
 */
function showSuccessPage(int $orderId, string $transactionId): void {
    $order = orderById($orderId);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pago Exitoso - Mall Virtual</title>
        <link rel="stylesheet" href="assets/css/modern.css">
    </head>
    <body style="background: var(--coast-sand); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: var(--font-body);">
        <div class="card" style="max-width: 500px; padding: var(--space-xxxl); text-align: center; background: var(--neutral-0);">
            <div style="width: 80px; height: 80px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <path d="M9 12l2 2 4-4"></path>
                    <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9c2.31 0 4.41.88 6 2.32"></path>
                </svg>
            </div>
            
            <h1 style="color: var(--primary-500); margin-bottom: var(--space-md);">¡Pago Exitoso!</h1>
            
            <p style="color: var(--neutral-700); margin-bottom: var(--space-lg);">
                Tu pedido #<?= str_pad((string)$orderId, 6, '0', STR_PAD_LEFT) ?> ha sido confirmado
            </p>
            
            <div style="background: var(--neutral-100); padding: var(--space-lg); border-radius: var(--radius-sm); margin-bottom: var(--space-lg);">
                <p style="margin: 0 0 var(--space-xs);"><strong>Total Pagado:</strong> $<?= number_format((float)$order['total'], 2) ?></p>
                <p style="margin: 0 0 var(--space-xs);"><strong>Transacción:</strong> <?= htmlspecialchars($transactionId) ?></p>
                <p style="margin: 0;"><strong>Fecha:</strong> <?= date('d/m/Y H:i') ?></p>
            </div>
            
            <div style="margin-bottom: var(--space-lg);">
                <h3 style="color: var(--primary-500); margin-bottom: var(--space-sm);">¿Qué sigue?</h3>
                <ul style="text-align: left; color: var(--neutral-700);">
                    <li>Recibirás un email de confirmación</li>
                    <li>Los vendedores prepararán tus productos</li>
                    <li>Te contactaremos para coordinar la entrega</li>
                </ul>
            </div>
            
            <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
                <a href="index.php" class="btn" style="text-decoration: none;">Continuar Comprando</a>
                <a href="admin/orders.php" class="btn-outline" style="text-decoration: none;">Ver Mis Órdenes</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Mostrar página de configuración/mocks
 */
function showMockPaymentPage(array $order, array $orderItems, array $orderStoreTotals): void {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Configuración de Pagos - Mall Virtual</title>
        <link rel="stylesheet" href="assets/css/modern.css">
    </head>
    <body style="background: var(--coast-sand); min-height: 100vh; font-family: var(--font-body);">
        <div class="container" style="max-width: 800px; padding: var(--space-xxxl) var(--space-md);">
            <div class="card" style="background: var(--neutral-0); padding: var(--space-xxxl);">
                <div style="text-align: center; margin-bottom: var(--space-xl);">
                    <div style="width: 60px; height: 60px; background: var(--warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h1 style="color: var(--primary-500); margin-bottom: var(--space-sm);">Configuración de Pagos</h1>
                    <p style="color: var(--neutral-700);">El sistema está en modo desarrollo</p>
                </div>
                
                <div style="background: var(--neutral-100); padding: var(--space-lg); border-radius: var(--radius-sm); margin-bottom: var(--space-xl);">
                    <h3 style="margin: 0 0 var(--space-md); color: var(--primary-500);">Orden #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg);">
                        <div>
                            <p style="margin: 0 0 var(--space-xs);"><strong>Cliente:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                            <p style="margin: 0 0 var(--space-xs);"><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                            <p style="margin: 0 0 var(--space-xs);"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div>
                            <p style="margin: 0 0 var(--space-xs);"><strong>Total:</strong> $<?= number_format((float)$order['total'], 2) ?></p>
                            <p style="margin: 0 0 var(--space-xs);"><strong>Estado:</strong> <?= htmlspecialchars($order['payment_status']) ?></p>
                            <p style="margin: 0;"><strong>Método:</strong> Transbank</p>
                        </div>
                    </div>
                </div>
                
                <div style="background: var(--error); color: white; padding: var(--space-lg); border-radius: var(--radius-sm); margin-bottom: var(--space-xl);">
                    <h4 style="margin: 0 0 var(--space-sm);">⚠️ Configuración Pendiente</h4>
                    <p style="margin: 0;">Para activar pagos reales, configura las credenciales de Transbank en <code>config.php</code></p>
                </div>
                
                <?php if (defined('TRANSBANK_MOCK') && TRANSBANK_MOCK): ?>
                <form method="post" style="background: var(--neutral-100); padding: var(--space-lg); border-radius: var(--radius-sm); margin-bottom: var(--space-xl);">
                    <h4 style="margin: 0 0 var(--space-lg); color: var(--primary-500);">Simular Pago</h4>
                    <div style="display: flex; gap: var(--space-md); justify-content: center;">
                        <button type="submit" name="simulate" value="success" class="btn">
                            ✅ Simular Éxito
                        </button>
                        <button type="submit" name="simulate" value="fail" class="btn-outline" style="border-color: var(--error); color: var(--error);">
                            ❌ Simular Fallo
                        </button>
                    </div>
                </form>
                
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate'])): ?>
                    <?php 
                    $paymentId = getExistingPayment($order['id'])['id'] ?? null;
                    if ($paymentId) {
                        if ($_POST['simulate'] === 'success') {
                            $txId = 'MOCK-TBK-' . time();
                            if (markPaymentPaid((int)$paymentId, $txId)) {
                                echo '<div style="background: var(--success); color: white; padding: var(--space-lg); border-radius: var(--radius-sm); text-align: center;">';
                                echo '<h4 style="margin: 0 0 var(--space-sm);">¡Simulación Exitosa!</h4>';
                                echo '<p style="margin: 0;">Transacción: ' . $txId . '</p>';
                                echo '<a href="admin/order.php?id=' . $order['id'] . '" style="color: white; text-decoration: underline;">Ver Orden</a>';
                                echo '</div>';
                                sendOrderConfirmationEmail($order['id']);
                            }
                        } else {
                            if (markPaymentFailed((int)$paymentId, 'MOCK-TBK-FAIL')) {
                                echo '<div style="background: var(--error); color: white; padding: var(--space-lg); border-radius: var(--radius-sm); text-align: center;">';
                                echo '<h4 style="margin: 0 0 var(--space-sm);">Simulación de Fallo</h4>';
                                echo '<p style="margin: 0;">La transacción fue simulada como fallida.</p>';
                                echo '<a href="admin/order.php?id=' . $order['id'] . '" style="color: white; text-decoration: underline;">Ver Orden</a>';
                                echo '</div>';
                            }
                        }
                    }
                    ?>
                <?php endif; ?>
                <?php endif; ?>
                
                <div style="text-align: center;">
                    <a href="admin/orders.php" style="color: var(--primary-500); text-decoration: none;">← Volver a Órdenes</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Mostrar página de error
 */
function showPaymentError(string $message, int $orderId): void {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Error en el Pago - Mall Virtual</title>
        <link rel="stylesheet" href="assets/css/modern.css">
    </head>
    <body style="background: var(--coast-sand); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: var(--font-body);">
        <div class="card" style="max-width: 500px; padding: var(--space-xxxl); text-align: center; background: var(--neutral-0);">
            <div style="width: 80px; height: 80px; background: var(--error); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <path d="M18 6L6 18M6 6l12 12"></path>
                </svg>
            </div>
            
            <h1 style="color: var(--error); margin-bottom: var(--space-md);">Error en el Pago</h1>
            
            <p style="color: var(--neutral-700); margin-bottom: var(--space-lg);">
                <?= htmlspecialchars($message) ?>
            </p>
            
            <div style="background: var(--neutral-100); padding: var(--space-lg); border-radius: var(--radius-sm); margin-bottom: var(--space-lg);">
                <p style="margin: 0; color: var(--neutral-600); font-size: 14px;">
                    Orden #<?= str_pad((string)$orderId, 6, '0', STR_PAD_LEFT) ?>
                </p>
            </div>
            
            <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
                <a href="checkout.php" class="btn">Intentar de Nuevo</a>
                <a href="index.php" class="btn-outline">Inicio</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Procesar resultado de Transbank (función común)
 */
function processTransbankResult(array $result, int $orderId): void {
    try {
        if ($result['status'] === 'AUTHORIZED') {
            $transactionId = $result['transaction']['id'];
            
            // Marcar pago como exitoso
            $paymentId = getPaymentIdByOrder($orderId);
            if (markPaymentPaid($paymentId, $transactionId)) {
                // Enviar confirmación por email
                sendOrderConfirmationEmail($orderId);
                
                // Mostrar página de éxito
                showSuccessPage($orderId, $transactionId);
            } else {
                throw new Exception('No se pudo marcar el pago como pagado');
            }
        } else {
            throw new Exception('Transacción no autorizada: ' . ($result['status'] ?? 'desconocido'));
        }
    } catch (Exception $e) {
        logPaymentError($e, $orderId);
        showPaymentError('Error al procesar el pago. Contacta al soporte.', $orderId);
    }
}



/**
 * Log de errores de pago
 */
function logPaymentError(Exception $e, int $orderId): void {
    $errorLog = [
        'timestamp' => date('c'),
        'order_id' => $orderId,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    error_log('Transbank Error: ' . json_encode($errorLog));
}
?>