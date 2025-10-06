<?php

namespace App\Services;

use App\Config\Database;
use App\Helpers\Security;
use App\Services\ClientBusinessLogic;
use PDO;
use Exception;

/**
 * Servicio especializado en validaciones de datos
 * Responsabilidad única: Validar datos de entrada
 */
class ValidationService
{
    private $db;
    private $clientBusinessLogic;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->clientBusinessLogic = new ClientBusinessLogic();
    }

    /**
     * Validar datos de usuario para creación
     */
    public function validateUserData(array $data): array
    {
        $errors = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es requerido';
        } elseif (strlen($data['nombre']) < 3) {
            $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
        }

        // Validar RUT
        if (empty($data['rut'])) {
            $errors['rut'] = 'El RUT es requerido';
        } elseif (!$this->validateRut($data['rut'])) {
            $errors['rut'] = 'El RUT no es válido';
        } elseif (!$this->isRutAvailable($data['rut'])) {
            $errors['rut'] = 'El RUT ya está registrado';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!$this->validateEmail($data['email'])) {
            $errors['email'] = 'El email no es válido';
        } elseif (!$this->isEmailAvailable($data['email'])) {
            $errors['email'] = 'El email ya está registrado';
        }

        // Validar username
        if (empty($data['nombre_usuario'])) {
            $errors['nombre_usuario'] = 'El nombre de usuario es requerido';
        } elseif (strlen($data['nombre_usuario']) < 4) {
            $errors['nombre_usuario'] = 'El nombre de usuario debe tener al menos 4 caracteres';
        } elseif (!$this->isUsernameAvailable($data['nombre_usuario'])) {
            $errors['nombre_usuario'] = 'El nombre de usuario ya existe';
        }

        // Validar contraseña
        if (empty($data['password'])) {
            $errors['password'] = 'La contraseña es requerida';
        } else {
            $passwordErrors = $this->validatePasswordStrength($data['password']);
            if (!empty($passwordErrors)) {
                $errors['password'] = implode(', ', $passwordErrors);
            }
        }

        // Validar tipo de usuario
        if (empty($data['usuario_tipo_id'])) {
            $errors['usuario_tipo_id'] = 'El tipo de usuario es requerido';
        }

        return $errors;
    }

    /**
     * Validar datos de usuario para actualización
     */
    public function validateUserDataForUpdate(array $data, int $userId): array
    {
        $errors = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es requerido';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } elseif (!$this->validateEmail($data['email'])) {
            $errors['email'] = 'El email no es válido';
        } elseif (!$this->isEmailAvailable($data['email'], $userId)) {
            $errors['email'] = 'El email ya está registrado';
        }

        // Validar tipo de usuario
        if (empty($data['usuario_tipo_id'])) {
            $errors['usuario_tipo_id'] = 'El tipo de usuario es requerido';
        }

        return $errors;
    }

    /**
     * Validar RUT chileno
     */
    public function validateRut(string $rut): bool
    {
        $rut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($rut) < 2) {
            return false;
        }

        $dv = strtoupper(substr($rut, -1));
        $numero = substr($rut, 0, -1);

        $suma = 0;
        $multiplicador = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += $numero[$i] * $multiplicador;
            $multiplicador = $multiplicador == 7 ? 2 : $multiplicador + 1;
        }

        $resto = $suma % 11;
        $dvCalculado = $resto == 0 ? '0' : ($resto == 1 ? 'K' : (string)(11 - $resto));

        return $dv === $dvCalculado;
    }

    /**
     * Validar email
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validar fortaleza de contraseña
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una mayúscula';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una minúscula';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial';
        }

        return $errors;
    }

    /**
     * Verificar disponibilidad de username
     */
    public function isUsernameAvailable(string $username, int $excludeUserId = 0): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ?";
            $params = [$username];
            
            if ($excludeUserId > 0) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            error_log("Error verificando username: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar disponibilidad de email
     */
    public function isEmailAvailable(string $email, int $excludeUserId = 0): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
            $params = [$email];
            
            if ($excludeUserId > 0) {
                $sql .= " AND id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            error_log("Error verificando email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar disponibilidad de RUT
     */
    public function isRutAvailable(string $rut, int $excludeUserId = 0): bool
    {
        try {
            $cleanRut = preg_replace('/[^0-9kK]/', '', $rut);
            $sql = "SELECT COUNT(*) FROM personas WHERE rut = ?";
            $params = [$cleanRut];
            
            if ($excludeUserId > 0) {
                $sql .= " AND usuario_id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            error_log("Error verificando RUT: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar datos de usuario con sanitización y respuesta completa
     * (Versión completa para casos que requieren datos sanitizados)
     */
    public function validateUserDataComplete(array $data): array
    {
        $errors = [];
        $sanitizedData = [];

        // Validar y sanitizar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es requerido';
        } else {
            $sanitizedData['nombre'] = Security::sanitizeInput($data['nombre']);
        }

        // Validar y sanitizar RUT
        if (empty($data['rut'])) {
            $errors['rut'] = 'El RUT es requerido';
        } else {
            $sanitizedData['rut'] = Security::sanitizeInput($data['rut']);
            if (!Security::validateRut($sanitizedData['rut'])) {
                $errors['rut'] = 'El RUT no es válido';
            } elseif (!$this->isRutAvailable($sanitizedData['rut'])) {
                $errors['rut'] = 'El RUT ya está registrado';
            }
        }

        // Validar y sanitizar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } else {
            $sanitizedData['email'] = Security::sanitizeInput($data['email']);
            if (!Security::validateEmail($sanitizedData['email'])) {
                $errors['email'] = 'El email no es válido';
            } elseif (!$this->isEmailAvailable($sanitizedData['email'])) {
                $errors['email'] = 'El email ya está registrado';
            }
        }

        // Validar y sanitizar nombre de usuario
        if (empty($data['nombre_usuario'])) {
            $errors['nombre_usuario'] = 'El nombre de usuario es requerido';
        } else {
            $sanitizedData['nombre_usuario'] = Security::sanitizeInput($data['nombre_usuario']);
            if (!$this->isUsernameAvailable($sanitizedData['nombre_usuario'])) {
                $errors['nombre_usuario'] = 'El nombre de usuario ya existe';
            }
        }

        // Validar contraseña
        if (empty($data['password'])) {
            $errors['password'] = 'La contraseña es requerida';
        } else {
            $sanitizedData['password'] = $data['password']; // No sanitizar contraseñas
            $passwordErrors = Security::validatePasswordStrength($data['password']);
            if (!empty($passwordErrors)) {
                $errors['password'] = implode(', ', $passwordErrors);
            }
        }

        // Validar tipo de usuario
        if (empty($data['usuario_tipo_id'])) {
            $errors['usuario_tipo_id'] = 'El tipo de usuario es requerido';
        } else {
            $sanitizedData['usuario_tipo_id'] = (int)$data['usuario_tipo_id'];
        }

        // Sanitizar otros campos si están presentes
        foreach (['telefono', 'direccion', 'estado_id', 'cliente_id'] as $field) {
            if (isset($data[$field])) {
                $sanitizedData[$field] = Security::sanitizeInput($data[$field]);
            }
        }

        // Validaciones de lógica de negocio para clientes
        if (!empty($data['usuario_tipo_id'])) {
            $clientValidationErrors = $this->clientBusinessLogic->validateClientLogic($sanitizedData);
            $errors = array_merge($errors, $clientValidationErrors);
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitizedData
        ];
    }

    /**
     * Validar campo específico
     */
    public function validateField(string $field, string $value, int $excludeUserId = 0): array
    {
        $isValid = true;
        $message = '';

        switch ($field) {
            case 'username':
                $isValid = $this->isUsernameAvailable($value, $excludeUserId);
                $message = $isValid ? 'Nombre de usuario disponible' : 'Nombre de usuario ya existe';
                break;

            case 'email':
                $isValid = $this->isEmailAvailable($value, $excludeUserId);
                $message = $isValid ? 'Email disponible' : 'Email ya registrado';
                break;

            case 'rut':
                $isValid = $this->validateRut($value);
                $message = $isValid ? 'RUT válido' : 'RUT inválido';
                if ($isValid) {
                    $isValid = $this->isRutAvailable($value, $excludeUserId);
                    $message = $isValid ? 'RUT disponible' : 'RUT ya registrado';
                }
                break;

            default:
                $isValid = false;
                $message = 'Campo no válido';
        }

        return [
            'valid' => $isValid,
            'message' => $message
        ];
    }


}
