<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;

use PDO;
use PDOException;
use Exception;

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
            //dashboard_project_list (in clienteId int, in estadoTipoId int, in tareaTipoId int, in fechaDesde date, in fechaHasta date)
            $sql = "CALL dashboard_project_list(?, ?, ?, ?, ?);";

            $params = [];

            // Aplicar filtros
            $params[] = !isset($filters['cliente_id']) || empty($filters['cliente_id']) ? 0 : $filters['cliente_id'];
            $params[] = !isset($filters['estado_tipo_id']) || empty($filters['estado_tipo_id']) ? 0 : $filters['estado_tipo_id'];
            $params[] = !isset($filters['tarea_tipo_id']) || empty($filters['tarea_tipo_id']) ? 0 : $filters['tarea_tipo_id'];
            $params[] = !isset($filters['fecha_desde']) || empty($filters['fecha_desde']) ? null : $filters['fecha_desde'];
            $params[] = !isset($filters['fecha_hasta']) || empty($filters['fecha_hasta']) ? null : $filters['fecha_hasta'];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Project::getAll error: ' . $e->getMessage());
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
                       c.id as cliente_id, c.razon_social as cliente_nombre, c.rut as cliente_rut,
                       c.direccion as cliente_direccion, c.telefono as cliente_telefono,
                       tt.id as tarea_tipo_id, tt.nombre as tipo_tarea,
                       et.id as estado_tipo_id, et.nombre as estado_nombre,
                       cc.id as contraparte_id,
                       CONCAT(per.nombre, ' (', per.rut, ')') as contraparte_nombre,
                       cc.email as contraparte_email, cc.telefono as contraparte_telefono,
                       cc.cargo as contraparte_cargo
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON p.estado_tipo_id = et.id
                INNER JOIN cliente_contrapartes cc ON p.contraparte_id = cc.id
                INNER JOIN personas per ON cc.persona_id = per.id
                WHERE p.id = ? AND p.estado_tipo_id != 4
            ");

            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Project::find error: ' . $e->getMessage());
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
                $data['estado_tipo_id'] ?? 1, // Creado por defecto
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
            Logger::error('Project::create error: ' . $e->getMessage());
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
            Logger::error('Project::update error: ' . $e->getMessage());
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
            Logger::error('Project::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todas las tareas de un proyecto
     */
    public function countProjectTasks(int $projectId, ?string $fechaInicio, ?string $fechaFin, ?int $estadoTipoId = null): int
    {
        try {
            $sql = "SELECT Count(1) as total
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN usuarios p ON pt.planificador_id = p.id
                LEFT JOIN usuarios e ON pt.ejecutor_id = e.id
                LEFT JOIN usuarios s ON pt.supervisor_id = s.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                LEFT JOIN historial_tareas ht ON pt.id = ht.proyecto_tarea_id
                    AND ht.id = (SELECT MAX(id) FROM historial_tareas WHERE proyecto_tarea_id = pt.id)
                WHERE pt.proyecto_id = ? AND pt.estado_tipo_id != 4 ";

            $params = [];
            $params[] = $projectId;

            if (!empty($fechaInicio)) {
                $sql .= " and pt.fecha_inicio >= ? ";
                $params[] = $fechaInicio;
            }
            if (!empty($fechaFin)) {
                $sql .= " and pt.fecha_inicio <= ? ";
                $params[] = $fechaFin;
            }
            if (!empty($estadoTipoId)) {
                $sql .= " and pt.estado_tipo_id = ? ";
                $params[] = $estadoTipoId;
            }

            $sql .= " ORDER BY pt.prioridad DESC, pt.fecha_inicio ASC ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            Logger::error('Project::getProjectTasks error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener todas las tareas de un proyecto
     */
    public function getProjectTasks(int $projectId, int $limit = 7, int $offset = 0, ?string $fechaInicio = null, ?string $fechaFin = null, ?int $estadoTipoId = null): array
    {
        try {
            $sql = "SELECT pt.id, pt.proyecto_id, pt.tarea_id, 
                pt.planificador_id, pt.ejecutor_id, pt.supervisor_id, 
                pt.fecha_inicio, pt.duracion_horas, pt.fecha_fin, pt.prioridad, 
                pt.estado_tipo_id, pt.fecha_Creado, pt.fecha_modificacion,
                t.nombre as tarea_nombre, t.descripcion as tarea_descripcion,
                p.nombre_usuario as planificador_nombre,
                e.nombre_usuario as ejecutor_nombre,
                s.nombre_usuario as supervisor_nombre,
                et.nombre as estado_nombre,
                ht.fecha_evento, ht.comentario
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN usuarios p ON pt.planificador_id = p.id
                LEFT JOIN usuarios e ON pt.ejecutor_id = e.id
                LEFT JOIN usuarios s ON pt.supervisor_id = s.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                LEFT JOIN historial_tareas ht ON pt.id = ht.proyecto_tarea_id
                    AND ht.id = (SELECT MAX(id) FROM historial_tareas WHERE proyecto_tarea_id = pt.id)
                WHERE pt.proyecto_id = ? AND pt.estado_tipo_id != 4 ";

            $params = [];
            $params[] = $projectId;

            if (!empty($fechaInicio)) {
                $sql .= " and pt.fecha_inicio >= ? ";
                $params[] = $fechaInicio;
            }
            if (!empty($fechaFin)) {
                $sql .= " and pt.fecha_inicio <= ? ";
                $params[] = $fechaFin;
            }
            if (!empty($estadoTipoId)) {
                $sql .= " and pt.estado_tipo_id = ? ";
                $params[] = $estadoTipoId;
            }

            $sql .= " ORDER BY pt.prioridad DESC, pt.fecha_inicio ASC, pt.id LIMIT ? OFFSET ? ";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Project::getProjectTasks error: ' . $e->getMessage());
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
                WHERE pt.proyecto_id = ? AND pt.estado_tipo_id in (2, 5, 6, 7, 8)
            ");

            $stmt->execute([$projectId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calcular progreso
            $total = $stats['total_tareas'] ?? 0;
            $completadas = $stats['tareas_aprobadas'] ?? 0;
            $stats['progreso_porcentaje'] = $total > 0 ? round(($completadas / $total) * 100, 2) : 0;

            return $stats;
        } catch (PDOException $e) {
            Logger::error('Project::getProjectStats error: ' . $e->getMessage());
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
            Logger::error('Project::getProjectHolidays error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener feriados de un proyecto
     */
    public function getProjectHolidaysForViewManager(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT fecha, CASE DAYOFWEEK(fecha)
                           WHEN 1 THEN 'Domingo'
                           WHEN 2 THEN 'Lunes'
                           WHEN 3 THEN 'Martes'
                           WHEN 4 THEN 'Miércoles'
                           WHEN 5 THEN 'Jueves'
                           WHEN 6 THEN 'Viernes'
                           WHEN 7 THEN 'Sábado'
                       END as dia_semana
                FROM proyecto_feriados
                WHERE proyecto_id = ? AND estado_tipo_id != 4
                AND DAYOFWEEK(fecha) in (2, 3, 4, 5, 6)
                ORDER BY fecha
            ");

            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Project::getProjectHolidays error: ' . $e->getMessage());
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
            Logger::error('Project::changeStatus error: ' . $e->getMessage());
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
     * Obtener asignaciones usuario-grupo de un proyecto (excluye eliminados)
     */
    public function getUsuariosGrupo(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT pug.id,
                        pug.proyecto_id,
                        pug.usuario_id,
                        u.nombre_usuario AS username,
                        u.estado_tipo_id AS usuario_estado_tipo_id,
                        pug.grupo_id,
                        gt.nombre AS grupo_nombre,
                        pug.estado_tipo_id
                 FROM proyecto_usuarios_grupo pug
                 INNER JOIN usuarios u ON pug.usuario_id = u.id
                 INNER JOIN grupo_tipos gt ON pug.grupo_id = gt.id
                 WHERE pug.proyecto_id = ? AND pug.estado_tipo_id != 4
                 ORDER BY u.nombre_usuario"
            );
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            \App\Helpers\Logger::error('Project::getUsuariosGrupo error: ' . $e->getMessage());
            return [];
        }
    }

    /** Comprobar si el proyecto está activo */
    public function isActive(int $projectId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT estado_tipo_id FROM proyectos WHERE id = ?");
            $stmt->execute([$projectId]);
            return (int)$stmt->fetchColumn() === 2;
        } catch (Exception $e) {
            \App\Helpers\Logger::error('Project::isActive error: ' . $e->getMessage());
            return false;
        }
    }

    /** Crear asignación usuario-grupo (evitando duplicados) */
    public function addUsuarioGrupo(int $projectId, int $usuarioId, int $grupoId): array
    {
        try {
            // validar duplicidad
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyecto_usuarios_grupo WHERE proyecto_id = ? AND usuario_id = ? AND grupo_id = ? AND estado_tipo_id != 4");
            $stmt->execute([$projectId, $usuarioId, $grupoId]);
            if ((int)$stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Ya existe la asociación'];
            }

            $stmt = $this->db->prepare("INSERT INTO proyecto_usuarios_grupo (proyecto_id, usuario_id, grupo_id, estado_tipo_id) VALUES (?, ?, ?, 2)");
            $ok = $stmt->execute([$projectId, $usuarioId, $grupoId]);
            return $ok ? ['success' => true] : ['success' => false, 'message' => 'Error al crear'];
        } catch (Exception $e) {
            \App\Helpers\Logger::error('Project::addUsuarioGrupo error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno'];
        }
    }

    /** Actualizar grupo de una asignación */
    public function updateUsuarioGrupo(int $id, int $grupoId): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE proyecto_usuarios_grupo SET grupo_id = ? WHERE id = ? AND estado_tipo_id != 4");
            $ok = $stmt->execute([$grupoId, $id]);
            return $ok ? ['success' => true] : ['success' => false, 'message' => 'Error al actualizar'];
        } catch (Exception $e) {
            \App\Helpers\Logger::error('Project::updateUsuarioGrupo error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno'];
        }
    }

    /** Soft delete de una asignación */
    public function deleteUsuarioGrupo(int $id): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE proyecto_usuarios_grupo SET estado_tipo_id = 4 WHERE id = ? AND estado_tipo_id != 4");
            $ok = $stmt->execute([$id]);
            return $ok ? ['success' => true] : ['success' => false, 'message' => 'Error al eliminar'];
        } catch (Exception $e) {
            \App\Helpers\Logger::error('Project::deleteUsuarioGrupo error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno'];
        }
    }

    /** Obtener todos los tipos de grupo (para selector) */
    public function getGrupoTipos(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre FROM grupo_tipos ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            \App\Helpers\Logger::error('Project::getGrupoTipos error: ' . $e->getMessage());
            return [];
        }
    }

    /** Obtener todos los usuarios (para selector) */
    public function getAllUsers(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre_usuario FROM usuarios WHERE estado_tipo_id != 4 ORDER BY nombre_usuario");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            \App\Helpers\Logger::error('Project::getAllUsers error: ' . $e->getMessage());
            return [];
        }
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
                       c.razon_social as cliente_nombre,
                       et.nombre as estado_nombre
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN estado_tipos et ON p.estado_tipo_id = et.id
                LEFT JOIN cliente_contrapartes cc ON p.contraparte_id = cc.id
                LEFT JOIN personas per ON cc.persona_id = per.id
                WHERE p.estado_tipo_id != 4
                AND (
                    c.razon_social LIKE ? OR
                    p.direccion LIKE ? OR
                    per.nombre LIKE ? OR
                    cc.email LIKE ?
                )
                ORDER BY p.fecha_inicio DESC
            ");

            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Project::search error: ' . $e->getMessage());
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
            Logger::error('Project::addHolidays error: ' . $e->getMessage());
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
            Logger::error('Project::removeAllHolidays error: ' . $e->getMessage());
            return false;
        }
    }
}
