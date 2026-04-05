<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use PDO;
use PDOException;
use Exception;

class Espacios
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las regiones
     */
    public function getRegiones(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre FROM regiones ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Espacios::getRegiones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener provincias por región
     */
    public function getProvincias(int $regionId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre FROM provincia WHERE region_id = ? ORDER BY nombre");
            $stmt->execute([$regionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Espacios::getProvincias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener comunas por provincia
     */
    public function getComunas(int $provinciaId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre FROM comunas WHERE provincia_id = ? ORDER BY nombre");
            $stmt->execute([$provinciaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Espacios::getComunas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener direcciones por proyecto
     */
    public function getDireccionesByProyecto(int $proyectoId): array
    {
        try {
            $sql = "SELECT d.*, c.nombre as comuna_nombre 
                    FROM direcciones d 
                    INNER JOIN comunas c ON d.comuna_id = c.id 
                    WHERE d.proyecto_id = ? 
                    ORDER BY d.calle, d.numero";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proyectoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Espacios::getDireccionesByProyecto: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear una nueva dirección
     */
    public function createDireccion(array $data): int
    {
        try {
            $sql = "INSERT INTO direcciones (proyecto_id, calle, letra, numero, ind_sin_numero, ind_localidad, localidad, referencia, lat, lng, comuna_id) 
                    VALUES (:proyecto_id, :calle, :letra, :numero, :ind_sin_numero, :ind_localidad, :localidad, :referencia, :lat, :lng, :comuna_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Logger::error("Espacios::createDireccion: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener tipos de espacio
     */
    public function getTiposEspacio(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, tipo_espacio_padre_id, nivel_jerarquico, descripcion FROM tipos_espacio ORDER BY nivel_jerarquico, nombre");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Espacios::getTiposEspacio: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener jerarquía de espacios por dirección
     */
    public function getEspaciosByDireccion(int $direccionId): array
    {
        try {
            $sql = "SELECT e.*, te.nombre as tipo_nombre 
                    FROM espacios e 
                    INNER JOIN tipos_espacio te ON e.tipos_espacio_id = te.id 
                    WHERE e.direccion_id = ? 
                    ORDER BY e.nivel, e.orden, e.nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$direccionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Espacios::getEspaciosByDireccion: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar si un espacio ya existe en una dirección con los mismos criterios
     */
    public function existeEspacio(array $data): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM espacios 
                    WHERE direccion_id = :direccion_id 
                    AND (espacio_padre_id = :espacio_padre_id OR (espacio_padre_id IS NULL AND :espacio_padre_id IS NULL))
                    AND nombre = :nombre 
                    AND codigo = :codigo 
                    AND nivel = :nivel";

            $params = [
                ':direccion_id' => $data['direccion_id'],
                ':espacio_padre_id' => $data['espacio_padre_id'],
                ':nombre' => $data['nombre'],
                ':codigo' => $data['codigo'],
                ':nivel' => $data['nivel']
            ];

            if (isset($data['id'])) {
                $sql .= " AND id != :id";
                $params[':id'] = $data['id'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Logger::error("Espacios::existeEspacio: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear un nuevo espacio
     */
    public function createEspacio(array $data): int
    {
        try {
            $sql = "INSERT INTO espacios (direccion_id, espacio_padre_id, nombre, tipos_espacio_id, codigo, descripcion, nivel, orden) 
                    VALUES (:direccion_id, :espacio_padre_id, :nombre, :tipos_espacio_id, :codigo, :descripcion, :nivel, :orden)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Logger::error("Espacios::createEspacio: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar un espacio
     */
    public function updateEspacio(array $data): bool
    {
        try {
            $sql = "UPDATE espacios SET 
                    espacio_padre_id = :espacio_padre_id, 
                    nombre = :nombre, 
                    tipos_espacio_id = :tipos_espacio_id, 
                    codigo = :codigo, 
                    descripcion = :descripcion, 
                    nivel = :nivel, 
                    orden = :orden 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            Logger::error("Espacios::updateEspacio: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un espacio (y validar que no tenga hijos)
     */
    public function deleteEspacio(int $id): bool
    {
        try {
            // Validar hijos
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM espacios WHERE espacio_padre_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("No se puede eliminar un espacio que tiene sub-espacios asociados.");
            }

            $stmt = $this->db->prepare("DELETE FROM espacios WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            Logger::error("Espacios::deleteEspacio: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener un espacio por ID
     */
    public function getEspacioById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM espacios WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Logger::error("Espacios::getEspacioById: " . $e->getMessage());
            return null;
        }
    }
}
