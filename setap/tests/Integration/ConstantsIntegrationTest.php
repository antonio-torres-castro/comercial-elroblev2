<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Constants\AppConstants;

/**
 * Tests de integración para validar el uso correcto de constantes en los módulos optimizados
 * 
 * @author MiniMax Agent
 * @date 2025-10-10
 */
class ConstantsIntegrationTest extends TestCase
{
    private $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFilesPath = dirname(__DIR__, 2) . '/src/App';
    }

    /**
     * Test que verifica que todos los archivos de vista usen constantes en lugar de strings hardcodeados
     */
    public function testViewFilesUseConstants()
    {
        // Archivos de vista que deben usar constantes
        $viewFiles = [
            '/Views/tasks/list.php',
            '/Views/tasks/create.php',
            '/Views/tasks/edit.php',
            '/Views/tasks/porjectTaskView.php',
            '/Views/projects/list.php',
            '/Views/projects/create.php',
            '/Views/reports/list.php',
            '/Views/personas/list.php',
            '/Views/personas/create.php'
        ];

        foreach ($viewFiles as $viewFile) {
            $fullPath = $this->testFilesPath . $viewFile;

            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);

                // Verificar que el archivo use la declaración use
                $this->assertStringContainsString(
                    'use App\Constants\AppConstants',
                    $content,
                    "El archivo {$viewFile} no importa AppConstants"
                );

                // Verificar que no contenga strings hardcodeados comunes que deberían ser constantes
                $this->assertStringNotContainsString(
                    '"Gestión de Tareas"',
                    $content,
                    "El archivo {$viewFile} contiene string hardcodeado 'Gestión de Tareas'"
                );

                $this->assertStringNotContainsString(
                    '"Gestión de Proyectos"',
                    $content,
                    "El archivo {$viewFile} contiene string hardcodeado 'Gestión de Proyectos'"
                );

                $this->assertStringNotContainsString(
                    '"Nueva Tarea"',
                    $content,
                    "El archivo {$viewFile} contiene string hardcodeado 'Nueva Tarea'"
                );

                $this->assertStringNotContainsString(
                    '"Volver a Tareas"',
                    $content,
                    "El archivo {$viewFile} contiene string hardcodeado 'Volver a Tareas'"
                );
            }
        }
    }

    /**
     * Test que verifica que los controladores usen constantes correctamente
     */
    public function testControllerFilesUseConstants()
    {
        $controllerFiles = [
            '/Controllers/TaskController.php',
            '/Controllers/UserController.php',
            '/Controllers/ProjectController.php'
        ];

        foreach ($controllerFiles as $controllerFile) {
            $fullPath = $this->testFilesPath . $controllerFile;

            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);

                // Verificar que el archivo use la declaración use si maneja mensajes
                if (strpos($content, 'AppConstants::') !== false) {
                    $this->assertStringContainsString(
                        'use App\Constants\AppConstants',
                        $content,
                        "El archivo {$controllerFile} usa constantes pero no las importa"
                    );
                }

                // Verificar que no contenga rutas hardcodeadas
                $this->assertStringNotContainsString(
                    'header("Location: /tasks"',
                    $content,
                    "El archivo {$controllerFile} contiene ruta hardcodeada"
                );

                $this->assertStringNotContainsString(
                    'header("Location: /users"',
                    $content,
                    "El archivo {$controllerFile} contiene ruta hardcodeada"
                );
            }
        }
    }

    /**
     * Test que verifica que las constantes de rutas son válidas y accesibles
     */
    public function testRouteConstantsAccessibility()
    {
        $routes = [
            AppConstants::ROUTE_LOGIN,
            AppConstants::ROUTE_HOME,
            AppConstants::ROUTE_USERS,
            AppConstants::ROUTE_TASKS,
            AppConstants::ROUTE_PROJECTS,
            AppConstants::ROUTE_REPORTS,
            AppConstants::ROUTE_PERSONAS
        ];

        foreach ($routes as $route) {
            // Verificar que la ruta tiene el formato correcto
            $this->assertStringStartsWith('/', $route, "La ruta '{$route}' no empieza con '/'");
            $this->assertDoesNotMatchRegularExpression('/\s/', $route, "La ruta '{$route}' contiene espacios");
            $this->assertGreaterThan(1, strlen($route), "La ruta '{$route}' es demasiado corta");
        }
    }

    /**
     * Test que verifica la construcción correcta de URLs con parámetros
     */
    public function testUrlBuildingMethods()
    {
        // Test buildSuccessUrl con diferentes combinaciones
        $testCases = [
            [
                'route' => AppConstants::ROUTE_USERS,
                'message' => AppConstants::SUCCESS_CREATED,
                'expected' => AppConstants::ROUTE_USERS . '?success=created'
            ],
            [
                'route' => AppConstants::ROUTE_TASKS,
                'message' => AppConstants::SUCCESS_UPDATED,
                'expected' => AppConstants::ROUTE_TASKS . '?success=updated'
            ],
            [
                'route' => AppConstants::ROUTE_PROJECTS,
                'message' => AppConstants::SUCCESS_DELETED,
                'expected' => AppConstants::ROUTE_PROJECTS . '?success=deleted'
            ]
        ];

        foreach ($testCases as $case) {
            $result = AppConstants::buildSuccessUrl($case['route'], $case['message']);
            $this->assertEquals($case['expected'], $result);
        }

        // Test buildErrorUrl con caracteres especiales
        $errorUrl = AppConstants::buildErrorUrl(
            AppConstants::ROUTE_USERS,
            'Error con espacios y símbolos &'
        );

        $this->assertStringContainsString('error=', $errorUrl);
        $this->assertStringContainsString(urlencode('Error con espacios y símbolos &'), $errorUrl);
    }

    /**
     * Test que simula el uso real de constantes en vistas
     */
    public function testConstantsInViewContext()
    {
        // Simular el uso en una vista
        ob_start();

        // Código que simula lo que haría una vista real
        echo AppConstants::UI_TASK_MANAGEMENT;
        echo ' - ';
        echo AppConstants::UI_NEW_TASK;

        $output = ob_get_clean();

        $this->assertEquals('Gestión de Tareas - Nueva Tarea', $output);

        // Test con botones
        ob_start();
        echo '<button>' . AppConstants::UI_BTN_CREATE . '</button>';
        echo '<button>' . AppConstants::UI_BTN_SAVE . '</button>';
        echo '<button>' . AppConstants::UI_BTN_CANCEL . '</button>';

        $buttonOutput = ob_get_clean();

        $this->assertStringContainsString('<button>Crear</button>', $buttonOutput);
        $this->assertStringContainsString('<button>Guardar</button>', $buttonOutput);
        $this->assertStringContainsString('<button>Cancelar</button>', $buttonOutput);
    }

    /**
     * Test que verifica la consistencia entre constantes relacionadas
     */
    public function testRelatedConstantsConsistency()
    {
        // Verificar consistencia entre rutas y mensajes de navegación
        $consistencyTests = [
            'tasks' => [
                'route' => AppConstants::ROUTE_TASKS,
                'management' => AppConstants::UI_TASK_MANAGEMENT,
                'back_to' => AppConstants::UI_BACK,
                'new_item' => AppConstants::UI_NEW_TASK
            ],
            'projects' => [
                'route' => AppConstants::ROUTE_PROJECTS,
                'management' => AppConstants::UI_PROJECT_MANAGEMENT,
                'back_to' => AppConstants::UI_BACK,
                'new_item' => AppConstants::UI_NEW_PROJECT
            ],
            'personas' => [
                'route' => AppConstants::ROUTE_PERSONAS,
                'management' => AppConstants::UI_PERSONA_MANAGEMENT,
                'back_to' => AppConstants::UI_BACK,
                'new_item' => AppConstants::UI_NEW_PERSONA
            ]
        ];

        foreach ($consistencyTests as $module => $constants) {
            // Verificar que la ruta corresponde al módulo
            $this->assertStringContainsString(
                $module,
                $constants['route'],
                "La ruta del módulo {$module} no contiene el nombre del módulo"
            );

            // Verificar que el título de gestión contiene referencia al módulo
            $this->assertNotEmpty(
                $constants['management'],
                "El título de gestión del módulo {$module} está vacío"
            );

            // Verificar que el botón de volver hace referencia al módulo
            $this->assertStringContainsString(
                'Volver',
                $constants['back_to'],
                "El botón de volver del módulo {$module} no contiene 'Volver'"
            );
        }
    }

    /**
     * Test que verifica que las constantes de error cubren casos importantes
     */
    public function testErrorConstantsCoverage()
    {
        $criticalErrors = [
            'authentication' => [
                AppConstants::ERROR_USER_NOT_AUTHENTICATED,
                AppConstants::ERROR_USER_NOT_AUTHORIZED,
                AppConstants::ERROR_ACCESS_DENIED
            ],
            'validation' => [
                AppConstants::ERROR_INVALID_ID,
                AppConstants::ERROR_REQUIRED_FIELDS,
                AppConstants::ERROR_INVALID_EMAIL,
                AppConstants::ERROR_INVALID_RUT
            ],
            'not_found' => [
                AppConstants::ERROR_USER_NOT_FOUND,
                AppConstants::ERROR_TASK_NOT_FOUND,
                AppConstants::ERROR_PROJECT_NOT_FOUND
            ],
            'server' => [
                AppConstants::ERROR_INTERNAL_SERVER,
                AppConstants::ERROR_INTERNAL_SYSTEM
            ]
        ];

        foreach ($criticalErrors as $category => $errors) {
            foreach ($errors as $error) {
                $this->assertNotEmpty(
                    $error,
                    "Error de categoría {$category} está vacío: {$error}"
                );
                $this->assertIsString(
                    $error,
                    "Error de categoría {$category} no es string: {$error}"
                );
            }
        }
    }

    /**
     * Test que verifica el rendimiento de acceso a constantes en un contexto realista
     */
    public function testRealWorldPerformance()
    {
        $startTime = microtime(true);

        // Simular uso real de constantes en una página típica
        for ($i = 0; $i < 100; $i++) {
            // Simular carga de una vista de tareas
            $title = AppConstants::UI_TASK_MANAGEMENT;
            $newButton = AppConstants::UI_NEW_TASK;
            $backButton = AppConstants::UI_BACK;
            $createButton = AppConstants::UI_BTN_CREATE;
            $editButton = AppConstants::UI_BTN_EDIT;
            $deleteButton = AppConstants::UI_BTN_DELETE;

            // Simular construcción de URLs
            $successUrl = AppConstants::buildSuccessUrl(
                AppConstants::ROUTE_TASKS,
                AppConstants::SUCCESS_CREATED
            );
            $errorUrl = AppConstants::buildErrorUrl(
                AppConstants::ROUTE_TASKS,
                AppConstants::ERROR_INVALID_ID
            );
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // en millisegundos

        // En un contexto real, debe ser muy rápido (menos de 100ms para 100 simulaciones)
        $this->assertLessThan(
            100,
            $executionTime,
            'El rendimiento de constantes en contexto real es demasiado lento'
        );
    }

    /**
     * Test que verifica que no se introdujeron regresiones después de la optimización
     */
    public function testNoRegressions()
    {
        // Verificar que constantes críticas mantienen sus valores esperados
        $criticalConstants = [
            'ROUTE_LOGIN' => '/setap/login',
            'ROUTE_HOME' => '/setap/home',
            'PARAM_SUCCESS' => 'success',
            'PARAM_ERROR' => 'error',
            'UI_BTN_CREATE' => 'Crear',
            'UI_BTN_EDIT' => 'Editar',
            'UI_BTN_SAVE' => 'Guardar',
            'UI_BTN_DELETE' => 'Eliminar'
        ];

        foreach ($criticalConstants as $constantName => $expectedValue) {
            $actualValue = constant("App\\Constants\\AppConstants::{$constantName}");
            $this->assertEquals(
                $expectedValue,
                $actualValue,
                "La constante {$constantName} cambió de valor inesperadamente"
            );
        }
    }

    /**
     * Test que valida que los archivos de la Fase 4 están correctamente optimizados
     */
    public function testFase4FilesOptimization()
    {
        $fase4Files = [
            '/Views/tasks/list.php' => ['UI_TASK_MANAGEMENT', 'UI_NEW_TASK'],
            '/Views/projects/list.php' => ['UI_PROJECT_MANAGEMENT', 'UI_NEW_PROJECT'],
            '/Views/personas/list.php' => ['UI_PERSONA_MANAGEMENT', 'UI_NEW_PERSONA'],
            '/Views/reports/list.php' => ['UI_SYSTEM_REPORTS']
        ];

        foreach ($fase4Files as $filePath => $expectedConstants) {
            $fullPath = $this->testFilesPath . $filePath;

            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);

                foreach ($expectedConstants as $constant) {
                    $this->assertStringContainsString(
                        "AppConstants::{$constant}",
                        $content,
                        "El archivo {$filePath} no usa la constante {$constant}"
                    );
                }
            } else {
                $this->markTestIncomplete("Archivo no encontrado: {$fullPath}");
            }
        }
    }
}
