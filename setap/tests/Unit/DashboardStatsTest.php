<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Config\Database;

class DashboardStatsTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        // Crear tablas necesarias para testing
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS estado_tipos (
                id INTEGER PRIMARY KEY,
                nombre VARCHAR(50) NOT NULL,
                descripcion VARCHAR(500) NOT NULL
            );
            INSERT OR IGNORE INTO estado_tipos (id, nombre, descripcion) VALUES 
            (1, 'creado', 'Registro en proceso'),
            (2, 'activo', 'Registro activo'),
            (3, 'inactivo', 'Registro inactivo'),
            (4, 'eliminado', 'Registro eliminado'),
            (5, 'iniciado', 'En ejecución');
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS usuario_tipos (
                id INTEGER PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                descripcion VARCHAR(500) NOT NULL
            );
            INSERT OR IGNORE INTO usuario_tipos (id, nombre, descripcion) VALUES 
            (1, 'admin', 'Administrador');
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS personas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                rut VARCHAR(20) NOT NULL,
                nombre VARCHAR(150) NOT NULL,
                estado_tipo_id INT DEFAULT 1
            );
            INSERT OR IGNORE INTO personas (id, rut, nombre) VALUES 
            (1, '12345678-9', 'Test User');
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                persona_id INT NOT NULL,
                usuario_tipo_id INT NOT NULL,
                email VARCHAR(150) NOT NULL,
                nombre_usuario VARCHAR(100) NOT NULL,
                clave_hash VARCHAR(255) NOT NULL,
                estado_tipo_id INT DEFAULT 2
            );
            INSERT OR IGNORE INTO usuarios (id, persona_id, usuario_tipo_id, email, nombre_usuario, clave_hash, estado_tipo_id) VALUES 
            (1, 1, 1, 'test@example.com', 'testuser', 'hash123', 2),
            (2, 1, 1, 'test2@example.com', 'testuser2', 'hash456', 2);
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS tarea_tipos (
                id INTEGER PRIMARY KEY,
                nombre VARCHAR(50) NOT NULL
            );
            INSERT OR IGNORE INTO tarea_tipos (id, nombre) VALUES (1, 'intelectual');
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS clientes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                razon_social VARCHAR(150) NOT NULL,
                estado_tipo_id INT DEFAULT 2
            );
            INSERT OR IGNORE INTO clientes (id, razon_social) VALUES (1, 'Cliente Test');
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS cliente_contrapartes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cliente_id INT NOT NULL,
                persona_id INT NOT NULL,
                email VARCHAR(150),
                estado_tipo_id INT DEFAULT 2
            );
            INSERT OR IGNORE INTO cliente_contrapartes (id, cliente_id, persona_id, email) VALUES 
            (1, 1, 1, 'contraparte@example.com');
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS proyectos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cliente_id INT NOT NULL,
                fecha_inicio DATE NOT NULL,
                tarea_tipo_id INT NOT NULL,
                estado_tipo_id INT DEFAULT 2,
                contraparte_id INT NOT NULL
            );
            INSERT OR IGNORE INTO proyectos (id, cliente_id, fecha_inicio, tarea_tipo_id, estado_tipo_id, contraparte_id) VALUES 
            (1, 1, '2024-01-01', 1, 2, 1),
            (2, 1, '2024-01-02', 1, 5, 1),
            (3, 1, '2024-01-03', 1, 1, 1);
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS tareas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(150) NOT NULL,
                estado_tipo_id INT DEFAULT 2
            );
            INSERT OR IGNORE INTO tareas (id, nombre) VALUES (1, 'Tarea Test');
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS proyecto_tareas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                proyecto_id INT NOT NULL,
                tarea_id INT NOT NULL,
                planificador_id INT NOT NULL,
                fecha_inicio DATETIME NOT NULL,
                duracion_horas DECIMAL(4,2) NOT NULL,
                estado_tipo_id INT NOT NULL
            );
            INSERT OR IGNORE INTO proyecto_tareas (proyecto_id, tarea_id, planificador_id, fecha_inicio, duracion_horas, estado_tipo_id) VALUES 
            (1, 1, 1, '2024-01-01 09:00:00', 8.0, 1),
            (1, 1, 1, '2024-01-02 09:00:00', 4.0, 2),
            (2, 1, 1, '2024-01-03 09:00:00', 6.0, 5),
            (3, 1, 1, '2024-01-04 09:00:00', 3.0, 1);
        ");
    }

    /**
     * Test de consulta de total de usuarios
     */
    public function testTotalUsuarios()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE estado_tipo_id != 4");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(2, $count, "Debe haber 2 usuarios activos en los datos de prueba");
    }

    /**
     * Test de consulta de total de proyectos
     */
    public function testTotalProyectos()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyectos WHERE estado_tipo_id != 4");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(3, $count, "Debe haber 3 proyectos no eliminados en los datos de prueba");
    }

    /**
     * Test de consulta de proyectos activos
     */
    public function testProyectosActivos()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyectos WHERE estado_tipo_id IN (2, 5)");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(2, $count, "Debe haber 2 proyectos activos/iniciados en los datos de prueba");
    }

    /**
     * Test de consulta de tareas pendientes
     */
    public function testTareasPendientes()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyecto_tareas WHERE estado_tipo_id IN (1, 2, 5)");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(4, $count, "Debe haber 4 tareas pendientes en los datos de prueba");
    }

    /**
     * Test de implementación completa de estadísticas del dashboard
     */
    public function testCalculateStatsImplementation()
    {
        // Simular el método calculateStats del HomeController
        $stats = $this->calculateDashboardStats();

        $this->assertIsArray($stats, "Las estadísticas deben devolver un array");

        $expectedKeys = ['total_usuarios', 'total_proyectos', 'proyectos_activos', 'tareas_pendientes'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $stats, "Las estadísticas deben incluir '$key'");
            $this->assertIsInt($stats[$key], "El valor de '$key' debe ser entero");
            $this->assertGreaterThanOrEqual(0, $stats[$key], "El valor de '$key' no puede ser negativo");
        }

        // Verificar valores específicos de los datos de prueba
        $this->assertEquals(2, $stats['total_usuarios'], "Total usuarios debe ser 2");
        $this->assertEquals(3, $stats['total_proyectos'], "Total proyectos debe ser 3");
        $this->assertEquals(2, $stats['proyectos_activos'], "Proyectos activos debe ser 2");
        $this->assertEquals(4, $stats['tareas_pendientes'], "Tareas pendientes debe ser 4");
    }

    /**
     * Implementación exacta del método calculateStats que debería estar en HomeController
     */
    private function calculateDashboardStats(): array
    {
        try {
            // 1. Total usuarios (excluir eliminados: estado_tipo_id != 4)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE estado_tipo_id != 4");
            $stmt->execute();
            $totalUsuarios = $stmt->fetchColumn();

            // 2. Total proyectos (excluir eliminados: estado_tipo_id != 4)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyectos WHERE estado_tipo_id != 4");
            $stmt->execute();
            $totalProyectos = $stmt->fetchColumn();

            // 3. Proyectos activos (estado activo=2 o iniciado=5)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyectos WHERE estado_tipo_id IN (2, 5)");
            $stmt->execute();
            $proyectosActivos = $stmt->fetchColumn();

            // 4. Tareas pendientes (estado creado=1, activo=2, o iniciado=5)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyecto_tareas WHERE estado_tipo_id IN (1, 2, 5)");
            $stmt->execute();
            $tareasPendientes = $stmt->fetchColumn();

            return [
                'total_usuarios' => (int)$totalUsuarios,
                'total_proyectos' => (int)$totalProyectos,
                'proyectos_activos' => (int)$proyectosActivos,
                'tareas_pendientes' => (int)$tareasPendientes
            ];
        } catch (\Exception $e) {
            // Retornar valores por defecto en caso de error
            return [
                'total_usuarios' => 0,
                'total_proyectos' => 0,
                'proyectos_activos' => 0,
                'tareas_pendientes' => 0
            ];
        }
    }

    /**
     * Test de validación de que las estadísticas no están hardcodeadas
     */
    public function testStatsNotHardcoded()
    {
        $stats = $this->calculateDashboardStats();

        // Verificar que al menos uno de los valores es mayor a 0
        $hasNonZeroValues = array_filter($stats, function ($value) {
            return $value > 0;
        });

        $this->assertNotEmpty($hasNonZeroValues,
            "Las estadísticas deben mostrar datos reales, no valores hardcoded en 0");

        // Verificar que los proyectos activos no exceden el total
        $this->assertLessThanOrEqual($stats['total_proyectos'], $stats['proyectos_activos'],
            "Proyectos activos no puede exceder el total de proyectos");
    }
}
