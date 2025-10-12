<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\AuthController;
use App\Services\AuthService;
use App\Services\AuthViewService;
use App\Services\AuthValidationService;
use App\Helpers\Security;

class AuthControllerTest extends TestCase
{
    private $authController;
    private $authServiceMock;
    private $authViewServiceMock;
    private $authValidationServiceMock;

    protected function setUp(): void
    {
        // Limpiar variables de sesión
        $_SESSION = [];
        $_POST = [];
        
        // Crear controller
        $this->authController = new AuthController();
        
        // No necesitamos mocks complejos para tests básicos
        // Solo verificaremos que los métodos existen y son accesibles
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function testIsAuthExempt()
    {
        $reflection = new \ReflectionClass($this->authController);
        $method = $reflection->getMethod('isAuthExempt');
        
        $result = $method->invoke($this->authController);
        $this->assertTrue($result, 'AuthController debe estar exento de autenticación');
    }

    public function testShowLoginFormMethodExists()
    {
        $this->assertTrue(
            method_exists($this->authController, 'showLoginForm'),
            'El método showLoginForm debe existir'
        );
    }

    public function testLoginMethodExists()
    {
        $this->assertTrue(
            method_exists($this->authController, 'login'),
            'El método login debe existir'
        );
    }

    public function testLogoutMethodExists()
    {
        $this->assertTrue(
            method_exists($this->authController, 'logout'),
            'El método logout debe existir'
        );
    }

    public function testInitializeControllerMethodExists()
    {
        $reflection = new \ReflectionClass($this->authController);
        $this->assertTrue(
            $reflection->hasMethod('initializeController'),
            'El método initializeController debe existir'
        );
    }

    public function testControllerExtendsAbstractBaseController()
    {
        $this->assertInstanceOf(
            'App\Controllers\AbstractBaseController',
            $this->authController,
            'AuthController debe extender AbstractBaseController'
        );
    }

    public function testControllerUsesCommonValidationsTrait()
    {
        $traits = class_uses($this->authController);
        $this->assertContains(
            'App\Traits\CommonValidationsTrait',
            $traits,
            'AuthController debe usar CommonValidationsTrait'
        );
    }

    public function testAuthServiceInstantiation()
    {
        // Verificar que se puede instanciar AuthService (dependencia crítica)
        $authService = new AuthService();
        $this->assertInstanceOf(
            'App\Services\AuthService',
            $authService,
            'AuthService debe poder instanciarse correctamente'
        );
    }

    public function testAuthViewServiceInstantiation()
    {
        // Verificar que se puede instanciar AuthViewService
        $authViewService = new AuthViewService();
        $this->assertInstanceOf(
            'App\Services\AuthViewService',
            $authViewService,
            'AuthViewService debe poder instanciarse correctamente'
        );
    }

    public function testAuthValidationServiceInstantiation()
    {
        // Verificar que se puede instanciar AuthValidationService
        $authValidationService = new AuthValidationService();
        $this->assertInstanceOf(
            'App\Services\AuthValidationService',
            $authValidationService,
            'AuthValidationService debe poder instanciarse correctamente'
        );
    }

    public function testControllerCanBeInstantiated()
    {
        $controller = new AuthController();
        $this->assertInstanceOf(
            'App\Controllers\AuthController',
            $controller,
            'AuthController debe poder instanciarse correctamente'
        );
    }
}
