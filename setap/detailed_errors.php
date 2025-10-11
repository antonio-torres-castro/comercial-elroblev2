#!/usr/bin/env php
<?php
/**
 * Lista detallada de errores específicos en tests
 * 
 * @author MiniMax Agent
 * @date 2025-10-11
 */

echo "📋 LISTA DETALLADA DE ERRORES EN TESTS - SETAP\n";
echo "================================================\n\n";

// Ejecutar cada test individualmente para obtener errores específicos
$testFiles = [
    'tests/Unit/AppConstantsTest.php',
    'tests/Unit/PersonaControllerTest.php',
    'tests/Unit/TaskControllerTest.php', 
    'tests/Unit/ProjectControllerTest.php',
    'tests/Unit/UserTest.php',
    'tests/Integration/AuthTest.php',
    'tests/Integration/ConstantsIntegrationTest.php'
];

$allErrors = [];
$errorCount = 1;

foreach ($testFiles as $testFile) {
    echo "🔍 Analizando: $testFile\n";
    
    $output = shell_exec("cd " . __DIR__ . " && php ./vendor/bin/phpunit $testFile --testdox 2>&1");
    
    // Buscar errores en la salida
    if (strpos($output, '✘') !== false) {
        $lines = explode("\n", $output);
        $inError = false;
        $currentError = '';
        $testName = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Detectar nombre del test con error
            if (strpos($line, '✘') !== false) {
                $testName = $line;
                $inError = true;
                $currentError = '';
            }
            // Capturar detalles del error
            elseif ($inError && (strpos($line, '│') !== false || strpos($line, '┐') !== false || strpos($line, '├') !== false || strpos($line, '┴') !== false)) {
                $currentError .= $line . "\n";
            }
            // Finalizar captura del error
            elseif ($inError && (empty($line) || strpos($line, '✔') !== false || strpos($line, '✘') !== false)) {
                if (!empty($currentError) && !empty($testName)) {
                    $allErrors[] = [
                        'file' => $testFile,
                        'test' => $testName,
                        'error' => trim($currentError),
                        'number' => $errorCount++
                    ];
                }
                if (strpos($line, '✘') !== false) {
                    $testName = $line;
                    $currentError = '';
                } else {
                    $inError = false;
                    $testName = '';
                    $currentError = '';
                }
            }
        }
        
        // Capturar último error si existe
        if ($inError && !empty($currentError) && !empty($testName)) {
            $allErrors[] = [
                'file' => $testFile,
                'test' => $testName,
                'error' => trim($currentError),
                'number' => $errorCount++
            ];
        }
    } else {
        echo "   ✅ Sin errores detectados\n";
    }
}

echo "\n📋 LISTA COMPLETA DE ERRORES:\n";
echo "==============================\n\n";

if (empty($allErrors)) {
    echo "🎉 ¡Excelente! No se encontraron errores en los tests.\n";
} else {
    foreach ($allErrors as $error) {
        echo "🔴 ERROR #{$error['number']}:\n";
        echo "📁 Archivo: {$error['file']}\n";
        echo "🧪 Test: {$error['test']}\n";
        echo "💥 Detalles:\n{$error['error']}\n";
        echo str_repeat("-", 70) . "\n\n";
    }
    
    // Clasificación de errores
    echo "📊 CLASIFICACIÓN DE ERRORES:\n";
    echo "=============================\n\n";
    
    $categories = [
        'constants' => ['pattern' => 'Undefined constant', 'count' => 0, 'errors' => []],
        'methods' => ['pattern' => 'Call to undefined method', 'count' => 0, 'errors' => []],
        'mocks' => ['pattern' => 'MethodCannotBeConfiguredException', 'count' => 0, 'errors' => []],
        'regex' => ['pattern' => 'preg_match', 'count' => 0, 'errors' => []],
        'assertions' => ['pattern' => 'Failed asserting', 'count' => 0, 'errors' => []],
        'other' => ['pattern' => '', 'count' => 0, 'errors' => []]
    ];
    
    foreach ($allErrors as $error) {
        $categorized = false;
        foreach ($categories as $category => &$cat) {
            if ($category === 'other') continue;
            if (stripos($error['error'], $cat['pattern']) !== false) {
                $cat['count']++;
                $cat['errors'][] = $error['number'];
                $categorized = true;
                break;
            }
        }
        if (!$categorized) {
            $categories['other']['count']++;
            $categories['other']['errors'][] = $error['number'];
        }
    }
    
    foreach ($categories as $category => $data) {
        if ($data['count'] > 0) {
            $categoryName = ucfirst($category);
            echo "📌 $categoryName: {$data['count']} error(es) - #{" . implode(', #', $data['errors']) . "}\n";
        }
    }
    
    echo "\n🎯 PRIORIDADES DE CORRECCIÓN:\n";
    echo "=============================\n";
    echo "1. 🔴 ALTA PRIORIDAD - Constants & Methods (impiden ejecución)\n";
    echo "2. 🟡 MEDIA PRIORIDAD - Mocks & Assertions (lógica incorrecta)\n";
    echo "3. 🟢 BAJA PRIORIDAD - Regex & Other (refinamientos)\n";
    
    echo "\n📈 ESTADÍSTICAS:\n";
    echo "================\n";
    echo "Total errores encontrados: " . count($allErrors) . "\n";
    echo "Archivos afectados: " . count(array_unique(array_column($allErrors, 'file'))) . "\n";
    echo "Porcentaje de tests con errores: " . round((count($allErrors) / 70) * 100, 1) . "%\n";
}

echo "\n🔧 PRÓXIMOS PASOS RECOMENDADOS:\n";
echo "===============================\n";
echo "1. Revisar cada error listado arriba\n";
echo "2. Decidir cuáles corregir basándose en prioridades\n";
echo "3. Confirmar que se desea proceder con las correcciones\n";
echo "4. Ejecutar correcciones en orden de prioridad\n";

echo "\n✨ Análisis completado!\n";
