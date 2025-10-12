<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Client;

class ClientModelTest extends TestCase
{
    private $clientModel;

    protected function setUp(): void
    {
        $this->clientModel = new Client();
    }

    public function testClientModelInstantiation()
    {
        $this->assertInstanceOf(
            'App\Models\Client',
            $this->clientModel,
            'Client model debe poder instanciarse'
        );
    }

    public function testClientModelHasRequiredMethods()
    {
        $requiredMethods = [
            'getAll',
            'find',
            'create',
            'update',
            'delete',
            'rutExists',
            'getCounterparties',
            'addCounterpartie',
            'updateCounterpartie',
            'deleteCounterpartie',
            'findCounterpartie',
            'getAllCounterparties',
            'counterpartieExists',
            'getStatusTypes',
            'validateRut',
            'formatRut'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->clientModel, $method),
                "Client model debe tener el método $method"
            );
        }
    }

    public function testGetAllReturnsArray()
    {
        $result = $this->clientModel->getAll();
        $this->assertIsArray($result, 'getAll debe retornar un array');
    }

    public function testGetAllWithFilters()
    {
        $filters = ['rut' => '12345678'];
        $result = $this->clientModel->getAll($filters);
        $this->assertIsArray($result, 'getAll con filtros debe retornar un array');
    }

    public function testGetStatusTypesReturnsArray()
    {
        $result = $this->clientModel->getStatusTypes();
        $this->assertIsArray($result, 'getStatusTypes debe retornar un array');
    }

    public function testValidateRutWithValidRut()
    {
        // Test con RUT válido conocido
        $validRut = '12345678-5';
        $result = $this->clientModel->validateRut($validRut);
        $this->assertIsBool($result, 'validateRut debe retornar un boolean');
    }

    public function testValidateRutWithInvalidRut()
    {
        // Test con RUT inválido
        $invalidRut = 'invalid-rut';
        $result = $this->clientModel->validateRut($invalidRut);
        $this->assertIsBool($result, 'validateRut debe retornar un boolean');
    }

    public function testFormatRutReturnsString()
    {
        $rut = '12345678-5';
        $result = $this->clientModel->formatRut($rut);
        $this->assertIsString($result, 'formatRut debe retornar un string');
    }

    public function testRutExistsReturnsBool()
    {
        // Test básico de existencia de RUT
        $rut = 'test-rut';
        $result = $this->clientModel->rutExists($rut);
        $this->assertIsBool($result, 'rutExists debe retornar un boolean');
    }

    public function testGetCounterpartiesReturnsArray()
    {
        // Test básico para contrapartes
        $clientId = 1;
        $result = $this->clientModel->getCounterparties($clientId);
        $this->assertIsArray($result, 'getCounterparties debe retornar un array');
    }

    public function testGetAllCounterpartiesReturnsArray()
    {
        $result = $this->clientModel->getAllCounterparties();
        $this->assertIsArray($result, 'getAllCounterparties debe retornar un array');
    }

    public function testCounterpartieExistsReturnsBool()
    {
        $clientId = 1;
        $personaId = 1;
        $result = $this->clientModel->counterpartieExists($clientId, $personaId);
        $this->assertIsBool($result, 'counterpartieExists debe retornar un boolean');
    }

    public function testFindReturnsNullOrArray()
    {
        $id = 999999; // ID que probablemente no existe
        $result = $this->clientModel->find($id);
        $this->assertTrue(
            is_null($result) || is_array($result),
            'find debe retornar null o un array'
        );
    }

    public function testFindCounterpartieReturnsNullOrArray()
    {
        $id = 999999; // ID que probablemente no existe
        $result = $this->clientModel->findCounterpartie($id);
        $this->assertTrue(
            is_null($result) || is_array($result),
            'findCounterpartie debe retornar null o un array'
        );
    }

    public function testClientModelHasCorrectTableProperty()
    {
        $reflection = new \ReflectionClass($this->clientModel);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $tableValue = $tableProperty->getValue($this->clientModel);
        
        $this->assertEquals(
            'clientes',
            $tableValue,
            'La tabla debe ser "clientes"'
        );
    }

    public function testClientModelHasDatabaseConnection()
    {
        $reflection = new \ReflectionClass($this->clientModel);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $dbValue = $dbProperty->getValue($this->clientModel);
        
        $this->assertNotNull($dbValue, 'El modelo debe tener una conexión a la base de datos');
    }
}
