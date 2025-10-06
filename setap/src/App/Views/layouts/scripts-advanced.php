<?php
/**
 * Scripts avanzados opcionales
 * Incluye: jQuery, DataTables, y otras librerías según necesidad
 *
 * Uso:
 * $scripts = ['jquery', 'datatables'];
 * include __DIR__ . '/scripts-advanced.php';
 */

// Scripts disponibles
$available_scripts = [
    'jquery' => [
        'url' => 'https://code.jquery.com/jquery-3.7.1.min.js',
        'check' => 'window.jQuery'
    ],
    'datatables' => [
        'requires' => ['jquery'],
        'css' => 'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css',
        'js' => [
            'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
            'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js'
        ],
        'check' => 'window.jQuery && window.jQuery.fn.DataTable'
    ]
];

// Si no se especifican scripts, no cargar nada
$scripts = $scripts ?? [];

if (!empty($scripts)) {
    // Prevenir carga múltiple
    if (!defined('SETAP_ADVANCED_SCRIPTS_LOADED')) {
        define('SETAP_ADVANCED_SCRIPTS_LOADED', true);

        // Resolver dependencias
        $scripts_to_load = [];
        foreach ($scripts as $script) {
            if (isset($available_scripts[$script])) {
                // Agregar dependencias
                if (isset($available_scripts[$script]['requires'])) {
                    foreach ($available_scripts[$script]['requires'] as $dep) {
                        if (!in_array($dep, $scripts_to_load)) {
                            $scripts_to_load[] = $dep;
                        }
                    }
                }
                if (!in_array($script, $scripts_to_load)) {
                    $scripts_to_load[] = $script;
                }
            }
        }

        // Cargar CSS
        echo "    <!-- Scripts Avanzados de SETAP -->\n";
        foreach ($scripts_to_load as $script) {
            if (isset($available_scripts[$script]['css'])) {
                echo "    <link href=\"{$available_scripts[$script]['css']}\" rel=\"stylesheet\">\n";
            }
        }

        // Cargar JavaScript
        foreach ($scripts_to_load as $script) {
            $script_config = $available_scripts[$script];

            if (isset($script_config['url'])) {
                echo "    <script src=\"{$script_config['url']}\"></script>\n";
            } elseif (isset($script_config['js'])) {
                if (is_array($script_config['js'])) {
                    foreach ($script_config['js'] as $js_url) {
                        echo "    <script src=\"{$js_url}\"></script>\n";
                    }
                } else {
                    echo "    <script src=\"{$script_config['js']}\"></script>\n";
                }
            }
        }

        // Script de verificación
        echo "    <script>\n";
        echo "        // Verificar carga de scripts avanzados\n";
        echo "        window.SETAP = window.SETAP || {};\n";
        echo "        window.SETAP.scriptsLoaded = window.SETAP.scriptsLoaded || {};\n";

        foreach ($scripts_to_load as $script) {
            echo "        window.SETAP.scriptsLoaded.{$script} = true;\n";
        }

        echo "        console.log('SETAP Advanced Scripts cargados:', " . json_encode($scripts_to_load) . ");\n";
        echo "    </script>\n";
    }
}
?>