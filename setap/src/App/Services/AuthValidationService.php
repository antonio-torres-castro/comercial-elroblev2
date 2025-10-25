<?php

namespace App\Services;

use App\Helpers\Security;
use App\Constants\AppConstants;
use App\Services\CustomLogger;

class AuthValidationService
{
    /**
     * Validar credenciales de login
     */
    public function validateLoginCredentials(array $postData): array
    {
        $errors = [];
        $data = [];

        // Validar CSRF token
        $csrfToken = $postData['csrf_token'] ?? '';

        // Validación normal de CSRF
        if (!Security::validateCsrfToken($csrfToken)) {
            $error = AppConstants::ERROR_INVALID_SECURITY_TOKEN;
            $errors[] = $error;
            return ['isValid' => false, 'errors' => $errors, 'data' => $data];
        }

        // Obtener y validar credenciales
        $identifier = Security::sanitizeInput($postData['identifier'] ?? '');
        $password = $postData['password'] ?? '';

        if (empty($identifier)) {
            $errors[] = 'El usuario o email es requerido';
        }

        if (empty($password)) {
            $errors[] = 'La contraseña es requerida';
        }

        if (empty($errors)) {
            $data['identifier'] = $identifier;
            $data['password'] = $password;
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }

    /**
     * Formatear errores para mostrar
     */
    public function formatErrorsForDisplay(array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        if (count($errors) === 1) {
            return $errors[0];
        }

        return implode(' ', $errors);
    }
}
