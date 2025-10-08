<?php
namespace App\Services;

use App\Config\Database;
use PDO;
use Exception;

/**
 * CommonDataService - Centraliza consultas duplicadas
 * Elimina métodos duplicados en múltiples controladores
 */
class CommonDataService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tipos de usuario (usado en AccessController, PermissionsController, UserController)
     */
    public function getUserTypes(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM usuario_tipos
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo tipos de usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Estados tipo (usado en PersonaController, UserController, etc.)
     */
    public function getEstadosTipo(array $includeIds = [1, 2, 3, 4]): array
    {
        try {
            $placeholders = str_repeat('?,', count($includeIds) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM estado_tipos
                WHERE id IN ($placeholders)
                ORDER BY id
            ");
            $stmt->execute($includeIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo estados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clientes activos (usado en ClientController, ProjectController)
     */
    public function getActiveClients(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, razon_social, rut
                FROM clientes
                WHERE estado_tipo_id != 4
                ORDER BY razon_social
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo clientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Usuarios activos (usado en múltiples controladores)
     */
    public function getActiveUsers(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.username, p.nombre as nombre_completo, ut.nombre as tipo_usuario
                FROM usuarios u
                INNER JOIN personas p ON u.persona_id = p.id
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE u.estado_tipo_id != 4
                ORDER BY p.nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tipos de tareas (usado en ProjectController, TaskController)
     */
    public function getTaskTypes(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM tarea_tipos
                WHERE estado_tipo_id != 4
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo tipos de tarea: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Proyectos activos (usado en TaskController, otros)
     */
    public function getActiveProjects(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.direccion, c.razon_social as cliente_nombre,
                       CONCAT(c.razon_social, ' - ', p.direccion) as proyecto_display
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE p.estado_tipo_id IN (1, 2, 5)
                ORDER BY c.razon_social, p.direccion
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo proyectos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Personas disponibles (usado en UserController, ClientController)
     */
    public function getAvailablePersonas(?int $excludeUserId = null): array
    {
        try {
            $sql = "
                SELECT p.id, p.rut, p.nombre, p.telefono
                FROM personas p
                WHERE p.estado_tipo_id != 4
            ";
            $params = [];

            if ($excludeUserId) {
                $sql .= " AND p.id NOT IN (SELECT persona_id FROM usuarios WHERE id != ? AND estado_tipo_id != 4)";
                $params[] = $excludeUserId;
            } else {
                $sql .= " AND p.id NOT IN (SELECT persona_id FROM usuarios WHERE estado_tipo_id != 4)";
            }

            $sql .= " ORDER BY p.nombre";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo personas disponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Menús activos por grupo (usado en AccessController, MenuController)
     */
    public function getMenusByGroup(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.id, m.nombre, m.descripcion, m.display, m.icono,
                       mg.id as grupo_id, mg.nombre as grupo_nombre
                FROM menu m
                LEFT JOIN menu_grupo mg ON m.menu_grupo_id = mg.id
                WHERE m.estado_tipo_id = 2
                ORDER BY mg.nombre, m.orden, m.nombre
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agrupar por grupos
            $grouped = [];
            foreach ($result as $menu) {
                $groupName = $menu['grupo_nombre'] ?? 'Sin Grupo';
                if (!isset($grouped[$groupName])) {
                    $grouped[$groupName] = [];
                }
                $grouped[$groupName][] = $menu;
            }

            return $grouped;
        } catch (Exception $e) {
            error_log("Error obteniendo menús por grupo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validación de existencia de entidad
     * Método genérico para validar si una entidad existe
     */
    public function entityExists(string $table, int $id, string $statusField = 'estado_tipo_id'): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM {$table} 
                WHERE id = ? AND {$statusField} != 4
            ");
            $stmt->execute([$id]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error validando existencia en {$table}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas básicas para dashboards
     */
    public function getBasicStats(): array
    {
        try {
            return [
                'total_usuarios' => $this->getCount('usuarios'),
                'total_clientes' => $this->getCount('clientes'),
                'total_proyectos' => $this->getCount('proyectos'),
                'proyectos_activos' => $this->getCount('proyectos', 'estado_tipo_id IN (1, 2, 5)'),
                'tareas_pendientes' => $this->getCount('proyecto_tareas', 'estado_tipo_id IN (1, 2, 6)')
            ];
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas básicas: " . $e->getMessage());
            return [];
        }
    }

    private function getCount(string $table, string $condition = 'estado_tipo_id != 4'): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$condition}");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}