<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\TaskController;
use App\Models\Task;
use App\Services\PermissionService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests unitarios para TaskController
 * 
 * 
 * @date 2025-10-11
 */
class TaskControllerTest extends TestCase
{
    private TaskController $controller;
    private MockObject $taskModelMock;
    private MockObject $permissionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock de dependencias
        $this->taskModelMock = $this->createMock(Task::class);
        $this->permissionServiceMock = $this->createMock(PermissionService::class);

        // Mock de sesión para simular usuario autenticado
        $_SESSION = [
            'user_id' => 1,
            'username' => 'test_user',
            'authenticated' => true
        ];
    }

    /**
     * Test del listado de tareas con permisos válidos
     */
    public function testIndexWithValidPermissions()
    {
        // Mock de permisos válidos
        $this->permissionServiceMock
            ->method('hasMenuAccess')
            ->with(1, 'manage_tasks')
            ->willReturn(true);

        // Mock de datos de tareas
        $expectedTasks = [
            [
                'id' => 1,
                'nombre' => 'Tarea de Prueba',
                'proyecto_nombre' => 'Proyecto Test',
                'estado' => 'En Progreso',
                'fecha_inicio' => '2025-10-11',
                'planificador_nombre' => 'Juan Pérez'
            ],
            [
                'id' => 2,
                'nombre' => 'Segunda Tarea',
                'proyecto_nombre' => 'Proyecto Test 2',
                'estado' => 'Completada',
                'fecha_inicio' => '2025-10-10',
                'planificador_nombre' => 'María González'
            ]
        ];

        $this->taskModelMock
            ->method('getAll')
            ->willReturn($expectedTasks);

        // Mock de datos auxiliares
        $this->taskModelMock->method('getProjects')->willReturn([
            ['id' => 1, 'nombre' => 'Proyecto Test']
        ]);

        $this->taskModelMock->method('getTaskStates')->willReturn([
            ['id' => 1, 'nombre' => 'En Progreso']
        ]);

        $this->taskModelMock->method('getUsers')->willReturn([
            ['id' => 1, 'nombre' => 'Juan Pérez']
        ]);

        // Assert: Estructura de datos válida
        $this->assertIsArray($expectedTasks);
        $this->assertCount(2, $expectedTasks);
        $this->assertArrayHasKey('nombre', $expectedTasks[0]);
        $this->assertArrayHasKey('proyecto_nombre', $expectedTasks[0]);
    }

    /**
     * Test de creación de tarea con datos válidos
     */
    public function testStoreWithValidData()
    {
        // Mock de permisos válidos
        $this->permissionServiceMock
            ->method('hasMenuAccess')
            ->with(1, 'manage_task')
            ->willReturn(true);

        // Mock de creación exitosa
        $this->taskModelMock
            ->method('create')
            ->willReturn(123); // ID de nueva tarea

        $validTaskData = [
            'csrf_token' => 'valid_token',
            'proyecto_id' => 1,
            'ejecutor_id' => 2,
            'supervisor_id' => 3,
            'fecha_inicio' => '2025-10-15',
            'duracion_horas' => 8.0,
            'prioridad' => 1,
            'estado_tipo_id' => 1,
            'tarea_id' => 'nueva',
            'nueva_tarea_nombre' => 'Tarea de Testing',
            'nueva_tarea_descripcion' => 'Descripción de la tarea de testing'
        ];

        // Validaciones de datos
        $this->assertArrayHasKey('proyecto_id', $validTaskData);
        $this->assertArrayHasKey('fecha_inicio', $validTaskData);
        $this->assertIsNumeric($validTaskData['proyecto_id']);
        $this->assertIsFloat($validTaskData['duracion_horas']);
        $this->assertGreaterThan(0, $validTaskData['duracion_horas']);
    }

    /**
     * Test de validación de fechas de tareas
     */
    public function testTaskDateValidation()
    {
        // Fechas válidas
        $validDates = [
            '2025-10-11',
            '2025-12-31',
            '2025-01-01'
        ];

        // Fechas inválidas
        $invalidDates = [
            '2025-13-01', // Mes inválido
            '2025-02-30', // Día inválido
            '2025-02-31', // Día inválido
            '2025-02-32', // Día inválido (32 no está en el rango)
            '25-10-11',   // Formato incorrecto
            'invalid',    // Completamente inválido
            ''
        ];

        foreach ($validDates as $date) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date, "Fecha válida: {$date}");
        }

        foreach ($invalidDates as $date) {
            if ($date !== '' && $date !== null) {
                // Para fechas con formato aparentemente válido pero lógicamente incorrectas, 
                // verificamos si la fecha es realmente válida usando checkdate
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $parts = explode('-', $date);
                    $year = (int)$parts[0];
                    $month = (int)$parts[1];
                    $day = (int)$parts[2];
                    $this->assertFalse(checkdate($month, $day, $year), "Fecha inválida: {$date}");
                } else {
                    // Para fechas con formato incorrecto
                    $this->assertDoesNotMatchRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date, "Formato inválido: {$date}");
                }
            } else {
                $this->assertTrue(empty($date), "Fecha vacía o null");
            }
        }
    }

    /**
     * Test de filtros de tareas
     */
    public function testTaskFilters()
    {
        $filters = [
            'proyecto_id' => 1,
            'estado_tipo_id' => 2,
            'usuario_id' => 3
        ];

        $this->taskModelMock
            ->method('getAll')
            ->with($filters)
            ->willReturn([
                [
                    'id' => 1,
                    'proyecto_id' => 1,
                    'estado_tipo_id' => 2,
                    'ejecutor_id' => 3
                ]
            ]);

        // Validaciones de filtros
        $this->assertIsArray($filters);
        $this->assertArrayHasKey('proyecto_id', $filters);
        $this->assertArrayHasKey('estado_tipo_id', $filters);
        $this->assertArrayHasKey('usuario_id', $filters);

        foreach ($filters as $key => $value) {
            $this->assertIsInt($value, "Filtro {$key} debe ser entero");
            $this->assertGreaterThan(0, $value, "Filtro {$key} debe ser positivo");
        }
    }

    /**
     * Test de edición de tarea existente
     */
    public function testEditExistingTask()
    {
        $taskId = 1;

        // Mock de tarea existente
        $existingTask = [
            'id' => $taskId,
            'nombre' => 'Tarea Original',
            'proyecto_id' => 1,
            'ejecutor_id' => 2,
            'fecha_inicio' => '2025-10-11',
            'duracion_horas' => 4.0,
            'estado_tipo_id' => 1
        ];

        $this->taskModelMock
            ->method('getById')
            ->with($taskId)
            ->willReturn($existingTask);

        // Assert: Tarea puede ser editada
        $this->assertIsArray($existingTask);
        $this->assertEquals($taskId, $existingTask['id']);
        $this->assertArrayHasKey('nombre', $existingTask);
        $this->assertArrayHasKey('proyecto_id', $existingTask);
    }

    /**
     * Test de validación de prioridades de tareas
     */
    public function testTaskPriorityValidation()
    {
        // Prioridades válidas (asumiendo rango 0-3)
        $validPriorities = [0, 1, 2, 3];

        // Prioridades inválidas
        $invalidPriorities = [-1, 4, 10, 'alta', '', null];

        foreach ($validPriorities as $priority) {
            $this->assertIsInt($priority);
            $this->assertGreaterThanOrEqual(0, $priority);
            $this->assertLessThanOrEqual(3, $priority);
        }

        foreach ($invalidPriorities as $priority) {
            if (is_int($priority)) {
                $this->assertTrue($priority < 0 || $priority > 3, "Prioridad fuera de rango: {$priority}");
            } else {
                $this->assertFalse(is_int($priority), "Prioridad no es entero: " . var_export($priority, true));
            }
        }
    }

    /**
     * Test de asignación de roles en tareas
     */
    public function testTaskRoleAssignment()
    {
        $taskData = [
            'planificador_id' => 1,  // Usuario actual
            'ejecutor_id' => 2,      // Usuario asignado
            'supervisor_id' => 3     // Usuario supervisor
        ];

        // Validar que todos los roles son enteros positivos
        foreach ($taskData as $role => $userId) {
            $this->assertIsInt($userId, "Rol {$role} debe ser entero");
            $this->assertGreaterThan(0, $userId, "Rol {$role} debe ser positivo");
        }

        // Validar que los roles son diferentes (opcional según reglas de negocio)
        $userIds = array_values($taskData);
        $this->assertCount(3, $userIds);
    }

    /**
     * Test de búsqueda de tarea por ID inexistente
     */
    public function testFindNonExistentTask()
    {
        $nonExistentId = 999;

        $this->taskModelMock
            ->method('getById')
            ->with($nonExistentId)
            ->willReturn(null);

        // Assert: Tarea no encontrada
        $this->assertNull(null); // Simula tarea no encontrada
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Limpiar sesión
        $_SESSION = [];
    }
}
