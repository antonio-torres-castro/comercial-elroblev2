<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class ProyectoFeriado
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los feriados de un proyecto
     */
    public function getByProject(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pf.*,
                       et.nombre as estado_nombre,
                       CASE DAYOFWEEK(pf.fecha)
                           WHEN 1 THEN 'Domingo'
                           WHEN 2 THEN 'Lunes'
                           WHEN 3 THEN 'Martes'
                           WHEN 4 THEN 'Miércoles'
                           WHEN 5 THEN 'Jueves'
                           WHEN 6 THEN 'Viernes'
                           WHEN 7 THEN 'Sábado'
                       END as dia_semana
                FROM proyecto_feriados pf
                INNER JOIN estado_tipos et ON pf.estado_tipo_id = et.id
                WHERE pf.proyecto_id = ? AND pf.estado_tipo_id != 4
                ORDER BY pf.fecha ASC
            ");

            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::getByProject error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear feriados masivamente por días de la semana
     */
    public function createRecurrentHolidays(int $projectId, array $diasSemana, string $fechaInicio, string $fechaFin, int $indIrrenunciable = 0, string $observaciones = ''): array
    {
        try {
            $this->db->beginTransaction();

            $result = [
                'created' => 0,
                'updated' => 0,
                'conflicts' => []
            ];

            // Generar todas las fechas
            $start = new \DateTime($fechaInicio);
            $end = new \DateTime($fechaFin);
            $fechasGeneradas = [];

            while ($start <= $end) {
                $dayOfWeek = (int)$start->format('w'); // 0=domingo, 1=lunes, etc.

                if (in_array($dayOfWeek, $diasSemana)) {
                    $fechasGeneradas[] = $start->format('Y-m-d');
                }

                $start->add(new \DateInterval('P1D'));
            }

            // Insertar o actualizar cada fecha
            foreach ($fechasGeneradas as $fecha) {
                $conflictInfo = $this->upsertHoliday($projectId, $fecha, [
                    'tipo_feriado' => 'recurrente',
                    'ind_irrenunciable' => $indIrrenunciable,
                    'observaciones' => $observaciones
                ]);

                if ($conflictInfo['action'] === 'created') {
                    $result['created']++;
                } elseif ($conflictInfo['action'] === 'updated') {
                    $result['updated']++;
                }

                if (!empty($conflictInfo['task_conflicts'])) {
                    $result['conflicts'][] = [
                        'fecha' => $fecha,
                        'tasks' => $conflictInfo['task_conflicts']
                    ];
                }
            }

            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('ProyectoFeriado::createRecurrentHolidays error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Crear feriado en fecha específica
     */
    public function createSpecificHoliday(int $projectId, string $fecha, int $indIrrenunciable = 0, string $observaciones = ''): array
    {
        try {
            return $this->upsertHoliday($projectId, $fecha, [
                'tipo_feriado' => 'especifico',
                'ind_irrenunciable' => $indIrrenunciable,
                'observaciones' => $observaciones
            ]);
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::createSpecificHoliday error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Crear feriados en rango de fechas
     */
    public function createRangeHolidays(int $projectId, string $fechaInicio, string $fechaFin, int $indIrrenunciable = 0, string $observaciones = ''): array
    {
        try {
            $this->db->beginTransaction();

            $result = [
                'created' => 0,
                'updated' => 0,
                'conflicts' => []
            ];

            $start = new \DateTime($fechaInicio);
            $end = new \DateTime($fechaFin);

            while ($start <= $end) {
                $fecha = $start->format('Y-m-d');

                $conflictInfo = $this->upsertHoliday($projectId, $fecha, [
                    'tipo_feriado' => 'especifico',
                    'ind_irrenunciable' => $indIrrenunciable,
                    'observaciones' => $observaciones
                ]);

                if ($conflictInfo['action'] === 'created') {
                    $result['created']++;
                } elseif ($conflictInfo['action'] === 'updated') {
                    $result['updated']++;
                }

                if (!empty($conflictInfo['task_conflicts'])) {
                    $result['conflicts'][] = [
                        'fecha' => $fecha,
                        'tasks' => $conflictInfo['task_conflicts']
                    ];
                }

                $start->add(new \DateInterval('P1D'));
            }

            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('ProyectoFeriado::createRangeHolidays error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Insertar o actualizar feriado (upsert)
     */
    private function upsertHoliday(int $projectId, string $fecha, array $data): array
    {
        try {
            // Verificar si existe
            $stmt = $this->db->prepare("
                SELECT id FROM proyecto_feriados
                WHERE proyecto_id = ? AND fecha = ?
            ");
            $stmt->execute([$projectId, $fecha]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            // Detectar conflictos con tareas
            $taskConflicts = $this->detectTaskConflicts($projectId, [$fecha]);

            if ($existing) {
                // Actualizar registro existente
                $stmt = $this->db->prepare("
                    UPDATE proyecto_feriados SET
                        tipo_feriado = ?,
                        ind_irrenunciable = ?,
                        observaciones = ?,
                        estado_tipo_id = 2,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                $stmt->execute([
                    $data['tipo_feriado'],
                    $data['ind_irrenunciable'],
                    $data['observaciones'],
                    $existing['id']
                ]);

                return [
                    'action' => 'updated',
                    'id' => $existing['id'],
                    'task_conflicts' => $taskConflicts[$fecha] ?? []
                ];
            } else {
                // Crear nuevo registro
                $stmt = $this->db->prepare("
                    INSERT INTO proyecto_feriados (
                        proyecto_id, fecha, tipo_feriado,
                        ind_irrenunciable, observaciones,
                        estado_tipo_id, created_at
                    ) VALUES (?, ?, ?, ?, ?, 2, CURRENT_TIMESTAMP)
                ");

                $stmt->execute([
                    $projectId,
                    $fecha,
                    $data['tipo_feriado'],
                    $data['ind_irrenunciable'],
                    $data['observaciones']
                ]);

                return [
                    'action' => 'created',
                    'id' => $this->db->lastInsertId(),
                    'task_conflicts' => $taskConflicts[$fecha] ?? []
                ];
            }
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::upsertHoliday error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Detectar conflictos con tareas en fechas específicas
     */
    public function detectTaskConflicts(int $projectId, array $fechas): array
    {
        try {
            if (empty($fechas)) {
                return [];
            }

            $placeholders = str_repeat('?,', count($fechas) - 1) . '?';
            $params = array_merge([$projectId], $fechas);

            $stmt = $this->db->prepare("
                SELECT pt.id, pt.fecha_inicio, pt.fecha_fin, t.nombre as tarea_nombre,
                       et.nombre as estado_nombre
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                WHERE pt.proyecto_id = ?
                AND pt.estado_tipo_id NOT IN (4, 6, 7, 8)
                AND (pt.fecha_inicio IN ($placeholders)
                     OR pt.fecha_fin IN ($placeholders)
                     OR (pt.fecha_inicio <= ? AND pt.fecha_fin >= ?))
            ");

            // Preparar parámetros para las consultas de rango
            $minFecha = min($fechas);
            $maxFecha = max($fechas);
            $allParams = array_merge($params, $fechas, [$maxFecha, $minFecha]);

            $stmt->execute($allParams);
            $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organizar conflictos por fecha
            $result = [];
            foreach ($fechas as $fecha) {
                $result[$fecha] = [];
                foreach ($conflicts as $conflict) {
                    if (
                        $conflict['fecha_inicio'] === $fecha ||
                        $conflict['fecha_fin'] === $fecha ||
                        ($conflict['fecha_inicio'] <= $fecha && $conflict['fecha_fin'] >= $fecha)
                    ) {
                        $result[$fecha][] = $conflict;
                    }
                }
            }

            return $result;
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::detectTaskConflicts error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Mover tareas conflictivas hacia adelante
     */
    public function moveTasksForward(int $projectId, array $taskIds, int $diasAMover = 1): bool
    {
        try {
            $this->db->beginTransaction();

            foreach ($taskIds as $taskId) {
                // Obtener fechas actuales de la tarea
                $stmt = $this->db->prepare("
                    SELECT fecha_inicio, fecha_fin
                    FROM proyecto_tareas
                    WHERE id = ? AND proyecto_id = ?
                ");
                $stmt->execute([$taskId, $projectId]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($task) {
                    $newStartDate = $this->addWorkingDays($projectId, $task['fecha_inicio'], $diasAMover);
                    $newEndDate = $task['fecha_fin'] ?
                        $this->addWorkingDays($projectId, $task['fecha_fin'], $diasAMover) : null;

                    // Actualizar fechas de la tarea
                    $stmt = $this->db->prepare("
                        UPDATE proyecto_tareas SET
                            fecha_inicio = ?,
                            fecha_fin = ?,
                            fecha_modificacion = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([$newStartDate, $newEndDate, $taskId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('ProyectoFeriado::moveTasksForward error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Agregar días laborables a una fecha (saltando feriados)
     */
    private function addWorkingDays(int $projectId, string $startDate, int $workingDays): string
    {
        try {
            $date = new \DateTime($startDate);
            $addedDays = 0;

            while ($addedDays < $workingDays) {
                $date->add(new \DateInterval('P1D'));

                // Verificar si el día no es feriado
                if (!$this->isHoliday($projectId, $date->format('Y-m-d'))) {
                    $addedDays++;
                }
            }

            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            error_log('ProyectoFeriado::addWorkingDays error: ' . $e->getMessage());
            return $startDate;
        }
    }

    /**
     * Verificar si una fecha es feriado
     */
    public function isHoliday(int $projectId, string $fecha): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM proyecto_feriados
                WHERE proyecto_id = ? AND fecha = ? AND estado_tipo_id = 2
            ");
            $stmt->execute([$projectId, $fecha]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::isHoliday error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener días laborables en un rango de fechas
     */
    public function getWorkingDays(int $projectId, string $startDate, string $endDate): array
    {
        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $workingDays = [];

            while ($start <= $end) {
                $dateStr = $start->format('Y-m-d');
                if (!$this->isHoliday($projectId, $dateStr)) {
                    $workingDays[] = $dateStr;
                }
                $start->add(new \DateInterval('P1D'));
            }

            return $workingDays;
        } catch (\Exception $e) {
            error_log('ProyectoFeriado::getWorkingDays error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar feriado existente
     */
    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE proyecto_feriados SET
                    tipo_feriado = ?,
                    ind_irrenunciable = ?,
                    observaciones = ?,
                    estado_tipo_id = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            return $stmt->execute([
                $data['tipo_feriado'] ?? 'especifico',
                $data['ind_irrenunciable'] ?? 0,
                $data['observaciones'] ?? '',
                $data['estado_tipo_id'] ?? 2,
                $id
            ]);
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar feriado (eliminación lógica)
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE proyecto_feriados SET
                    estado_tipo_id = 4,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener feriado por ID
     */
    public function find(int $id): array|false
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pf.*, et.nombre as estado_nombre
                FROM proyecto_feriados pf
                INNER JOIN estado_tipos et ON pf.estado_tipo_id = et.id
                WHERE pf.id = ?
            ");

            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::find error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de feriados de un proyecto
     */
    public function getProjectHolidayStats(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) as total_feriados,
                    COUNT(CASE WHEN tipo_feriado = 'recurrente' THEN 1 END) as feriados_recurrentes,
                    COUNT(CASE WHEN tipo_feriado = 'especifico' THEN 1 END) as feriados_especificos,
                    COUNT(CASE WHEN ind_irrenunciable = 1 THEN 1 END) as feriados_irrenunciables,
                    MIN(fecha) as primer_feriado,
                    MAX(fecha) as ultimo_feriado
                FROM proyecto_feriados
                WHERE proyecto_id = ? AND estado_tipo_id = 2
            ");

            $stmt->execute([$projectId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('ProyectoFeriado::getProjectHolidayStats error: ' . $e->getMessage());
            return [];
        }
    }
}
