<?php

namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

/**
 * Servicio especializado en lógica de negocio de personas
 * Responsabilidad única: Gestionar operaciones de personas
 */
class PersonaService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crear una nueva persona
     */
    public function createPersona(array $data): ?int
    {
        try {
            $sql = "
                INSERT INTO personas (nombre, rut, telefono, direccion, estado_tipo_id, fecha_Creado)
                VALUES (?, ?, ?, ?, 1, NOW())
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nombre'],
                $this->cleanRut($data['rut']),
                $data['telefono'] ?? '',
                $data['direccion'] ?? ''
            ]);

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando persona: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar datos de una persona
     */
    public function updatePersona(int $personaId, array $data): bool
    {
        try {
            $sql = "
                UPDATE personas
                SET nombre = ?, telefono = ?, direccion = ?, fecha_modificacion = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['nombre'],
                $data['telefono'] ?? '',
                $data['direccion'] ?? '',
                $personaId
            ]);
        } catch (Exception $e) {
            error_log("Error actualizando persona: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener persona por ID
     */
    public function getPersonaById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM personas WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("Error obteniendo persona: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener persona por RUT
     */
    public function getPersonaByRut(string $rut): ?array
    {
        try {
            $cleanRut = $this->cleanRut($rut);
            $sql = "SELECT * FROM personas WHERE rut = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cleanRut]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("Error obteniendo persona por RUT: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si una persona puede ser usuario cliente
     */
    public function canBeClientUser(string $personRut, int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT rut FROM clientes WHERE id = ?");
            $stmt->execute([$clientId]);
            $clientRut = $stmt->fetchColumn();

            if (!$clientRut) {
                return false;
            }

            // Comparar RUTs limpios
            $cleanPersonRut = $this->cleanRut($personRut);
            $cleanClientRut = $this->cleanRut($clientRut);

            return $cleanPersonRut === $cleanClientRut;
        } catch (Exception $e) {
            error_log("Error validando RUT de cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si una persona puede ser contraparte
     */
    public function canBeCounterparty(int $personaId, int $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM cliente_contrapartes
                WHERE persona_id = ? AND cliente_id = ? AND estado_tipo_id != 4
            ");
            $stmt->execute([$personaId, $clientId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error validando contraparte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear relación persona-usuario
     */
    public function linkPersonaToUser(int $personaId, int $userId): bool
    {
        try {
            $sql = "UPDATE personas SET usuario_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $personaId]);
        } catch (Exception $e) {
            error_log("Error vinculando persona a usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener contrapartes de un cliente
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
        } catch (Exception $e) {
            error_log("Error obteniendo contrapartes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpiar RUT (remover puntos, guiones y espacios)
     */
    private function cleanRut(string $rut): string
    {
        return preg_replace('/[^0-9kK]/', '', strtolower($rut));
    }

    /**
     * Formatear RUT para mostrar
     */
    public function formatRut(string $rut): string
    {
        $cleanRut = $this->cleanRut($rut);
        if (strlen($cleanRut) < 2) {
            return $rut;
        }

        $dv = strtoupper(substr($cleanRut, -1));
        $numero = substr($cleanRut, 0, -1);

        // Agregar puntos cada 3 dígitos desde la derecha
        $numero = strrev(chunk_split(strrev($numero), 3, '.'));
        $numero = rtrim($numero, '.');

        return $numero . '-' . $dv;
    }

    /**
     * Validar integridad de datos de persona
     */
    public function validatePersonaIntegrity(array $data): array
    {
        $errors = [];

        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es requerido';
        }

        if (empty($data['rut'])) {
            $errors['rut'] = 'El RUT es requerido';
        }

        // Validar formato de teléfono si se proporciona
        if (!empty($data['telefono']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{8,15}$/', $data['telefono'])) {
            $errors['telefono'] = 'El formato del teléfono no es válido';
        }

        return $errors;
    }
}
