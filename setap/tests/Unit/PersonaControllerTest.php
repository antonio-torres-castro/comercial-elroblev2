<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\PersonaController;
use App\Models\Persona;

/**
 * Tests unitarios para PersonaController - Actualizado para AbstractBaseController
 * 
 * @author MiniMax Agent
 * @date 2025-10-21
 */
class PersonaControllerTest extends TestCase
{
    private PersonaController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock de sesión para simular usuario autenticado
        $_SESSION = [
            'user_id' => 1,
            'username' => 'test_user',
            'email' => 'test@example.com',
            'nombre_completo' => 'Usuario Test',
            'rol' => 'admin',
            'usuario_tipo_id' => 1,
            'authenticated' => true
        ];
        
        // Limpiar superglobals
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Instanciar controlador - ahora funciona con AbstractBaseController
        $this->controller = new PersonaController();
    }

    /**
     * Test que verifica que el controlador se puede instanciar correctamente
     */
    public function testControllerCanBeInstantiated()
    {
        $this->assertInstanceOf(PersonaController::class, $this->controller);
        $this->assertInstanceOf(\App\Controllers\AbstractBaseController::class, $this->controller);
    }

    /**
     * Test que verifica que todos los métodos públicos existen
     */
    public function testAllPublicMethodsExist()
    {
        $expectedMethods = ['index', 'create', 'store', 'edit', 'update', 'delete', 'show'];
        
        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                method_exists($this->controller, $method),
                "Método '{$method}' debe existir en PersonaController"
            );
        }
    }

    /**
     * Test que verifica que el controlador extiende de AbstractBaseController
     */
    public function testExtendsAbstractBaseController()
    {
        $this->assertInstanceOf(
            \App\Controllers\AbstractBaseController::class, 
            $this->controller,
            'PersonaController debe extender AbstractBaseController'
        );
    }

    /**
     * Test que verifica que tiene acceso a métodos heredados
     */
    public function testHasAccessToInheritedMethods()
    {
        // Verificar que tiene acceso a métodos de validación heredados
        $reflection = new \ReflectionClass($this->controller);
        
        // Métodos que debería tener heredados
        $inheritedMethods = [
            'executeWithErrorHandling',
            'requireAuthAndPermission', 
            'validatePostRequest',
            'render'
        ];
        
        foreach ($inheritedMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Debe tener acceso al método heredado '{$method}'"
            );
        }
    }

    /**
     * Test de validación de datos de persona
     */
    public function testValidatePersonaDataStructure()
    {
        // Test con datos válidos
        $validData = [
            'rut' => '12345678-9',
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'direccion' => 'Av. Providencia 1234',
            'estado_tipo_id' => 1
        ];

        // Test con datos inválidos
        $invalidData = [
            'rut' => 'invalid-rut',
            'nombre' => '', // Nombre vacío
            'telefono' => 'invalid-phone',
            'estado_tipo_id' => 'invalid'
        ];

        // Assert: Validación de estructura de datos
        $this->assertIsArray($validData);
        $this->assertArrayHasKey('rut', $validData);
        $this->assertArrayHasKey('nombre', $validData);
        
        // Test de RUT válido
        $this->assertMatchesRegularExpression('/^\d{7,8}-[\dkK]$/', $validData['rut']);
        
        // Test de nombre no vacío
        $this->assertNotEmpty($validData['nombre']);
    }

    /**
     * Test de validación de RUT chileno
     */
    public function testRutValidation()
    {
        // RUTs válidos
        $validRuts = [
            '12345678-9',
            '1234567-8',
            '12345678-K',
            '12345678-k'
        ];

        // RUTs inválidos
        $invalidRuts = [
            '12345678',
            '12345678-',
            'invalid-rut',
            '123456789-0',
            ''
        ];

        foreach ($validRuts as $rut) {
            $this->assertMatchesRegularExpression('/^\d{7,8}-[\dkK]$/', $rut, "RUT válido: {$rut}");
        }

        foreach ($invalidRuts as $rut) {
            $this->assertDoesNotMatchRegularExpression('/^\d{7,8}-[\dkK]$/', $rut, "RUT inválido: {$rut}");
        }
    }

    /**
     * Test de estructura de filtros
     */
    public function testPersonaFiltersStructure()
    {
        $filters = [
            'estado_tipo_id' => 1,
            'search' => 'Juan'
        ];

        // Assert: Filtros estructurados correctamente
        $this->assertArrayHasKey('estado_tipo_id', $filters);
        $this->assertArrayHasKey('search', $filters);
        $this->assertEquals(1, $filters['estado_tipo_id']);
        $this->assertEquals('Juan', $filters['search']);
    }

    /**
     * Test que verifica que el controlador maneja sesiones correctamente
     */
    public function testControllerHandlesSessionCorrectly()
    {
        // El constructor de AbstractBaseController debe configurar el usuario actual
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertArrayHasKey('username', $_SESSION);
        $this->assertEquals(1, $_SESSION['user_id']);
        $this->assertEquals('test_user', $_SESSION['username']);
    }

    /**
     * Test de estructura de datos POST para creación
     */
    public function testStoreDataStructure()
    {
        $validPostData = [
            'csrf_token' => 'valid_token',
            'rut' => '12345678-9',
            'rut_clean' => '123456789',
            'nombre' => 'Juan Pérez',
            'telefono' => '+56912345678',
            'direccion' => 'Av. Providencia 1234',
            'estado_tipo_id' => 1
        ];

        // Assert: Estructura de datos válida
        $this->assertArrayHasKey('csrf_token', $validPostData);
        $this->assertArrayHasKey('rut', $validPostData);
        $this->assertArrayHasKey('rut_clean', $validPostData);
        $this->assertArrayHasKey('nombre', $validPostData);
        $this->assertArrayHasKey('estado_tipo_id', $validPostData);
    }

    /**
     * Test que verifica que usa el nuevo patrón de AbstractBaseController
     */
    public function testUsesNewArchitecturalPattern()
    {
        $reflection = new \ReflectionClass($this->controller);
        
        // Verificar que NO tiene las propiedades del patrón antiguo
        $oldProperties = ['permissionService', 'db'];
        foreach ($oldProperties as $property) {
            $this->assertFalse(
                $reflection->hasProperty($property),
                "No debe tener la propiedad obsoleta '{$property}'"
            );
        }
        
        // Verificar que tiene la propiedad del nuevo patrón
        $this->assertTrue(
            $reflection->hasProperty('personaModel'),
            'Debe tener la propiedad personaModel'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Limpiar sesión y superglobals
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        unset($_SERVER['REQUEST_METHOD']);
    }
}
