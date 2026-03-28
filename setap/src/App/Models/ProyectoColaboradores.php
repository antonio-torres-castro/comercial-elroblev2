<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use DateInterval;
use DateTime;
use PDO;
use PDOException;

class ProyectoColaboradores
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getTiposFecha(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, descripcion FROM tipos_fecha ORDER BY id");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::getTiposFecha error: ' . $e->getMessage());
            return [];
        }
    }

    public function getProjectExecutors(int $projectId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT pug.id as pug_id,
                    pug.usuario_id,
                    pug.hh as hh_default,
                    u.nombre_usuario,
                    p.nombre as nombre_completo
                FROM proyecto_usuarios_grupo pug
                INNER JOIN usuarios u ON pug.usuario_id = u.id
                INNER JOIN personas p ON u.persona_id = p.id
                WHERE pug.proyecto_id = ? AND pug.grupo_id = 4 AND pug.estado_tipo_id != 4
                ORDER BY p.nombre");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::getProjectExecutors error: ' . $e->getMessage());
            return [];
        }
    }

    public function getAvailableExecutors(int $projectId, int $proveedorId, bool $isAdmin): array
    {
        try {
            $params = [];
            $sql = "SELECT u.id, u.nombre_usuario, p.nombre as nombre_completo
                FROM usuarios u
                INNER JOIN personas p ON u.persona_id = p.id
                WHERE u.estado_tipo_id = 2 AND u.usuario_tipo_id = 4";

            if (!$isAdmin) {
                $sql .= " AND u.proveedor_id = ?";
                $params[] = $proveedorId;
            }

            $sql .= " AND NOT EXISTS (
                    SELECT 1 FROM proyecto_usuarios_grupo pug
                    WHERE pug.proyecto_id = ?
                      AND pug.usuario_id = u.id
                      AND pug.grupo_id = 4
                      AND pug.estado_tipo_id != 4
                )";
            $params[] = $projectId;

            $sql .= " ORDER BY p.nombre";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::getAvailableExecutors error: ' . $e->getMessage());
            return [];
        }
    }

    public function addExecutorToProject(int $projectId, int $usuarioId, int $grupoId, float $hh): array
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(1) FROM proyecto_usuarios_grupo
                WHERE proyecto_id = ? AND usuario_id = ? AND grupo_id = ? AND estado_tipo_id != 4");
            $stmt->execute([$projectId, $usuarioId, $grupoId]);
            if ((int)$stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'La asociacion ya existe'];
            }

            $stmt = $this->db->prepare("INSERT INTO proyecto_usuarios_grupo
                (proyecto_id, grupo_id, usuario_id, hh, estado_tipo_id)
                VALUES (?, ?, ?, ?, 2)");
            $ok = $stmt->execute([$projectId, $grupoId, $usuarioId, $hh]);

            return $ok ? ['success' => true] : ['success' => false, 'message' => 'No se pudo insertar'];
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::addExecutorToProject error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno'];
        }
    }

    public function getExecutorDefaultHh(int $projectId, int $usuarioId, int $grupoId): float
    {
        try {
            $stmt = $this->db->prepare("SELECT hh FROM proyecto_usuarios_grupo
                WHERE proyecto_id = ? AND usuario_id = ? AND grupo_id = ? AND estado_tipo_id != 4
                LIMIT 1");
            $stmt->execute([$projectId, $usuarioId, $grupoId]);
            $hh = $stmt->fetchColumn();
            return $hh !== false ? (float)$hh : 0.0;
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::getExecutorDefaultHh error: ' . $e->getMessage());
            return 0.0;
        }
    }
    public function getWorkingDays(string $fechaInicio, string $fechaFin): array
    {
        try {
            $start = new DateTime($fechaInicio);
            $end = new DateTime($fechaFin);
            $days = [];

            while ($start <= $end) {
                $dayOfWeek = (int)$start->format('N');
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                    $days[] = $start->format('Y-m-d');
                }
                $start->add(new DateInterval('P1D'));
            }

            return $days;
        } catch (\Exception $e) {
            Logger::error('ProyectoColaboradores::getWorkingDays error: ' . $e->getMessage());
            return [];
        }
    }

    public function seedAvailabilityForUser(
        int $projectId,
        int $usuarioId,
        int $grupoId,
        string $fechaInicio,
        string $fechaFin,
        float $defaultHh,
        int $tipoFechaId = 1,
        array $excludedDates = []
    ): array {
        try {
            $workingDays = $this->getWorkingDays($fechaInicio, $fechaFin);
            if (!empty($excludedDates)) {
                $excludedMap = array_flip($excludedDates);
                $workingDays = array_values(array_filter($workingDays, function ($fecha) use ($excludedMap) {
                    return !isset($excludedMap[$fecha]);
                }));
            }

            if (empty($workingDays)) {
                return ['created' => 0];
            }

            $stmt = $this->db->prepare("SELECT fecha FROM proyecto_usuarios_grupo_disponibilidad
                WHERE proyecto_id = ? AND usuario_id = ? AND grupo_id = ? AND fecha BETWEEN ? AND ?");
            $stmt->execute([$projectId, $usuarioId, $grupoId, $fechaInicio, $fechaFin]);
            $existing = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $existingMap = array_flip($existing);

            $this->db->beginTransaction();
            $insert = $this->db->prepare("INSERT INTO proyecto_usuarios_grupo_disponibilidad
                (proyecto_id, grupo_id, usuario_id, fecha, hh, tipo_fecha_id)
                VALUES (?, ?, ?, ?, ?, ?)");

            $created = 0;
            foreach ($workingDays as $fecha) {
                if (isset($existingMap[$fecha])) {
                    continue;
                }
                $insert->execute([$projectId, $grupoId, $usuarioId, $fecha, $defaultHh, $tipoFechaId]);
                $created++;
            }

            $this->db->commit();
            return ['created' => $created];
        } catch (PDOException $e) {
            $this->db->rollBack();
            Logger::error('ProyectoColaboradores::seedAvailabilityForUser error: ' . $e->getMessage());
            return ['created' => 0, 'error' => $e->getMessage()];
        }
    }

    public function countDisponibilidadByUserProject(int $projectId, int $usuarioId, int $grupoId): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(1) FROM proyecto_usuarios_grupo_disponibilidad
                WHERE proyecto_id = ? AND usuario_id = ? AND grupo_id = ?");
            $stmt->execute([$projectId, $usuarioId, $grupoId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::countDisponibilidadByUserProject error: ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteDisponibilidad(int $projectId, int $usuarioId, int $grupoId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM proyecto_usuarios_grupo_disponibilidad
                WHERE proyecto_id = ? AND usuario_id = ? AND grupo_id = ?");
            return $stmt->execute([$projectId, $usuarioId, $grupoId]);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::deleteDisponibilidad error: ' . $e->getMessage());
            return false;
        }
    }

    public function getDisponibilidad(
        int $projectId,
        int $usuarioId,
        int $grupoId,
        string $fechaInicio,
        string $fechaFin
    ): array {
        try {
            $stmt = $this->db->prepare("SELECT d.id, d.fecha, d.hh, d.tipo_fecha_id, tf.nombre as tipo_fecha_nombre
                FROM proyecto_usuarios_grupo_disponibilidad d
                INNER JOIN tipos_fecha tf ON d.tipo_fecha_id = tf.id
                WHERE d.proyecto_id = ? AND d.usuario_id = ? AND d.grupo_id = ?
                  AND d.fecha BETWEEN ? AND ?
                ORDER BY d.fecha");
            $stmt->execute([$projectId, $usuarioId, $grupoId, $fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::getDisponibilidad error: ' . $e->getMessage());
            return [];
        }
    }

    public function getDisponibilidadByUsers(
        int $projectId,
        array $usuarioIds,
        int $grupoId,
        string $fechaInicio,
        string $fechaFin
    ): array {
        if (empty($usuarioIds)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($usuarioIds), '?'));
            $sql = "SELECT usuario_id, fecha, hh, tipo_fecha_id
                FROM proyecto_usuarios_grupo_disponibilidad
                WHERE proyecto_id = ? AND grupo_id = ?
                  AND usuario_id IN ($placeholders)
                  AND fecha BETWEEN ? AND ?";

            $params = array_merge([$projectId, $grupoId], $usuarioIds, [$fechaInicio, $fechaFin]);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::getDisponibilidadByUsers error: ' . $e->getMessage());
            return [];
        }
    }

    public function upsertDisponibilidad(
        int $projectId,
        int $usuarioId,
        int $grupoId,
        string $fecha,
        float $hh,
        int $tipoFechaId
    ): bool {
        try {
            $stmt = $this->db->prepare("SELECT id FROM proyecto_usuarios_grupo_disponibilidad
                WHERE proyecto_id = ? AND usuario_id = ? AND grupo_id = ? AND fecha = ?");
            $stmt->execute([$projectId, $usuarioId, $grupoId, $fecha]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $this->db->prepare("UPDATE proyecto_usuarios_grupo_disponibilidad
                    SET hh = ?, tipo_fecha_id = ?
                    WHERE id = ?");
                return $stmt->execute([$hh, $tipoFechaId, $existing['id']]);
            }

            $stmt = $this->db->prepare("INSERT INTO proyecto_usuarios_grupo_disponibilidad
                (proyecto_id, grupo_id, usuario_id, fecha, hh, tipo_fecha_id)
                VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$projectId, $grupoId, $usuarioId, $fecha, $hh, $tipoFechaId]);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::upsertDisponibilidad error: ' . $e->getMessage());
            return false;
        }
    }

    public function getHorasOtrosProyectos(
        int $usuarioId,
        int $projectId,
        string $fechaInicio,
        string $fechaFin
    ): array {
        try {
            $stmt = $this->db->prepare("SELECT DATE(pt.fecha_inicio) as fecha,
                    SUM(pt.duracion_horas) as hh_op
                FROM proyecto_tareas pt
                WHERE pt.ejecutor_id = ?
                  AND pt.proyecto_id != ?
                  AND pt.estado_tipo_id IN (2, 5, 6, 7, 8)
                  AND pt.fecha_inicio BETWEEN ? AND ?
                GROUP BY DATE(pt.fecha_inicio)");
            $stmt->execute([$usuarioId, $projectId, $fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::getHorasOtrosProyectos error: ' . $e->getMessage());
            return [];
        }
    }

    public function getHorasOtrosProyectosByUsers(
        array $usuarioIds,
        int $projectId,
        string $fechaInicio,
        string $fechaFin
    ): array {
        if (empty($usuarioIds)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($usuarioIds), '?'));
            $sql = "SELECT pt.ejecutor_id as usuario_id,
                    DATE(pt.fecha_inicio) as fecha,
                    SUM(pt.duracion_horas) as hh_op
                FROM proyecto_tareas pt
                WHERE pt.ejecutor_id IN ($placeholders)
                  AND pt.proyecto_id != ?
                  AND pt.estado_tipo_id IN (2, 5, 6, 7, 8)
                  AND pt.fecha_inicio BETWEEN ? AND ?
                GROUP BY pt.ejecutor_id, DATE(pt.fecha_inicio)";
            $params = array_merge($usuarioIds, [$projectId, $fechaInicio, $fechaFin]);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('ProyectoColaboradores::getHorasOtrosProyectosByUsers error: ' . $e->getMessage());
            return [];
        }
    }
}
