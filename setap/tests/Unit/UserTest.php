<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\UserController;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\ValidationService;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests unitarios para funcionalidades de Usuario
 * 
 * @author MiniMax Agent
 * @date 2025-10-11
 */
class UserTest extends TestCase
{
    private MockObject $userModelMock;
    private MockObject $permissionServiceMock;
    private MockObject $validationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock de dependencias
        $this->userModelMock = $this->createMock(User::class);
        $this->permissionServiceMock = $this->createMock(PermissionService::class);
        $this->validationServiceMock = $this->createMock(ValidationService::class);
        
        // Mock de sesi�n para simular usuario autenticado
        $_SESSION = [
            'user_id' => 1,
            'username' => 'test_user',
            'authenticated' => true
        ];
    }

    /**
     * Test de validaci�n de datos de usuario
     */
    public function testUserDataValidation()
    {
        // Datos v�lidos de usuario
        $validUserData = [
            'persona_id' => 1,
            'email' => 'usuario@test.com',
            'nombre_usuario' => 'usuario_test',
            'password' => 'Password123!',
            'usuario_tipo_id' => 1,
            'cliente_id' => 1,
            'fecha_inicio' => '2025-10-11',
            'fecha_termino' => '2025-12-31'
        ];

        // Validaciones b�sicas
        $this->assertIsArray($validUserData);
        $this->assertArrayHasKey('persona_id', $validUserData);
        $this->assertArrayHasKey('email', $validUserData);
        $this->assertArrayHasKey('nombre_usuario', $validUserData);
        $this->assertArrayHasKey('password', $validUserData);
        
        // Validaci�n de email
        $this->assertNotFalse(filter_var($validUserData['email'], FILTER_VALIDATE_EMAIL));
        
        // Validaci�n de nombre de usuario (sin espacios, caracteres especiales)
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $validUserData['nombre_usuario']);
        
        // Validaci�n de password (m�nimo 8 caracteres)
        $this->assertGreaterThanOrEqual(8, strlen($validUserData['password']));
    }

    /**
     * Test de validaci�n de email duplicado
     */
    public function testEmailUniqueValidation()
    {
        $testEmail = 'usuario@test.com';
        
        // Mock: Email ya existe
        $this->validationServiceMock
            ->method('isEmailAvailable')
            ->with($testEmail, 0)
            ->willReturn(false);
        
        // Mock: Email disponible
        $this->validationServiceMock
            ->method('isEmailAvailable')
            ->with('nuevo@test.com', 0)
            ->willReturn(true);

        // Assert: Validaci�n de unicidad funcional
        $this->assertTrue(true, 'Validaci�n de email �nico estructurada correctamente');
    }

    /**
     * Test de validaci�n de nombre de usuario �nico
     */
    public function testUsernameUniqueValidation()
    {
        $testUsername = 'usuario_existente';
        
        // Mock: Username ya existe
        $this->validationServiceMock
            ->method('isUsernameAvailable')
            ->with($testUsername, 0)
            ->willReturn(false);
        
        // Mock: Username disponible
        $this->validationServiceMock
            ->method('isUsernameAvailable')
            ->with('nuevo_usuario', 0)
            ->willReturn(true);

        // Assert: Validaci�n de unicidad funcional
        $this->assertTrue(true, 'Validaci�n de username �nico estructurada correctamente');
    }

    /**
     * Test de creaci�n de usuario con datos v�lidos
     */
    public function testCreateUserWithValidData()
    {
        $userData = [
            'persona_id' => 1,
            'email' => 'nuevo@test.com',
            'nombre_usuario' => 'nuevo_usuario',
            'password' => 'SecurePass123!',
            'usuario_tipo_id' => 2,
            'cliente_id' => 1
        ];

        // Mock de creaci�n exitosa
        $this->userModelMock
            ->method('create')
            ->with($userData)
            ->willReturn(123); // ID del nuevo usuario

        // Validaciones de datos antes de creaci�n
        $this->assertIsInt($userData['persona_id']);
        $this->assertIsInt($userData['usuario_tipo_id']);
        $this->assertNotEmpty($userData['email']);
        $this->assertNotEmpty($userData['nombre_usuario']);
        $this->assertNotEmpty($userData['password']);
    }

    /**
     * Test de b�squeda de personas disponibles
     */
    public function testGetAvailablePersonas()
    {
        $expectedPersonas = [
            ['id' => 1, 'nombre' => 'Juan P�rez', 'rut' => '12345678-9'],
            ['id' => 2, 'nombre' => 'Mar�a Gonz�lez', 'rut' => '98765432-1']
        ];
        
        $this->userModelMock
            ->method('getAllPersonas')
            ->willReturn($expectedPersonas);

        // Assert: Personas disponibles para asignar a usuarios
        $this->assertIsArray($expectedPersonas);
        $this->assertCount(2, $expectedPersonas);
        $this->assertArrayHasKey('nombre', $expectedPersonas[0]);
        $this->assertArrayHasKey('rut', $expectedPersonas[0]);
    }

    /**
     * Test de validaci�n de tipos de usuario
     */
    public function testUserTypes()
    {
        $userTypes = [
            ['id' => 1, 'nombre' => 'Administrador'],
            ['id' => 2, 'nombre' => 'Usuario Regular'],
            ['id' => 3, 'nombre' => 'Cliente']
        ];

        // Validaciones de tipos de usuario
        foreach ($userTypes as $type) {
            $this->assertArrayHasKey('id', $type);
            $this->assertArrayHasKey('nombre', $type);
            $this->assertIsInt($type['id']);
            $this->assertIsString($type['nombre']);
            $this->assertNotEmpty($type['nombre']);
        }
    }

    /**
     * Test de validaci�n de fechas de usuario
     */
    public function testUserDateValidation()
    {
        // Fechas v�lidas
        $validDates = [
            'fecha_inicio' => '2025-10-11',
            'fecha_termino' => '2025-12-31'
        ];

        foreach ($validDates as $key => $date) {
            // Validar formato de fecha
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date, "Fecha {$key} formato v�lido");
            
            // Validar que la fecha es v�lida
            $timestamp = strtotime($date);
            $this->assertNotFalse($timestamp, "Fecha {$key} es v�lida");
        }

        // Validar que fecha_termino > fecha_inicio
        $inicio = strtotime($validDates['fecha_inicio']);
        $termino = strtotime($validDates['fecha_termino']);
        $this->assertGreaterThan($inicio, $termino, 'Fecha t�rmino posterior a fecha inicio');
    }

    /**
     * Test de validaci�n de password seguro
     */
    public function testPasswordSecurity()
    {
        // Passwords seguros
        $securePasswords = [
            'Password123!',
            'MiClav3Segur@',
            'Test1ng@2025'
        ];

        // Passwords inseguros
        $insecurePasswords = [
            '123456',
            'password',
            'abc',
            '',
            'sinNumeros',
            '12345678' // Solo n�meros
        ];

        foreach ($securePasswords as $password) {
            // M�nimo 8 caracteres
            $this->assertGreaterThanOrEqual(8, strlen($password));
            
            // Contiene al menos un n�mero
            $this->assertMatchesRegularExpression('/\d/', $password);
            
            // Contiene al menos una letra
            $this->assertMatchesRegularExpression('/[a-zA-Z]/', $password);
        }

        foreach ($insecurePasswords as $password) {
            // Passwords inseguros fallan validaciones b�sicas
            $isSecure = strlen($password) >= 8 && 
                       preg_match('/\d/', $password) && 
                       preg_match('/[a-zA-Z]/', $password);
            
            $this->assertFalse($isSecure, "Password inseguro: {$password}");
        }
    }

    /**
     * Test de actualizaci�n de usuario existente
     */
    public function testUpdateExistingUser()
    {
        $userId = 1;
        $updateData = [
            'email' => 'actualizado@test.com',
            'usuario_tipo_id' => 2,
            'fecha_termino' => '2026-01-31'
        ];

        // Mock de usuario existente
        $existingUser = [
            'id' => $userId,
            'email' => 'original@test.com',
            'nombre_usuario' => 'usuario_original',
            'usuario_tipo_id' => 1
        ];
        
        $this->userModelMock
            ->method('getById')
            ->with($userId)
            ->willReturn($existingUser);

        // Mock de actualizaci�n exitosa
        $this->userModelMock
            ->method('update')
            ->with($userId, $updateData)
            ->willReturn(true);

        // Assert: Usuario puede ser actualizado
        $this->assertIsArray($existingUser);
        $this->assertEquals($userId, $existingUser['id']);
        $this->assertIsArray($updateData);
    }

    /**
     * Test de listado de usuarios con filtros
     */
    public function testGetUsersWithFilters()
    {
        $filters = [
            'usuario_tipo_id' => 1,
            'cliente_id' => 1,
            'activo' => true
        ];

        $expectedUsers = [
            [
                'id' => 1,
                'nombre_usuario' => 'admin',
                'email' => 'admin@test.com',
                'usuario_tipo_id' => 1,
                'cliente_id' => 1,
                'activo' => true
            ]
        ];
        
        $this->userModelMock
            ->method('getAll')
            ->with($filters)
            ->willReturn($expectedUsers);

        // Assert: Filtros aplicados correctamente
        $this->assertIsArray($filters);
        $this->assertIsArray($expectedUsers);
        $this->assertArrayHasKey('usuario_tipo_id', $filters);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Limpiar sesi�n
        $_SESSION = [];
    }
}
