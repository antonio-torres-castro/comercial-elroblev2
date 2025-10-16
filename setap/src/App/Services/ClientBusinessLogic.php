<?php

namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

/**
 * Servicio especializado en lógica de negocio de clientes
 * Responsabilidad única: Gestionar reglas de negocio específicas de clientes
 */
class ClientBusinessLogic
{
    private $db;
    private $personaService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->personaService = new PersonaService();
    }

    /**
     * Validar lógica de usuario cliente según reglas de negocio
     */
    public function validateClientUserLogic(array $data): array
    {
        $errors = [];

        if (empty($data['usuario_tipo_id'])) {
            return $errors; // No se puede validar sin tipo de usuario
        }

        $tipoUsuario = $this->getUserTypeName((int)$data['usuario_tipo_id']);

        // GAP 2: Usuarios de empresa propietaria NO deben tener cliente_id
        if (in_array($tipoUsuario, ['admin', 'planner', 'supervisor', 'executor'])) {
            if (!empty($data['cliente_id'])) {
                $errors['cliente_id'] = "Usuarios tipo '$tipoUsuario' no deben tener cliente asignado";
            }
        }

        // GAP 1: Usuarios de cliente deben tener cliente_id y validaciones especiales
        if (in_array($tipoUsuario, ['client', 'counterparty'])) {
            if (empty($data['cliente_id'])) {
                $errors['cliente_id'] = "Usuario tipo '$tipoUsuario' debe tener un cliente asignado";
            } else {
                $clientId = (int)$data['cliente_id'];

                // Validar que el cliente existe
                if (!$this->clientExists($clientId)) {
                    $errors['cliente_id'] = 'El cliente seleccionado no existe';
                } else {
                    // Validaciones especiales según tipo de usuario
                    if ($tipoUsuario === 'client') {
                        // GAP 1: Usuario 'client' debe tener mismo RUT que empresa
                        if (!empty($data['rut']) && !$this->personaService->canBeClientUser($data['rut'], $clientId)) {
                            $errors['rut'] = 'El RUT de la persona debe coincidir con el RUT del cliente seleccionado';
                        }
                    }
                    // Nota: La validación de counterparty se hará después de crear la persona
                }
            }
        }

        return $errors;
    }

    /**
     * Determinar cliente_id según el tipo de usuario y lógica de negocio
     */
    public function determineClienteId(array $data): ?int
    {
        $tipoUsuario = $this->getUserTypeName($data['usuario_tipo_id']);

        // Usuarios de la empresa propietaria NO deben tener cliente_id
        if (in_array($tipoUsuario, ['admin', 'planner', 'supervisor', 'executor'])) {
            return null;
        }

        // Usuarios de cliente deben tener cliente_id
        if (in_array($tipoUsuario, ['client', 'counterparty'])) {
            if (empty($data['cliente_id'])) {
                throw new Exception("Usuario tipo '$tipoUsuario' debe tener cliente_id asignado");
            }
            return (int)$data['cliente_id'];
        }

        return null;
    }

    /**
     * Validar que un usuario tipo 'counterparty' después de crear la persona
     */
    public function validateCounterpartyAfterPersonaCreation(int $personaId, int $clientId): bool
    {
        return $this->personaService->canBeCounterparty($personaId, $clientId);
    }

    /**
     * Obtener clientes disponibles para asignación
     */
    public function getAvailableClients(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, razon_social, rut
                FROM clientes
                WHERE estado_tipo_id = 2
                ORDER BY razon_social
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo clientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener información completa de un cliente
     */
    public function getClientInfo(int $clientId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, et.nombre as estado_nombre
                FROM clientes c
                INNER JOIN estado_tipos et ON c.estado_tipo_id = et.id
                WHERE c.id = ?
            ");
            $stmt->execute([$clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("Error obteniendo información del cliente: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si un cliente puede tener usuarios asignados
     */
    public function canClientHaveUsers(int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT estado_tipo_id
                FROM clientes
                WHERE id = ?
            ");
            $stmt->execute([$clientId]);
            $status = $stmt->fetchColumn();

            // Solo clientes activos (1) o en proceso (2) pueden tener usuarios
            return in_array($status, [1, 2]);
        } catch (Exception $e) {
            error_log("Error verificando estado del cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener usuarios de un cliente específico
     */
    public function getClientUsers(int $clientId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.nombre_usuario, u.email,
                       p.nombre as nombre_completo, p.rut,
                       ut.nombre as tipo_usuario,
                       et.nombre as estado
                FROM usuarios u
                INNER JOIN personas p ON u.persona_id = p.id
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                INNER JOIN estado_tipos et ON u.estado_tipo_id = et.id
                WHERE u.cliente_id = ?
                ORDER BY p.nombre
            ");
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo usuarios del cliente: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar reglas de negocio para cambio de cliente
     */
    public function validateClientChange(int $userId, int $newClientId): array
    {
        $errors = [];

        try {
            // Obtener información actual del usuario
            $stmt = $this->db->prepare("
                SELECT u.usuario_tipo_id, ut.nombre as tipo_usuario, u.cliente_id
                FROM usuarios u
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userInfo) {
                $errors['usuario'] = 'Usuario no encontrado';
                return $errors;
            }

            // Si es usuario de empresa, no puede tener cliente
            if (in_array($userInfo['tipo_usuario'], ['admin', 'planner', 'supervisor', 'executor'])) {
                $errors['cliente_id'] = 'Usuarios de empresa no pueden tener cliente asignado';
                return $errors;
            }

            // Verificar que el nuevo cliente existe y está activo
            if (!$this->canClientHaveUsers($newClientId)) {
                $errors['cliente_id'] = 'El cliente seleccionado no está disponible para asignación de usuarios';
            }

            // Validaciones específicas por tipo de usuario
            if ($userInfo['tipo_usuario'] === 'client') {
                // Para usuarios client, verificar compatibilidad de RUT
                $stmt = $this->db->prepare("
                    SELECT p.rut
                    FROM personas p
                    INNER JOIN usuarios u ON p.id = u.persona_id
                    WHERE u.id = ?
                ");
                $stmt->execute([$userId]);
                $personRut = $stmt->fetchColumn();

                if ($personRut && !$this->personaService->canBeClientUser($personRut, $newClientId)) {
                    $errors['cliente_id'] = 'El RUT de la persona no coincide con el RUT del cliente seleccionado';
                }
            }
        } catch (Exception $e) {
            error_log("Error validando cambio de cliente: " . $e->getMessage());
            $errors['sistema'] = 'Error interno del sistema';
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
            error_log("Error obteniendo tipo de usuario: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Verificar si un cliente existe
     */
    private function clientExists(int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE id = ? AND estado_tipo_id IN (1, 2)");
            $stmt->execute([$clientId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error verificando cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar reporte de usuarios por cliente
     */
    public function generateClientUsersReport(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.razon_social as cliente,
                       COUNT(u.id) as total_usuarios,
                       SUM(CASE WHEN ut.nombre = 'client' THEN 1 ELSE 0 END) as usuarios_client,
                       SUM(CASE WHEN ut.nombre = 'counterparty' THEN 1 ELSE 0 END) as usuarios_counterparty,
                       SUM(CASE WHEN u.estado_tipo_id = 1 THEN 1 ELSE 0 END) as usuarios_activos
                FROM clientes c
                LEFT JOIN usuarios u ON c.id = u.cliente_id
                LEFT JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE c.estado_tipo_id = 2
                GROUP BY c.id, c.razon_social
                ORDER BY c.razon_social
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error generando reporte: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar lógica de negocio específica para usuarios relacionados con clientes
     */
    public function validateClientLogic(array $data): array
    {
        $errors = [];

        if (empty($data['usuario_tipo_id'])) {
            return $errors; // No se puede validar sin tipo de usuario
        }

        $tipoUsuario = $this->getUserTypeName((int)$data['usuario_tipo_id']);

        // GAP 2: Usuarios de empresa propietaria NO deben tener cliente_id
        if (in_array($tipoUsuario, ['admin', 'planner', 'supervisor', 'executor'])) {
            if (!empty($data['cliente_id'])) {
                $errors['cliente_id'] = "Usuarios tipo '$tipoUsuario' no deben tener cliente asignado";
            }
        }

        // GAP 1: Usuarios de cliente deben tener cliente_id y validaciones especiales
        if (in_array($tipoUsuario, ['client', 'counterparty'])) {
            if (empty($data['cliente_id'])) {
                $errors['cliente_id'] = "Usuario tipo '$tipoUsuario' debe tener un cliente asignado";
            } else {
                $clientId = (int)$data['cliente_id'];

                // Validar que el cliente existe
                if (!$this->clientExists($clientId)) {
                    $errors['cliente_id'] = 'El cliente seleccionado no existe';
                } else {
                    // Validaciones especiales según tipo de usuario
                    if ($tipoUsuario === 'client') {
                        // GAP 1: Usuario 'client' debe tener mismo RUT que empresa
                        if (!empty($data['rut'])) {
                            // Esta validación debe ser delegada al modelo User
                            // Se implementará cuando se refactorice el modelo
                        }
                    }
                    // Nota: La validación de counterparty se hará después de crear la persona
                }
            }
        }

        return $errors;
    }
}
