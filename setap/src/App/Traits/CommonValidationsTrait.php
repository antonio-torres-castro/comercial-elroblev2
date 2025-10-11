<?php
namespace App\Traits;

use App\Helpers\Security;
use App\Constants\AppConstants;

/**
 * CommonValidationsTrait - Centraliza validaciones duplicadas
 * Elimina código repetitivo de validación en controladores
 */
trait CommonValidationsTrait
{
    /**
     * Validación completa para operaciones POST con CSRF
     * Reemplaza 10+ líneas duplicadas en cada controlador
     */
    protected function validatePostWithCsrf(): array
    {
        $errors = [];

        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $errors[] = AppConstants::ERROR_METHOD_NOT_ALLOWED;
            http_response_code(405);
            return $errors;
        }

        // Verificar token CSRF
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors[] = AppConstants::ERROR_INVALID_SECURITY_TOKEN;
            http_response_code(403);
            return $errors;
        }

        return $errors;
    }

    /**
     * Validación de ID obligatorio
     * Centraliza validación repetida en edit/update/delete
     */
    protected function validateRequiredId(string $source = 'GET'): array
    {
        $errors = [];
        $data = $source === 'POST' ? $_POST : $_GET;

        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'ID requerido y debe ser mayor a 0';
            http_response_code(400);
        }

        return $errors;
    }

    /**
     * Validación de campos obligatorios
     * Método genérico para validar campos requeridos
     */
    protected function validateRequiredFields(array $data, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $field => $message) {
            if (is_numeric($field)) {
                // Si no hay mensaje personalizado, usar el nombre del campo
                $fieldName = $message;
                $errorMessage = ucfirst(str_replace('_', ' ', $fieldName)) . ' es obligatorio';
            } else {
                // Si hay mensaje personalizado
                $fieldName = $field;
                $errorMessage = $message;
            }

            if (empty($data[$fieldName])) {
                $errors[] = $errorMessage;
            }
        }

        return $errors;
    }

    /**
     * Validación de longitud de campos
     * Centraliza validaciones de longitud duplicadas
     */
    protected function validateFieldLengths(array $data, array $lengthRules): array
    {
        $errors = [];

        foreach ($lengthRules as $field => $rules) {
            if (isset($data[$field])) {
                $value = $data[$field];
                $length = strlen($value);

                if (isset($rules['min']) && $length < $rules['min']) {
                    $errors[] = ucfirst($field) . " debe tener al menos {$rules['min']} caracteres";
                }

                if (isset($rules['max']) && $length > $rules['max']) {
                    $errors[] = ucfirst($field) . " no puede tener más de {$rules['max']} caracteres";
                }
            }
        }

        return $errors;
    }

    /**
     * Validación de fecha
     * Centraliza validaciones de fecha duplicadas
     */
    protected function validateDateField(string $date, string $fieldName, string $format = 'Y-m-d'): array
    {
        $errors = [];

        if (empty($date)) {
            $errors[] = ucfirst($fieldName) . " es obligatorio";
            return $errors;
        }

        if (!$this->isValidDate($date, $format)) {
            $errors[] = ucfirst($fieldName) . " tiene formato inválido";
        }

        return $errors;
    }

    /**
     * Validación de rango de fechas
     * Centraliza validación de fechas inicio/fin
     */
    protected function validateDateRange(?string $startDate, ?string $endDate): array
    {
        $errors = [];

        if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
            $errors[] = 'La fecha de inicio no puede ser posterior a la fecha de fin';
        }

        return $errors;
    }

    // El método validateEmail fue removido para evitar conflictos de firma con BaseController
    // Los controladores que usan este trait pueden usar $this->validateEmail() del BaseController

    /**
     * Validación de RUT chileno
     */
    protected function validateRut(?string $rut, bool $required = true): array
    {
        $errors = [];

        if ($required && empty($rut)) {
            $errors[] = 'RUT es obligatorio';
            return $errors;
        }

        if (!empty($rut) && !Security::validateRut($rut)) {
            $errors[] = 'RUT no es válido';
        }

        return $errors;
    }

    /**
     * Validación de teléfono
     */
    protected function validatePhone(?string $phone, bool $required = false): array
    {
        $errors = [];

        if ($required && empty($phone)) {
            $errors[] = 'Teléfono es obligatorio';
            return $errors;
        }

        if (!empty($phone)) {
            if (strlen($phone) > 20) {
                $errors[] = 'Teléfono no puede tener más de 20 caracteres';
            } elseif (!preg_match('/^[+]?[0-9\s\-\(\)]{8,15}$/', $phone)) {
                $errors[] = 'Formato de teléfono no válido';
            }
        }

        return $errors;
    }

    /**
     * Validación de ID de estado
     */
    protected function validateStatusId(?int $statusId): array
    {
        $errors = [];
        $validStatuses = [1, 2, 3, 4, 5, 6, 7, 8];

        if (empty($statusId) || !in_array($statusId, $validStatuses)) {
            $errors[] = 'Estado seleccionado no es válido';
        }

        return $errors;
    }

    /**
     * Validación de existencia de entidad relacionada
     * Usando el servicio común de datos
     */
    protected function validateEntityExists(int $id, string $table, string $entityName): array
    {
        $errors = [];

        if (!$this->commonDataService->entityExists($table, $id)) {
            $errors[] = ucfirst($entityName) . " seleccionado no existe o está inactivo";
        }

        return $errors;
    }

    /**
     * Combinador de errores de múltiples validaciones
     * Facilita la combinación de diferentes validaciones
     */
    protected function combineValidationErrors(array ...$errorArrays): array
    {
        return array_merge(...$errorArrays);
    }

    /**
     * Formateador de errores para mostrar al usuario
     */
    protected function formatValidationErrors(array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        return implode('. ', $errors);
    }

    /**
     * Validación de archivos subidos (para futuros uploads)
     */
    protected function validateUploadedFile(array $file, array $allowedTypes = [], int $maxSize = 2097152): array
    {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al subir el archivo';
            return $errors;
        }

        if ($file['size'] > $maxSize) {
            $errors[] = "El archivo no puede ser mayor a " . ($maxSize / 1024 / 1024) . "MB";
        }

        if (!empty($allowedTypes)) {
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Tipo de archivo no permitido. Tipos válidos: " . implode(', ', $allowedTypes);
            }
        }

        return $errors;
    }
}
