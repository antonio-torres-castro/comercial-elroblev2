<?php

namespace App\Middlewares;

use App\Helpers\Security;

class AuthMiddleware
{
    public function handle(): void
    {
        // Verificar si hay sesión activa
        if (!Security::isAuthenticated()) {
            $this->redirectToLogin();
            return;
        }

        // Verificar si la sesión ha expirado (opcional)
        if ($this->isSessionExpired()) {
            session_destroy();
            $this->redirectToLogin();
            return;
        }
    }

    private function redirectToLogin(): void
    {
        if ($this->isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autenticado', 'redirect' => '/login']);
        } else {
            header('Location: /login');
        }
        exit;
    }

    private function isSessionExpired(): bool
    {
        if (!isset($_SESSION['login_time'])) {
            return true;
        }

        $sessionLifetime = 3600; // 1 hora por defecto
        return (time() - $_SESSION['login_time']) > $sessionLifetime;
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
               && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}