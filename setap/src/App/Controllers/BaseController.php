<?php

namespace App\Controllers;

use App\Helpers\Security;

/**
 * BaseController - Clase base para todos los controladores
 * Contiene funcionalidades comunes compartidas entre controladores
 */
abstract class BaseController
{
    /**
     * Obtiene la información del usuario autenticado actual
     * @return array|null Array con datos del usuario o null si no está autenticado
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
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - SETAP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="alert alert-danger text-center">
                            <h4 class="alert-heading">Error</h4>
                            <p>' . htmlspecialchars($message) . '</p>
                            <hr>
                            <p class="mb-0">
                                <a href="/dashboard" class="btn btn-primary">Volver al Dashboard</a>
                            </p>
                        </div>
                    </div>
                </div>
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
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
