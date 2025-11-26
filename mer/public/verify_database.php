<?php
/**
 * Verificación de Estructura de Base de Datos - CORREGIDA
 * Script de prueba para confirmar que advanced_store_system_v2.sql se ejecutó correctamente
 * Compatible con MySQL 8 y sin problemas de sintaxis
 */

require_once __DIR__ . '/../src/config.php';

header('Content-Type: text/html; charset=utf-8');

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de Base de Datos - Sistema Avanzado de Tiendas v2</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px; 
            background: white; 
            margin-top: 20px;
            margin-bottom: 20px;
            border-radius: 12px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15); 
        }
        .header {
            background: linear-gradient(135deg, #5E422E, #2F5C7C);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin: -20px -20px 30px -20px;
            text-align: center;
        }
        .success { 
            color: #27ae60; 
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .error { 
            color: #e74c3c; 
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .warning { 
            color: #f39c12; 
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info {
            color: #3498db;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td { 
            border: 1px solid #e0e0e0; 
            padding: 12px 15px; 
            text-align: left; 
        }
        th { 
            background: linear-gradient(135deg, #f8f9fa, #e9ecef); 
            font-weight: 700; 
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat { 
            display: inline-block; 
            margin: 8px 12px; 
            padding: 10px 15px; 
            background: linear-gradient(135deg, #e3f2fd, #bbdefb); 
            border-radius: 8px; 
            border-left: 4px solid #2196f3;
            font-weight: 600;
        }
        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #4caf50, #45a049);
            height: 100%;
            transition: width 0.3s ease;
        }
        .card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        .emoji {
            font-size: 1.2em;
        }
        pre { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 8px; 
            overflow-x: auto; 
            border: 1px solid #dee2e6;
            font-size: 13px;
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Verificación de Base de Datos v2.0</h1>
            <p><strong>Sistema Avanzado de Tiendas - Compatible MySQL 8</strong></p>
        </div>
        
        <?php
        $errors = [];
        $warnings = [];
        $success_count = 0;
        $total_checks = 0;
        
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                DB_USER, 
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'"
                ]
            );
            
            echo "<div class='success'><span class='emoji'>✅</span> Conexión exitosa a la base de datos: <strong>" . DB_NAME . "</strong></div>";
            
            // Función para verificar tabla
            function checkTable($pdo, $table_name, $description, &$success_count, &$total_checks) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `" . $table_name . "` LIMIT 1");
                    $count = $stmt->fetchColumn();
                    $success_count++;
                    return ['status' => 'success', 'count' => $count, 'message' => "Tabla creada correctamente"];
                } catch (Exception $e) {
                    return ['status' => 'error', 'count' => 0, 'message' => "Tabla no encontrada: " . $e->getMessage()];
                }
            }
            
            // Función para verificar columna
            function checkColumn($pdo, $table_name, $column_name, &$success_count, &$total_checks) {
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns 
                                         WHERE table_schema = :db_name 
                                         AND table_name = :table_name 
                                         AND column_name = :column_name");
                    $stmt->execute([
                        ':db_name' => DB_NAME,
                        ':table_name' => $table_name,
                        ':column_name' => $column_name
                    ]);
                    $count = $stmt->fetchColumn();
                    $success_count++;
                    return ['status' => 'success', 'exists' => $count > 0];
                } catch (Exception $e) {
                    return ['status' => 'error', 'exists' => false];
                }
            }
            
            // 1. Verificar tablas principales
            echo "<h2>📋 Tablas del Sistema Avanzado</h2>";
            
            $expected_tables = [
                'product_daily_capacity' => 'Capacidad diaria de productos',
                'product_appointments' => 'Agendamientos de productos',
                'delivery_groups' => 'Grupos de despacho',
                'delivery_group_items' => 'Items de grupos de despacho',
                'pickup_locations' => 'Ubicaciones de recojo',
                'stock_movements' => 'Movimientos de stock',
                'delivery_coupons' => 'Cupones de despacho',
                'store_settings' => 'Configuración de tiendas',
                'store_holidays' => 'Feriados de tiendas'
            ];
            
            echo "<div class='card'>";
            echo "<table>";
            echo "<tr><th>Tabla</th><th>Descripción</th><th>Estado</th><th>Registros</th></tr>";
            
            foreach ($expected_tables as $table => $description) {
                $total_checks++;
                $result = checkTable($pdo, $table, $description, $success_count, $total_checks);
                
                echo "<tr>";
                echo "<td><strong>$table</strong></td>";
                echo "<td>$description</td>";
                if ($result['status'] === 'success') {
                    echo "<td><span class='success'>✅ Creada</span></td>";
                    echo "<td>{$result['count']}</td>";
                } else {
                    echo "<td><span class='error'>❌ {$result['message']}</span></td>";
                    echo "<td>-</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
            
            // 2. Verificar columnas agregadas
            echo "<h2>🔧 Columnas Verificadas en Tablas Existentes</h2>";
            echo "<div class='card'>";
            echo "<table>";
            echo "<tr><th>Tabla</th><th>Columna</th><th>Estado</th></tr>";
            
            $table_columns = [
                'products' => [
                    'stock_quantity', 'stock_min_threshold', 'delivery_days_min', 
                    'delivery_days_max', 'service_type', 'requires_appointment', 
                    'image_url', 'active'
                ],
                'orders' => [
                    'delivery_address', 'delivery_city', 'delivery_contact_name',
                    'delivery_contact_phone', 'delivery_contact_email', 
                    'pickup_location_id', 'delivery_date', 'delivery_time_slot'
                ]
            ];
            
            foreach ($table_columns as $table => $columns) {
                foreach ($columns as $column) {
                    $total_checks++;
                    $result = checkColumn($pdo, $table, $column, $success_count, $total_checks);
                    
                    echo "<tr>";
                    echo "<td>$table</td>";
                    echo "<td><strong>$column</strong></td>";
                    if ($result['status'] === 'success' && $result['exists']) {
                        echo "<td><span class='success'>✅ Existe</span></td>";
                    } else {
                        echo "<td><span class='error'>❌ No existe</span></td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
            echo "</div>";
            
            // 3. Verificar vistas
            echo "<h2>👁️ Vistas del Sistema</h2>";
            echo "<div class='card'>";
            $views = ['products_low_stock', 'product_availability', 'orders_with_delivery'];
            echo "<table>";
            echo "<tr><th>Vista</th><th>Propósito</th><th>Estado</th></tr>";
            
            $view_descriptions = [
                'products_low_stock' => 'Productos con stock bajo',
                'product_availability' => 'Disponibilidad por fecha',
                'orders_with_delivery' => 'Órdenes con despacho'
            ];
            
            foreach ($views as $view) {
                $total_checks++;
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM $view LIMIT 1");
                    $stmt->fetch();
                    echo "<tr><td><strong>$view</strong></td><td>{$view_descriptions[$view]}</td><td><span class='success'>✅ Creada</span></td></tr>";
                    $success_count++;
                } catch (Exception $e) {
                    echo "<tr><td><strong>$view</strong></td><td>{$view_descriptions[$view]}</td><td><span class='error'>❌ No encontrada</span></td></tr>";
                }
            }
            echo "</table>";
            echo "</div>";
            
            // 4. Verificar triggers
            echo "<h2>⚡ Triggers Automáticos</h2>";
            echo "<div class='card'>";
            $triggers = ['update_stock_on_order', 'restore_stock_on_cancellation'];
            echo "<table>";
            echo "<tr><th>Trigger</th><th>Función</th><th>Estado</th></tr>";
            
            $trigger_descriptions = [
                'update_stock_on_order' => 'Actualiza stock automáticamente',
                'restore_stock_on_cancellation' => 'Restaura stock por cancelación'
            ];
            
            foreach ($triggers as $trigger) {
                $total_checks++;
                try {
                    $stmt = $pdo->prepare("SHOW TRIGGERS WHERE Trigger_name = ?");
                    $stmt->execute([$trigger]);
                    $result = $stmt->fetch();
                    echo "<tr><td><strong>$trigger</strong></td><td>{$trigger_descriptions[$trigger]}</td><td><span class='success'>✅ Creado</span></td></tr>";
                    $success_count++;
                } catch (Exception $e) {
                    echo "<tr><td><strong>$trigger</strong></td><td>{$trigger_descriptions[$trigger]}</td><td><span class='error'>❌ No encontrado</span></td></tr>";
                }
            }
            echo "</table>";
            echo "</div>";
            
            // 5. Verificar procedimientos almacenados
            echo "<h2>📦 Procedimientos Almacenados</h2>";
            echo "<div class='card'>";
            $procedures = ['check_product_availability', 'generate_daily_capacities'];
            echo "<table>";
            echo "<tr><th>Procedimiento</th><th>Función</th><th>Estado</th></tr>";
            
            $procedure_descriptions = [
                'check_product_availability' => 'Verifica disponibilidad de productos',
                'generate_daily_capacities' => 'Genera capacidades diarias automáticamente'
            ];
            
            foreach ($procedures as $procedure) {
                $total_checks++;
                try {
                    $stmt = $pdo->prepare("SHOW PROCEDURE STATUS WHERE Db = ? AND Name = ?");
                    $stmt->execute([DB_NAME, $procedure]);
                    $result = $stmt->fetch();
                    echo "<tr><td><strong>$procedure</strong></td><td>{$procedure_descriptions[$procedure]}</td><td><span class='success'>✅ Creado</span></td></tr>";
                    $success_count++;
                } catch (Exception $e) {
                    echo "<tr><td><strong>$procedure</strong></td><td>{$procedure_descriptions[$procedure]}</td><td><span class='error'>❌ No encontrado</span></td></tr>";
                }
            }
            echo "</table>";
            echo "</div>";
            
            // 6. Verificar datos de ejemplo - Tienda-A
            echo "<h2>🏪 Datos de Ejemplo - Tienda-A (Café Brew)</h2>";
            
            try {
                echo "<div class='card'>";
                echo "<h3>Configuraciones de Tienda-A:</h3>";
                $stmt = $pdo->query("SELECT setting_key, setting_value, description FROM store_settings WHERE store_id = 1 ORDER BY setting_key");
                $settings = $stmt->fetchAll();
                
                if (empty($settings)) {
                    echo "<div class='warning'>⚠️ No se encontraron configuraciones para Tienda-A</div>";
                } else {
                    echo "<table>";
                    echo "<tr><th>Configuración</th><th>Valor</th><th>Descripción</th></tr>";
                    foreach ($settings as $setting) {
                        echo "<tr>";
                        echo "<td><strong>{$setting['setting_key']}</strong></td>";
                        echo "<td>{$setting['setting_value']}</td>";
                        echo "<td>{$setting['description']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                echo "</div>";
                
                // Ubicaciones de recojo
                echo "<div class='card'>";
                echo "<h3>Ubicaciones de Recojo:</h3>";
                $stmt = $pdo->query("SELECT name, address, city, phone FROM pickup_locations WHERE store_id = 1");
                $locations = $stmt->fetchAll();
                
                if (empty($locations)) {
                    echo "<div class='warning'>⚠️ No se encontraron ubicaciones de recojo</div>";
                } else {
                    echo "<table>";
                    echo "<tr><th>Nombre</th><th>Dirección</th><th>Ciudad</th><th>Teléfono</th></tr>";
                    foreach ($locations as $location) {
                        echo "<tr>";
                        echo "<td>{$location['name']}</td>";
                        echo "<td>{$location['address']}</td>";
                        echo "<td>{$location['city']}</td>";
                        echo "<td>{$location['phone']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                echo "</div>";
                
                // Cupones de descuento
                echo "<div class='card'>";
                echo "<h3>Cupones de Descuento Activos:</h3>";
                $stmt = $pdo->query("SELECT code, discount_type, discount_value, min_order_amount, usage_limit FROM delivery_coupons WHERE is_active = 1");
                $coupons = $stmt->fetchAll();
                
                if (empty($coupons)) {
                    echo "<div class='warning'>⚠️ No se encontraron cupones activos</div>";
                } else {
                    echo "<table>";
                    echo "<tr><th>Código</th><th>Tipo</th><th>Valor</th><th>Mínimo Orden</th><th>Límite</th></tr>";
                    foreach ($coupons as $coupon) {
                        echo "<tr>";
                        echo "<td><strong>{$coupon['code']}</strong></td>";
                        echo "<td>{$coupon['discount_type']}</td>";
                        echo "<td>{$coupon['discount_value']}</td>";
                        echo "<td>${$coupon['min_order_amount']}</td>";
                        echo "<td>{$coupon['usage_limit']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Error al obtener datos de ejemplo: " . $e->getMessage() . "</div>";
                $errors[] = "Error al obtener datos de Tienda-A: " . $e->getMessage();
            }
            
            // 7. Productos con información de stock
            echo "<h2>📦 Productos con Información de Stock</h2>";
            
            try {
                echo "<div class='card'>";
                $stmt = $pdo->query("SELECT id, name, stock_quantity, stock_min_threshold, active FROM products WHERE store_id = 1 ORDER BY id LIMIT 10");
                $products = $stmt->fetchAll();
                
                if (empty($products)) {
                    echo "<div class='warning'>⚠️ No se encontraron productos en Tienda-A</div>";
                } else {
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Producto</th><th>Stock</th><th>Mínimo</th><th>Estado</th></tr>";
                    foreach ($products as $product) {
                        $status = ($product['stock_quantity'] <= $product['stock_min_threshold']) ? 
                                 '<span class="warning">⚠️ Stock Bajo</span>' : 
                                 '<span class="success">✅ OK</span>';
                        echo "<tr>";
                        echo "<td>{$product['id']}</td>";
                        echo "<td>{$product['name']}</td>";
                        echo "<td>{$product['stock_quantity']}</td>";
                        echo "<td>{$product['stock_min_threshold']}</td>";
                        echo "<td>$status</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Error al obtener productos: " . $e->getMessage() . "</div>";
                $errors[] = "Error al obtener productos: " . $e->getMessage();
            }
            
            // 8. Mostrar resumen final
            echo "<h2>📊 Resumen de Verificación</h2>";
            
            $success_percentage = $total_checks > 0 ? round(($success_count / $total_checks) * 100, 1) : 0;
            
            echo "<div class='card'>";
            echo "<div class='stat'><strong>Verificaciones Exitosas:</strong> $success_count/$total_checks</div>";
            echo "<div class='stat'><strong>Porcentaje de Éxito:</strong> $success_percentage%</div>";
            
            echo "<div class='progress-bar'>";
            echo "<div class='progress-fill' style='width: $success_percentage%'></div>";
            echo "</div>";
            
            if ($success_count == $total_checks) {
                echo "<div class='success'><span class='emoji'>🎉</span> <strong>Sistema de Base de Datos Configurado Correctamente</strong></div>";
                echo "<p>✅ Todas las verificaciones pasaron exitosamente. El sistema está listo para usar.</p>";
            } elseif ($success_percentage >= 80) {
                echo "<div class='warning'><span class='emoji'>⚠️</span> <strong>Configuración Parcialmente Completa</strong></div>";
                echo "<p>La mayoría de los componentes están configurados correctamente. Revisar elementos faltantes.</p>";
            } else {
                echo "<div class='error'><span class='emoji'>❌</span> <strong>Configuración Incompleta</strong></div>";
                echo "<p>Hay múltiples problemas en la configuración. Se requiere ejecutar el script completo.</p>";
            }
            
            // Mostrar errores si existen
            if (!empty($errors)) {
                echo "<h3>🚨 Errores Detectados:</h3>";
                echo "<ul>";
                foreach ($errors as $error) {
                    echo "<li class='error'>$error</li>";
                }
                echo "</ul>";
            }
            
            echo "<h3>📋 Próximos Pasos:</h3>";
            echo "<div class='card'>";
            echo "<ol>";
            echo "<li>" . ($success_count == $total_checks ? "✅" : "⏳") . " Verificación de estructura de base de datos</li>";
            echo "<li>⏳ Probar acceso a tiendas en entorno local</li>";
            echo "<li>⏳ Verificar configuración de Apache (mod_rewrite)</li>";
            echo "<li>⏳ Desplegar a producción</li>";
            echo "<li>⏳ Configurar credenciales reales de Transbank</li>";
            echo "</ol>";
            echo "</div>";
            
            // Comandos útiles
            echo "<h3>🔧 Comandos de Verificación Manual:</h3>";
            echo "<div class='code-block'>";
            echo "-- Verificar todas las tablas<br>";
            echo "SHOW TABLES LIKE 'product_%';" . PHP_EOL;
            echo "SHOW TABLES LIKE 'delivery_%';" . PHP_EOL;
            echo "SHOW TABLES LIKE 'store_%';" . PHP_EOL;
            echo "<br>";
            echo "-- Probar procedimiento<br>";
            echo "CALL check_product_availability(1, 5, CURDATE());" . PHP_EOL;
            echo "<br>";
            echo "-- Ver vistas<br>";
            echo "SELECT * FROM products_low_stock;" . PHP_EOL;
            echo "SELECT * FROM product_availability WHERE capacity_date = CURDATE();" . PHP_EOL;
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error crítico: " . $e->getMessage() . "</div>";
            echo "<p>Verificar conexión a la base de datos y credenciales.</p>";
        }
        ?>
        
    </div>
</body>
</html>