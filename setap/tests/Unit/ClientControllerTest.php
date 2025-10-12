<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\ClientController;
use App\Models\Client;
use App\Models\Persona;
use App\Services\PermissionService;
use App\Services\ClientValidationService;
use App\Services\CounterpartieService;
use App\Core\ViewRenderer;

class ClientControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // Simular autenticación para tests
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'test_user';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    public function testClientControllerExtendsBaseController()
    {
        // Note: Constructor requires authentication, so we test class structure
        $reflection = new \ReflectionClass('App\Controllers\ClientController');
        $this->assertTrue(
            $reflection->isSubclassOf('App\Controllers\BaseController'),
            'ClientController debe extender BaseController'
        );
    }

    public function testClientControllerHasRequiredMethods()
    {
        $reflection = new \ReflectionClass('App\Controllers\ClientController');
        
        $requiredMethods = ['index', '__construct'];
        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "ClientController debe tener el método $method"
            );
        }
    }

    public function testClientModelInstantiation()
    {
        $clientModel = new Client();
        $this->assertInstanceOf(
            'App\Models\Client',
            $clientModel,
            'Client model debe poder instanciarse'
        );
    }

    public function testPersonaModelInstantiation()
    {
        $personaModel = new Persona();
        $this->assertInstanceOf(
            'App\Models\Persona',
            $personaModel,
            'Persona model debe poder instanciarse'
        );
    }

    public function testPermissionServiceInstantiation()
    {
        $permissionService = new PermissionService();
        $this->assertInstanceOf(
            'App\Services\PermissionService',
            $permissionService,
            'PermissionService debe poder instanciarse'
        );
    }

    public function testClientValidationServiceInstantiation()
    {
        $clientValidationService = new ClientValidationService();
        $this->assertInstanceOf(
            'App\Services\ClientValidationService',
            $clientValidationService,
            'ClientValidationService debe poder instanciarse'
        );
    }

    public function testCounterpartieServiceInstantiation()
    {
        $counterpartieService = new CounterpartieService();
        $this->assertInstanceOf(
            'App\Services\CounterpartieService',
            $counterpartieService,
            'CounterpartieService debe poder instanciarse'
        );
    }

    public function testViewRendererInstantiation()
    {
        $viewRenderer = new ViewRenderer();
        $this->assertInstanceOf(
            'App\Core\ViewRenderer',
            $viewRenderer,
            'ViewRenderer debe poder instanciarse'
        );
    }

    public function testControllerHasProperDependencies()
    {
        $reflection = new \ReflectionClass('App\Controllers\ClientController');
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor, 'ClientController debe tener constructor');
        
        // Verificar que el constructor inicializa las dependencias necesarias
        $properties = $reflection->getProperties();
        $expectedProperties = [
            'clientModel',
            'personaModel', 
            'permissionService',
            'clientValidationService',
            'counterpartieService',
            'viewRenderer'
        ];
        
        $propertyNames = array_map(function($prop) {
            return $prop->getName();
        }, $properties);
        
        foreach ($expectedProperties as $expectedProp) {
            $this->assertContains(
                $expectedProp,
                $propertyNames,
                "ClientController debe tener la propiedad $expectedProp"
            );
        }
    }
}
