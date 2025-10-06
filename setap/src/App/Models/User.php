<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;
use Exception;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los usuarios con información relacionada
     */
    public function getAll(): array
    {
        try {
            $sql = "
                SELECT u.id, u.nombre_usuario, u.email, u.fecha_Creado, u.cliente_id,
                       p.nombre as nombre_completo, p.rut, p.telefono, p.direccion,
                       ut.nombre as rol, ut.id as usuario_tipo_id,
                       et.nombre as estado,
                       c.razon_social as cliente_nombre
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                INNER JOIN estado_tipos et ON u.estado_tipo_id = et.id /*siempre tiene un estado el registro*/
                LEFT JOIN clientes c ON u.cliente_id = c.id
                ORDER BY u.fecha_Creado DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en User::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear un nuevo usuario (solo tabla usuarios, persona debe existir)
     */
    public function create(array $data): ?int
    {
        try {
            $this->db->beginTransaction();

            // Validar que la persona existe y no tiene usuario asociado
            if (!$this->isPersonaAvailableForUser($data['persona_id'])) {
                throw new Exception('La persona seleccionada ya tiene un usuario asociado o no existe');
            }

            // Validar reglas de negocio según tipo de usuario
            $this->validateBusinessRules($data);

            // Crear usuario
            $userId = $this->createUsuario($data, $data['persona_id']);
            if (!$userId) {
                throw new Exception('Error creando usuario');
            }

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en User::create: " . $e->getMessage());
            throw $e; // Re-lanzar para manejo específico en controlador
        }
    }

    /**
     * Crear registro en tabla usuarios
     */
    private function createUsuario(array $data, int $personaId): ?int
    {
        try {
            // Determinar cliente_id según tipo de usuario y validaciones de negocio
            $clienteId = $this->determineClienteId($data);
            
            $sql = "
                INSERT INTO usuarios (
                    persona_id, 
                    nombre_usuario, 
                    email, 
                    clave_hash, 
                    usuario_tipo_id, 
                    cliente_id, 
                    estado_tipo_id, 
                    fecha_inicio,
                    fecha_termino,
                    fecha_Creado
                ) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, NOW())
            ";

            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $personaId,
                $data['nombre_usuario'],
                $data['email'],
                $hashedPassword,
                $data['usuario_tipo_id'],
                $clienteId,
                $data['fecha_inicio'] ?? null,
                $data['fecha_termino'] ?? null
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener usuario por ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "
                SELECT u.*, p.nombre as nombre_completo, p.rut, p.telefono, p.direccion,
                ut.nombre as rol,
                et.nombre as estado, /*atributo de estado desplegado al usuario*/
                c.razon_social as cliente_nombre
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                INNER JOIN estado_tipos et ON et.Id = u.estado_tipo_id /* Siempre tiene un estado */
                LEFT JOIN clientes c ON u.cliente_id = c.id
                WHERE u.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error en User::getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(int $id, array $data): bool
    {
        try {
            $this->db->beginTransaction();

            // Actualizar persona
            $personaSql = "
                UPDATE personas p
                INNER JOIN usuarios u ON p.id = u.persona_id
                SET p.nombre = ?, p.telefono = ?, p.direccion = ?, p.fecha_modificacion = NOW()
                WHERE u.id = ?
            ";

            $stmt = $this->db->prepare($personaSql);
            $stmt->execute([
                $data['nombre'],
                $data['telefono'] ?? '',
                $data['direccion'] ?? '',
                $id
            ]);

            // Determinar cliente_id para la actualización
            $clienteId = $this->determineClienteId($data);

            // Actualizar usuario
            $usuarioSql = "
                UPDATE usuarios 
                SET email = ?, usuario_tipo_id = ?, cliente_id = ?, estado_tipo_id = ?, fecha_modificacion = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($usuarioSql);
            $stmt->execute([
                $data['email'],
                $data['usuario_tipo_id'],
                $clienteId,
                $data['estado_tipo_id'] ?? 1,
                $id
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en User::update: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en User::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar usuario (cambiar estado), el estado id = 4 es eliminado
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE usuarios SET estado_tipo_id = 4, fecha_modificacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error en User::delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar estado del usuario
     */
    public function updateStatus(int $id, int $status): bool
    {
        try {
            $sql = "UPDATE usuarios SET estado_tipo_id = ?, fecha_modificacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Error en User::updateStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar contraseña del usuario
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        try {
            $sql = "UPDATE usuarios SET clave_hash = ?, fecha_modificacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$hashedPassword, $id]);
        } catch (PDOException $e) {
            error_log("Error en User::updatePassword: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar perfil del usuario (campos seguros solamente)
     */
    public function updateProfile(int $id, array $data): bool
    {
        try {
            $this->db->beginTransaction();

            // Actualizar datos de persona (nombre, teléfono, dirección)
            $personaSql = "
                UPDATE personas p
                INNER JOIN usuarios u ON p.id = u.persona_id
                SET p.nombre = ?, p.telefono = ?, p.direccion = ?, p.fecha_modificacion = NOW()
                WHERE u.id = ?
            ";

            $stmt = $this->db->prepare($personaSql);
            $stmt->execute([
                $data['nombre'],
                $data['telefono'] ?? '',
                $data['direccion'] ?? '',
                $id
            ]);

            // Actualizar email del usuario (sin cambiar el rol)
            $usuarioSql = "
                UPDATE usuarios 
                SET email = ?, fecha_modificacion = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($usuarioSql);
            $stmt->execute([
                $data['email'],
                $id
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en User::updateProfile: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener usuario completo por ID
     */
    public function findComplete(int $id): ?array
    {
        try {
            $sql = "
                SELECT u.id, u.nombre_usuario, u.email, u.fecha_Creado, u.cliente_id,
                       p.nombre as nombre_completo, p.rut, p.telefono, p.direccion,
                       ut.nombre as rol, ut.id as usuario_tipo_id,
                       et.nombre as estado,
                       c.razon_social as cliente_nombre
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id 
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                INNER JOIN estado_tipos et ON u.estado_tipo_id = et.id
                LEFT JOIN clientes c ON u.cliente_id = c.id
                WHERE u.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error en User::findComplete: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Determinar cliente_id según el tipo de usuario y lógica de negocio
     * GAP 1 y GAP 2: Solo usuarios 'client' y 'counterparty' deben tener cliente_id
     */
    private function determineClienteId(array $data): ?int
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
     * Obtener nombre del tipo de usuario por ID
     */
    private function getUserTypeName(int $userTypeId): string
    {
        try {
            $stmt = $this->db->prepare("SELECT nombre FROM usuario_tipos WHERE id = ?");
            $stmt->execute([$userTypeId]);
            return $stmt->fetchColumn() ?: '';
        } catch (PDOException $e) {
            error_log("Error obteniendo tipo de usuario: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Validar que un usuario tipo 'client' tenga el mismo RUT que el cliente
     * GAP 1: Logica de usuarios cliente
     */
    public function validateClientUserRut(string $personRut, int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT rut FROM clientes WHERE id = ?");
            $stmt->execute([$clientId]);
            $clientRut = $stmt->fetchColumn();
            
            if (!$clientRut) {
                return false;
            }
            
            // Limpiar y comparar RUTs
            $cleanPersonRut = preg_replace('/[^0-9kK]/', '', strtolower($personRut));
            $cleanClientRut = preg_replace('/[^0-9kK]/', '', strtolower($clientRut));
            
            return $cleanPersonRut === $cleanClientRut;
        } catch (PDOException $e) {
            error_log("Error validando RUT de cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar que un usuario tipo 'counterparty' este en cliente_contrapartes
     * GAP 1: Logica de usuarios contraparte
     */
    public function validateCounterpartyExists(int $personaId, int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM cliente_contrapartes 
                WHERE persona_id = ? AND cliente_id = ? AND estado_tipo_id != 4
            ");
            $stmt->execute([$personaId, $clientId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error validando contraparte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los clientes disponibles para asignacion
     */
    public function getAvailableClients(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, razon_social, rut 
                FROM clientes 
                WHERE estado_tipo_id IN (1, 2) 
                ORDER BY razon_social
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo clientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener contrapartes disponibles para un cliente
     */
    public function getClientCounterparties(int $clientId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cc.id, p.nombre, p.rut, cc.cargo, cc.email
                FROM cliente_contrapartes cc
                INNER JOIN personas p ON cc.persona_id = p.id
                WHERE cc.cliente_id = ? AND cc.estado_tipo_id != 4
                ORDER BY p.nombre
            ");
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo contrapartes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener personas disponibles (que no tienen usuario asociado)
     */
    public function getAvailablePersonas(?string $search = null): array
    {
        try {
            $sql = "
                SELECT p.id, p.rut, p.nombre, p.telefono, p.direccion
                FROM personas p
                LEFT JOIN usuarios u ON p.id = u.persona_id
                WHERE u.persona_id IS NULL 
                AND p.estado_tipo_id IN (1, 2)
            ";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (p.nombre LIKE ? OR p.rut LIKE ?)";
                $searchParam = "%{$search}%";
                $params = [$searchParam, $searchParam];
            }
            
            $sql .= " ORDER BY p.nombre";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo personas disponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si una persona está disponible para crear usuario
     */
    public function isPersonaAvailableForUser(int $personaId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM usuarios u 
                INNER JOIN personas p ON u.persona_id = p.id
                WHERE u.persona_id = ? AND p.estado_tipo_id IN (1, 2)
            ");
            $stmt->execute([$personaId]);
            return $stmt->fetchColumn() == 0;
        } catch (PDOException $e) {
            error_log("Error verificando disponibilidad de persona: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener información completa de una persona por ID
     */
    public function getPersonaById(int $personaId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, rut, nombre, telefono, direccion
                FROM personas 
                WHERE id = ? AND estado_tipo_id IN (1, 2)
            ");
            $stmt->execute([$personaId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error obteniendo persona: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validar reglas de negocio según tipo de usuario
     */
    private function validateBusinessRules(array $data): void
    {
        $tipoUsuario = $this->getUserTypeName($data['usuario_tipo_id']);
        
        // Regla: Solo usuarios tipo 'counterparty' (id=6) pueden tener cliente_id
        if ($tipoUsuario === 'counterparty') {
            if (empty($data['cliente_id'])) {
                throw new Exception("Usuario tipo 'counterparty' debe tener un cliente asociado");
            }
            
            // Verificar que la persona existe como contraparte del cliente
            if (!$this->validateCounterpartyExists($data['persona_id'], $data['cliente_id'])) {
                throw new Exception("La persona debe estar registrada como contraparte del cliente seleccionado");
            }
        }
        
        // Regla: Usuarios internos (admin, planner, supervisor, executor) NO deben tener cliente_id
        if (in_array($tipoUsuario, ['admin', 'planner', 'supervisor', 'executor']) && !empty($data['cliente_id'])) {
            throw new Exception("Usuario tipo '$tipoUsuario' no debe tener cliente asociado");
        }
        
        // Regla: Usuarios tipo 'client' deben tener cliente_id y el RUT debe coincidir
        if ($tipoUsuario === 'client') {
            if (empty($data['cliente_id'])) {
                throw new Exception("Usuario tipo 'client' debe tener un cliente asociado");
            }
            
            $persona = $this->getPersonaById($data['persona_id']);
            if (!$persona || !$this->validateClientUserRut($persona['rut'], $data['cliente_id'])) {
                throw new Exception("El RUT de la persona debe coincidir con el RUT del cliente");
            }
        }
    }

    /**
     * Buscar contrapartes disponibles para un cliente (personas sin usuario)
     */
    public function getAvailableCounterparties(int $clientId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.rut, p.nombre, p.telefono, cc.cargo, cc.email
                FROM cliente_contrapartes cc
                INNER JOIN personas p ON cc.persona_id = p.id
                LEFT JOIN usuarios u ON p.id = u.persona_id
                WHERE cc.cliente_id = ? 
                AND cc.estado_tipo_id IN (1, 2)
                AND u.persona_id IS NULL
                ORDER BY p.nombre
            ");
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo contrapartes disponibles: " . $e->getMessage());
            return [];
        }
    }
}
