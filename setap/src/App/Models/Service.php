<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use DateTime;
use Exception;
use PDO;

class Service
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getCategoriesByParent(?int $parentId): array
    {
        if ($parentId === null || $parentId === 0) {
            $stmt = $this->db->query("SELECT id, parent_id, nombre FROM servicio_categorias WHERE parent_id IS NULL ORDER BY nombre ASC");
        } else {
            $stmt = $this->db->prepare("SELECT id, parent_id, nombre FROM servicio_categorias WHERE parent_id = ? ORDER BY nombre ASC");
            $stmt->execute([$parentId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategories(): array
    {
        $stmt = $this->db->query("SELECT id, parent_id, nombre FROM servicio_categorias ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getParentCategories(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT p.id, p.parent_id, p.nombre 
                                  FROM servicio_categorias p
                                  INNER JOIN servicio_categorias h ON h.parent_id = p.id
                                  ORDER BY p.nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createCategory(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO servicio_categorias (parent_id, nombre) VALUES (?, ?)");
        $stmt->execute([
            !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            trim($data['nombre'])
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getTypes(array $filters = []): array
    {
        $sql = "SELECT st.*, sc.nombre AS categoria_nombre, p.razon_social AS proveedor_nombre, et.nombre AS estado_nombre
                FROM servicio_tipos st
                LEFT JOIN servicio_categorias sc ON sc.id = st.servicio_categoria_id
                LEFT JOIN proveedores p ON p.id = st.proveedor_id
                LEFT JOIN estado_tipos et ON et.id = st.estado_tipo_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['proveedor_id'])) {
            $sql .= " AND st.proveedor_id = ?";
            $params[] = (int)$filters['proveedor_id'];
        }

        $sql .= " ORDER BY st.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createType(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO servicio_tipos (
                proveedor_id, servicio_categoria_id, codigo, nombre, descripcion, color,
                requiere_aprobacion_cliente, requiere_firma_cliente, genera_proyecto_servicio,
                duracion_estimada_dias, estado_tipo_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$data['proveedor_id'],
            !empty($data['servicio_categoria_id']) ? (int)$data['servicio_categoria_id'] : null,
            $data['codigo'] ?? null,
            trim($data['nombre']),
            $data['descripcion'] ?? null,
            $data['color'] ?? null,
            !empty($data['requiere_aprobacion_cliente']) ? 1 : 0,
            !empty($data['requiere_firma_cliente']) ? 1 : 0,
            !empty($data['genera_proyecto_servicio']) ? 1 : 0,
            !empty($data['duracion_estimada_dias']) ? (int)$data['duracion_estimada_dias'] : null,
            (int)($data['estado_tipo_id'] ?? 2)
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getCatalog(array $filters = []): array
    {
        $sql = "SELECT s.*, st.nombre AS tipo_nombre, sc.nombre AS categoria_nombre,
                    p.razon_social AS proveedor_nombre, sv.id AS version_actual_id,
                    sv.version AS version_actual, sv.precio_base, sv.tiempo_estimado_dias,
                    COUNT(sd.id) AS procesos_count
                FROM servicios s
                INNER JOIN servicio_tipos st ON st.id = s.servicio_tipo_id
                LEFT JOIN servicio_categorias sc ON sc.id = st.servicio_categoria_id
                LEFT JOIN proveedores p ON p.id = s.proveedor_id
                LEFT JOIN servicios_versiones sv ON sv.servicio_id = s.id AND sv.ind_version_actual = 1
                LEFT JOIN servicios_detalle sd ON sd.servicio_version_id = sv.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['proveedor_id'])) {
            $sql .= " AND s.proveedor_id = ?";
            $params[] = (int)$filters['proveedor_id'];
        }

        if (!empty($filters['nombre'])) {
            $sql .= " AND s.nombre LIKE ?";
            $params[] = '%' . $filters['nombre'] . '%';
        }

        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $sql .= " AND s.activo = ?";
            $params[] = (int)$filters['activo'];
        }

        $sql .= " GROUP BY s.id, st.nombre, sc.nombre, p.razon_social, sv.id, sv.version, sv.precio_base, sv.tiempo_estimado_dias
                  ORDER BY s.fecha_creacion DESC, s.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findService(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT s.*, st.nombre AS tipo_nombre, p.razon_social AS proveedor_nombre
            FROM servicios s
            INNER JOIN servicio_tipos st ON st.id = s.servicio_tipo_id
            LEFT JOIN proveedores p ON p.id = s.proveedor_id
            WHERE s.id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        return $service ?: null;
    }

    public function createServiceWithVersion(array $data): int
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO servicios (
                    proveedor_id, servicio_tipo_id, codigo, nombre, descripcion, activo
                ) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                (int)$data['proveedor_id'],
                (int)$data['servicio_tipo_id'],
                $data['codigo'] ?? null,
                trim($data['nombre']),
                $data['descripcion'] ?? null,
                !empty($data['activo']) ? 1 : 0
            ]);
            $serviceId = (int)$this->db->lastInsertId();

            $versionId = $this->createVersionInternal($serviceId, $data, true);
            $this->replaceServiceDetail($versionId, $data);

            $this->db->commit();
            return $serviceId;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error("Service::createServiceWithVersion: " . $e->getMessage());
            throw $e;
        }
    }

    public function createNewVersion(int $serviceId, array $data): int
    {
        try {
            $this->db->beginTransaction();
            $this->db->prepare("UPDATE servicios_versiones SET ind_version_actual = 0 WHERE servicio_id = ?")->execute([$serviceId]);
            $versionId = $this->createVersionInternal($serviceId, $data, true);
            $this->replaceServiceDetail($versionId, $data);
            $this->db->commit();
            return $versionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error("Service::createNewVersion: " . $e->getMessage());
            throw $e;
        }
    }

    private function createVersionInternal(int $serviceId, array $data, bool $current): int
    {
        $version = $this->nextVersionNumber($serviceId);
        $stmt = $this->db->prepare("INSERT INTO servicios_versiones (
                servicio_id, version, nombre_version, descripcion, precio_base, tiempo_estimado_dias,
                vigente_desde, vigente_hasta, activo, ind_version_actual
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $serviceId,
            $version,
            $data['nombre_version'] ?: 'Version ' . $version,
            $data['version_descripcion'] ?? $data['descripcion'] ?? null,
            $data['precio_base'] !== '' ? (float)$data['precio_base'] : null,
            $data['tiempo_estimado_dias'] !== '' ? (int)$data['tiempo_estimado_dias'] : null,
            $data['vigente_desde'] ?: date('Y-m-d'),
            $data['vigente_hasta'] ?: null,
            1,
            $current ? 1 : 0
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function nextVersionNumber(int $serviceId): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(version), 0) + 1 FROM servicios_versiones WHERE servicio_id = ?");
        $stmt->execute([$serviceId]);
        return (int)$stmt->fetchColumn();
    }

    private function replaceServiceDetail(int $versionId, array $data): void
    {
        $processes = json_decode($data['service_processes_json'] ?? '[]', true);
        if (!is_array($processes)) {
            $processes = [];
        }

        foreach ($processes as $index => $process) {
            if (empty($process['proveedor_proceso_id'])) {
                continue;
            }

            $stmt = $this->db->prepare("INSERT INTO servicios_detalle (
                    servicio_version_id, proveedor_proceso_id, orden_ejecucion, dias_desde_inicio, obligatorio
                ) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $versionId,
                (int)$process['proveedor_proceso_id'],
                (int)($process['orden_ejecucion'] ?? ($index + 1)),
                (int)($process['dias_desde_inicio'] ?? $index),
                !empty($process['obligatorio']) ? 1 : 0
            ]);
            $detailId = (int)$this->db->lastInsertId();

            $this->insertResources('servicios_detalle_insumos', $detailId, $process['insumos'] ?? []);
            $this->insertResources('servicios_detalle_activos', $detailId, $process['activos'] ?? []);
        }
    }

    private function insertResources(string $table, int $detailId, array $resources): void
    {
        foreach ($resources as $resource) {
            if (empty($resource['nombre'])) {
                continue;
            }

            if ($table === 'servicios_detalle_insumos') {
                $stmt = $this->db->prepare("INSERT INTO servicios_detalle_insumos (
                    servicio_detalle_id, nombre, descripcion, cantidad, unidad_medida, obligatorio
                ) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $detailId,
                    trim($resource['nombre']),
                    $resource['descripcion'] ?? null,
                    (float)($resource['cantidad'] ?? 1),
                    $resource['unidad_medida'] ?? null,
                    !empty($resource['obligatorio']) ? 1 : 0
                ]);
                continue;
            }

            $stmt = $this->db->prepare("INSERT INTO servicios_detalle_activos (
                servicio_detalle_id, nombre, descripcion, cantidad, obligatorio
            ) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $detailId,
                trim($resource['nombre']),
                $resource['descripcion'] ?? null,
                (int)($resource['cantidad'] ?? 1),
                !empty($resource['obligatorio']) ? 1 : 0
            ]);
        }
    }

    public function getVersions(int $serviceId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM servicios_versiones WHERE servicio_id = ? ORDER BY version DESC");
        $stmt->execute([$serviceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVersionDetail(int $versionId): array
    {
        $stmt = $this->db->prepare("SELECT sd.*, pp.nombre AS proceso_nombre, pp.descripcion AS proceso_descripcion
            FROM servicios_detalle sd
            INNER JOIN proveedor_procesos pp ON pp.id = sd.proveedor_proceso_id
            WHERE sd.servicio_version_id = ?
            ORDER BY sd.orden_ejecucion ASC, sd.id ASC");
        $stmt->execute([$versionId]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($details as &$detail) {
            $detail['tareas'] = $this->getProcessTasks((int)$detail['proveedor_proceso_id']);
            $detail['insumos'] = $this->getDetailResources('servicios_detalle_insumos', (int)$detail['id']);
            $detail['activos'] = $this->getDetailResources('servicios_detalle_activos', (int)$detail['id']);
        }

        return $details;
    }

    private function getProcessTasks(int $processId): array
    {
        $stmt = $this->db->prepare("SELECT ppt.*, t.nombre AS tarea_nombre, t.descripcion AS tarea_descripcion
            FROM proveedor_proceso_tareas ppt
            INNER JOIN tareas t ON t.id = ppt.tarea_id
            WHERE ppt.proveedor_proceso_id = ?
            ORDER BY ppt.id ASC");
        $stmt->execute([$processId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDetailResources(string $table, int $detailId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE servicio_detalle_id = ? ORDER BY nombre ASC");
        $stmt->execute([$detailId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServiceVersionsForSelect(?int $providerId = null): array
    {
        $sql = "SELECT sv.id, sv.version, sv.nombre_version, sv.tiempo_estimado_dias,
                    s.nombre AS servicio_nombre, s.proveedor_id, p.razon_social AS proveedor_nombre
                FROM servicios_versiones sv
                INNER JOIN servicios s ON s.id = sv.servicio_id
                LEFT JOIN proveedores p ON p.id = s.proveedor_id
                WHERE sv.activo = 1 AND s.activo = 1";
        $params = [];

        if ($providerId) {
            $sql .= " AND s.proveedor_id = ?";
            $params[] = $providerId;
        }

        $sql .= " ORDER BY s.nombre ASC, sv.version DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServiceVersion(int $versionId): ?array
    {
        $stmt = $this->db->prepare("SELECT sv.*, s.nombre AS servicio_nombre, s.descripcion AS servicio_descripcion,
                    s.proveedor_id, st.nombre AS tipo_nombre
                FROM servicios_versiones sv
                INNER JOIN servicios s ON s.id = sv.servicio_id
                INNER JOIN servicio_tipos st ON st.id = s.servicio_tipo_id
                WHERE sv.id = ?");
        $stmt->execute([$versionId]);
        $version = $stmt->fetch(PDO::FETCH_ASSOC);
        return $version ?: null;
    }

    public function getClientsForService(?int $providerId = null): array
    {
        $sql = "SELECT id, razon_social, rut, proveedor_id
                FROM clientes
                WHERE estado_tipo_id != 4 AND ind_cliente_servicio = 1";
        $params = [];
        if ($providerId) {
            $sql .= " AND proveedor_id = ?";
            $params[] = $providerId;
        }
        $sql .= " ORDER BY razon_social ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createServiceClient(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO clientes (
                proveedor_id, rut, razon_social, direccion, email, telefono, ind_cliente_servicio, estado_tipo_id
            ) VALUES (?, ?, ?, ?, ?, ?, 1, 2)");
        $stmt->execute([
            (int)$data['proveedor_id'],
            $data['rut'] ?? null,
            trim($data['razon_social']),
            $data['direccion'] ?? null,
            $data['email'] ?? null,
            $data['telefono'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getOperationalProjects(int $providerId): array
    {
        $stmt = $this->db->prepare("SELECT p.id, p.cliente_id, p.fecha_inicio, p.fecha_fin,
                    c.razon_social AS cliente_nombre,
                    CONCAT(c.razon_social, ' | ', DATE_FORMAT(p.fecha_inicio, '%Y-%m-%d'), ' - ', DATE_FORMAT(p.fecha_fin, '%Y-%m-%d')) AS nombre
                FROM proyectos p
                INNER JOIN clientes c ON c.id = p.cliente_id
                WHERE p.proveedor_id = ? AND p.estado_tipo_id != 4
                ORDER BY p.fecha_inicio ASC");
        $stmt->execute([$providerId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($projects as &$project) {
            $project['dias_habiles'] = $this->countWorkingDays($project['fecha_inicio'], $project['fecha_fin']);
        }

        return $projects;
    }

    public function getPlannedServices(array $filters, array $user): array
    {
        $sql = "SELECT sp.*, c.razon_social AS cliente_nombre, p.razon_social AS proveedor_nombre,
                    s.nombre AS servicio_nombre, sv.version, et.nombre AS estado_nombre,
                    COUNT(spt.id) AS tareas_total,
                    SUM(CASE WHEN pt.estado_tipo_id IN (6,8) THEN 1 ELSE 0 END) AS tareas_completadas
                FROM servicios_planificados sp
                INNER JOIN clientes c ON c.id = sp.cliente_id
                INNER JOIN servicios_versiones sv ON sv.id = sp.servicio_version_id
                INNER JOIN servicios s ON s.id = sv.servicio_id
                LEFT JOIN proveedores p ON p.id = sp.proveedor_id
                LEFT JOIN estado_tipos et ON et.id = sp.estado_operacional_id
                LEFT JOIN servicios_planificados_tareas spt ON spt.servicio_planificado_id = sp.id
                LEFT JOIN proyecto_tareas pt ON pt.id = spt.proyecto_tarea_id
                WHERE 1=1";
        $params = [];

        $this->appendVisibilityFilter($sql, $params, $user);

        if (!empty($filters['proveedor_id'])) {
            $sql .= " AND sp.proveedor_id = ?";
            $params[] = (int)$filters['proveedor_id'];
        }

        if (!empty($filters['cliente_id'])) {
            $sql .= " AND sp.cliente_id = ?";
            $params[] = (int)$filters['cliente_id'];
        }

        if (!empty($filters['estado_operacional_id'])) {
            $sql .= " AND sp.estado_operacional_id = ?";
            $params[] = (int)$filters['estado_operacional_id'];
        }

        if (!empty($filters['id'])) {
            $sql .= " AND sp.id = ?";
            $params[] = (int)$filters['id'];
        }

        $sql .= " GROUP BY sp.id, c.razon_social, p.razon_social, s.nombre, sv.version, et.nombre
                  ORDER BY sp.fecha_creacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['porcentaje_calculado'] = $this->calculateProgressFromCounts((int)$row['tareas_total'], (int)$row['tareas_completadas']);
            if ($row['porcentaje_calculado'] >= 100 && (int)($row['estado_operacional_id'] ?? 0) !== 8) {
                $this->db->prepare("UPDATE servicios_planificados
                        SET porcentaje_avance = 100, estado_operacional_id = 8, fecha_termino_real = COALESCE(fecha_termino_real, CURRENT_DATE)
                        WHERE id = ?")
                    ->execute([(int)$row['id']]);
                $row['porcentaje_avance'] = 100;
                $row['estado_operacional_id'] = 8;
                $row['estado_nombre'] = 'Completado';
            }
        }

        return $rows;
    }

    private function appendVisibilityFilter(string &$sql, array &$params, array $user): void
    {
        if ((int)$user['id'] === 1) {
            return;
        }

        $userType = (int)($user['usuario_tipo_id'] ?? 0);
        if ($userType === 5 && !empty($user['cliente_id'])) {
            $sql .= " AND sp.cliente_id = ? AND EXISTS (
                SELECT 1 FROM clientes cx
                WHERE cx.id = sp.cliente_id AND cx.ind_cliente_servicio = 1
            )";
            $params[] = (int)$user['cliente_id'];
            return;
        }

        if ($userType === 4) {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM servicios_planificados_tareas sx
                INNER JOIN proyecto_tareas ptx ON ptx.id = sx.proyecto_tarea_id
                WHERE sx.servicio_planificado_id = sp.id AND ptx.supervisor_id = ?
            )";
            $params[] = (int)$user['id'];
            return;
        }

        if (!empty($user['proveedor_id'])) {
            $sql .= " AND sp.proveedor_id = ?";
            $params[] = (int)$user['proveedor_id'];
        }
    }

    public function planService(array $data, array $user): int
    {
        try {
            $this->db->beginTransaction();

            $version = $this->getServiceVersion((int)$data['servicio_version_id']);
            if (!$version) {
                throw new Exception('Version de servicio no encontrada');
            }

            $projectIds = array_map('intval', $data['proyecto_ids'] ?? []);
            if (empty($projectIds)) {
                throw new Exception('Debe seleccionar al menos un proyecto operacional');
            }

            $projects = $this->getProjectsByIds($projectIds, (int)$version['proveedor_id']);
            if (count($projects) !== count(array_unique($projectIds))) {
                throw new Exception('Uno o mas proyectos no pertenecen al proveedor del servicio');
            }

            $details = $this->getVersionDetail((int)$version['id']);
            $requiredDays = max((int)($version['tiempo_estimado_dias'] ?? 0), $this->estimateRequiredDays($details));
            $availableDays = array_sum(array_map(fn($p) => (int)$p['dias_habiles'], $projects));
            if ($requiredDays > 0 && $availableDays < $requiredDays) {
                throw new Exception("Los proyectos seleccionados tienen {$availableDays} dias habiles y el servicio requiere {$requiredDays}");
            }

            $stmt = $this->db->prepare("INSERT INTO servicios_planificados (
                    proveedor_id, cliente_id, servicio_version_id, version_nombre_snapshot, nombre, descripcion,
                    fecha_inicio, fecha_termino_estimada, porcentaje_avance, estado_operacional_id,
                    usuario_creacion_id, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 2, ?, ?)");
            $stmt->execute([
                (int)$version['proveedor_id'],
                (int)$data['cliente_id'],
                (int)$version['id'],
                $version['servicio_nombre'] . ' v' . $version['version'],
                trim($data['nombre'] ?: $version['servicio_nombre']),
                $data['descripcion'] ?? $version['servicio_descripcion'] ?? null,
                $data['fecha_inicio'],
                $this->calculateEndDate($data['fecha_inicio'], $requiredDays),
                (int)$user['id'],
                $data['observaciones'] ?? null
            ]);
            $plannedId = (int)$this->db->lastInsertId();

            foreach (array_values($projects) as $index => $project) {
                $stmt = $this->db->prepare("INSERT INTO servicios_planificados_proyectos (
                    servicio_planificado_id, proyecto_id, orden, fecha_inicio, fecha_termino
                ) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $plannedId,
                    (int)$project['id'],
                    $index + 1,
                    $project['fecha_inicio'],
                    $project['fecha_fin']
                ]);
            }

            $this->generateOperationalTasks($plannedId, $details, $projects, $data['fecha_inicio'], (int)$user['id']);
            $this->refreshProgress($plannedId);
            $this->logHistory($plannedId, (int)$user['id'], 'creacion', null, 'planificado', ['input' => $data]);

            $this->db->commit();
            return $plannedId;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error("Service::planService: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateOperationalTasks(int $plannedId, array $details, array $projects, string $startDate, int $plannerId): void
    {
        $visualOrder = 1;
        foreach ($details as $detail) {
            foreach ($detail['tareas'] as $task) {
                $scheduledDate = $this->nextWorkingDate($startDate, (int)$detail['dias_desde_inicio'] + $visualOrder - 1);
                $project = $this->selectProjectForDate($projects, $scheduledDate);
                if (!$project) {
                    throw new Exception("No hay proyecto operacional que cubra la fecha {$scheduledDate}");
                }

                $duration = (float)($task['hh'] ?? 1);
                $stmt = $this->db->prepare("INSERT INTO proyecto_tareas (
                        proyecto_id, tarea_id, planificador_id, ejecutor_id, supervisor_id,
                        fecha_inicio, duracion_horas, fecha_fin, prioridad, estado_tipo_id
                    ) VALUES (?, ?, ?, NULL, NULL, ?, ?, ?, ?, 2)");
                $stmt->execute([
                    (int)$project['id'],
                    (int)$task['tarea_id'],
                    $plannerId,
                    $scheduledDate . ' 09:00:00',
                    $duration,
                    $scheduledDate . ' 18:00:00',
                    (int)($task['prioridad'] ?? 5)
                ]);
                $projectTaskId = (int)$this->db->lastInsertId();

                $stmt = $this->db->prepare("INSERT INTO servicios_planificados_tareas (
                    servicio_planificado_id, proyecto_tarea_id, orden_visualizacion, fecha_programada_original
                ) VALUES (?, ?, ?, ?)");
                $stmt->execute([$plannedId, $projectTaskId, $visualOrder, $scheduledDate]);
                $visualOrder++;
            }
        }
    }

    private function getProjectsByIds(array $projectIds, int $providerId): array
    {
        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
        $params = array_merge([$providerId], $projectIds);
        $stmt = $this->db->prepare("SELECT id, fecha_inicio, fecha_fin
            FROM proyectos
            WHERE proveedor_id = ? AND id IN ({$placeholders}) AND estado_tipo_id != 4
            ORDER BY fecha_inicio ASC");
        $stmt->execute($params);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($projects as &$project) {
            $project['dias_habiles'] = $this->countWorkingDays($project['fecha_inicio'], $project['fecha_fin']);
        }
        return $projects;
    }

    private function selectProjectForDate(array $projects, string $date): ?array
    {
        foreach ($projects as $project) {
            if ($date >= substr($project['fecha_inicio'], 0, 10) && $date <= substr($project['fecha_fin'], 0, 10)) {
                return $project;
            }
        }
        return null;
    }

    private function estimateRequiredDays(array $details): int
    {
        $count = 0;
        foreach ($details as $detail) {
            $count += max(1, count($detail['tareas']));
        }
        return $count;
    }

    private function calculateEndDate(string $startDate, int $days): string
    {
        return $this->nextWorkingDate($startDate, max(0, $days - 1));
    }

    private function nextWorkingDate(string $startDate, int $offset): string
    {
        $date = new DateTime($startDate);
        $added = 0;
        while ($added < $offset || in_array((int)$date->format('N'), [6, 7], true)) {
            $date->modify('+1 day');
            if (!in_array((int)$date->format('N'), [6, 7], true)) {
                $added++;
            }
        }
        return $date->format('Y-m-d');
    }

    private function countWorkingDays(string $startDate, string $endDate): int
    {
        $start = new DateTime(substr($startDate, 0, 10));
        $end = new DateTime(substr($endDate, 0, 10));
        $days = 0;
        while ($start <= $end) {
            if (!in_array((int)$start->format('N'), [6, 7], true)) {
                $days++;
            }
            $start->modify('+1 day');
        }
        return $days;
    }

    public function findPlannedService(int $id, array $user): ?array
    {
        $rows = $this->getPlannedServices(['id' => $id], $user);
        foreach ($rows as $row) {
            if ((int)$row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    public function getTrackingDetail(int $plannedId): array
    {
        $stmt = $this->db->prepare("SELECT sp.servicio_version_id FROM servicios_planificados sp WHERE sp.id = ?");
        $stmt->execute([$plannedId]);
        $versionId = (int)$stmt->fetchColumn();
        $processes = $this->getVersionDetail($versionId);

        $stmt = $this->db->prepare("SELECT spt.orden_visualizacion, spt.fecha_programada_original,
                    pt.id AS proyecto_tarea_id, pt.estado_tipo_id, pt.fecha_inicio, pt.fecha_fin,
                    pt.supervisor_id, pt.ejecutor_id, t.nombre AS tarea_nombre,
                    et.nombre AS estado_nombre, ue.nombre AS ejecutor_nombre
                FROM servicios_planificados_tareas spt
                INNER JOIN proyecto_tareas pt ON pt.id = spt.proyecto_tarea_id
                INNER JOIN tareas t ON t.id = pt.tarea_id
                LEFT JOIN estado_tipos et ON et.id = pt.estado_tipo_id
                LEFT JOIN usuarios ue ON ue.id = pt.ejecutor_id
                WHERE spt.servicio_planificado_id = ?
                ORDER BY spt.orden_visualizacion ASC");
        $stmt->execute([$plannedId]);
        return [
            'processes' => $processes,
            'tasks' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    public function changePlannedState(int $plannedId, int $newState, int $userId, string $action): void
    {
        $stmt = $this->db->prepare("SELECT estado_operacional_id FROM servicios_planificados WHERE id = ?");
        $stmt->execute([$plannedId]);
        $oldState = $stmt->fetchColumn();

        $this->db->prepare("UPDATE servicios_planificados SET estado_operacional_id = ? WHERE id = ?")
            ->execute([$newState, $plannedId]);

        if ($action === 'termino_anticipado') {
            $this->db->prepare("UPDATE proyecto_tareas pt
                    INNER JOIN servicios_planificados_tareas spt ON spt.proyecto_tarea_id = pt.id
                    SET pt.estado_tipo_id = 4
                    WHERE spt.servicio_planificado_id = ? AND pt.estado_tipo_id IN (1,2,3)")
                ->execute([$plannedId]);
        }

        $this->logHistory($plannedId, $userId, $action, (string)$oldState, (string)$newState);
    }

    public function replan(int $plannedId, string $fromDate, int $days, int $userId): void
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE proyecto_tareas pt
                INNER JOIN servicios_planificados_tareas spt ON spt.proyecto_tarea_id = pt.id
                SET pt.fecha_inicio = DATE_ADD(pt.fecha_inicio, INTERVAL ? DAY),
                    pt.fecha_fin = DATE_ADD(pt.fecha_fin, INTERVAL ? DAY),
                    spt.ind_replanificada = 1
                WHERE spt.servicio_planificado_id = ?
                  AND DATE(pt.fecha_inicio) >= ?
                  AND pt.estado_tipo_id IN (1,2,3)");
            $stmt->execute([$days, $days, $plannedId, $fromDate]);
            $this->logHistory($plannedId, $userId, 'replanificacion', null, null, [
                'desde' => $fromDate,
                'dias_desfase' => $days
            ]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function refreshProgress(int $plannedId): float
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total,
                SUM(CASE WHEN pt.estado_tipo_id IN (6,8) THEN 1 ELSE 0 END) AS completadas
            FROM servicios_planificados_tareas spt
            INNER JOIN proyecto_tareas pt ON pt.id = spt.proyecto_tarea_id
            WHERE spt.servicio_planificado_id = ? AND pt.estado_tipo_id != 4");
        $stmt->execute([$plannedId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'completadas' => 0];
        $progress = $this->calculateProgressFromCounts((int)$row['total'], (int)$row['completadas']);
        $state = $progress >= 100 ? 8 : 2;
        $realEnd = $progress >= 100 ? date('Y-m-d') : null;

        $this->db->prepare("UPDATE servicios_planificados
                SET porcentaje_avance = ?, estado_operacional_id = ?, fecha_termino_real = COALESCE(?, fecha_termino_real)
                WHERE id = ?")
            ->execute([$progress, $state, $realEnd, $plannedId]);

        return $progress;
    }

    private function calculateProgressFromCounts(int $total, int $completed): float
    {
        if ($total <= 0) {
            return 0.0;
        }
        return round(($completed / $total) * 100, 2);
    }

    private function logHistory(int $plannedId, int $userId, string $action, ?string $oldState = null, ?string $newState = null, array $snapshot = []): void
    {
        try {
            if (!$this->tableExists('historial_servicios')) {
                return;
            }

            $stmt = $this->db->prepare("INSERT INTO historial_servicios (
                    servicio_planificado_id, usuario_id, accion, estado_anterior, estado_nuevo, snapshot_json, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([
                $plannedId,
                $userId,
                $action,
                $oldState,
                $newState,
                json_encode($snapshot, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (Exception $e) {
            Logger::error("Service::logHistory: " . $e->getMessage());
        }
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }
}
