<?php
namespace App\Controllers;
use App\Helpers\Security;
use App\Constants\AppConstants;
use DateTime;

abstract class BaseController
{
    /**
     * Obtener el usuario actual de la sesión
     * @return array|null Datos del usuario actual o null si no está autenticado
     */
    protected function getCurrentUser(): ?array
    {
        if (!Security::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'nombre_completo' => $_SESSION['nombre_completo'],
            'rol' => $_SESSION['rol'],
            'usuario_tipo_id' => $_SESSION['usuario_tipo_id']
        ];
    }

    /**
     * Renderiza una página de error con formato HTML
     * @param string $message Mensaje de error a mostrar
     * @return string HTML de la página de error
     */
    protected function renderError(string $message): string
    {
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error - SETAP</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .error { color: #d32f2f; background: #ffebee; padding: 20px; border-radius: 5px; max-width: 600px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="error">
        <h2>Error</h2>
        <p>' . htmlspecialchars($message) . '</p>
        <a href="/">Volver al inicio</a>
    </div>
</body>
</html>';
    }

    /**
     * Valida si una fecha tiene el formato correcto
     * @param string $date Fecha a validar
     * @param string $format Formato esperado (por defecto Y-m-d)
     * @return bool True si la fecha es válida
     */
    protected function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    // ===== MÉTODOS DE REDIRECCIÓN CON CONSTANTES =====

    /**
     * Redirige a la página de login
     */
    protected function redirectToLogin(): void
    {
        Security::redirect(AppConstants::ROUTE_LOGIN);
    }

    /**
     * Redirige a la página de home
     */
    protected function redirectToHome(): void
    {
        Security::redirect(AppConstants::ROUTE_HOME);
    }

    /**
     * Redirige con mensaje de éxito
     */
    protected function redirectWithSuccess(string $route, string $message): void
    {
        Security::redirect(AppConstants::buildSuccessUrl($route, $message));
    }

    /**
     * Redirige con mensaje de error
     */
    protected function redirectWithError(string $route, string $message): void
    {
        Security::redirect(AppConstants::buildErrorUrl($route, $message));
    }

    /**
     * Redirige a una ruta específica usando constantes
     */
    protected function redirectToRoute(string $route): void
    {
        Security::redirect($route);
    }
}
