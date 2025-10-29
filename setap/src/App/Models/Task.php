<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;

use PDO;
use PDOException;
use Exception;

class Task
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las tareas con información relacionada
     */
    public function getAll(array $filters = []): array
    {
        try {
            $sql = "
                SELECT
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
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                INNER JOIN usuarios plan ON pt.planificador_id = plan.id
                LEFT JOIN usuarios exec ON pt.ejecutor_id = exec.id
                LEFT JOIN usuarios super ON pt.supervisor_id = super.id
                WHERE pt.estado_tipo_id != 4
            ";

            $params = [];

            // Filtros
            if (!empty($filters['proyecto_id'])) {
                $sql .= " AND pt.proyecto_id = ?";
                $params[] = $filters['proyecto_id'];
            }

            if (!empty($filters['estado_tipo_id'])) {
                $sql .= " AND pt.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            if (!empty($filters['usuario_id'])) {
                $sql .= " AND (pt.ejecutor_id = ? OR pt.planificador_id = ? OR pt.supervisor_id = ?)";
                $params[] = $filters['usuario_id'];
                $params[] = $filters['usuario_id'];
                $params[] = $filters['usuario_id'];
            }

            $sql .= " ORDER BY pt.fecha_inicio DESC";

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
                    pt.*,
                    t.nombre as tarea_nombre,
                    t.descripcion as tarea_descripcion,
                    p.id as proyecto_id,
                    CONCAT('Proyecto para ', c.razon_social) as proyecto_nombre,
                    c.razon_social as cliente_nombre,
                    tt.nombre as tipo_tarea,
                    et.nombre as estado,
                    plan.nombre_usuario as planificador_nombre,
                    exec.nombre_usuario as ejecutor_nombre,
                    super.nombre_usuario as supervisor_nombre
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN proyectos p ON pt.proyecto_id = p.id
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                INNER JOIN usuarios plan ON pt.planificador_id = plan.id
                LEFT JOIN usuarios exec ON pt.ejecutor_id = exec.id
                LEFT JOIN usuarios super ON pt.supervisor_id = super.id
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
     * Crear nueva tarea en proyecto
     */
    public function create(array $data): ?int
    {
        try {
            $this->db->beginTransaction();

            // Primero crear la tarea en el catálogo general si no existe
            if (!empty($data['nueva_tarea_nombre'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO tareas (nombre, descripcion, estado_tipo_id)
                    VALUES (?, ?, 1)
                ");
                $stmt->execute([
                    $data['nueva_tarea_nombre'],
                    $data['nueva_tarea_descripcion'] ?? '',
                ]);
                $tareaId = $this->db->lastInsertId();
            } else {
                $tareaId = $data['tarea_id'];
            }

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
                    prioridad,
                    estado_tipo_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $data['proyecto_id'],
                $tareaId,
                $data['planificador_id'],
                $data['ejecutor_id'] ?? null,
                $data['supervisor_id'] ?? null,
                $data['fecha_inicio'],
                $data['duracion_horas'] ?? 1.0,
                $data['prioridad'] ?? 0,
                $data['estado_tipo_id'] ?? 1 // Estado "Creado" por defecto
            ]);

            if ($result) {
                $this->db->commit();
                return $this->db->lastInsertId();
            } else {
                $this->db->rollBack();
                return null;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
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
     * Eliminar tarea (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE proyecto_tareas SET estado_tipo_id = 4, fecha_modificacion = NOW() WHERE id = ?";
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
     * Obtener tipos de tareas (catálogo general)
     */
    public function getTasks(): array
    {
        try {
            $sql = "SELECT id, nombre, descripcion, estado_tipo_id, fecha_Creado, fecha_modificacion FROM tareas WHERE estado_tipo_id = 2 ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Task::getTaskTypes: " . $e->getMessage());
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
    public function getProjects(): array
    {
        try {
            $sql = "
                SELECT p.id, CONCAT('Proyecto para ', c.razon_social) as nombre, c.razon_social as cliente_nombre
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE p.estado_tipo_id IN (1, 2, 5)
                ORDER BY c.razon_social
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
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
    public function getTaskStates(): array
    {
        try {
            $sql = "
                SELECT id, nombre, descripcion
                FROM estado_tipos
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
     * Obtener estados de tareas
     */
    public function getTaskStatesForCreate(): array
    {
        // Todos los estados excepto eliminado
        try {
            $sql = "
                SELECT id, nombre, descripcion
                FROM estado_tipos
                WHERE id != 4
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
     * Validar si una transición de estado es válida
     */
    public function isValidStateTransition(int $currentState, int $newState): array
    {
        // Definir transiciones válidas según reglas de negocio
        $validTransitions = [
            1 => [2, 3, 4], // creado -> activo, inactivo, eliminado
            2 => [1, 3, 4, 5], // activo -> creado, inactivo, eliminado, iniciado
            3 => [1, 2, 4], // inactivo -> creado, activo, eliminado
            4 => [], // eliminado -> no se puede cambiar
            5 => [2, 6, 7], // iniciado -> activo, terminado, rechazado
            6 => [5, 7, 8], // terminado -> iniciado, rechazado, aprobado
            7 => [2, 5], // rechazado -> activo, iniciado
            8 => [6] // aprobado -> solo a terminado (para re-trabajo)
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
            if (!in_array($userRole, ['admin', 'planner'])) {
                return [
                    'valid' => false,
                    'message' => 'Solo usuarios Admin y Planner pueden modificar tareas aprobadas'
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
                'allowed_from' => [5, 6], // Solo desde iniciado y terminado
                'allowed_to' => [7, 8], // Solo a rechazado y aprobado
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
    public function changeState(int $taskId, int $newState, int $userId, string $userRole, string $reason = ''): array
    {
        try {
            $this->db->beginTransaction();

            // Obtener tarea actual
            $task = $this->getById($taskId);
            if (!$task) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ];
            }

            $currentState = (int)$task['estado_tipo_id'];

            // Validar transición de estado
            $transitionValidation = $this->isValidStateTransition($currentState, $newState);
            if (!$transitionValidation['valid']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => $transitionValidation['message']
                ];
            }

            // Validar permisos del usuario
            $userValidation = $this->canUserChangeState($currentState, $newState, $userRole);
            if (!$userValidation['valid']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => $userValidation['message']
                ];
            }

            // Actualizar estado
            $sql = "UPDATE proyecto_tareas SET estado_tipo_id = ?, fecha_modificacion = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$newState, $taskId]);

            if (!$success) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Error al actualizar el estado de la tarea'
                ];
            }

            // Registrar en historial si la tabla existe
            $this->registerStateHistory($taskId, $currentState, $newState, $userId, $reason);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Estado de la tarea actualizado correctamente'
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error("Task::changeState: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno al cambiar el estado de la tarea'
            ];
        }
    }

    /**
     * Registrar cambio de estado en historial
     */
    private function registerStateHistory(int $taskId, int $oldState, int $newState, int $userId, string $reason): void
    {
        try {
            // Verificar si la tabla historial_tareas existe
            $checkTable = $this->db->prepare("SHOW TABLES LIKE 'historial_tareas'");
            $checkTable->execute();

            if ($checkTable->rowCount() > 0) {
                $sql = "
                    INSERT INTO historial_tareas (
                        proyecto_tarea_id,
                        estado_anterior_id,
                        estado_nuevo_id,
                        usuario_id,
                        motivo,
                        fecha_cambio
                    ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$taskId, $oldState, $newState, $userId, $reason]);
            }
        } catch (PDOException $e) {
            // Solo logear el error, no interrumpir el proceso principal
            Logger::error("registrar historial de tarea: " . $e->getMessage());
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
}
