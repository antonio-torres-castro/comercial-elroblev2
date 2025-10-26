<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;

class AuthServiceTest extends TestCase
{
    private $authService;

    protected function setUp(): void
    {
        // Limpiar sesión antes de cada test
        $_SESSION = [];
        $this->authService = new AuthService();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testAuthServiceInstantiation()
    {
        $this->assertInstanceOf(
            'App\Services\AuthService',
            $this->authService,
            'AuthService debe poder instanciarse'
        );
    }

    public function testAuthServiceHasRequiredMethods()
    {
        $requiredMethods = [
            'authenticate',
            'login',
            'logout',
            'isAuthenticated',
            'getCurrentUser',
            'changePassword',
            'isSessionExpired'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->authService, $method),
                "AuthService debe tener el método $method"
            );
        }
    }

    public function testIsAuthenticatedReturnsBool()
    {
        $result = $this->authService->isAuthenticated();
        $this->assertIsBool($result, 'isAuthenticated debe retornar un boolean');
    }

    public function testIsAuthenticatedFalseWhenNotLoggedIn()
    {
        // Sin usuario en sesión, debe retornar false
        $result = $this->authService->isAuthenticated();
        $this->assertFalse($result, 'isAuthenticated debe retornar false cuando no hay usuario en sesión');
    }

    public function testGetCurrentUserWhenNotAuthenticated()
    {
        // Sin usuario en sesión
        $result = $this->authService->getCurrentUser();
        $this->assertNull($result, 'getCurrentUser debe retornar null cuando no hay usuario en sesión');
    }

    public function testIsSessionExpiredReturnsBool()
    {
        $result = $this->authService->isSessionExpired();
        $this->assertIsBool($result, 'isSessionExpired debe retornar un boolean');
    }

    public function testLogoutReturnsBool()
    {
        $result = $this->authService->logout();
        $this->assertIsBool($result, 'logout debe retornar un boolean');
    }

    public function testLogoutClearsSession()
    {
        // Simular usuario autenticado
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'test_user';

        $this->authService->logout();

        $this->assertEmpty($_SESSION, 'logout debe limpiar la sesión');
    }

    public function testAuthenticateWithInvalidCredentials()
    {
        // Test con credenciales que no existen
        $result = $this->authService->authenticate('nonexistent_user', 'wrong_password');

        // Con la nueva implementación, debe retornar array con success => false
        $this->assertIsArray($result, 'authenticate debe retornar array');
        $this->assertFalse($result['success'], 'authenticate debe retornar success=false con credenciales inválidas');
        $this->assertArrayHasKey('error_type', $result, 'Resultado debe incluir error_type');
        $this->assertArrayHasKey('friendly_message', $result, 'Resultado debe incluir friendly_message');
        $this->assertEquals('USER_NOT_FOUND', $result['error_type'], 'Error debe ser USER_NOT_FOUND');
    }

    public function testLoginRequiresArray()
    {
        // Test de estructura - login debe aceptar un array
        $this->expectException(\TypeError::class);
        $this->authService->login('invalid_input');
    }

    public function testLoginWithValidStructure()
    {
        // Test con estructura válida pero datos ficticios
        $userData = [
            'id' => 999999,
            'nombre_usuario' => 'test_user',
            'email' => 'test@example.com',
            'nombre_completo' => 'Test User',
            'rol' => 'admin',
            'usuario_tipo_id' => 1
        ];

        $result = $this->authService->login($userData);
        $this->assertIsBool($result, 'login debe retornar un boolean');
    }

    public function testChangePasswordRequiresValidInput()
    {
        // Test de estructura - changePassword debe aceptar int y string
        $this->expectException(\TypeError::class);
        $this->authService->changePassword('invalid_id', 'new_password');
    }

    public function testChangePasswordWithValidStructure()
    {
        $userId = 999999; // ID que probablemente no existe
        $newPassword = 'new_secure_password';

        $result = $this->authService->changePassword($userId, $newPassword);
        $this->assertIsBool($result, 'changePassword debe retornar un boolean');
    }

    public function testAuthServiceHasDatabaseConnection()
    {
        $reflection = new \ReflectionClass($this->authService);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $dbValue = $dbProperty->getValue($this->authService);

        $this->assertNotNull($dbValue, 'AuthService debe tener una conexión a la BD');
    }

    public function testAuthenticateAcceptsStringParameters()
    {
        // Verificar que authenticate acepta strings
        $identifier = 'test_user';
        $password = 'test_password';

        // No debe lanzar excepción de tipo
        $result = $this->authService->authenticate($identifier, $password);
        $this->assertIsArray($result, 'authenticate debe retornar array');
        $this->assertArrayHasKey('success', $result, 'Resultado debe incluir success');
        $this->assertIsBool($result['success'], 'success debe ser boolean');
    }

    public function testAuthenticateReturnsProperStructure()
    {
        // Test de estructura completa de respuesta
        $identifier = 'test_user';
        $password = 'test_password';

        $result = $this->authService->authenticate($identifier, $password);

        if ($result['success']) {
            // Si es exitoso, debe tener user
            $this->assertArrayHasKey('user', $result, 'Resultado exitoso debe incluir user');
            $this->assertNull($result['raw_error'], 'Resultado exitoso no debe tener raw_error');
        } else {
            // Si falla, debe tener error_type y friendly_message
            $this->assertArrayHasKey('error_type', $result, 'Resultado fallido debe incluir error_type');
            $this->assertArrayHasKey('friendly_message', $result, 'Resultado fallido debe incluir friendly_message');
        }

        // raw_error debe estar presente en todos los casos
        $this->assertArrayHasKey('raw_error', $result, 'Resultado siempre debe incluir raw_error');
    }

    public function testIsAuthenticatedAfterLogin()
    {
        // Simular login exitoso
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'test_user';
        $_SESSION['user_email'] = 'test@example.com';
        $_SESSION['login_time'] = time();

        $result = $this->authService->isAuthenticated();
        $this->assertTrue($result, 'isAuthenticated debe retornar true después de login');
    }

    public function testGetCurrentUserAfterLogin()
    {
        // Simular login exitoso
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'test_user';
        $_SESSION['user_email'] = 'test@example.com';
        $_SESSION['login_time'] = time();

        $result = $this->authService->getCurrentUser();
        $this->assertIsArray($result, 'getCurrentUser debe retornar array después de login');
        $this->assertArrayHasKey('id', $result, 'getCurrentUser debe incluir id');
        $this->assertArrayHasKey('nombre_usuario', $result, 'getCurrentUser debe incluir nombre_usuario');
    }
}
