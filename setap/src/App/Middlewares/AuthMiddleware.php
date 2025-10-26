<?php

namespace App\Middlewares;

use App\Helpers\Security;
use App\Helpers\Logger;
use App\Constants\AppConstants;
use Exception;

class AuthMiddleware
{
    public function handle(): void
    {
        try {
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
        } catch (Exception $e) {
            Logger::error('AuthMiddleware::handle error: ' . $e->getMessage());
        }
    }

    private function redirectToLogin(): void
    {
        if ($this->isAjaxRequest()) {
            $this->sendMiddlewareError(AppConstants::ERROR_USER_NOT_AUTHENTICATED, 401, ['redirect' => AppConstants::ROUTE_LOGIN]);
        } else {
            header('Location: ' . AppConstants::ROUTE_LOGIN);
            exit;
        }
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

    /**
     * Envía una respuesta JSON de error de middleware y termina la ejecución
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP
     * @param array $additionalData Datos adicionales
     */
    private function sendMiddlewareError(string $message, int $statusCode = 401, array $additionalData = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');

        $response = array_merge([
            'success' => false,
            'error' => $message,
            'message' => $message
        ], $additionalData);

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
