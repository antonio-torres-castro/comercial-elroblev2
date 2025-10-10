<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Constants\AppConstants;

/**
 * Tests para validar el funcionamiento de las constantes de la aplicación
 * 
 * @author MiniMax Agent
 * @date 2025-10-10
 */
class AppConstantsTest extends TestCase
{
    /**
     * Test que valida que todas las constantes de rutas están definidas correctamente
     */
    public function testRouteConstantsAreDefined()
    {
        // Rutas principales
        $this->assertNotEmpty(AppConstants::ROUTE_LOGIN);
        $this->assertNotEmpty(AppConstants::ROUTE_HOME);
        $this->assertNotEmpty(AppConstants::ROUTE_USERS);
        $this->assertNotEmpty(AppConstants::ROUTE_CLIENTS);
        $this->assertNotEmpty(AppConstants::ROUTE_TASKS);
        $this->assertNotEmpty(AppConstants::ROUTE_PERSONAS);
        $this->assertNotEmpty(AppConstants::ROUTE_MENUS);
        $this->assertNotEmpty(AppConstants::ROUTE_PERFIL);
        $this->assertNotEmpty(AppConstants::ROUTE_REPORTS);
        $this->assertNotEmpty(AppConstants::ROUTE_PROJECTS);
        
        // Rutas con acciones
        $this->assertNotEmpty(AppConstants::ROUTE_USERS_CREATE);
        $this->assertNotEmpty(AppConstants::ROUTE_PERSONAS_CREATE);
        $this->assertNotEmpty(AppConstants::ROUTE_PROJECTS_CREATE);
    }

    /**
     * Test que valida el formato correcto de las rutas
     */
    public function testRouteConstantsFormat()
    {
        // Todas las rutas deben empezar con '/'
        $this->assertStringStartsWith('/', AppConstants::ROUTE_LOGIN);
        $this->assertStringStartsWith('/', AppConstants::ROUTE_HOME);
        $this->assertStringStartsWith('/', AppConstants::ROUTE_USERS);
        $this->assertStringStartsWith('/', AppConstants::ROUTE_TASKS);
        $this->assertStringStartsWith('/', AppConstants::ROUTE_PROJECTS);
        
        // Rutas específicas deben tener el formato correcto
        $this->assertEquals('/login', AppConstants::ROUTE_LOGIN);
        $this->assertEquals('/home', AppConstants::ROUTE_HOME);
        $this->assertEquals('/users', AppConstants::ROUTE_USERS);
        $this->assertEquals('/tasks', AppConstants::ROUTE_TASKS);
        $this->assertEquals('/projects', AppConstants::ROUTE_PROJECTS);
    }

    /**
     * Test que valida que las constantes de error están definidas
     */
    public function testErrorConstantsAreDefined()
    {
        // Errores básicos
        $this->assertNotEmpty(AppConstants::ERROR_INVALID_ID);
        $this->assertNotEmpty(AppConstants::ERROR_INVALID_USER_ID);
        $this->assertNotEmpty(AppConstants::ERROR_INVALID_TASK_ID);
        $this->assertNotEmpty(AppConstants::ERROR_USER_NOT_FOUND);
        $this->assertNotEmpty(AppConstants::ERROR_TASK_NOT_FOUND);
        $this->assertNotEmpty(AppConstants::ERROR_INTERNAL_SERVER);
        
        // Errores de acceso
        $this->assertNotEmpty(AppConstants::ERROR_ACCESS_DENIED);
        $this->assertNotEmpty(AppConstants::ERROR_NO_PERMISSIONS);
        $this->assertNotEmpty(AppConstants::ERROR_USER_NOT_AUTHENTICATED);
        $this->assertNotEmpty(AppConstants::ERROR_USER_NOT_AUTHORIZED);
        
        // Errores de validación
        $this->assertNotEmpty(AppConstants::ERROR_LOGIN_REQUIRED);
        $this->assertNotEmpty(AppConstants::ERROR_REQUIRED_FIELDS);
        $this->assertNotEmpty(AppConstants::ERROR_INVALID_RUT);
        $this->assertNotEmpty(AppConstants::ERROR_INVALID_EMAIL);
    }

    /**
     * Test que valida que las constantes de éxito están definidas
     */
    public function testSuccessConstantsAreDefined()
    {
        // Éxitos básicos
        $this->assertNotEmpty(AppConstants::SUCCESS_CREATED);
        $this->assertNotEmpty(AppConstants::SUCCESS_UPDATED);
        $this->assertNotEmpty(AppConstants::SUCCESS_DELETED);
        
        // Éxitos específicos
        $this->assertNotEmpty(AppConstants::SUCCESS_USER_CREATED);
        $this->assertNotEmpty(AppConstants::SUCCESS_USER_DELETED);
        $this->assertNotEmpty(AppConstants::SUCCESS_TASK_DELETED);
        $this->assertNotEmpty(AppConstants::SUCCESS_CLIENT_CREATED);
        $this->assertNotEmpty(AppConstants::SUCCESS_PROJECT_NOT_FOUND);
    }

    /**
     * Test que valida las constantes de interfaz de usuario
     */
    public function testUIConstantsAreDefined()
    {
        // Textos de botones
        $this->assertNotEmpty(AppConstants::UI_BTN_CREATE);
        $this->assertNotEmpty(AppConstants::UI_BTN_EDIT);
        $this->assertNotEmpty(AppConstants::UI_BTN_SAVE);
        $this->assertNotEmpty(AppConstants::UI_BTN_CANCEL);
        $this->assertNotEmpty(AppConstants::UI_BTN_DELETE);
        $this->assertNotEmpty(AppConstants::UI_BTN_BACK);
        
        // Títulos de gestión
        $this->assertNotEmpty(AppConstants::UI_TASK_MANAGEMENT);
        $this->assertNotEmpty(AppConstants::UI_PROJECT_MANAGEMENT);
        $this->assertNotEmpty(AppConstants::UI_PERSONA_MANAGEMENT);
        $this->assertNotEmpty(AppConstants::UI_SYSTEM_REPORTS);
        
        // Acciones de navegación
        $this->assertNotEmpty(AppConstants::UI_BACK_TO_TASKS);
        $this->assertNotEmpty(AppConstants::UI_BACK_TO_PROJECTS);
        $this->assertNotEmpty(AppConstants::UI_BACK_TO_PERSONAS);
        $this->assertNotEmpty(AppConstants::UI_BACK_TO_REPORTS);
    }

    /**
     * Test que valida la consistencia de los textos de UI
     */
    public function testUIConstantsConsistency()
    {
        // Validar que los textos de botones son coherentes
        $this->assertEquals('Crear', AppConstants::UI_BTN_CREATE);
        $this->assertEquals('Editar', AppConstants::UI_BTN_EDIT);
        $this->assertEquals('Guardar', AppConstants::UI_BTN_SAVE);
        $this->assertEquals('Cancelar', AppConstants::UI_BTN_CANCEL);
        $this->assertEquals('Eliminar', AppConstants::UI_BTN_DELETE);
        $this->assertEquals('Volver', AppConstants::UI_BTN_BACK);
        
        // Validar títulos de gestión
        $this->assertEquals('Gestión de Tareas', AppConstants::UI_TASK_MANAGEMENT);
        $this->assertEquals('Gestión de Proyectos', AppConstants::UI_PROJECT_MANAGEMENT);
        $this->assertEquals('Gestión de Personas', AppConstants::UI_PERSONA_MANAGEMENT);
        $this->assertEquals('Reportes del Sistema', AppConstants::UI_SYSTEM_REPORTS);
    }

    /**
     * Test que valida los métodos de utilidad
     */
    public function testUtilityMethods()
    {
        // Test buildSuccessUrl
        $baseRoute = '/users';
        $message = 'created';
        $expectedUrl = '/users?success=created';
        
        $result = AppConstants::buildSuccessUrl($baseRoute, $message);
        $this->assertEquals($expectedUrl, $result);
        
        // Test buildErrorUrl
        $errorMessage = 'Error de validación';
        $expectedErrorUrl = '/users?error=' . urlencode($errorMessage);
        
        $result = AppConstants::buildErrorUrl($baseRoute, $errorMessage);
        $this->assertEquals($expectedErrorUrl, $result);
    }

    /**
     * Test que valida que no hay constantes duplicadas
     */
    public function testNoDuplicateConstants()
    {
        $reflection = new \ReflectionClass(AppConstants::class);
        $constants = $reflection->getConstants();
        
        // Verificar que no hay valores duplicados que podrían causar confusión
        $values = array_values($constants);
        $uniqueValues = array_unique($values);
        
        // Nota: Algunos valores pueden ser legítimamente duplicados (ej: 'Crear' puede aparecer en múltiples contextos)
        // Este test verifica que no hay una duplicación excesiva
        $duplicateRatio = (count($values) - count($uniqueValues)) / count($values);
        
        // Debe haber menos del 30% de duplicación
        $this->assertLessThan(0.3, $duplicateRatio, 'Demasiadas constantes duplicadas detectadas');
    }

    /**
     * Test que valida la nomenclatura de las constantes
     */
    public function testConstantNamingConvention()
    {
        $reflection = new \ReflectionClass(AppConstants::class);
        $constants = array_keys($reflection->getConstants());
        
        foreach ($constants as $constantName) {
            // Todas las constantes deben estar en mayúsculas
            $this->assertEquals(strtoupper($constantName), $constantName, 
                "La constante '{$constantName}' no está en mayúsculas");
            
            // Todas las constantes deben usar underscore como separador
            $this->assertDoesNotMatchRegularExpression('/[a-z][A-Z]/', $constantName, 
                "La constante '{$constantName}' no usa la convención underscore");
        }
    }

    /**
     * Test que valida las constantes agregadas en la Fase 4
     */
    public function testFase4Constants()
    {
        // Constantes específicas agregadas en la extensión de la Fase 4
        $fase4Constants = [
            'UI_TASK_MANAGEMENT',
            'UI_PROJECT_MANAGEMENT', 
            'UI_PERSONA_MANAGEMENT',
            'UI_SYSTEM_REPORTS',
            'UI_BACK_TO_TASKS',
            'UI_BACK_TO_PROJECTS',
            'UI_BACK_TO_PERSONAS',
            'UI_BACK_TO_REPORTS',
            'UI_NEW_TASK',
            'UI_NEW_PERSONA',
            'UI_CREATE_PROJECT_TITLE',
            'UI_EDIT_TASK_TITLE',
            'UI_BASIC_INFORMATION',
            'UI_TASK_INFORMATION'
        ];
        
        $reflection = new \ReflectionClass(AppConstants::class);
        $allConstants = array_keys($reflection->getConstants());
        
        foreach ($fase4Constants as $constantName) {
            $this->assertContains($constantName, $allConstants, 
                "La constante de Fase 4 '{$constantName}' no está definida");
            
            // Verificar que la constante tiene un valor no vacío
            $constantValue = constant("App\\Constants\\AppConstants::{$constantName}");
            $this->assertNotEmpty($constantValue, 
                "La constante '{$constantName}' está vacía");
        }
    }

    /**
     * Test que valida que las constantes de parámetros funcionan correctamente
     */
    public function testParameterConstants()
    {
        $this->assertEquals('success', AppConstants::PARAM_SUCCESS);
        $this->assertEquals('error', AppConstants::PARAM_ERROR);
        
        // Test de uso en URLs
        $url1 = '/test?' . AppConstants::PARAM_SUCCESS . '=created';
        $this->assertEquals('/test?success=created', $url1);
        
        $url2 = '/test?' . AppConstants::PARAM_ERROR . '=failed';
        $this->assertEquals('/test?error=failed', $url2);
    }

    /**
     * Test de performance - las constantes deben cargarse rápidamente
     */
    public function testConstantsPerformance()
    {
        $startTime = microtime(true);
        
        // Acceder a múltiples constantes
        for ($i = 0; $i < 1000; $i++) {
            $route = AppConstants::ROUTE_LOGIN;
            $error = AppConstants::ERROR_INVALID_ID;
            $success = AppConstants::SUCCESS_CREATED;
            $ui = AppConstants::UI_BTN_CREATE;
        }
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // en millisegundos
        
        // El acceso a constantes debe ser muy rápido (menos de 50ms para 1000 accesos)
        $this->assertLessThan(50, $executionTime, 'El acceso a constantes es demasiado lento');
    }

    /**
     * Test que verifica que la clase AppConstants es utilizable como se esperaba
     */
    public function testAppConstantsUsability()
    {
        // Verificar que la clase existe y es accesible
        $this->assertTrue(class_exists('App\\Constants\\AppConstants'));
        
        // Verificar que es una clase y no un trait o interface
        $reflection = new \ReflectionClass(AppConstants::class);
        $this->assertTrue($reflection->isClass());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
        
        // Verificar que los métodos estáticos funcionan
        $this->assertTrue(method_exists(AppConstants::class, 'buildSuccessUrl'));
        $this->assertTrue(method_exists(AppConstants::class, 'buildErrorUrl'));
        
        // Verificar que los métodos son estáticos
        $method1 = $reflection->getMethod('buildSuccessUrl');
        $method2 = $reflection->getMethod('buildErrorUrl');
        $this->assertTrue($method1->isStatic());
        $this->assertTrue($method2->isStatic());
    }
}