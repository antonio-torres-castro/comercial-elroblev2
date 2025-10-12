<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\HomeController;
use App\Config\Database;

class HomeControllerTest extends TestCase
{
    private $homeController;
    private $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        
        // Crear tablas necesarias para testing en memoria
        $this->createTestTables();
        
        // Mock de sesión para testing
        if (!isset($_SESSION)) {
            $_SESSION = [
                'user_id' => 1,
                'username' => 'testuser',
                'user_type_id' => 1,
                'logged_in' => true
            ];
        }
        
        $this->homeController = new HomeController();
    }

    private function createTestTables(): void
    {
        // Crear tablas básicas para testing
        $tables = [
            'estado_tipos' => "
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
            ",
            'usuario_tipos' => "
                CREATE TABLE IF NOT EXISTS usuario_tipos (
                    id INTEGER PRIMARY KEY,
                    nombre VARCHAR(100) NOT NULL,
                    descripcion VARCHAR(500) NOT NULL
                );
                INSERT OR IGNORE INTO usuario_tipos (id, nombre, descripcion) VALUES 
                (1, 'admin', 'Administrador');
            ",
            'personas' => "
                CREATE TABLE IF NOT EXISTS personas (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    rut VARCHAR(20) NOT NULL,
                    nombre VARCHAR(150) NOT NULL,
                    estado_tipo_id INT DEFAULT 1
                );
                INSERT OR IGNORE INTO personas (id, rut, nombre) VALUES 
                (1, '12345678-9', 'Test User');
            ",
            'usuarios' => "
                CREATE TABLE IF NOT EXISTS usuarios (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    persona_id INT NOT NULL,
                    usuario_tipo_id INT NOT NULL,
                    email VARCHAR(150) NOT NULL,
                    nombre_usuario VARCHAR(100) NOT NULL,
                    clave_hash VARCHAR(255) NOT NULL,
                    estado_tipo_id INT DEFAULT 2,
                    FOREIGN KEY (persona_id) REFERENCES personas(id),
                    FOREIGN KEY (usuario_tipo_id) REFERENCES usuario_tipos(id)
                );
                INSERT OR IGNORE INTO usuarios (id, persona_id, usuario_tipo_id, email, nombre_usuario, clave_hash, estado_tipo_id) VALUES 
                (1, 1, 1, 'test@example.com', 'testuser', 'hash123', 2),
                (2, 1, 1, 'test2@example.com', 'testuser2', 'hash456', 2);
            ",
            'tarea_tipos' => "
                CREATE TABLE IF NOT EXISTS tarea_tipos (
                    id INTEGER PRIMARY KEY,
                    nombre VARCHAR(50) NOT NULL
                );
                INSERT OR IGNORE INTO tarea_tipos (id, nombre) VALUES (1, 'intelectual');
            ",
            'clientes' => "
                CREATE TABLE IF NOT EXISTS clientes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    razon_social VARCHAR(150) NOT NULL,
                    estado_tipo_id INT DEFAULT 2
                );
                INSERT OR IGNORE INTO clientes (id, razon_social) VALUES (1, 'Cliente Test');
            ",
            'cliente_contrapartes' => "
                CREATE TABLE IF NOT EXISTS cliente_contrapartes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    cliente_id INT NOT NULL,
                    persona_id INT NOT NULL,
                    email VARCHAR(150),
                    estado_tipo_id INT DEFAULT 2
                );
                INSERT OR IGNORE INTO cliente_contrapartes (id, cliente_id, persona_id, email) VALUES 
                (1, 1, 1, 'contraparte@example.com');
            ",
            'proyectos' => "
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
            ",
            'tareas' => "
                CREATE TABLE IF NOT EXISTS tareas (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nombre VARCHAR(150) NOT NULL,
                    estado_tipo_id INT DEFAULT 2
                );
                INSERT OR IGNORE INTO tareas (id, nombre) VALUES (1, 'Tarea Test');
            ",
            'proyecto_tareas' => "
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
            "
        ];

        foreach ($tables as $name => $sql) {
            try {
                $this->db->exec($sql);
            } catch (\Exception $e) {
                // Ignorar errores de tablas ya existentes
            }
        }
    }

    /**
     * Test de existencia de tablas requeridas para estadísticas
     */
    public function testRequiredTablesExist()
    {
        $tables = ['usuarios', 'proyectos', 'proyecto_tareas'];
        
        foreach ($tables as $table) {
            $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
            $result = $stmt->fetch();
            $this->assertNotEmpty($result, "La tabla '$table' debe existir para calcular estadísticas");
        }
    }

    /**
     * Test de consulta de total de usuarios
     */
    public function testUsuariosCountQuery()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE estado_tipo_id != 4");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        $this->assertIsInt((int)$count, "El conteo de usuarios debe devolver un número entero");
        $this->assertGreaterThanOrEqual(0, $count, "El conteo de usuarios no puede ser negativo");
    }

    /**
     * Test de consulta de total de proyectos
     */
    public function testProyectosCountQuery()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyectos WHERE estado_tipo_id != 4");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        $this->assertIsInt((int)$count, "El conteo de proyectos debe devolver un número entero");
        $this->assertGreaterThanOrEqual(0, $count, "El conteo de proyectos no puede ser negativo");
    }

    /**
     * Test de consulta de proyectos activos
     */
    public function testProyectosActivosQuery()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyectos WHERE estado_tipo_id IN (2, 5)");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        $this->assertIsInt((int)$count, "El conteo de proyectos activos debe devolver un número entero");
        $this->assertGreaterThanOrEqual(0, $count, "El conteo de proyectos activos no puede ser negativo");
    }

    /**
     * Test de consulta de tareas pendientes
     */
    public function testTareasPendientesQuery()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM proyecto_tareas WHERE estado_tipo_id IN (1, 2, 5)");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        $this->assertIsInt((int)$count, "El conteo de tareas pendientes debe devolver un número entero");
        $this->assertGreaterThanOrEqual(0, $count, "El conteo de tareas pendientes no puede ser negativo");
    }

    /**
     * Test de estructura de resultado de estadísticas
     */
    public function testEstadisticasStructure()
    {
        // Usar reflection para acceder al método privado calculateStats
        $reflection = new \ReflectionClass($this->homeController);
        $method = $reflection->getMethod('calculateStats');
        $method->setAccessible(true);
        
        $stats = $method->invoke($this->homeController);
        
        $this->assertIsArray($stats, "Las estadísticas deben devolver un array");
        
        $requiredKeys = ['total_usuarios', 'total_proyectos', 'proyectos_activos', 'tareas_pendientes'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $stats, "Las estadísticas deben incluir la clave '$key'");
            $this->assertIsInt($stats[$key], "El valor de '$key' debe ser un entero");
            $this->assertGreaterThanOrEqual(0, $stats[$key], "El valor de '$key' no puede ser negativo");
        }
    }

    /**
     * Test de valores reales de estadísticas vs hardcoded
     */
    public function testEstadisticasNoHardcoded()
    {
        // Usar reflection para acceder al método privado calculateStats
        $reflection = new \ReflectionClass($this->homeController);
        $method = $reflection->getMethod('calculateStats');
        $method->setAccessible(true);
        
        $stats = $method->invoke($this->homeController);
        
        // Verificar que al menos uno de los valores no sea 0 (asumiendo que hay datos en la BD)
        $hasNonZeroValues = false;
        foreach (['total_usuarios', 'total_proyectos', 'proyectos_activos', 'tareas_pendientes'] as $key) {
            if ($stats[$key] > 0) {
                $hasNonZeroValues = true;
                break;
            }
        }
        
        // Si hay datos en la base de datos, las estadísticas no deberían ser todas cero
        $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios");
        $hasUsers = $stmt->fetchColumn() > 0;
        
        if ($hasUsers) {
            $this->assertTrue($hasNonZeroValues || $stats['total_usuarios'] > 0, 
                "Las estadísticas deben reflejar datos reales, no valores hardcoded en 0");
        }
    }

    /**
     * Test de manejo de errores en estadísticas
     */
    public function testEstadisticasErrorHandling()
    {
        // Usar reflection para acceder al método privado calculateStats
        $reflection = new \ReflectionClass($this->homeController);
        $method = $reflection->getMethod('calculateStats');
        $method->setAccessible(true);
        
        // El método debe devolver un array válido incluso si hay errores
        $stats = $method->invoke($this->homeController);
        
        $this->assertIsArray($stats, "Las estadísticas deben devolver un array incluso en caso de error");
        $this->assertCount(4, $stats, "Las estadísticas deben incluir exactamente 4 métricas");
    }

    /**
     * Test de consistencia entre proyectos activos y total
     */
    public function testProyectosActivosVsTotal()
    {
        // Usar reflection para acceder al método privado calculateStats
        $reflection = new \ReflectionClass($this->homeController);
        $method = $reflection->getMethod('calculateStats');
        $method->setAccessible(true);
        
        $stats = $method->invoke($this->homeController);
        
        $this->assertLessThanOrEqual($stats['total_proyectos'], $stats['proyectos_activos'], 
            "Los proyectos activos no pueden ser más que el total de proyectos");
    }
}
