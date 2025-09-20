<?php

namespace App\Middlewares;

use App\Helpers\Security;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!Security::isAuthenticated()) {
            // Guardar la URL a la que intentaba acceder para redirigir después del login
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            Security::redirect('/login');
        }
    }
}
