#!/usr/bin/env php
<?php
/**
 * Analizador de errores en tests - SETAP
 * 
 * 
 * @date 2025-10-11
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🔍 ANÁLISIS DETALLADO DE ERRORES EN TESTS\n";
echo "==========================================\n\n";

// Ejecutar PHPUnit y capturar salida
$output = shell_exec("cd " . __DIR__ . " && php ./vendor/bin/phpunit --testdox 2>&1");

// Parsear errores y failures
$lines = explode("\n", $output);
$errors = [];
$failures = [];
$currentSection = '';
$currentError = '';
$isInError = false;

foreach ($lines as $line) {
    $line = trim($line);

    // Detectar inicio de error/failure
    if (strpos($line, '✘') !== false) {
        if ($isInError && !empty($currentError)) {
            if (strpos($currentError, 'Error:') !== false) {
                $errors[] = $currentError;
            } else {
                $failures[] = $currentError;
            }
        }
        $currentError = $line;
        $isInError = true;
    }
    // Continuar capturando el error
    elseif ($isInError && (strpos($line, '┐') !== false || strpos($line, '├') !== false || strpos($line, '┴') !== false || strpos($line, '│') !== false)) {
        $currentError .= "\n" . $line;
    }
    // Terminar captura de error
    elseif ($isInError && (empty($line) || strpos($line, '✔') !== false)) {
        if (!empty($currentError)) {
            if (strpos($currentError, 'Error:') !== false) {
                $errors[] = $currentError;
            } else {
                $failures[] = $currentError;
            }
        }
        $currentError = '';
        $isInError = false;
    }
}

// Agregar último error si existe
if ($isInError && !empty($currentError)) {
    if (strpos($currentError, 'Error:') !== false) {
        $errors[] = $currentError;
    } else {
        $failures[] = $currentError;
    }
}

// Extraer información de resumen
$summaryLine = '';
foreach ($lines as $line) {
    if (strpos($line, 'Tests:') !== false && strpos($line, 'Assertions:') !== false) {
        $summaryLine = $line;
        break;
    }
}

echo "📊 RESUMEN DE EJECUCIÓN:\n";
echo "========================\n";
if (!empty($summaryLine)) {
    echo "$summaryLine\n\n";
}

// Mostrar errores
echo "❌ ERRORES ENCONTRADOS (" . count($errors) . "):\n";
echo "===================================\n";

if (empty($errors)) {
    echo "✅ No se encontraron errores de tipo 'Error'\n\n";
} else {
    foreach ($errors as $i => $error) {
        echo "\n🔴 ERROR #" . ($i + 1) . ":\n";
        echo str_repeat("-", 50) . "\n";
        echo $error . "\n";
    }
    echo "\n";
}

// Mostrar failures
echo "⚠️ FAILURES ENCONTRADOS (" . count($failures) . "):\n";
echo "=====================================\n";

if (empty($failures)) {
    echo "✅ No se encontraron failures\n\n";
} else {
    foreach ($failures as $i => $failure) {
        echo "\n🟡 FAILURE #" . ($i + 1) . ":\n";
        echo str_repeat("-", 50) . "\n";
        echo $failure . "\n";
    }
    echo "\n";
}

// Análisis de patrones de error
echo "🔍 ANÁLISIS DE PATRONES:\n";
echo "========================\n";

$allIssues = array_merge($errors, $failures);
$patterns = [
    'Undefined constant' => 0,
    'assertFilter' => 0,
    'MethodCannotBeConfiguredException' => 0,
    'preg_match' => 0,
    'Task State Validation' => 0,
    'find.*cannot be configured' => 0
];

foreach ($allIssues as $issue) {
    foreach ($patterns as $pattern => $count) {
        if (stripos($issue, $pattern) !== false) {
            $patterns[$pattern]++;
        }
    }
}

foreach ($patterns as $pattern => $count) {
    if ($count > 0) {
        echo "📌 $pattern: $count ocurrencias\n";
    }
}

echo "\n🎯 CLASIFICACIÓN POR SEVERIDAD:\n";
echo "===============================\n";

$critical = 0; // Errores que impiden ejecución
$major = 0;    // Failures que afectan lógica
$minor = 0;    // Issues menores

foreach ($errors as $error) {
    if (
        strpos($error, 'Undefined constant') !== false ||
        strpos($error, 'Call to undefined method') !== false
    ) {
        $critical++;
    } else {
        $major++;
    }
}

foreach ($failures as $failure) {
    if (
        strpos($failure, 'preg_match') !== false ||
        strpos($failure, 'asserting that false is true') !== false
    ) {
        $major++;
    } else {
        $minor++;
    }
}

echo "🔴 CRÍTICOS: $critical (requieren corrección inmediata)\n";
echo "🟡 MAYORES: $major (afectan funcionalidad)\n";
echo "🟢 MENORES: $minor (mejoras recomendadas)\n";

echo "\n📋 ARCHIVOS MÁS AFECTADOS:\n";
echo "===========================\n";

$fileIssues = [];
foreach ($allIssues as $issue) {
    if (preg_match('/\/workspace\/setap\/tests\/[^:]+\.php/', $issue, $matches)) {
        $file = basename($matches[0]);
        if (!isset($fileIssues[$file])) {
            $fileIssues[$file] = 0;
        }
        $fileIssues[$file]++;
    }
}

arsort($fileIssues);
foreach ($fileIssues as $file => $count) {
    echo "📁 $file: $count issues\n";
}

echo "\n🚀 ESTADO GENERAL:\n";
echo "==================\n";
$totalIssues = count($errors) + count($failures);
if ($totalIssues == 0) {
    echo "✅ Todos los tests pasan correctamente\n";
} elseif ($critical == 0 && $totalIssues <= 5) {
    echo "⚠️  Estado BUENO - Issues menores que no impiden funcionamiento\n";
} elseif ($critical <= 2) {
    echo "🔧 Estado MODERADO - Requiere correcciones pero funcional\n";
} else {
    echo "❌ Estado CRÍTICO - Requiere corrección inmediata\n";
}

echo "\n✨ Análisis completado!\n";
