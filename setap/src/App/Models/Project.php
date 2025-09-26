<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Project
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los proyectos con información relacionada
     */
    public function getAll(array $filters = []): array
    {
        try {
            $sql = "
                SELECT p.*, 
                       c.nombre as cliente_nombre,
                       tt.nombre as tipo_tarea,
                       et.nombre as estado_nombre,
                       cp.nombre as contraparte_nombre,
                       cp.email as contraparte_email,
                       cp.telefono as contraparte_telefono,
                       COUNT(pt.id) as total_tareas,
                       COUNT(CASE WHEN pt.estado_tipo_id = 8 THEN 1 END) as tareas_completadas
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON p.estado_tipo_id = et.id
                INNER JOIN cliente_contrapartes cp ON p.contraparte_id = cp.id
                LEFT JOIN proyecto_tareas pt ON p.id = pt.proyecto_id AND pt.estado_tipo_id != 4
                WHERE p.estado_tipo_id != 4
            ";

            $params = [];

            // Aplicar filtros
            if (!empty($filters['cliente_id'])) {
                $sql .= " AND p.cliente_id = ?";
                $params[] = $filters['cliente_id'];
            }

            if (!empty($filters['estado_tipo_id'])) {
                $sql .= " AND p.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            if (!empty($filters['tarea_tipo_id'])) {
                $sql .= " AND p.tarea_tipo_id = ?";
                $params[] = $filters['tarea_tipo_id'];
            }

            if (!empty($filters['fecha_desde'])) {
                $sql .= " AND p.fecha_inicio >= ?";
                $params[] = $filters['fecha_desde'];
            }

            if (!empty($filters['fecha_hasta'])) {
                $sql .= " AND p.fecha_inicio <= ?";
                $params[] = $filters['fecha_hasta'];
            }

            $sql .= " GROUP BY p.id ORDER BY p.fecha_inicio DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Project::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un proyecto específico por ID con toda su información
     */
    public function find(int $id): array|false
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       c.id as cliente_id, c.nombre as cliente_nombre, c.rut as cliente_rut,
                       c.direccion as cliente_direccion, c.telefono as cliente_telefono,
                       tt.id as tarea_tipo_id, tt.nombre as tipo_tarea,
                       et.id as estado_tipo_id, et.nombre as estado_nombre,
                       cp.id as contraparte_id, cp.nombre as contraparte_nombre,
                       cp.email as contraparte_email, cp.telefono as contraparte_telefono,
                       cp.cargo as contraparte_cargo
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON p.estado_tipo_id = et.id
                INNER JOIN cliente_contrapartes cp ON p.contraparte_id = cp.id
                WHERE p.id = ? AND p.estado_tipo_id != 4
            ");

            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Project::find error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear un nuevo proyecto
     */
    public function create(array $data): int|false
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO proyectos (
                    cliente_id, direccion, fecha_inicio, fecha_fin, 
                    tarea_tipo_id, estado_tipo_id, contraparte_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $data['cliente_id'],
                $data['direccion'] ?? null,
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                $data['tarea_tipo_id'],
                $data['estado_tipo_id'] ?? 1, // Activo por defecto
                $data['contraparte_id']
            ]);

            if ($result) {
                $projectId = $this->db->lastInsertId();

                // Si hay feriados, agregarlos
                if (!empty($data['feriados']) && is_array($data['feriados'])) {
                    $this->addHolidays($projectId, $data['feriados']);
                }

                $this->db->commit();
                return $projectId;
            }

            $this->db->rollBack();
            return false;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Project::create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar un proyecto
     */
    public function update(int $id, array $data): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE proyectos SET
                    cliente_id = ?, direccion = ?, fecha_inicio = ?, fecha_fin = ?,
                    tarea_tipo_id = ?, estado_tipo_id = ?, contraparte_id = ?,
                    fecha_modificacion = CURRENT_TIMESTAMP
                WHERE id = ? AND estado_tipo_id != 4
            ");

            $result = $stmt->execute([
                $data['cliente_id'],
                $data['direccion'] ?? null,
                $data['fecha_inicio'],
                $data['fecha_fin'] ?? null,
                $data['tarea_tipo_id'],
                $data['estado_tipo_id'],
                $data['contraparte_id'],
                $id
            ]);

            // Si hay nuevos feriados, actualizarlos
            if (isset($data['feriados']) && is_array($data['feriados'])) {
                // Eliminar feriados existentes (eliminación lógica)
                $this->removeAllHolidays($id);
                // Agregar nuevos feriados
                $this->addHolidays($id, $data['feriados']);
            }

            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Project::update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un proyecto (eliminación lógica)
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->beginTransaction();

            // Eliminar proyecto (lógicamente)
            $stmt = $this->db->prepare("
                UPDATE proyectos SET 
                    estado_tipo_id = 4, 
                    fecha_modificacion = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            // Eliminar tareas asociadas (lógicamente)
            $stmt = $this->db->prepare("
                UPDATE proyecto_tareas SET 
                    estado_tipo_id = 4, 
                    fecha_modificacion = CURRENT_TIMESTAMP 
                WHERE proyecto_id = ?
            ");
            $stmt->execute([$id]);

            // Eliminar feriados asociados (lógicamente)
            $stmt = $this->db->prepare("
                UPDATE proyecto_feriados SET 
                    estado_tipo_id = 4
                WHERE proyecto_id = ?
            ");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Project::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todas las tareas de un proyecto
     */
    public function getProjectTasks(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pt.*, 
                       t.nombre as tarea_nombre, t.descripcion as tarea_descripcion,
                       p.nombre_usuario as planificador_nombre,
                       e.nombre_usuario as ejecutor_nombre,
                       s.nombre_usuario as supervisor_nombre,
                       et.nombre as estado_nombre,
                       ht.fecha_inicio_real, ht.fecha_fin_real, ht.observaciones
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN usuarios p ON pt.planificador_id = p.id
                LEFT JOIN usuarios e ON pt.ejecutor_id = e.id
                LEFT JOIN usuarios s ON pt.supervisor_id = s.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                LEFT JOIN historial_tareas ht ON pt.id = ht.proyecto_tarea_id 
                    AND ht.id = (SELECT MAX(id) FROM historial_tareas WHERE proyecto_tarea_id = pt.id)
                WHERE pt.proyecto_id = ? AND pt.estado_tipo_id != 4
                ORDER BY pt.prioridad DESC, pt.fecha_inicio ASC
            ");

            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Project::getProjectTasks error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de un proyecto
     */
    public function getProjectStats(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_tareas,
                    COUNT(CASE WHEN pt.estado_tipo_id = 5 THEN 1 END) as tareas_iniciadas,
                    COUNT(CASE WHEN pt.estado_tipo_id = 6 THEN 1 END) as tareas_terminadas,
                    COUNT(CASE WHEN pt.estado_tipo_id = 7 THEN 1 END) as tareas_rechazadas,
                    COUNT(CASE WHEN pt.estado_tipo_id = 8 THEN 1 END) as tareas_aprobadas,
                    SUM(pt.duracion_horas) as horas_planificadas,
                    AVG(pt.duracion_horas) as promedio_horas_tarea,
                    MIN(pt.fecha_inicio) as fecha_inicio_primera_tarea,
                    MAX(pt.fecha_inicio) as fecha_inicio_ultima_tarea
                FROM proyecto_tareas pt
                WHERE pt.proyecto_id = ? AND pt.estado_tipo_id != 4
            ");

            $stmt->execute([$projectId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calcular progreso
            $total = $stats['total_tareas'] ?? 0;
            $completadas = $stats['tareas_aprobadas'] ?? 0;
            $stats['progreso_porcentaje'] = $total > 0 ? round(($completadas / $total) * 100, 2) : 0;

            return $stats;
        } catch (PDOException $e) {
            error_log('Project::getProjectStats error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener feriados de un proyecto
     */
    public function getProjectHolidays(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT fecha 
                FROM proyecto_feriados 
                WHERE proyecto_id = ? AND estado_tipo_id != 4
                ORDER BY fecha
            ");

            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('Project::getProjectHolidays error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cambiar estado de un proyecto
     */
    public function changeStatus(int $projectId, int $newStatusId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE proyectos SET 
                    estado_tipo_id = ?, 
                    fecha_modificacion = CURRENT_TIMESTAMP 
                WHERE id = ? AND estado_tipo_id != 4
            ");

            return $stmt->execute([$newStatusId, $projectId]);
        } catch (PDOException $e) {
            error_log('Project::changeStatus error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener proyectos por cliente
     */
    public function getByClient(int $clientId): array
    {
        return $this->getAll(['cliente_id' => $clientId]);
    }

    /**
     * Obtener proyectos activos
     */
    public function getActive(): array
    {
        return $this->getAll(['estado_tipo_id' => 2]); // 2 = activo
    }

    /**
     * Obtener proyectos por rango de fechas
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        return $this->getAll([
            'fecha_desde' => $startDate,
            'fecha_hasta' => $endDate
        ]);
    }

    /**
     * Buscar proyectos por término
     */
    public function search(string $term): array
    {
        try {
            $searchTerm = "%{$term}%";

            $stmt = $this->db->prepare("
                SELECT DISTINCT p.*, 
                       c.nombre as cliente_nombre,
                       et.nombre as estado_nombre
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN estado_tipos et ON p.estado_tipo_id = et.id
                LEFT JOIN cliente_contrapartes cp ON p.contraparte_id = cp.id
                WHERE p.estado_tipo_id != 4 
                AND (
                    c.nombre LIKE ? OR
                    p.direccion LIKE ? OR
                    cp.nombre LIKE ? OR
                    cp.email LIKE ?
                )
                ORDER BY p.fecha_inicio DESC
            ");

            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Project::search error: ' . $e->getMessage());
            return [];
        }
    }

    // ============ MÉTODOS PRIVADOS ============

    /**
     * Agregar feriados a un proyecto
     */
    private function addHolidays(int $projectId, array $holidays): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO proyecto_feriados (proyecto_id, fecha, estado_tipo_id) 
                VALUES (?, ?, 1)
            ");

            foreach ($holidays as $holiday) {
                $stmt->execute([$projectId, $holiday]);
            }

            return true;
        } catch (PDOException $e) {
            error_log('Project::addHolidays error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remover todos los feriados de un proyecto (eliminación lógica)
     */
    private function removeAllHolidays(int $projectId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE proyecto_feriados SET estado_tipo_id = 4 
                WHERE proyecto_id = ?
            ");

            return $stmt->execute([$projectId]);
        } catch (PDOException $e) {
            error_log('Project::removeAllHolidays error: ' . $e->getMessage());
            return false;
        }
    }
}
