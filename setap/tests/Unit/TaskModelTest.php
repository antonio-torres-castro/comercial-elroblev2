<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Task;

class TaskModelTest extends TestCase
{
    private $taskModel;

    protected function setUp(): void
    {
        $this->taskModel = new Task();
    }

    public function testTaskModelInstantiation()
    {
        $this->assertInstanceOf(
            'App\Models\Task',
            $this->taskModel,
            'Task model debe poder instanciarse'
        );
    }

    public function testTaskModelHasRequiredMethods()
    {
        $requiredMethods = [
            'getAll',
            'getById',
            'create',
            'update',
            'delete',
            'getTaskTypes',
            'getProjects',
            'getUsers',
            'getTaskStates',
            'isValidStateTransition',
            'canUserChangeState',
            'canExecuteTask',
            'changeState',
            'validateUpdateData',
            'isTaskOnHoliday',
            'getTasksOnHolidays',
            'validateTaskDatesWithHolidays',
            'getNextWorkingDay',
            'getWorkingDaysBetween'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->taskModel, $method),
                "Task model debe tener el método $method"
            );
        }
    }

    public function testGetAllReturnsArray()
    {
        $result = $this->taskModel->getAll();
        $this->assertIsArray($result, 'getAll debe retornar un array');
    }

    public function testGetAllWithFilters()
    {
        $filters = ['proyecto_id' => 1];
        $result = $this->taskModel->getAll($filters);
        $this->assertIsArray($result, 'getAll con filtros debe retornar un array');
    }

    public function testGetTaskTypesReturnsArray()
    {
        $result = $this->taskModel->getTaskTypes();
        $this->assertIsArray($result, 'getTaskTypes debe retornar un array');
    }

    public function testGetProjectsReturnsArray()
    {
        $result = $this->taskModel->getProjects();
        $this->assertIsArray($result, 'getProjects debe retornar un array');
    }

    public function testGetUsersReturnsArray()
    {
        $result = $this->taskModel->getUsers();
        $this->assertIsArray($result, 'getUsers debe retornar un array');
    }

    public function testGetTaskStatesReturnsArray()
    {
        $result = $this->taskModel->getTaskStates();
        $this->assertIsArray($result, 'getTaskStates debe retornar un array');
    }

    public function testGetByIdReturnsNullOrArray()
    {
        $id = 999999; // ID que probablemente no existe
        $result = $this->taskModel->getById($id);
        $this->assertTrue(
            is_null($result) || is_array($result),
            'getById debe retornar null o un array'
        );
    }

    public function testIsValidStateTransitionReturnsArray()
    {
        $currentState = 1;
        $newState = 2;
        $result = $this->taskModel->isValidStateTransition($currentState, $newState);
        $this->assertIsArray($result, 'isValidStateTransition debe retornar un array');
        $this->assertArrayHasKey('valid', $result, 'El resultado debe tener la clave "valid"');
        $this->assertIsBool($result['valid'], 'La clave "valid" debe ser boolean');
    }

    public function testCanUserChangeStateReturnsArray()
    {
        $currentState = 1;
        $newState = 2;
        $userRole = 'admin';
        $result = $this->taskModel->canUserChangeState($currentState, $newState, $userRole);
        $this->assertIsArray($result, 'canUserChangeState debe retornar un array');
        $this->assertArrayHasKey('can_change', $result, 'El resultado debe tener la clave "can_change"');
        $this->assertIsBool($result['can_change'], 'La clave "can_change" debe ser boolean');
    }

    public function testCanExecuteTaskReturnsArray()
    {
        $taskId = 1;
        $result = $this->taskModel->canExecuteTask($taskId);
        $this->assertIsArray($result, 'canExecuteTask debe retornar un array');
        $this->assertArrayHasKey('can_execute', $result, 'El resultado debe tener la clave "can_execute"');
        $this->assertIsBool($result['can_execute'], 'La clave "can_execute" debe ser boolean');
    }

    public function testIsTaskOnHolidayReturnsBool()
    {
        $taskId = 1;
        $result = $this->taskModel->isTaskOnHoliday($taskId);
        $this->assertIsBool($result, 'isTaskOnHoliday debe retornar un boolean');
    }

    public function testGetTasksOnHolidaysReturnsArray()
    {
        $projectId = 1;
        $result = $this->taskModel->getTasksOnHolidays($projectId);
        $this->assertIsArray($result, 'getTasksOnHolidays debe retornar un array');
    }

    public function testValidateTaskDatesWithHolidaysReturnsArray()
    {
        $projectId = 1;
        $fechaInicio = '2025-01-01';
        $result = $this->taskModel->validateTaskDatesWithHolidays($projectId, $fechaInicio);
        $this->assertIsArray($result, 'validateTaskDatesWithHolidays debe retornar un array');
        $this->assertArrayHasKey('valid', $result, 'El resultado debe tener la clave "valid"');
        $this->assertIsBool($result['valid'], 'La clave "valid" debe ser boolean');
    }

    public function testGetNextWorkingDayReturnsString()
    {
        $projectId = 1;
        $date = '2025-01-01';
        $result = $this->taskModel->getNextWorkingDay($projectId, $date);
        $this->assertIsString($result, 'getNextWorkingDay debe retornar un string');

        // Verificar que el formato de fecha sea válido
        $dateTime = \DateTime::createFromFormat('Y-m-d', $result);
        $this->assertNotFalse($dateTime, 'getNextWorkingDay debe retornar una fecha válida');
    }

    public function testGetWorkingDaysBetweenReturnsInt()
    {
        $projectId = 1;
        $startDate = '2025-01-01';
        $endDate = '2025-01-07';
        $result = $this->taskModel->getWorkingDaysBetween($projectId, $startDate, $endDate);
        $this->assertIsInt($result, 'getWorkingDaysBetween debe retornar un integer');
        $this->assertGreaterThanOrEqual(0, $result, 'getWorkingDaysBetween debe retornar un número positivo o cero');
    }

    public function testTaskModelHasCorrectTableProperty()
    {
        $reflection = new \ReflectionClass($this->taskModel);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $tableValue = $tableProperty->getValue($this->taskModel);

        $this->assertEquals(
            'tareas',
            $tableValue,
            'La tabla debe ser "tareas"'
        );
    }

    public function testTaskModelHasDatabaseConnection()
    {
        $reflection = new \ReflectionClass($this->taskModel);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $dbValue = $dbProperty->getValue($this->taskModel);

        $this->assertNotNull($dbValue, 'El modelo debe tener una conexión a la BD');
    }

    public function testValidateUpdateDataStructure()
    {
        $taskId = 1;
        $data = ['tarea_nombre' => 'Test Task'];
        $userRole = 'admin';

        $result = $this->taskModel->validateUpdateData($taskId, $data, $userRole);
        $this->assertIsArray($result, 'validateUpdateData debe retornar un array');
        $this->assertArrayHasKey('valid', $result, 'El resultado debe tener la clave "valid"');
        $this->assertIsBool($result['valid'], 'La clave "valid" debe ser boolean');
    }

    public function testChangeStateStructure()
    {
        // Test básico de estructura sin crear cambios reales
        $taskId = 999999; // ID que probablemente no existe
        $newState = 2;
        $userId = 1;
        $userRole = 'admin';

        $result = $this->taskModel->changeState($taskId, $newState, $userId, $userRole);
        $this->assertIsArray($result, 'changeState debe retornar un array');
        $this->assertArrayHasKey('success', $result, 'El resultado debe tener la clave "success"');
        $this->assertIsBool($result['success'], 'La clave "success" debe ser boolean');
    }
}
