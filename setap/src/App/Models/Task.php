<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;

use PDO;
use PDOException;
use Exception;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class Task
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }


    /**
     * Contar total de tareas según los filtros
     */
    public function countAll(array $filters = []): int
    {
        try {
            $uti = $filters['current_usuario_tipo_id'];
            $cu = $filters['current_usuario_id'];
            $params = [];

            $sql = "SELECT Count(distinct pt.id) as total
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN proyectos p ON pt.proyecto_id = p.id
                Inner Join proyecto_usuarios_grupo pug on pug.estado_tipo_id = 2 and pug.proyecto_id = p.id
				Inner Join grupo_tipos gt on gt.id between 1 and 5 and gt.id = pug.grupo_id
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id ";
            $strWhere = " WHERE pug.usuario_id = ? ";
            $params[] = $cu;

            // Filtros
            if (isset($filters['proyecto_id']) && !empty($filters['proyecto_id'])) {
                $strWhere .= " and pt.proyecto_id = ?";
                $params[] = $filters['proyecto_id'];
            }

            if (isset($uti) && $uti > 2) {
                $strWhere .= " AND pt.estado_tipo_id in (2, 5, 6, 7, 8)";
            }

            if (isset($uti) && $uti == 4) {
                $strWhere .= " AND (pt.ejecutor_id is null or pt.ejecutor_id = ?)";
                $params[] = $cu;
            }

            if (isset($filters['estado_tipo_id']) && !empty($filters['estado_tipo_id'])) {
                // Aseguramos que sea un array
                $estadoTipoIds = is_array($filters['estado_tipo_id'])
                    ? $filters['estado_tipo_id']
                    : [$filters['estado_tipo_id']];

                // Eliminamos vacíos o nulos
                $estadoTipoIds = array_filter($estadoTipoIds, fn($v) => $v !== '' && $v !== null);

                if (!empty($estadoTipoIds)) {
                    // Creamos placeholders (?, ?, ?, ...)
                    $placeholders = implode(', ', array_fill(0, count($estadoTipoIds), '?'));
                    // Agregamos la condición con el IN dinámico
                    $strWhere .= " AND pt.estado_tipo_id IN ($placeholders)";
                    // Agregamos todos los IDs al array de parámetros
                    $params = array_merge($params, $estadoTipoIds);
                }
            }

            if (isset($filters['fecha_inicio']) && isset($filters['fecha_fin']) && !empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
                $strWhere .= " AND pt.fecha_inicio between ? and ?";
                $params[] = $filters['fecha_inicio'];
                $params[] = $filters['fecha_fin'];
            }

            if (isset($filters['fecha_inicio']) && !empty($filters['fecha_inicio']) && (!isset($filters['fecha_fin']) || empty($filters['fecha_fin']))) {
                $strWhere .= " AND pt.fecha_inicio >= ?";
                $params[] = $filters['fecha_inicio'];
            }

            if ((!isset($filters['fecha_inicio']) || empty($filters['fecha_inicio'])) && isset($filters['fecha_fin']) && !empty($filters['fecha_fin'])) {
                $strWhere .= " AND pt.fecha_inicio <= ?";
                $params[] = $filters['fecha_fin'];
            }

            $sql .= $strWhere;
            $sql .= " ORDER BY pt.fecha_inicio DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            Logger::error("Task::countAll: " . $e->getMessage());
            return 0;
        }
    }


    /**
     * Obtener todas las tareas con información relacionada
     */
    public function getAll(array $filters = [], int $limit = 7, int $offset = 0): array
    {
        try {
            $uti = $filters['current_usuario_tipo_id'];
            $cu = $filters['current_usuario_id'];
            $params = [];

            $sql = "SELECT Distinct
                    pt.id,
                    pt.tarea_id,
                    t.nombre as tarea_nombre,
                    t.descripcion,
                    pt.fecha_inicio,
                    pt.duracion_horas,
                    pt.prioridad,
                    pt.fecha_Creado,
                    p.id as proyecto_id,
                    CONCAT('Proyecto para ', c.razon_social) as proyecto_nombre,
                    c.razon_social as cliente_nombre,
                    tt.nombre as tipo_tarea,
                    et.nombre as estado,
                    et.id as estado_tipo_id,
                    plan.nombre_usuario as planificador_nombre,
                    exec.nombre_usuario as ejecutor_nombre,
                    super.nombre_usuario as supervisor_nombre
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN proyectos p ON pt.proyecto_id = p.id
                Inner Join proyecto_usuarios_grupo pug on pug.estado_tipo_id = 2 and pug.proyecto_id = p.id
                Inner Join grupo_tipos gt on gt.id between 1 and 5 and gt.id = pug.grupo_id
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                INNER JOIN usuarios plan ON pt.planificador_id = plan.id
                LEFT JOIN usuarios exec ON pt.ejecutor_id = exec.id
                LEFT JOIN usuarios super ON pt.supervisor_id = super.id ";
            $strWhere = " WHERE pug.usuario_id = ? ";
            $params[] = $cu;

            // Filtros
            if (isset($filters['proyecto_id']) && !empty($filters['proyecto_id'])) {
                $strWhere .= " and pt.proyecto_id = ?";
                $params[] = $filters['proyecto_id'];
            }

            if (isset($uti) && $uti > 2) {
                $strWhere .= " AND pt.estado_tipo_id in (2, 5, 6, 7, 8)";
            }

            if (isset($uti) && $uti == 4) {
                $strWhere .= " AND (pt.ejecutor_id is null or pt.ejecutor_id = ?)";
                $params[] = $cu;
            }

            if (isset($filters['estado_tipo_id']) && !empty($filters['estado_tipo_id'])) {
                // Aseguramos que sea un array
                $estadoTipoIds = is_array($filters['estado_tipo_id'])
                    ? $filters['estado_tipo_id']
                    : [$filters['estado_tipo_id']];

                // Eliminamos vacíos o nulos
                $estadoTipoIds = array_filter($estadoTipoIds, fn($v) => $v !== '' && $v !== null);

                if (!empty($estadoTipoIds)) {
                    // Creamos placeholders (?, ?, ?, ...)
                    $placeholders = implode(', ', array_fill(0, count($estadoTipoIds), '?'));
                    // Agregamos la condición con el IN dinámico
                    $strWhere .= " AND pt.estado_tipo_id IN ($placeholders)";
                    // Agregamos todos los IDs al array de parámetros
                    $params = array_merge($params, $estadoTipoIds);
                }
            }

            if (isset($filters['fecha_inicio']) && isset($filters['fecha_fin']) && !empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
                $strWhere .= " AND pt.fecha_inicio between ? and ?";
                $params[] = $filters['fecha_inicio'];
                $params[] = $filters['fecha_fin'];
            }

            if (isset($filters['fecha_inicio']) && !empty($filters['fecha_inicio']) && (!isset($filters['fecha_fin']) || empty($filters['fecha_fin']))) {
                $strWhere .= " AND pt.fecha_inicio >= ?";
                $params[] = $filters['fecha_inicio'];
            }

            if ((!isset($filters['fecha_inicio']) || empty($filters['fecha_inicio'])) && isset($filters['fecha_fin']) && !empty($filters['fecha_fin'])) {
                $strWhere .= " AND pt.fecha_inicio <= ?";
                $params[] = $filters['fecha_fin'];
            }

            $sql .= $strWhere;
            $sql .= " ORDER BY pt.fecha_inicio ASC, pt.id asc LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tarea por ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "
                SELECT
                    pt.id, pt.proyecto_id, pt.tarea_id, 
                    pt.planificador_id, pt.ejecutor_id, pt.supervisor_id, 
                    pt.fecha_inicio, pt.duracion_horas, pt.fecha_fin,
                    pt.prioridad, pt.estado_tipo_id, 
                    pt.fecha_Creado, pt.fecha_modificacion,
                    t.nombre             as tarea_nombre,
                    t.descripcion        as tarea_descripcion,
                    p.id                 as proyecto_id,
                    CONCAT('Proyecto para ', c.razon_social) as proyecto_nombre,
                    c.razon_social       as cliente_nombre,
                    tt.nombre            as tipo_tarea,
                    et.nombre            as estado,
                    plan.nombre_usuario  as planificador_nombre,
                    exec.nombre_usuario  as ejecutor_nombre,
                    super.nombre_usuario as supervisor_nombre,
                    p.tarea_tipo_id,
                    p.contraparte_id
                FROM proyecto_tareas pt
                INNER JOIN tareas        t   ON pt.tarea_id = t.id
                INNER JOIN proyectos     p   ON pt.proyecto_id = p.id
                INNER JOIN clientes      c   ON p.cliente_id = c.id
                INNER JOIN tarea_tipos  tt   ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et   ON pt.estado_tipo_id = et.id
                INNER JOIN usuarios     plan ON pt.planificador_id = plan.id
                LEFT JOIN usuarios      exec ON pt.ejecutor_id = exec.id
                LEFT JOIN usuarios      super ON pt.supervisor_id = super.id
                WHERE pt.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Logger::error("Task::getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nueva tarea
     */
    public function taskCreate(?string $tareaNombre, ?string $tareaDescripcion, ?int $tareaCategoriaId): ?int
    {
        try {
            $this->db->beginTransaction();
            $tareaId = 0;
            if (!empty($tareaNombre)) {
                $stmt = $this->db->prepare("SELECT id FROM tareas WHERE nombre = ?");
                $stmt->execute([$tareaNombre]);
                $arrayTareaId = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($arrayTareaId)) {
                    $stmt = $this->db->prepare("INSERT INTO tareas (nombre, descripcion, estado_tipo_id, tarea_categoria_id) VALUES (?, ?, 2, ?)");
                    $stmt->execute([$tareaNombre, $tareaDescripcion ?? '', $tareaCategoriaId ?? 0]);
                    $tareaId = $this->db->lastInsertId();
                } else {
                    $tareaId = $arrayTareaId[0];
                }
            } else {
                $this->db->rollBack();
                Logger::error("Task::create: no se puede crear tarea sin nombre");
            }
            $this->db->commit();
            return $tareaId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error("Task::create: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear tarea en proyecto
     */
    public function projectTaskCreate(array $data): ?bool
    {
        try {
            $proyectoTareasId = 0;
            $this->db->beginTransaction();
            // Verificar si existe
            $stmt = $this->db->prepare("
                SELECT id FROM proyecto_tareas
                WHERE proyecto_id = ? and tarea_id = ? and ejecutor_id = ?  and fecha_inicio = ?
            ");
            $stmt->execute([$data['proyecto_id'], $data['tarea_id'], $data['ejecutor_id'] ?? null, $data['fecha_inicio']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $proyectoTareasId = $existing['id'];
                // Actualizar registro existente
                $stmt = $this->db->prepare("
                    UPDATE proyecto_tareas SET
                        planificador_id = ?,
                        supervisor_id = ?,
                        duracion_horas = ?,
                        prioridad = ?,
                        estado_tipo_id = ?,
                        fecha_modificacion = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                $result = $stmt->execute([
                    $data['planificador_id'],
                    $data['supervisor_id'] ?? null,
                    $data['duracion_horas'] ?? 1.0,
                    $data['prioridad'] ?? 0,
                    $data['estado_tipo_id'] ?? 1,
                    $existing['id']
                ]);
            } else {
                // Luego crear la asignación proyecto-tarea
                $stmt = $this->db->prepare("
                INSERT INTO proyecto_tareas (
                    proyecto_id,
                    tarea_id,
                    planificador_id,
                    ejecutor_id,
                    supervisor_id,
                    fecha_inicio,
                    duracion_horas,
                    fecha_fin,
                    prioridad,
                    estado_tipo_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

                $result = $stmt->execute([
                    $data['proyecto_id'],
                    $data['tarea_id'],
                    $data['planificador_id'],
                    $data['ejecutor_id'] ?? null,
                    $data['supervisor_id'] ?? null,
                    $data['fecha_inicio'],
                    $data['duracion_horas'] ?? 1.0,
                    $data['fecha_fin'],
                    $data['prioridad'] ?? 0,
                    $data['estado_tipo_id'] ?? 1 // Estado "Creado" por defecto
                ]);

                if ($result) {
                    $proyectoTareasId = $this->db->lastInsertId();
                }
            }

            if ($result) {
                $this->db->commit();
            } else {
                $this->db->rollBack();
            }
            return ($proyectoTareasId ?? 0) > 0;
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error("Task::projectTaskCreate: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear tarea en proyecto masivamente
     */
    public function projectTaskCreateMasivo(array $data): ?bool
    {
        try {
            $result = false;

            $diasSemana = $data['dias_semana'] ?? [];
            // Convertir días a array de enteros
            $diasSemana = array_map('intval', $diasSemana);
            // Generar todas las fechas
            $start = new \DateTime($data['fecha_inicio']);
            $end = new \DateTime($data['fecha_fin']);
            while ($start <= $end) {
                $dayOfWeek = (int)$start->format('w'); // 0=domingo, 1=lunes, etc.
                if (in_array($dayOfWeek, $diasSemana)) {
                    $data['fecha_inicio'] = $start->format('Y-m-d');
                    $data['fecha_fin'] = $data['fecha_inicio'];
                    $result = $this->projectTaskCreate($data);
                }
                $start->add(new \DateInterval('P1D'));
            }

            return $result;
        } catch (PDOException $e) {
            Logger::error("Task::projectTaskCreateMasivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear tarea en proyecto rango de fechas
     */
    public function projectTaskCreateRango(array $data): ?bool
    {
        try {
            $result = false;
            // Generar todas las fechas
            $start = new \DateTime($data['fecha_inicio']);
            $end = new \DateTime($data['fecha_fin']);
            while ($start <= $end) {
                $data['fecha_inicio'] = $start->format('Y-m-d');
                $data['fecha_fin'] = $data['fecha_inicio'];
                $result = $this->projectTaskCreate($data);

                $start->add(new \DateInterval('P1D'));
            }

            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error("Task::projectTaskCreateRango: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear nueva tarea en proyecto
     */
    public function create(array $data): ?bool
    {
        try {
            $result = false;
            $tipoO = $data['tipo_ocurrencia'];
            //Determinar a cual create llamamos
            if ($tipoO == 1) {
                $result = $this->projectTaskCreateMasivo($data);
            }
            //Fecha especifica
            if ($tipoO == 2) {
                $result = $this->projectTaskCreate($data);
            }
            //Rango de fechas todos los dias
            if ($tipoO == 3) {
                $result = $this->projectTaskCreateRango($data);
            }

            return $result;
        } catch (PDOException $e) {
            Logger::error("Task::create: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar tarea en proyecto
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "
                UPDATE proyecto_tareas
                SET
                    proyecto_id = ?,
                    planificador_id = ?,
                    ejecutor_id = ?,
                    supervisor_id = ?,
                    fecha_inicio = ?,
                    duracion_horas = ?,
                    prioridad = ?,
                    estado_tipo_id = ?,
                    fecha_modificacion = CURRENT_TIMESTAMP
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['proyecto_id'],
                $data['planificador_id'],
                $data['ejecutor_id'] ?? null,
                $data['supervisor_id'] ?? null,
                $data['fecha_inicio'],
                $data['duracion_horas'] ?? 1.0,
                $data['prioridad'] ?? 0,
                $data['estado_tipo_id'],
                $id
            ]);
        } catch (PDOException $e) {
            Logger::error("Task::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar tarea en proyecto
     */
    public function updateT(int $id, array $data): bool
    {
        try {
            $sql = "
                UPDATE tareas
                SET
                    nombre = ?,
                    descripcion = ?,
                    tarea_categoria_id = ?,
                    estado_tipo_id = ?,
                    fecha_modificacion = CURRENT_TIMESTAMP
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['nombre'],
                $data['descripcion'],
                $data['tarea_categoria_id'],
                $data['estado_tipo_id'],
                $id
            ]);
        } catch (PDOException $e) {
            Logger::error("Task::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar tarea(s) de proyecto (soft delete)
     */
    public function delete(int $id, bool $deleteAllOccurrences = false): bool
    {
        try {
            if ($deleteAllOccurrences) {
                $sql = "UPDATE proyecto_tareas pt
                        INNER JOIN proyecto_tareas source_task ON source_task.id = ?
                        SET pt.estado_tipo_id = 4, pt.fecha_modificacion = NOW()
                        WHERE pt.proyecto_id = source_task.proyecto_id
                          AND pt.tarea_id = source_task.tarea_id
                          AND pt.estado_tipo_id < 5";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$id]);
            }

            $sql = "UPDATE proyecto_tareas SET estado_tipo_id = 4, fecha_modificacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            Logger::error("Task::delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar tarea (soft delete)
     */
    public function deleteT(int $id): bool
    {
        try {
            $sql = "UPDATE tareas SET estado_tipo_id = 4, fecha_modificacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            Logger::error("Task::delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tipos de tareas disponibles (catálogo general)
     */
    public function getTaskTypes(): array
    {
        try {
            $sql = "SELECT id, nombre FROM tarea_tipos ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de tareas disponibles (catálogo general)
     */
    public function getTaskCategorys(): array
    {
        try {
            $sql = "SELECT id, nombre FROM tarea_categorias ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskCategorys: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de tareas (catálogo general)
     */
    public function getTasks(): array
    {
        try {
            $sql = "SELECT id, nombre, descripcion, tarea_categoria_id, estado_tipo_id, fecha_Creado, fecha_modificacion FROM tareas WHERE estado_tipo_id = 2 ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de tareas (catálogo general)
     */
    public function getAllTasks(): array
    {
        try {
            $sql = "SELECT t.id, t.nombre, t.descripcion, t.tarea_categoria_id, t.estado_tipo_id, t.fecha_Creado, t.fecha_modificacion, tc.nombre as categoria, et.nombre as estado FROM tareas t INNER JOIN tarea_categorias tc on tc.id = t.tarea_categoria_id INNER JOIN estado_tipos et on et.id = t.estado_tipo_id ORDER BY t.nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de tareas (catálogo general) por id de categoria para tareas
     */
    public function getGroupTasks(?int $id): array
    {
        try {
            $sql = "SELECT t.id, t.nombre, t.descripcion, t.tarea_categoria_id, t.estado_tipo_id, t.fecha_Creado, t.fecha_modificacion, tc.nombre as categoria, et.nombre as estado FROM tareas t INNER JOIN tarea_categorias tc on tc.id = t.tarea_categoria_id INNER JOIN estado_tipos et on et.id = t.estado_tipo_id WHERE t.tarea_categoria_id = ? ORDER BY t.nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tarea por id 
     */
    public function getTaskById(?int $id): array
    {
        try {
            $sql = "SELECT t.id, t.nombre, t.descripcion, t.tarea_categoria_id, t.estado_tipo_id, t.fecha_Creado, t.fecha_modificacion, tc.nombre as categoria, et.nombre as estado FROM tareas t INNER JOIN tarea_categorias tc on tc.id = t.tarea_categoria_id INNER JOIN estado_tipos et on et.id = t.estado_tipo_id WHERE t.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskById: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tarea por id 
     */
    public function getTaskByName(?string $name): array
    {
        try {
            $sql = "SELECT t.id, t.nombre, t.descripcion, t.tarea_categoria_id, t.estado_tipo_id, t.fecha_Creado, t.fecha_modificacion, tc.nombre as categoria, et.nombre as estado FROM tareas t INNER JOIN tarea_categorias tc on tc.id = t.tarea_categoria_id INNER JOIN estado_tipos et on et.id = t.estado_tipo_id WHERE t.nombre = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskByName: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tareas activas (catálogo general)
     */
    public function getTasksForCreate(): array
    {
        try {
            $sql = "SELECT id, nombre, descripcion FROM tareas WHERE estado_tipo_id = 2 ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener proyectos disponibles
     */
    public function getProjects(?array $filters = []): array
    {
        $uti = $filters['current_usuario_tipo_id'] ?? 0;
        $myFilters = [];

        try {
            $sql = "Select DISTINCT p.id, 
                                    CONCAT(c.razon_social, ' (', p.fecha_inicio, '.', p.fecha_fin, ')') as nombre, 
                                    c.razon_social as cliente_nombre
                    From proyectos p 
                    Inner Join clientes c on p.cliente_id = c.id
                    Inner Join proyecto_usuarios_grupo pug on pug.estado_tipo_id = 2 and pug.proyecto_id = p.id
                    Inner Join grupo_tipos gt on gt.id between 1 and 5 and gt.id = pug.grupo_id ";

            if ($uti == 1 || $uti == 2) {
                $sql .= " Where p.estado_tipo_id IN (1, 2, 5)";
            } else {
                $sql .= " Where p.estado_tipo_id = 2";
            }

            $sql .= " and pug.usuario_id = ? ";
            $myFilters[] = $filters['current_usuario_id'];

            $sql .= " ORDER BY c.razon_social";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($myFilters);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getProjects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener proyectos disponibles
     */
    public function getProjectsActivos(?string $usuario_id): array
    {
        try {
            $sql = "Select DISTINCT p.id, 
                                    CONCAT(c.razon_social, ' (', p.fecha_inicio, '.', p.fecha_fin, ')') as nombre, 
                                    c.razon_social as cliente_nombre
                    From proyectos p 
                    Inner Join clientes c on p.cliente_id = c.id
                    Inner Join proyecto_usuarios_grupo pug on pug.estado_tipo_id = 2 and pug.proyecto_id = p.id
                    Inner Join grupo_tipos gt on gt.id between 1 and 5 and gt.id = pug.grupo_id
                    WHERE p.estado_tipo_id = 2";

            $params = [];
            if (!empty($usuario_id)) {
                $sql .= " and pug.usuario_id = ?";
                $params[] = $usuario_id;
            }
            $sql .= " ORDER BY c.razon_social";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getProjects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener proyectos disponibles
     */
    public function getProjectById(?int $id = 0): array
    {
        try {
            $sql = "
                SELECT p.id, 
                CONCAT('Proyecto para ', c.razon_social) as nombre, 
                c.razon_social as cliente_nombre
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE p.estado_tipo_id IN (1, 2, 5) and p.id = ?
                ORDER BY c.razon_social
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getProjects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener usuarios disponibles para asignación
     */
    public function getUsers(): array
    {
        try {
            $sql = "
                SELECT u.id, u.nombre_usuario, p.nombre as nombre_completo
                FROM usuarios u
                INNER JOIN personas p ON u.persona_id = p.id
                WHERE u.estado_tipo_id = 2
                ORDER BY p.nombre
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener usuarios disponibles para asignación
     */
    public function getExecutorUsers(): array
    {
        try {
            $sql = "
                SELECT u.id, u.nombre_usuario, p.nombre as nombre_completo
                FROM usuarios u
                INNER JOIN personas p ON u.persona_id = p.id
                WHERE u.estado_tipo_id = 2 and u.usuario_tipo_id = 4
                ORDER BY p.nombre
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener usuarios disponibles para asignación
     */
    public function getSupervisorUsers(): array
    {
        try {
            $sql = "
                SELECT u.id, u.nombre_usuario, p.nombre as nombre_completo
                FROM usuarios u
                INNER JOIN personas p ON u.persona_id = p.id
                WHERE u.estado_tipo_id = 2 and u.usuario_tipo_id = 3
                ORDER BY p.nombre
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estados de tareas
     */
    public function getTaskStates(?array $filters = []): array
    {
        try {
            $uti = isset($filters['current_usuario_tipo_id']) ? $filters['current_usuario_tipo_id'] : 0;

            $sql = "Select id, nombre, descripcion From estado_tipos";
            if ($uti > 2) {
                $sql .= " Where id In (2, 5, 6, 7, 8)";
            }
            $sql .= " Order By id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskStates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estados de tareas para filtro de mis tareas
     */
    public function getTaskStatesMyListFilter(): array
    {
        try {
            $sql = "
                SELECT id, nombre, descripcion
                FROM estado_tipos
                WHERE id in (2, 5, 7) 
                ORDER BY id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskStates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estados de tareas para creacion
     */
    public function getTaskStatesForCreate(): array
    {
        // Solo el estado de activo y creado 
        try {
            $sql = "
                SELECT id, nombre, descripcion
                FROM estado_tipos
                WHERE id in (1, 2)
                ORDER BY id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskStates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estados de tareas para nueva tarea
     */
    public function getTaskStatesForNewTask(): array
    {
        // Solo el estado de activo y creado 
        try {
            $sql = "
                SELECT id, nombre, descripcion
                FROM estado_tipos
                WHERE id in (1, 2, 4)
                ORDER BY id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskStates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estado_tipo_id de id en proyecto_tareas
     */
    public function getProjectTaskState($Id): int
    {
        try {
            $sql = "SELECT estado_tipo_id FROM proyecto_tareas WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$Id]);
            $proyecto_tarea_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $proyecto_tarea_estado[0]['estado_tipo_id'];
        } catch (PDOException $e) {
            Logger::error("Task::getTaskTypes: " . $e->getMessage());
            return -1;
        }
    }

    /**
     * Validar si una transición de estado es válida
     */
    public function isValidStateTransition(int $currentState, int $newState): array
    {
        // Definir transiciones válidas según reglas de negocio
        $validTransitions = [
            1 => [2, 4], // creado -> activo, eliminado
            2 => [3, 5], // activo -> inactivo, iniciado
            3 => [2], // inactivo -> activo
            4 => [1], // eliminado -> creado
            5 => [6], // iniciado -> terminado
            6 => [7, 8], // terminado -> rechazado, aprobado
            7 => [2, 5, 6], // rechazado -> activo, iniciado, terminado
            8 => [6, 7] // aprobado -> terminado, rechazado
        ];

        $isValid = isset($validTransitions[$currentState]) &&
            in_array($newState, $validTransitions[$currentState]);

        $message = '';
        if (!$isValid) {
            $stateNames = [
                1 => 'Creado',
                2 => 'Activo',
                3 => 'Inactivo',
                4 => 'Eliminado',
                5 => 'Iniciado',
                6 => 'Terminado',
                7 => 'Rechazado',
                8 => 'Aprobado'
            ];

            $currentName = $stateNames[$currentState] ?? 'Desconocido';
            $newName = $stateNames[$newState] ?? 'Desconocido';

            $message = "No es posible cambiar de estado '{$currentName}' a '{$newName}'";
        }

        return [
            'valid' => $isValid,
            'message' => $message
        ];
    }

    /**
     * Validar si el usuario puede cambiar el estado según su rol
     */
    public function canUserChangeState(int $currentState, int $newState, string $userRole): array
    {
        // Estados que solo admin y planner pueden modificar cuando están aprobados
        if ($currentState == 8) { // aprobado
            if (!in_array($userRole, ['admin', 'planner', 'supervisor'])) {
                return [
                    'valid' => false,
                    'message' => 'Admin, Planner y Supervisor pueden modificar tareas aprobadas'
                ];
            }
        }

        // Validaciones específicas por rol
        $restrictions = [
            'executor' => [
                'allowed_from' => [2, 5], // Solo desde activo e iniciado
                'allowed_to' => [5, 6], // Solo a iniciado y terminado
                'message' => 'Los ejecutores solo pueden iniciar tareas activas o marcarlas como terminadas'
            ],
            'supervisor' => [
                'allowed_from' => [5, 6, 8, 7], // Desde iniciado, terminado, aprobado, rechazada
                'allowed_to' => [6, 7, 8], // A terminado, rechazado y aprobado
                'message' => 'Los supervisores solo pueden aprobar o rechazar tareas iniciadas o terminadas'
            ]
        ];

        if (isset($restrictions[$userRole])) {
            $restriction = $restrictions[$userRole];

            if (
                !in_array($currentState, $restriction['allowed_from']) ||
                !in_array($newState, $restriction['allowed_to'])
            ) {
                return [
                    'valid' => false,
                    'message' => $restriction['message']
                ];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validar si la tarea puede ser ejecutada según su estado
     */
    public function canExecuteTask(int $taskId): array
    {
        try {
            $task = $this->getById($taskId);
            if (!$task) {
                return [
                    'valid' => false,
                    'message' => 'Tarea no encontrada'
                ];
            }

            // Estados válidos para ejecución: 2(activo), 5(iniciado), 6(terminado), 7(rechazado), 8(aprobado)
            $executableStates = [2, 5, 6, 7, 8];

            if (!in_array($task['estado_tipo_id'], $executableStates)) {
                return [
                    'valid' => false,
                    'message' => 'La tarea debe estar en estado Activo, Iniciado, Terminado, Rechazado o Aprobado para poder ejecutarse'
                ];
            }

            return ['valid' => true, 'message' => ''];
        } catch (PDOException $e) {
            Logger::error("Task::canExecuteTask: " . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Error al verificar el estado de la tarea'
            ];
        }
    }

    /**
     * Cambiar estado de una tarea con validaciones
     */
    public function changeState(int $taskId, int $newState, int $userId, string $userRole, string $reason = '', array $photos = []): array
    {
        try {
            $params = [];
            $params[] = $newState;

            $this->db->beginTransaction();

            // Obtener tarea actual
            $task = $this->getById($taskId);
            if (!$task) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tarea no encontrada'];
            }

            $currentState = (int)$task['estado_tipo_id'];
            $supervisor_id = (int)$task['supervisor_id'];
            $contraparte_id = (int)$task['contraparte_id'];

            $ejecutor_id = (int)$task['ejecutor_id'];

            if (!empty($ejecutor_id)) {
                if ($ejecutor_id != $userId && ($newState == 5)) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Tarea asignada a otro usuario'];
                }
            }
            // Validar transición de estado
            $transitionValidation = $userRole == 'admin' ? ['valid' => true] : $this->isValidStateTransition($currentState, $newState);
            if (!$transitionValidation['valid']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => $transitionValidation['message']];
            }

            // Validar permisos del usuario
            $userValidation = $this->canUserChangeState($currentState, $newState, $userRole);
            if (!$userValidation['valid']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => $userValidation['message']];
            }

            // Actualizar estado
            $sqlAutoAsignacion = '';
            if ($newState == 5 && $ejecutor_id == null) {
                $sqlAutoAsignacion =  ", ejecutor_id = ?";
                $params[] = $userId;
            }
            $params[] = $taskId;

            $sql = "UPDATE proyecto_tareas SET estado_tipo_id = ?, fecha_modificacion = CURRENT_TIMESTAMP {$sqlAutoAsignacion} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);

            if (!$success) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Error al actualizar el estado de la tarea'];
            }

            // Registrar en historial si la tabla existe
            $historyId = $this->registerStateHistory($taskId, $currentState, $newState, $userId, $reason, $supervisor_id, $contraparte_id);
            if (!empty($photos) && $historyId > 0) {
                $this->registerHistoryPhotos($historyId, $photos, $newState);
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'Estado de la tarea actualizado correctamente'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error("Task::changeState: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno al cambiar el estado de la tarea'];
        }
    }

    /**
     * Registrar cambio de estado en historial
     */
    private function registerStateHistory(int $taskId, int $oldState, int $newState, int $userId, string $reason, $supervisor_id, $contraparte_id): int
    {
        try {
            // Verificar si la tabla historial_tareas existe
            $checkTable = $this->db->prepare("SHOW TABLES LIKE 'historial_tareas'");
            $checkTable->execute();

            if ($checkTable->rowCount() > 0) {
                $sql = "
                    INSERT INTO historial_tareas (
                        proyecto_tarea_id,
                        usuario_id,
                        supervisor_id,
                        contraparte_id,
                        fecha_evento,
                        comentario,
                        estado_tipo_anterior,
                        estado_tipo_nuevo
                    ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?)
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$taskId, $userId, $supervisor_id, $contraparte_id, $reason, $oldState, $newState]);

                return (int)$this->db->lastInsertId();
            }

            return 0;
        } catch (PDOException $e) {
            // Solo logear el error, no interrumpir el proceso principal
            Logger::error("registrar historial de tarea: " . $e->getMessage());
            return 0;
        }
    }

    private function registerHistoryPhotos(int $historyId, array $photoUrls, int $stateTypeId): void
    {
        if ($historyId <= 0 || empty($photoUrls)) {
            return;
        }

        $sql = "INSERT INTO tarea_fotos (historial_tarea_id, url_foto, fecha_Creado, estado_tipo_id) VALUES (?, ?, CURRENT_TIMESTAMP, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($photoUrls as $photoUrl) {
            $stmt->execute([$historyId, $photoUrl, $stateTypeId]);
        }
    }

    /**
     * Validar datos de actualización con validaciones de estado
     */
    public function validateUpdateData(int $taskId, array $data, string $userRole): array
    {
        $errors = [];

        try {
            $task = $this->getById($taskId);
            if (!$task) {
                $errors[] = 'Tarea no encontrada';
                return $errors;
            }

            $currentState = (int)$task['estado_tipo_id'];

            // Si se intenta cambiar el estado
            if (isset($data['estado_tipo_id']) && $data['estado_tipo_id'] != $currentState) {
                $newState = (int)$data['estado_tipo_id'];

                // Validar transición
                $transitionValidation = $this->isValidStateTransition($currentState, $newState);
                if (!$transitionValidation['valid']) {
                    $errors[] = $transitionValidation['message'];
                }

                // Validar permisos del usuario
                $userValidation = $this->canUserChangeState($currentState, $newState, $userRole);
                if (!$userValidation['valid']) {
                    $errors[] = $userValidation['message'];
                }
            }

            // Validar si se pueden hacer cambios según el estado actual
            if ($currentState == 8 && !in_array($userRole, ['admin', 'planner'])) {
                $errors[] = 'Solo usuarios Admin y Planner pueden modificar tareas aprobadas';
            }
        } catch (Exception $e) {
            Logger::error("Task::validateUpdateData: " . $e->getMessage());
            $errors[] = 'Error al validar los datos de actualización';
        }

        return $errors;
    }

    /**
     * Verificar si una tarea está programada en un día feriado
     */
    public function isTaskOnHoliday(int $taskId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pt.fecha_inicio, pt.fecha_fin, pt.proyecto_id
                FROM proyecto_tareas pt
                WHERE pt.id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                return false;
            }

            // Verificar si la fecha de inicio o fin está en feriados
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as holiday_count
                FROM proyecto_feriados pf
                WHERE pf.proyecto_id = ?
                AND pf.estado_tipo_id = 2
                AND (pf.fecha = ? OR (? IS NOT NULL AND pf.fecha = ?))
            ");

            $stmt->execute([
                $task['proyecto_id'],
                $task['fecha_inicio'],
                $task['fecha_fin'],
                $task['fecha_fin']
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['holiday_count'] > 0);
        } catch (PDOException $e) {
            Logger::error('Task::isTaskOnHoliday error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tareas que están programadas en feriados de un proyecto
     */
    public function getTasksOnHolidays(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT pt.id, pt.fecha_inicio, pt.fecha_fin,
                       t.nombre as tarea_nombre,
                       et.nombre as estado_nombre,
                       pf.fecha as fecha_feriado,
                       pf.ind_irrenunciable,
                       pf.observaciones as feriado_observaciones
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                INNER JOIN proyecto_feriados pf ON pt.proyecto_id = pf.proyecto_id
                WHERE pt.proyecto_id = ?
                AND pt.estado_tipo_id NOT IN (4, 6, 7, 8)
                AND pf.estado_tipo_id = 2
                AND (pf.fecha = pt.fecha_inicio
                     OR pf.fecha = pt.fecha_fin
                     OR (pt.fecha_inicio <= pf.fecha AND pt.fecha_fin >= pf.fecha))
                ORDER BY pf.fecha, pt.fecha_inicio
            ");

            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Task::getTasksOnHolidays error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar fechas de tarea considerando feriados
     */
    public function validateTaskDatesWithHolidays(int $projectId, string $fechaInicio, ?string $fechaFin = null): array
    {
        $warnings = [];

        try {
            // Verificar si fecha de inicio es feriado
            $stmt = $this->db->prepare("
                SELECT fecha, ind_irrenunciable, observaciones
                FROM proyecto_feriados
                WHERE proyecto_id = ? AND fecha = ? AND estado_tipo_id = 2
            ");

            $stmt->execute([$projectId, $fechaInicio]);
            $holidayStart = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($holidayStart) {
                $type = $holidayStart['ind_irrenunciable'] ? 'irrenunciable' : 'renunciable';
                $warnings[] = [
                    'type' => 'holiday_start',
                    'message' => "La fecha de inicio ({$fechaInicio}) es un feriado {$type}",
                    'date' => $fechaInicio,
                    'holiday_type' => $type,
                    'observations' => $holidayStart['observaciones']
                ];
            }

            // Verificar si fecha de fin es feriado
            if ($fechaFin) {
                $stmt->execute([$projectId, $fechaFin]);
                $holidayEnd = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($holidayEnd) {
                    $type = $holidayEnd['ind_irrenunciable'] ? 'irrenunciable' : 'renunciable';
                    $warnings[] = [
                        'type' => 'holiday_end',
                        'message' => "La fecha de fin ({$fechaFin}) es un feriado {$type}",
                        'date' => $fechaFin,
                        'holiday_type' => $type,
                        'observations' => $holidayEnd['observaciones']
                    ];
                }
            }
        } catch (PDOException $e) {
            Logger::error('Task::validateTaskDatesWithHolidays error: ' . $e->getMessage());
        }

        return $warnings;
    }

    /**
     * Sugerir próxima fecha hábil para una tarea
     */
    public function getNextWorkingDay(int $projectId, string $date): string
    {
        try {
            $currentDate = new \DateTime($date);
            $maxIterations = 30; // Evitar bucle infinito
            $iterations = 0;

            while ($iterations < $maxIterations) {
                $dateStr = $currentDate->format('Y-m-d');

                // Verificar si es feriado
                $stmt = $this->db->prepare("
                    SELECT id FROM proyecto_feriados
                    WHERE proyecto_id = ? AND fecha = ? AND estado_tipo_id = 2
                ");
                $stmt->execute([$projectId, $dateStr]);

                if ($stmt->rowCount() === 0) {
                    // No es feriado, retornar esta fecha
                    return $dateStr;
                }

                // Avanzar un día
                $currentDate->add(new \DateInterval('P1D'));
                $iterations++;
            }

            // Si no encontramos día hábil en 30 días, retornar fecha original
            return $date;
        } catch (\Exception $e) {
            Logger::error('Task::getNextWorkingDay error: ' . $e->getMessage());
            return $date;
        }
    }

    /**
     * Calcular días laborables entre dos fechas excluyendo feriados
     */
    public function getWorkingDaysBetween(int $projectId, string $startDate, string $endDate): int
    {
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $workingDays = 0;

            if ($start > $end) {
                return 0;
            }

            while ($start <= $end) {
                $dateStr = $start->format('Y-m-d');

                // Verificar si no es feriado
                $stmt = $this->db->prepare("
                    SELECT id FROM proyecto_feriados
                    WHERE proyecto_id = ? AND fecha = ? AND estado_tipo_id = 2
                ");
                $stmt->execute([$projectId, $dateStr]);

                if ($stmt->rowCount() === 0) {
                    $workingDays++;
                }

                $start->add(new \DateInterval('P1D'));
            }

            return $workingDays;
        } catch (\Exception $e) {
            Logger::error('Task::getWorkingDaysBetween error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener historial de cambios de una tarea específica
     */
    public function getTaskHistory(int $proyectoTareaId): array
    {
        try {
            $sql = "
                SELECT
                    ht.id,
                    ht.proyecto_tarea_id,
                    ht.usuario_id,
                    u.email as usuario_email,
                    ht.supervisor_id,
                    us.email as supervisor_email,
                    ht.contraparte_id,
                    ht.fecha_evento,
                    ht.comentario,
                    eta.nombre as estado_anterior,
                    etn.nombre as estado_nuevo
                FROM historial_tareas ht
                JOIN proyecto_tareas pt ON pt.id = ht.proyecto_tarea_id
                JOIN usuarios u ON u.id = ht.usuario_id
                JOIN usuarios us ON us.id = ht.supervisor_id
                JOIN estado_tipos etn ON etn.id = ht.estado_tipo_nuevo
                LEFT JOIN estado_tipos eta ON eta.id = ht.estado_tipo_anterior
                WHERE ht.proyecto_tarea_id = ?
                ORDER BY ht.fecha_evento DESC
                LIMIT 10
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proyectoTareaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskHistory: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener evidencias fotográficas para un conjunto de registros de historial
     */
    public function getTaskHistoryPhotos(array $historialIds): array
    {
        try {
            if (empty($historialIds)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($historialIds), '?'));
            $sql = "
                SELECT
                    tf.id,
                    tf.historial_tarea_id,
                    tf.url_foto,
                    tf.fecha_Creado,
                    tf.estado_tipo_id,
                    et.nombre as estado_nombre
                FROM tarea_fotos tf
                LEFT JOIN estado_tipos et ON et.id = tf.estado_tipo_id
                WHERE tf.historial_tarea_id IN ($placeholders)
                ORDER BY tf.fecha_Creado DESC, tf.id DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($historialIds));
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $groupedPhotos = [];
            foreach ($photos as $photo) {
                $historialId = (int) $photo['historial_tarea_id'];
                if (!isset($groupedPhotos[$historialId])) {
                    $groupedPhotos[$historialId] = [];
                }

                $photo['url_foto'] = $this->copyHistoryPhotoToPublicUploads($photo['url_foto'] ?? '');
                $groupedPhotos[$historialId][] = $photo;
            }

            return $groupedPhotos;
        } catch (PDOException $e) {
            Logger::error("Task::getTaskHistoryPhotos: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Eliminar de public/uploads las evidencias asociadas al historial de una tarea.
     */
    public function clearTaskHistoryUploads(int $proyectoTareaId): int
    {
        try {
            if ($proyectoTareaId <= 0) {
                return 0;
            }

            $sql = "
                SELECT tf.url_foto
                FROM tarea_fotos tf
                INNER JOIN historial_tareas ht ON ht.id = tf.historial_tarea_id
                WHERE ht.proyecto_tarea_id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proyectoTareaId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return 0;
            }

            $setapRoot = dirname(__DIR__, 3);
            $publicUploadsDir = $setapRoot . '/public/uploads';

            if (!is_dir($publicUploadsDir)) {
                return 0;
            }

            $deletedCount = 0;
            foreach ($rows as $row) {
                $safeFileName = $this->buildSafeUploadFileName($row['url_foto'] ?? '');
                if ($safeFileName === '') {
                    continue;
                }

                $uploadPath = $publicUploadsDir . '/' . $safeFileName;
                if (is_file($uploadPath) && @unlink($uploadPath)) {
                    $deletedCount++;
                }
            }

            return $deletedCount;
        } catch (PDOException $e) {
            Logger::error('Task::clearTaskHistoryUploads: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Copiar una foto histórica desde storage a public/uploads y devolver su URL pública.
     */
    private function copyHistoryPhotoToPublicUploads(string $originalUrl): string
    {
        if ($originalUrl === '') {
            return $originalUrl;
        }

        try {
            $setapRoot = dirname(__DIR__, 3);
            $publicUploadsDir = $setapRoot . '/public/uploads';
            $storagePhotosDir = $setapRoot . '/storage/fotos';

            if (!is_dir($publicUploadsDir) && !mkdir($publicUploadsDir, 0775, true) && !is_dir($publicUploadsDir)) {
                Logger::error('Task::copyHistoryPhotoToPublicUploads no pudo crear directorio uploads');
                return $originalUrl;
            }

            $safeFileName = $this->buildSafeUploadFileName($originalUrl);
            if ($safeFileName === '') {
                return $originalUrl;
            }

            $fileName = basename(parse_url($originalUrl, PHP_URL_PATH) ?? $originalUrl);
            $destinationPath = $publicUploadsDir . '/' . $safeFileName;

            $sourceCandidates = [
                $storagePhotosDir . '/' . $fileName,
                $storagePhotosDir . '/' . $safeFileName,
                $setapRoot . '/' . ltrim(str_replace('\\', '/', parse_url($originalUrl, PHP_URL_PATH) ?? ''), '/'),
                $setapRoot . '/' . ltrim(str_replace('\\', '/', $originalUrl), '/'),
            ];

            $sourcePath = '';
            foreach ($sourceCandidates as $candidate) {
                if ($candidate !== '' && is_file($candidate)) {
                    $sourcePath = $candidate;
                    break;
                }
            }

            if ($sourcePath === '') {
                Logger::error('Task::copyHistoryPhotoToPublicUploads no encontró origen para: ' . $originalUrl);
                return $originalUrl;
            }

            if (!is_file($destinationPath)) {
                if (!@copy($sourcePath, $destinationPath)) {
                    Logger::error('Task::copyHistoryPhotoToPublicUploads error al copiar: ' . $sourcePath);
                    return $originalUrl;
                }
            }

            return BASE_PATH . '/public/uploads/' . rawurlencode($safeFileName);
        } catch (\Throwable $e) {
            Logger::error('Task::copyHistoryPhotoToPublicUploads: ' . $e->getMessage());
            return $originalUrl;
        }
    }
    /**
     * Normalizar nombre de archivo para uso en public/uploads.
     */
    private function buildSafeUploadFileName(string $url): string
    {
        $fileName = basename(parse_url($url, PHP_URL_PATH) ?? $url);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return '';
        }

        return preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?: $fileName;
    }

}
