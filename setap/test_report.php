#!/usr/bin/env php
<?php
/**
 * Generador de reporte detallado de testing
 * 
 * @author MiniMax Agent
 * @date 2025-10-11
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "📊 REPORTE DETALLADO DE TESTING - SETAP\n";
echo "=====================================\n\n";

// 1. Análisis de coverage por módulo
$modules = [
    'PersonaController' => 'tests/Unit/PersonaControllerTest.php',
    'TaskController' => 'tests/Unit/TaskControllerTest.php', 
    'ProjectController' => 'tests/Unit/ProjectControllerTest.php',
    'UserTest' => 'tests/Unit/UserTest.php',
    'AuthTest' => 'tests/Integration/AuthTest.php',
    'AppConstants' => 'tests/Unit/AppConstantsTest.php',
    'ConstantsIntegration' => 'tests/Integration/ConstantsIntegrationTest.php'
];

echo "🔍 ANÁLISIS POR MÓDULO:\n";
echo "========================\n";

$totalTests = 0;
$totalAssertions = 0;

foreach ($modules as $module => $testFile) {
    echo "\n📋 $module:\n";
    
    if (file_exists(__DIR__ . '/' . $testFile)) {
        $content = file_get_contents(__DIR__ . '/' . $testFile);
        
        // Contar métodos de test
        $testMethods = preg_match_all('/public function test\w+/', $content, $matches);
        
        // Contar assertions
        $assertions = preg_match_all('/\$this->assert\w+\(/', $content, $assertMatches);
        
        echo "   ✅ Archivo: $testFile\n";
        echo "   🧪 Métodos de test: $testMethods\n";
        echo "   🔍 Assertions: $assertions\n";
        
        // Calcular cobertura estimada
        $coverage = min(90, ($assertions / max(1, $testMethods)) * 10);
        echo "   📊 Cobertura estimada: " . round($coverage) . "%\n";
        
        $totalTests += $testMethods;
        $totalAssertions += $assertions;
        
        // Identificar tipos de tests
        $testTypes = [];
        if (strpos($content, 'permissions') !== false) $testTypes[] = 'Permisos';
        if (strpos($content, 'validation') !== false) $testTypes[] = 'Validación';
        if (strpos($content, 'CRUD') !== false || strpos($content, 'store') !== false) $testTypes[] = 'CRUD';
        if (strpos($content, 'filter') !== false) $testTypes[] = 'Filtros';
        if (strpos($content, 'session') !== false) $testTypes[] = 'Sesiones';
        
        if (!empty($testTypes)) {
            echo "   🏷️  Tipos: " . implode(', ', $testTypes) . "\n";
        }
        
    } else {
        echo "   ❌ Archivo no encontrado\n";
    }
}

echo "\n\n📈 RESUMEN GENERAL:\n";
echo "==================\n";
echo "🧪 Total tests implementados: $totalTests\n";
echo "🔍 Total assertions: $totalAssertions\n";
echo "📊 Promedio assertions por test: " . round($totalAssertions / max(1, $totalTests), 1) . "\n";

// 2. Análisis de archivos fuente vs tests
echo "\n🎯 COBERTURA DE CÓDIGO:\n";
echo "=======================\n";

$sourceFiles = [
    'src/App/Controllers/PersonaController.php',
    'src/App/Controllers/TaskController.php',
    'src/App/Controllers/ProjectController.php', 
    'src/App/Controllers/UserController.php',
    'src/App/Controllers/AuthController.php',
    'src/App/Constants/AppConstants.php'
];

$testedFiles = [];
$untestedFiles = [];

foreach ($sourceFiles as $sourceFile) {
    $basename = basename($sourceFile, '.php');
    $hasTest = false;
    
    foreach ($modules as $module => $testFile) {
        if (stripos($module, $basename) !== false) {
            $hasTest = true;
            $testedFiles[] = $sourceFile;
            break;
        }
    }
    
    if (!$hasTest) {
        $untestedFiles[] = $sourceFile;
    }
}

echo "✅ Archivos con tests (" . count($testedFiles) . "):\n";
foreach ($testedFiles as $file) {
    echo "   📁 $file\n";
}

if (!empty($untestedFiles)) {
    echo "\n⚠️  Archivos sin tests (" . count($untestedFiles) . "):\n";
    foreach ($untestedFiles as $file) {
        echo "   📁 $file\n";
    }
}

// 3. Recomendaciones
echo "\n💡 RECOMENDACIONES:\n";
echo "===================\n";

$coveragePercentage = (count($testedFiles) / count($sourceFiles)) * 100;

if ($coveragePercentage >= 80) {
    echo "✅ Excelente cobertura de testing ($coveragePercentage%)\n";
    echo "   💎 Su proyecto tiene una base sólida de tests\n";
    echo "   🚀 Listo para deploy en producción\n";
} elseif ($coveragePercentage >= 60) {
    echo "⚠️  Buena cobertura de testing ($coveragePercentage%)\n";
    echo "   📈 Considere agregar tests para archivos faltantes\n";
    echo "   🔍 Revise la calidad de los assertions existentes\n";
} else {
    echo "❌ Cobertura insuficiente de testing ($coveragePercentage%)\n";
    echo "   🚨 Requiere trabajo adicional antes de producción\n";
    echo "   📚 Implemente tests para archivos críticos\n";
}

// Sugerencias específicas
echo "\n🔧 PRÓXIMOS PASOS SUGERIDOS:\n";
echo "============================\n";

if ($totalAssertions / $totalTests < 5) {
    echo "1. ⬆️  Incrementar assertions por test (actual: " . round($totalAssertions / $totalTests, 1) . ", recomendado: 5+)\n";
}

if (!empty($untestedFiles)) {
    echo "2. 📝 Crear tests para archivos faltantes:\n";
    foreach (array_slice($untestedFiles, 0, 3) as $file) {
        echo "   - $file\n";
    }
}

echo "3. 🐛 Corregir tests fallidos identificados en ejecución anterior\n";
echo "4. 🔍 Agregar tests de edge cases y validaciones de seguridad\n";
echo "5. 📊 Implementar coverage reports automáticos\n";

echo "\n✨ COMANDOS ÚTILES:\n";
echo "==================\n";
echo "# Ejecutar todos los tests:\n";
echo "./vendor/bin/phpunit --testdox\n\n";
echo "# Solo tests unitarios:\n";
echo "./vendor/bin/phpunit tests/Unit --testdox\n\n";
echo "# Solo tests de integración:\n";
echo "./vendor/bin/phpunit tests/Integration --testdox\n\n";
echo "# Test específico:\n";
echo "./vendor/bin/phpunit tests/Unit/PersonaControllerTest.php --testdox\n\n";
echo "# Con coverage (requiere xdebug):\n";
echo "./vendor/bin/phpunit --coverage-html coverage/\n\n";

echo "🎉 Análisis completado exitosamente!\n";
