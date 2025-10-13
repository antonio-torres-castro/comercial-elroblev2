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
 * 
 * IMPORTANTE: Separación de responsabilidades por tabla:
 * - validateUserData(): Valida campos de la tabla 'usuarios' (persona_id, email, nombre_usuario, etc.)
 * - validatePersonaData(): Valida campos de la tabla 'personas' (rut, nombre, telefono, etc.)
 * 
 * El mantenedor de usuarios NO maneja datos de persona directamente,
 * solo asocia un persona_id y cliente_id existentes.
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
     * Solo valida campos de la tabla 'usuarios'
     */
    public function validateUserData(array $data): array
    {
        $errors = [];

        // Validar persona_id (referencia a la tabla personas)
        if (empty($data['persona_id'])) {
            $errors['persona_id'] = 'La persona es requerida';
        } elseif (!$this->isValidPersonaId($data['persona_id'])) {
            $errors['persona_id'] = 'La persona seleccionada no existe';
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
        } elseif (!$this->isValidUserTypeId($data['usuario_tipo_id'])) {
            $errors['usuario_tipo_id'] = 'El tipo de usuario seleccionado no existe';
        }

        // Validar cliente_id (opcional)
        if (!empty($data['cliente_id']) && !$this->isValidClienteId($data['cliente_id'])) {
            $errors['cliente_id'] = 'El cliente seleccionado no existe';
        }

        // Validar estado_tipo_id (opcional, tiene valor por defecto)
        if (!empty($data['estado_tipo_id']) && !$this->isValidEstadoTipoId($data['estado_tipo_id'])) {
            $errors['estado_tipo_id'] = 'El estado seleccionado no existe';
        }

        return $errors;
    }

    /**
     * Validar datos de usuario para actualización
     * Solo valida campos de la tabla 'usuarios'
     */
    public function validateUserDataForUpdate(array $data, int $userId): array
    {
        $errors = [];

        // Validar persona_id (solo si se está cambiando)
        if (isset($data['persona_id'])) {
            if (empty($data['persona_id'])) {
                $errors['persona_id'] = 'La persona es requerida';
            } elseif (!$this->isValidPersonaId($data['persona_id'])) {
                $errors['persona_id'] = 'La persona seleccionada no existe';
            }
        }

        // Validar email (solo si se está cambiando)
        if (isset($data['email'])) {
            if (empty($data['email'])) {
                $errors['email'] = 'El email es requerido';
            } elseif (!$this->validateEmail($data['email'])) {
                $errors['email'] = 'El email no es válido';
            } elseif (!$this->isEmailAvailable($data['email'], $userId)) {
                $errors['email'] = 'El email ya está registrado';
            }
        }

        // Validar nombre_usuario (solo si se está cambiando)
        if (isset($data['nombre_usuario'])) {
            if (empty($data['nombre_usuario'])) {
                $errors['nombre_usuario'] = 'El nombre de usuario es requerido';
            } elseif (strlen($data['nombre_usuario']) < 4) {
                $errors['nombre_usuario'] = 'El nombre de usuario debe tener al menos 4 caracteres';
            } elseif (!$this->isUsernameAvailable($data['nombre_usuario'], $userId)) {
                $errors['nombre_usuario'] = 'El nombre de usuario ya existe';
            }
        }

        // Validar tipo de usuario (solo si se está cambiando)
        if (isset($data['usuario_tipo_id'])) {
            if (empty($data['usuario_tipo_id'])) {
                $errors['usuario_tipo_id'] = 'El tipo de usuario es requerido';
            } elseif (!$this->isValidUserTypeId($data['usuario_tipo_id'])) {
                $errors['usuario_tipo_id'] = 'El tipo de usuario seleccionado no existe';
            }
        }

        // Validar cliente_id (solo si se está cambiando)
        if (isset($data['cliente_id']) && !empty($data['cliente_id'])) {
            if (!$this->isValidClienteId($data['cliente_id'])) {
                $errors['cliente_id'] = 'El cliente seleccionado no existe';
            }
        }

        // Validar estado_tipo_id (solo si se está cambiando)
        if (isset($data['estado_tipo_id']) && !empty($data['estado_tipo_id'])) {
            if (!$this->isValidEstadoTipoId($data['estado_tipo_id'])) {
                $errors['estado_tipo_id'] = 'El estado seleccionado no existe';
            }
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
     * Solo procesa campos de la tabla 'usuarios'
     */
    public function validateUserDataComplete(array $data): array
    {
        $errors = [];
        $sanitizedData = [];

        // Validar persona_id
        if (empty($data['persona_id'])) {
            $errors['persona_id'] = 'La persona es requerida';
        } else {
            $sanitizedData['persona_id'] = (int)$data['persona_id'];
            if (!$this->isValidPersonaId($sanitizedData['persona_id'])) {
                $errors['persona_id'] = 'La persona seleccionada no existe';
            }
        }

        // Validar y sanitizar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido';
        } else {
            $sanitizedData['email'] = Security::sanitizeInput($data['email']);
            if (!$this->validateEmail($sanitizedData['email'])) {
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
            if (strlen($sanitizedData['nombre_usuario']) < 4) {
                $errors['nombre_usuario'] = 'El nombre de usuario debe tener al menos 4 caracteres';
            } elseif (!$this->isUsernameAvailable($sanitizedData['nombre_usuario'])) {
                $errors['nombre_usuario'] = 'El nombre de usuario ya existe';
            }
        }

        // Validar contraseña
        if (empty($data['password'])) {
            $errors['password'] = 'La contraseña es requerida';
        } else {
            $sanitizedData['password'] = $data['password']; // No sanitizar contraseñas
            $passwordErrors = $this->validatePasswordStrength($data['password']);
            if (!empty($passwordErrors)) {
                $errors['password'] = implode(', ', $passwordErrors);
            }
        }

        // Validar tipo de usuario
        if (empty($data['usuario_tipo_id'])) {
            $errors['usuario_tipo_id'] = 'El tipo de usuario es requerido';
        } else {
            $sanitizedData['usuario_tipo_id'] = (int)$data['usuario_tipo_id'];
            if (!$this->isValidUserTypeId($sanitizedData['usuario_tipo_id'])) {
                $errors['usuario_tipo_id'] = 'El tipo de usuario seleccionado no existe';
            }
        }

        // Validar cliente_id (opcional)
        if (isset($data['cliente_id']) && !empty($data['cliente_id'])) {
            $sanitizedData['cliente_id'] = (int)$data['cliente_id'];
            if (!$this->isValidClienteId($sanitizedData['cliente_id'])) {
                $errors['cliente_id'] = 'El cliente seleccionado no existe';
            }
        }

        // Validar estado_tipo_id (opcional)
        if (isset($data['estado_tipo_id']) && !empty($data['estado_tipo_id'])) {
            $sanitizedData['estado_tipo_id'] = (int)$data['estado_tipo_id'];
            if (!$this->isValidEstadoTipoId($sanitizedData['estado_tipo_id'])) {
                $errors['estado_tipo_id'] = 'El estado seleccionado no existe';
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

    /**
     * Verificar si existe una persona con el ID dado
     */
    public function isValidPersonaId(int $personaId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM personas WHERE id = ? AND estado_tipo_id != 3";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$personaId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error verificando persona_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un tipo de usuario con el ID dado
     */
    public function isValidUserTypeId(int $userTypeId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuario_tipos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userTypeId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error verificando usuario_tipo_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un cliente con el ID dado
     */
    public function isValidClienteId(int $clienteId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM clientes WHERE id = ? AND estado_tipo_id != 3";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$clienteId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error verificando cliente_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe un estado tipo con el ID dado
     */
    public function isValidEstadoTipoId(int $estadoTipoId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM estado_tipos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$estadoTipoId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error verificando estado_tipo_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar datos de persona (para tabla 'personas')
     * Esta función puede usarse cuando se necesite validar una persona nueva
     */
    public function validatePersonaData(array $data, ?int $excludePersonaId = null): array
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
        } elseif (!$this->isRutAvailable($data['rut'], $excludePersonaId)) {
            $errors['rut'] = 'El RUT ya está registrado';
        }

        // Validar teléfono (opcional)
        if (!empty($data['telefono']) && !preg_match('/^[+]?[0-9\s\-\(\)]{8,15}$/', $data['telefono'])) {
            $errors['telefono'] = 'El formato del teléfono no es válido';
        }

        // Validar dirección (opcional)
        if (!empty($data['direccion']) && strlen($data['direccion']) > 255) {
            $errors['direccion'] = 'La dirección es demasiado larga (máximo 255 caracteres)';
        }

        // Validar estado_tipo_id (opcional)
        if (!empty($data['estado_tipo_id']) && !$this->isValidEstadoTipoId($data['estado_tipo_id'])) {
            $errors['estado_tipo_id'] = 'El estado seleccionado no existe';
        }

        return $errors;
    }
}
