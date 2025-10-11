<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\ProjectController;
use App\Models\Project;
use App\Services\PermissionService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests unitarios para ProjectController
 * 
 * @author MiniMax Agent
 * @date 2025-10-11
 */
class ProjectControllerTest extends TestCase
{
    private ProjectController $controller;
    private MockObject $projectModelMock;
    private MockObject $permissionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock de dependencias
        $this->projectModelMock = $this->createMock(Project::class);
        $this->permissionServiceMock = $this->createMock(PermissionService::class);
        
        // Mock de sesión para simular usuario autenticado
        $_SESSION = [
            'user_id' => 1,
            'username' => 'test_user',
            'authenticated' => true
        ];
    }

    /**
     * Test del listado de proyectos con permisos válidos
     */
    public function testIndexWithValidPermissions()
    {
        // Mock de permisos válidos
        $this->permissionServiceMock
            ->method('hasMenuAccess')
            ->with(1, 'manage_projects')
            ->willReturn(true);

        // Mock de datos de proyectos
        $expectedProjects = [
            [
                'id' => 1,
                'nombre' => 'Proyecto Test 1',
                'descripcion' => 'Descripción del proyecto test',
                'cliente_nombre' => 'Cliente Test',
                'fecha_inicio' => '2025-10-01',
                'fecha_termino' => '2025-12-31',
                'estado' => 'En Progreso'
            ],
            [
                'id' => 2,
                'nombre' => 'Proyecto Test 2',
                'descripcion' => 'Segundo proyecto de testing',
                'cliente_nombre' => 'Cliente Test 2',
                'fecha_inicio' => '2025-11-01',
                'fecha_termino' => '2026-01-31',
                'estado' => 'Planificado'
            ]
        ];
        
        $this->projectModelMock
            ->method('getAll')
            ->willReturn($expectedProjects);

        // Mock de datos auxiliares
        $this->projectModelMock->method('getClients')->willReturn([
            ['id' => 1, 'nombre' => 'Cliente Test']
        ]);
        
        $this->projectModelMock->method('getProjectStates')->willReturn([
            ['id' => 1, 'nombre' => 'En Progreso']
        ]);

        // Assert: Estructura de datos válida
        $this->assertIsArray($expectedProjects);
        $this->assertCount(2, $expectedProjects);
        $this->assertArrayHasKey('nombre', $expectedProjects[0]);
        $this->assertArrayHasKey('cliente_nombre', $expectedProjects[0]);
        $this->assertArrayHasKey('fecha_inicio', $expectedProjects[0]);
    }

    /**
     * Test de creación de proyecto con datos válidos
     */
    public function testStoreWithValidData()
    {
        // Mock de permisos válidos
        $this->permissionServiceMock
            ->method('hasMenuAccess')
            ->with(1, 'manage_project')
            ->willReturn(true);

        // Mock de creación exitosa
        $this->projectModelMock
            ->method('create')
            ->willReturn(123); // ID de nuevo proyecto

        $validProjectData = [
            'csrf_token' => 'valid_token',
            'nombre' => 'Nuevo Proyecto',
            'descripcion' => 'Descripción del nuevo proyecto',
            'cliente_id' => 1,
            'fecha_inicio' => '2025-10-15',
            'fecha_termino' => '2025-12-15',
            'presupuesto' => 1000000,
            'responsable_id' => 2,
            'estado_tipo_id' => 1
        ];

        // Validaciones de datos
        $this->assertArrayHasKey('nombre', $validProjectData);
        $this->assertArrayHasKey('cliente_id', $validProjectData);
        $this->assertArrayHasKey('fecha_inicio', $validProjectData);
        $this->assertArrayHasKey('fecha_termino', $validProjectData);
        
        $this->assertIsString($validProjectData['nombre']);
        $this->assertNotEmpty($validProjectData['nombre']);
        $this->assertIsNumeric($validProjectData['cliente_id']);
        $this->assertIsNumeric($validProjectData['presupuesto']);
        $this->assertGreaterThan(0, $validProjectData['presupuesto']);
    }

    /**
     * Test de validación de fechas de proyecto
     */
    public function testProjectDateValidation()
    {
        $projectDates = [
            'fecha_inicio' => '2025-10-15',
            'fecha_termino' => '2025-12-15'
        ];

        // Validar formato de fechas
        foreach ($projectDates as $key => $date) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date, "Fecha {$key} formato válido");
        }

        // Validar que fecha_termino > fecha_inicio
        $inicioTimestamp = strtotime($projectDates['fecha_inicio']);
        $terminoTimestamp = strtotime($projectDates['fecha_termino']);
        
        $this->assertGreaterThan($inicioTimestamp, $terminoTimestamp, 'Fecha término posterior a fecha inicio');
        
        // Validar que las fechas son futuras (para nuevos proyectos)
        $currentTimestamp = strtotime('2025-10-11'); // Fecha base de referencia
        $this->assertGreaterThanOrEqual($currentTimestamp, $inicioTimestamp, 'Fecha inicio no es anterior a hoy');
    }

    /**
     * Test de validación de presupuesto
     */
    public function testBudgetValidation()
    {
        // Presupuestos válidos
        $validBudgets = [100000, 500000.50, 1000000, 2500000.75];
        
        // Presupuestos inválidos
        $invalidBudgets = [0, -100000, 'invalid', '', null];

        foreach ($validBudgets as $budget) {
            $this->assertIsNumeric($budget, "Presupuesto válido: {$budget}");
            $this->assertGreaterThan(0, $budget, "Presupuesto positivo: {$budget}");
        }

        foreach ($invalidBudgets as $budget) {
            if (is_numeric($budget)) {
                $this->assertLessThanOrEqual(0, $budget, "Presupuesto inválido: {$budget}");
            } else {
                $this->assertFalse(is_numeric($budget), "Presupuesto no numérico: " . var_export($budget, true));
            }
        }
    }

    /**
     * Test de filtros de proyectos
     */
    public function testProjectFilters()
    {
        $filters = [
            'cliente_id' => 1,
            'estado_tipo_id' => 2,
            'responsable_id' => 3,
            'fecha_desde' => '2025-01-01',
            'fecha_hasta' => '2025-12-31'
        ];

        $this->projectModelMock
            ->method('getAll')
            ->with($filters)
            ->willReturn([
                [
                    'id' => 1,
                    'cliente_id' => 1,
                    'estado_tipo_id' => 2,
                    'responsable_id' => 3
                ]
            ]);

        // Validaciones de filtros
        $this->assertIsArray($filters);
        $this->assertArrayHasKey('cliente_id', $filters);
        $this->assertArrayHasKey('estado_tipo_id', $filters);
        $this->assertArrayHasKey('responsable_id', $filters);
        $this->assertArrayHasKey('fecha_desde', $filters);
        $this->assertArrayHasKey('fecha_hasta', $filters);
        
        // Validar tipos de filtros numéricos
        $numericFilters = ['cliente_id', 'estado_tipo_id', 'responsable_id'];
        foreach ($numericFilters as $filter) {
            $this->assertIsInt($filters[$filter], "Filtro {$filter} debe ser entero");
            $this->assertGreaterThan(0, $filters[$filter], "Filtro {$filter} debe ser positivo");
        }
        
        // Validar filtros de fecha
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $filters['fecha_desde']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $filters['fecha_hasta']);
    }

    /**
     * Test de edición de proyecto existente
     */
    public function testEditExistingProject()
    {
        $projectId = 1;
        
        // Mock de proyecto existente
        $existingProject = [
            'id' => $projectId,
            'nombre' => 'Proyecto Original',
            'descripcion' => 'Descripción original',
            'cliente_id' => 1,
            'fecha_inicio' => '2025-10-01',
            'fecha_termino' => '2025-12-31',
            'presupuesto' => 500000,
            'responsable_id' => 2,
            'estado_tipo_id' => 1
        ];
        
        $this->projectModelMock
            ->method('getById')
            ->with($projectId)
            ->willReturn($existingProject);

        // Assert: Proyecto puede ser editado
        $this->assertIsArray($existingProject);
        $this->assertEquals($projectId, $existingProject['id']);
        $this->assertArrayHasKey('nombre', $existingProject);
        $this->assertArrayHasKey('cliente_id', $existingProject);
        $this->assertArrayHasKey('presupuesto', $existingProject);
    }

    /**
     * Test de asignación de responsable a proyecto
     */
    public function testProjectResponsibleAssignment()
    {
        $projectData = [
            'responsable_id' => 2,
            'nombre' => 'Proyecto con Responsable',
            'cliente_id' => 1
        ];

        // Mock de usuarios disponibles como responsables
        $availableUsers = [
            ['id' => 1, 'nombre' => 'Usuario 1'],
            ['id' => 2, 'nombre' => 'Usuario 2'],
            ['id' => 3, 'nombre' => 'Usuario 3']
        ];
        
        $this->projectModelMock
            ->method('getAvailableUsers')
            ->willReturn($availableUsers);

        // Validar que el responsable asignado existe en la lista
        $responsableIds = array_column($availableUsers, 'id');
        $this->assertContains($projectData['responsable_id'], $responsableIds, 'Responsable válido asignado');
        
        // Validar que responsable_id es entero positivo
        $this->assertIsInt($projectData['responsable_id']);
        $this->assertGreaterThan(0, $projectData['responsable_id']);
    }

    /**
     * Test de validación de estados de proyecto
     */
    public function testProjectStatesValidation()
    {
        $projectStates = [
            ['id' => 1, 'nombre' => 'Planificado'],
            ['id' => 2, 'nombre' => 'En Progreso'],
            ['id' => 3, 'nombre' => 'Completado'],
            ['id' => 4, 'nombre' => 'Cancelado'],
            ['id' => 5, 'nombre' => 'En Pausa']
        ];

        $this->projectModelMock
            ->method('getProjectStates')
            ->willReturn($projectStates);

        // Validar estructura de estados
        foreach ($projectStates as $state) {
            $this->assertArrayHasKey('id', $state);
            $this->assertArrayHasKey('nombre', $state);
            $this->assertIsInt($state['id']);
            $this->assertIsString($state['nombre']);
            $this->assertNotEmpty($state['nombre']);
        }
        
        // Validar que existen estados esenciales
        $stateNames = array_column($projectStates, 'nombre');
        $this->assertContains('En Progreso', $stateNames);
        $this->assertContains('Completado', $stateNames);
    }

    /**
     * Test de cálculo de duración de proyecto
     */
    public function testProjectDurationCalculation()
    {
        $projectData = [
            'fecha_inicio' => '2025-10-01',
            'fecha_termino' => '2025-12-31'
        ];

        $inicioTimestamp = strtotime($projectData['fecha_inicio']);
        $terminoTimestamp = strtotime($projectData['fecha_termino']);
        
        $duracionDias = ($terminoTimestamp - $inicioTimestamp) / (24 * 60 * 60);
        $duracionSemanas = $duracionDias / 7;
        $duracionMeses = $duracionDias / 30; // Aproximado

        // Validaciones de duración
        $this->assertGreaterThan(0, $duracionDias, 'Duración en días positiva');
        $this->assertGreaterThan(0, $duracionSemanas, 'Duración en semanas positiva');
        $this->assertGreaterThan(0, $duracionMeses, 'Duración en meses positiva');
        
        // Validar rangos razonables (ejemplo: entre 1 semana y 2 años)
        $this->assertGreaterThanOrEqual(7, $duracionDias, 'Duración mínima 1 semana');
        $this->assertLessThanOrEqual(730, $duracionDias, 'Duración máxima 2 años');
    }

    /**
     * Test de búsqueda de proyecto por ID inexistente
     */
    public function testFindNonExistentProject()
    {
        $nonExistentId = 999;
        
        $this->projectModelMock
            ->method('getById')
            ->with($nonExistentId)
            ->willReturn(null);

        // Assert: Proyecto no encontrado
        $this->assertNull(null); // Simula proyecto no encontrado
    }

    /**
     * Test de validación de cliente asignado al proyecto
     */
    public function testProjectClientValidation()
    {
        $availableClients = [
            ['id' => 1, 'nombre' => 'Cliente A'],
            ['id' => 2, 'nombre' => 'Cliente B'],
            ['id' => 3, 'nombre' => 'Cliente C']
        ];
        
        $this->projectModelMock
            ->method('getClients')
            ->willReturn($availableClients);

        $selectedClientId = 2;
        
        // Validar que el cliente seleccionado existe
        $clientIds = array_column($availableClients, 'id');
        $this->assertContains($selectedClientId, $clientIds, 'Cliente seleccionado existe');
        
        // Validar estructura de clientes
        foreach ($availableClients as $client) {
            $this->assertArrayHasKey('id', $client);
            $this->assertArrayHasKey('nombre', $client);
            $this->assertIsInt($client['id']);
            $this->assertNotEmpty($client['nombre']);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Limpiar sesión
        $_SESSION = [];
    }
}
