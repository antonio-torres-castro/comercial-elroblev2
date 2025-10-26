<?php

namespace App\Services;

use App\Config\Database;
use App\Helpers\Security;
use App\Helpers\Logger;

use PDO;
use Exception;

/**
 * Servicio especializado en validaciones del mantenedor de usuarios
 * Responsabilidad única: Validar datos específicos para la gestión de usuarios
 */
class UserValidationService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Validar datos para creación de usuario (sin datos de persona)
     */
    public function validateUserCreationData(array $data): array
    {
        $errors = [];

        // Validar persona_id
        if (empty($data['persona_id'])) {
            $errors['persona_id'] = 'Debe seleccionar una persona';
        } elseif (!$this->isPersonaAvailable($data['persona_id'])) {
            $errors['persona_id'] = 'La persona seleccionada ya tiene un usuario asociado';
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

        // Validaciones específicas según tipo de usuario
        $businessErrors = $this->validateBusinessLogic($data);
        $errors = array_merge($errors, $businessErrors);

        return $errors;
    }

    /**
     * Validar datos para actualización de usuario
     */
    public function validateUserUpdateData(array $data, int $userId): array
    {
        $errors = [];

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

        // Validaciones específicas según tipo de usuario para actualización
        $businessErrors = $this->validateBusinessLogicForUpdate($data, $userId);
        $errors = array_merge($errors, $businessErrors);

        return $errors;
    }

    /**
     * Validar lógica de negocio según tipo de usuario
     */
    private function validateBusinessLogic(array $data): array
    {
        $errors = [];
        $tipoUsuario = $this->getUserTypeName($data['usuario_tipo_id']);

        switch ($tipoUsuario) {
            case 'counterparty':
                // Solo tipo counterparty (id=6) puede tener cliente_id
                if (empty($data['cliente_id'])) {
                    $errors['cliente_id'] = 'Usuario tipo counterparty debe tener un cliente asociado';
                } elseif (!empty($data['persona_id'])) {
                    // Verificar que la persona está registrada como contraparte del cliente
                    if (!$this->isPersonaCounterpartyOfClient($data['persona_id'], $data['cliente_id'])) {
                        $errors['cliente_id'] = 'La persona debe estar registrada como contraparte del cliente seleccionado';
                    }
                }
                break;

            case 'client':
                // Usuario tipo client debe tener cliente_id y RUT debe coincidir
                if (empty($data['cliente_id'])) {
                    $errors['cliente_id'] = 'Usuario tipo client debe tener un cliente asociado';
                } elseif (!empty($data['persona_id'])) {
                    if (!$this->validateClientUserRut($data['persona_id'], $data['cliente_id'])) {
                        $errors['cliente_id'] = 'El RUT de la persona debe coincidir con el RUT del cliente';
                    }
                }
                break;

            case 'admin':
            case 'planner':
            case 'supervisor':
            case 'executor':
                // Usuarios internos NO deben tener cliente_id
                if (!empty($data['cliente_id'])) {
                    $errors['cliente_id'] = "Usuario tipo {$tipoUsuario} no debe tener cliente asociado";
                }
                break;
        }

        return $errors;
    }

    /**
     * Validar lógica de negocio para actualización
     */
    private function validateBusinessLogicForUpdate(array $data, int $userId): array
    {
        $errors = [];
        $tipoUsuario = $this->getUserTypeName($data['usuario_tipo_id']);

        // Obtener persona_id del usuario actual
        $personaId = $this->getPersonaIdByUserId($userId);
        if (!$personaId) {
            $errors['general'] = 'No se pudo obtener información de la persona asociada';
            return $errors;
        }

        // Crear array temporal para validaciones
        $tempData = array_merge($data, ['persona_id' => $personaId]);

        return $this->validateBusinessLogic($tempData);
    }

    /**
     * Verificar si una persona está disponible para crear usuario
     */
    private function isPersonaAvailable(int $personaId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM usuarios u 
                WHERE u.persona_id = ?
            ");
            $stmt->execute([$personaId]);
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            Logger::error("verificando disponibilidad de persona: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si email está disponible
     */
    private function isEmailAvailable(string $email, int $excludeUserId = 0): bool
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
            Logger::error("verificando email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si username está disponible
     */
    private function isUsernameAvailable(string $username, int $excludeUserId = 0): bool
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
            Logger::error("verificando username: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar email
     */
    private function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validar fortaleza de contraseña
     */
    private function validatePasswordStrength(string $password): array
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
     * Obtener nombre del tipo de usuario por ID
     */
    private function getUserTypeName(int $userTypeId): string
    {
        try {
            $stmt = $this->db->prepare("SELECT nombre FROM usuario_tipos WHERE id = ?");
            $stmt->execute([$userTypeId]);
            return $stmt->fetchColumn() ?: '';
        } catch (Exception $e) {
            Logger::error("obteniendo tipo de usuario: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Verificar si persona es contraparte del cliente
     */
    private function isPersonaCounterpartyOfClient(int $personaId, int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM cliente_contrapartes 
                WHERE persona_id = ? AND cliente_id = ? AND estado_tipo_id IN (1, 2)
            ");
            $stmt->execute([$personaId, $clientId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            Logger::error("verificando contraparte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar que RUT de persona coincide con RUT de cliente
     */
    private function validateClientUserRut(int $personaId, int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.rut as persona_rut, c.rut as cliente_rut
                FROM personas p, clientes c
                WHERE p.id = ? AND c.id = ?
            ");
            $stmt->execute([$personaId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return false;
            }

            // Limpiar y comparar RUTs
            $personaRut = preg_replace('/[^0-9kK]/', '', strtolower($result['persona_rut']));
            $clienteRut = preg_replace('/[^0-9kK]/', '', strtolower($result['cliente_rut']));

            return $personaRut === $clienteRut;
        } catch (Exception $e) {
            Logger::error("validando RUT cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener persona_id por user_id
     */
    private function getPersonaIdByUserId(int $userId): ?int
    {
        try {
            $stmt = $this->db->prepare("SELECT persona_id FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            Logger::error("obteniendo persona_id: " . $e->getMessage());
            return null;
        }
    }
}
