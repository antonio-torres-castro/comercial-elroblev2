<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/auth_functions.php';

init_secure_session();

// Cerrar sesión del usuario
logoutUser();

// Redirigir al portal principal
header('Location: /mer/public/');
exit;
?>