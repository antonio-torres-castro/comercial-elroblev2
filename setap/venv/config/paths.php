<?php
// Configuración de rutas para entorno virtual
define('VENV_ROOT', __DIR__ . '/..');
define('PROJECT_ROOT', dirname(VENV_ROOT));
define('VENDOR_DIR', VENV_ROOT . '/vendor');

// Agregar vendor al include_path si existe
if (is_dir(VENDOR_DIR)) {
    set_include_path(get_include_path() . PATH_SEPARATOR . VENDOR_DIR);
}
