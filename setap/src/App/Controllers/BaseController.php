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

    // ===== MÉTODOS DE VALIDACIÓN DE DATOS - FASE 3 =====

    /**
     * Valida y obtiene datos de $_POST con valores por defecto
     * @param array $fields Array de campos con valores por defecto ['campo' => 'valor_defecto']
     * @return array Datos validados y sanitizados
     */
    protected function validatePostData(array $fields): array
    {
        $validated = [];
        foreach ($fields as $field => $default) {
            $value = $_POST[$field] ?? $default;
            $validated[$field] = is_string($value) ? Security::sanitizeInput($value) : $value;
        }
        return $validated;
    }

    /**
     * Valida y obtiene datos de $_GET con valores por defecto
     * @param array $fields Array de campos con valores por defecto ['campo' => 'valor_defecto']
     * @return array Datos validados y sanitizados
     */
    protected function validateGetData(array $fields): array
    {
        $validated = [];
        foreach ($fields as $field => $default) {
            $value = $_GET[$field] ?? $default;
            $validated[$field] = is_string($value) ? Security::sanitizeInput($value) : $value;
        }
        return $validated;
    }

    /**
     * Verifica si existe un valor anidado en un array de forma segura
     * @param array $data Array a verificar
     * @param string $path Ruta anidada usando notación de punto: 'menu.id'
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor encontrado o valor por defecto
     */
    protected function safeArrayAccess(array $data, string $path, $default = null)
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Valida que campos requeridos estén presentes en un array
     * @param array $data Datos a validar
     * @param array $requiredFields Campos requeridos
     * @return array Lista de errores (vacío si todo está bien)
     */
    protected function validateRequiredFields(array $data, array $requiredFields): array
    {
        $errors = [];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo '{$field}' es requerido";
            }
        }
        return $errors;
    }

    // ===== VALIDACIONES COMUNES - FASE 3.3 OPTIMIZACIÓN =====

    /**
     * Valida un email
     * @param string $email Email a validar
     * @param string $fieldName Nombre del campo para error
     * @return array Lista de errores
     */
    protected function validateEmail(string $email, string $fieldName = 'email'): array
    {
        $errors = [];
        if (empty($email)) {
            $errors[] = "El {$fieldName} es requerido";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El {$fieldName} no tiene un formato válido";
        }
        return $errors;
    }

    /**
     * Valida longitud de un campo
     * @param string $value Valor a validar
     * @param string $fieldName Nombre del campo
     * @param int $minLength Longitud mínima
     * @param int $maxLength Longitud máxima
     * @return array Lista de errores
     */
    protected function validateLength(string $value, string $fieldName, int $minLength = 0, int $maxLength = 255): array
    {
        $errors = [];
        $length = strlen($value);

        if ($minLength > 0 && $length < $minLength) {
            $errors[] = "El {$fieldName} debe tener al menos {$minLength} caracteres";
        }

        if ($length > $maxLength) {
            $errors[] = "El {$fieldName} no puede exceder {$maxLength} caracteres";
        }

        return $errors;
    }

    /**
     * Valida que un valor esté en una lista de opciones válidas
     * @param mixed $value Valor a validar
     * @param array $validOptions Opciones válidas
     * @param string $fieldName Nombre del campo
     * @return array Lista de errores
     */
    protected function validateInArray($value, array $validOptions, string $fieldName): array
    {
        $errors = [];
        if (!in_array($value, $validOptions)) {
            $errors[] = "El {$fieldName} no es válido";
        }
        return $errors;
    }

    /**
     * Manejo estandarizado de errores de validación - FASE 3.3
     * @param array $errors Lista de errores
     * @param array $inputData Datos del formulario para preservar
     * @param string $redirectUrl URL de redirección
     */
    protected function handleValidationErrors(array $errors, array $inputData, string $redirectUrl): void
    {
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $inputData;
            $this->redirectToRoute($redirectUrl);
        }
    }

    // ===== MÉTODOS PARA RESPUESTAS JSON ELEGANTES - SISTEMA DE ALERTAS =====

    /**
     * Envía una respuesta JSON estandarizada y termina la ejecución
     * Reemplaza el uso crudo de echo json_encode()
     * @param array $data Datos a enviar como JSON
     * @param int $statusCode Código de estado HTTP (default: 200)
     * @param bool $prettyPrint Si debe formatearse el JSON (default: false)
     */
    protected function jsonResponse(array $data, int $statusCode = 200, bool $prettyPrint = false): void
    {
        // Configurar headers apropiados
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');

        // Preparar opciones de JSON
        $options = JSON_UNESCAPED_UNICODE;
        if ($prettyPrint) {
            $options |= JSON_PRETTY_PRINT;
        }

        // Enviar respuesta y terminar
        echo json_encode($data, $options);
        exit;
    }

    /**
     * Envía una respuesta JSON de éxito estandarizada
     * @param string $message Mensaje de éxito
     * @param array $additionalData Datos adicionales para incluir (opcional)
     * @param int $statusCode Código de estado HTTP (default: 200)
     */
    protected function jsonSuccess(string $message, array $additionalData = [], int $statusCode = 200): void
    {
        $data = array_merge([
            'success' => true,
            'message' => $message
        ], $additionalData);

        $this->jsonResponse($data, $statusCode);
    }

    /**
     * Envía una respuesta JSON de error estandarizada
     * @param string $message Mensaje de error
     * @param array $additionalData Datos adicionales para incluir (opcional)
     * @param int $statusCode Código de estado HTTP (default: 400)
     */
    protected function jsonError(string $message, array $additionalData = [], int $statusCode = 400): void
    {
        $data = array_merge([
            'success' => false,
            'message' => $message
        ], $additionalData);

        $this->jsonResponse($data, $statusCode);
    }

    /**
     * Envía una respuesta JSON de validación fallida
     * @param array $errors Lista de errores de validación
     * @param string $message Mensaje general (opcional)
     * @param int $statusCode Código de estado HTTP (default: 422)
     */
    protected function jsonValidationError(array $errors, string $message = 'Error de validación', int $statusCode = 422): void
    {
        $this->jsonError($message, ['errors' => $errors], $statusCode);
    }

    /**
     * Envía una respuesta JSON de no autorizado
     * @param string $message Mensaje personalizado (opcional)
     */
    protected function jsonUnauthorized(?string $message = null): void
    {
        $message = $message ?? AppConstants::ERROR_USER_NOT_AUTHORIZED;
        $this->jsonError($message, [], 401);
    }

    /**
     * Envía una respuesta JSON de prohibido/sin permisos
     * @param string $message Mensaje personalizado (opcional)
     */
    protected function jsonForbidden(string $message = 'Sin permisos suficientes'): void
    {
        $this->jsonError($message, [], 403);
    }

    /**
     * Envía una respuesta JSON de recurso no encontrado
     * @param string $message Mensaje personalizado (opcional)
     */
    protected function jsonNotFound(string $message = 'Recurso no encontrado'): void
    {
        $this->jsonError($message, [], 404);
    }

    /**
     * Envía una respuesta JSON para errores internos del servidor
     * @param string $message Mensaje personalizado (opcional)
     * @param bool $logError Si debe registrar el error en logs (default: true)
     */
    protected function jsonInternalError(string $message = 'Error interno del servidor', bool $logError = true): void
    {
        if ($logError) {
            error_log("JSON Internal Error: {$message}");
        }
        $this->jsonError($message, [], 500);
    }
}
