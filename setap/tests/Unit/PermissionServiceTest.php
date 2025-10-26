<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PermissionService;

class PermissionServiceTest extends TestCase
{
    private $permissionService;

    protected function setUp(): void
    {
        $this->permissionService = new PermissionService();
    }

    public function testPermissionServiceInstantiation()
    {
        $this->assertInstanceOf(
            'App\Services\PermissionService',
            $this->permissionService,
            'PermissionService debe poder instanciarse'
        );
    }

    public function testPermissionServiceHasRequiredMethods()
    {
        $requiredMethods = [
            'hasPermission',
            'hasMenuAccess',
            'getUserMenus',
            'hasAnyPermission',
            'hasAllPermissions'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->permissionService, $method),
                "PermissionService debe tener el método $method"
            );
        }
    }

    public function testHasPermissionReturnsBool()
    {
        $userId = 1;
        $permissionName = 'test_permission';

        $result = $this->permissionService->hasPermission($userId, $permissionName);
        $this->assertIsBool($result, 'hasPermission debe retornar un boolean');
    }

    public function testHasMenuAccessReturnsBool()
    {
        $userId = 1;
        $menuName = 'test_menu';

        $result = $this->permissionService->hasMenuAccess($userId, $menuName);
        $this->assertIsBool($result, 'hasMenuAccess debe retornar un boolean');
    }

    public function testGetUserMenusReturnsArray()
    {
        $userId = 1;

        $result = $this->permissionService->getUserMenus($userId);
        $this->assertIsArray($result, 'getUserMenus debe retornar un array');
    }

    public function testHasAnyPermissionReturnsBool()
    {
        $userId = 1;
        $permissions = ['permission1', 'permission2'];

        $result = $this->permissionService->hasAnyPermission($userId, $permissions);
        $this->assertIsBool($result, 'hasAnyPermission debe retornar un boolean');
    }

    public function testHasAllPermissionsReturnsBool()
    {
        $userId = 1;
        $permissions = ['permission1', 'permission2'];

        $result = $this->permissionService->hasAllPermissions($userId, $permissions);
        $this->assertIsBool($result, 'hasAllPermissions debe retornar un boolean');
    }

    public function testHasPermissionAcceptsCorrectTypes()
    {
        // Verificar que acepta int y string
        $userId = 1;
        $permissionName = 'test_permission';

        // No debe lanzar excepción de tipo
        $result = $this->permissionService->hasPermission($userId, $permissionName);
        $this->assertIsBool($result, 'hasPermission debe aceptar int y string');
    }

    public function testHasMenuAccessAcceptsCorrectTypes()
    {
        // Verificar que acepta int y string
        $userId = 1;
        $menuName = 'test_menu';

        // No debe lanzar excepción de tipo
        $result = $this->permissionService->hasMenuAccess($userId, $menuName);
        $this->assertIsBool($result, 'hasMenuAccess debe aceptar int y string');
    }

    public function testHasAnyPermissionAcceptsArray()
    {
        $userId = 1;
        $permissions = ['permission1', 'permission2', 'permission3'];

        // No debe lanzar excepción de tipo
        $result = $this->permissionService->hasAnyPermission($userId, $permissions);
        $this->assertIsBool($result, 'hasAnyPermission debe aceptar array');
    }

    public function testHasAllPermissionsAcceptsArray()
    {
        $userId = 1;
        $permissions = ['permission1', 'permission2', 'permission3'];

        // No debe lanzar excepción de tipo
        $result = $this->permissionService->hasAllPermissions($userId, $permissions);
        $this->assertIsBool($result, 'hasAllPermissions debe aceptar array');
    }

    public function testHasAnyPermissionWithEmptyArray()
    {
        $userId = 1;
        $permissions = [];

        $result = $this->permissionService->hasAnyPermission($userId, $permissions);
        $this->assertIsBool($result, 'hasAnyPermission debe manejar array vacío');
    }

    public function testHasAllPermissionsWithEmptyArray()
    {
        $userId = 1;
        $permissions = [];

        $result = $this->permissionService->hasAllPermissions($userId, $permissions);
        $this->assertIsBool($result, 'hasAllPermissions debe manejar array vacío');
    }

    public function testPermissionServiceHasDatabaseConnection()
    {
        $reflection = new \ReflectionClass($this->permissionService);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $dbValue = $dbProperty->getValue($this->permissionService);

        $this->assertNotNull($dbValue, 'PermissionService debe tener una conexión a la BD');
    }

    public function testGetUserMenusWithNonExistentUser()
    {
        $userId = 999999; // ID que probablemente no existe

        $result = $this->permissionService->getUserMenus($userId);
        $this->assertIsArray($result, 'getUserMenus debe retornar array incluso para usuarios inexistentes');
    }

    public function testHasPermissionWithNonExistentUser()
    {
        $userId = 999999; // ID que probablemente no existe
        $permissionName = 'test_permission';

        $result = $this->permissionService->hasPermission($userId, $permissionName);
        $this->assertFalse($result, 'hasPermission debe retornar false para usuarios inexistentes');
    }

    public function testHasMenuAccessWithNonExistentUser()
    {
        $userId = 999999; // ID que probablemente no existe
        $menuName = 'test_menu';

        $result = $this->permissionService->hasMenuAccess($userId, $menuName);
        $this->assertFalse($result, 'hasMenuAccess debe retornar false para usuarios inexistentes');
    }
}
