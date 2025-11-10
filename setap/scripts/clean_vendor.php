<?php

/**
 * Script para limpiar vendor de archivos innecesarios para producción
 * 
 */

echo "🧹 Iniciando limpieza de vendor para producción...\n";

// Directorios y archivos a eliminar
$patterns = [
    // Tests y archivos de prueba
    '/test\.php$/i',
    '/Test\.php$/i',
    '/_test\.php$/i',
    '/.*\/tests\//i',
    '/.*\/test\//i',
    '/.*\/Tests\//i',
    '/.*\/Test\//i',

    // Documentación (mantener solo CHANGELOG y LICENSE)
    '/\.md$/i',
    '/\.rst$/i',
    '/\.txt$/i',
    '!/CHANGELOG\.md$/i',
    '!/LICENSE.*$/i',
    '!/COPYING.*$/i',

    // Archivos de desarrollo
    '/\.dev$/i',
    '/\.dist$/i',
    '/\.sample$/i',
    '/\.example$/i',

    // Git y control de versiones
    '/\.gitignore$/i',
    '/\.gitattributes$/i',
    '/\.github\//i',

    // Archivos temporales
    '/\.tmp$/i',
    '/\.temp$/i',
    '/\.log$/i',
    '/\.cache$/i',

    // Archivos del sistema
    '/\.DS_Store$/i',
    '/Thumbs\.db$/i',
    '/desktop\.ini$/i',

    // Archivos de编辑器
    '/\.vscode\//i',
    '/\.idea\//i',
    '/\.sublime-project$/i',
    '/\.sublime-workspace$/i'
];

$vendorDir = __DIR__ . '/vendor';
$removed = 0;
$totalSize = 0;

if (!is_dir($vendorDir)) {
    die("❌ Error: No se encontró el directorio vendor/\n");
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($vendorDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $filePath = $file->getPathname();
        $relativePath = str_replace($vendorDir . '/', '', $filePath);

        $shouldRemove = false;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $relativePath)) {
                $shouldRemove = true;
                break;
            }
        }

        if ($shouldRemove) {
            $fileSize = $file->getSize();
            $totalSize += $fileSize;

            if (unlink($filePath)) {
                $removed++;
                echo "🗑️  Eliminado: " . $relativePath . " (" . formatBytes($fileSize) . ")\n";
            }
        }
    }
}

// Eliminar directorios vacíos
$emptyDirs = 0;
function removeEmptyDirectories($dir)
{
    global $emptyDirs;

    if (!is_dir($dir)) return;

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            removeEmptyDirectories($path);
        }
    }

    // Si el directorio está vacío, eliminarlo
    $files = array_diff(scandir($dir), ['.', '..']);
    if (empty($files)) {
        rmdir($dir);
        $emptyDirs++;
        echo "📁 Directorio vacío eliminado: " . str_replace(__DIR__ . '/', '', $dir) . "\n";
    }
}

removeEmptyDirectories($vendorDir);

echo "\n📊 RESUMEN DE LIMPIEZA:\n";
echo "========================\n";
echo "✅ Archivos eliminados: $removed\n";
echo "💾 Espacio liberado: " . formatBytes($totalSize) . "\n";
echo "📁 Directorios vacíos eliminados: $emptyDirs\n";
echo "\n🎉 Limpieza completada!\n";

function formatBytes($size, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}
