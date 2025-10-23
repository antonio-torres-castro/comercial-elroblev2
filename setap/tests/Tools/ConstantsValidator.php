<?php

/**
 * Script de Validación de Optimización de Constantes
 * 
 * Este script valida que los archivos optimizados no contengan strings hardcodeados
 * y que estén usando correctamente las constantes de AppConstants.php
 * 
 * @author MiniMax Agent
 * @date 2025-10-10
 */

namespace App\Tools;

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Constants\AppConstants;

class ConstantsValidator
{
    private $errors = [];
    private $warnings = [];
    private $validatedFiles = 0;
    private $basePath;
    
    // Strings que NO deberían aparecer hardcodeados en los archivos optimizados
    private $forbiddenStrings = [
        'Gestión de Tareas',
        'Gestión de Proyectos', 
        'Gestión de Personas',
        'Reportes del Sistema',
        'Nueva Tarea',
        'Nuevo Proyecto',
        'Nueva Persona',
        'Volver a Tareas',
        'Volver a Proyectos', 
        'Volver a Personas',
        'Crear Proyecto',
        'Editar Tarea',
        'Información Básica',
        'Información de la Tarea',
        'Búsqueda Avanzada'
    ];
    
    // Patrones de rutas hardcodeadas que deberían usar constantes
    private $forbiddenRoutePatterns = [
        '/header\(["\']Location:\s*\/tasks["\']/',
        '/header\(["\']Location:\s*\/users["\']/',
        '/header\(["\']Location:\s*\/projects["\']/',
        '/href=["\']\/tasks["\']/',
        '/href=["\']\/users["\']/',
        '/href=["\']\/projects["\']/'
    ];
    
    public function __construct()
    {
        $this->basePath = dirname(__DIR__, 2);
        
        if (!file_exists($this->basePath . '/src/App/Constants/AppConstants.php')) {
            throw new \Exception('AppConstants.php no encontrado en la ruta esperada');
        }
    }
    
    /**
     * Ejecuta la validación completa
     */
    public function validate(): array
    {
        echo "🔍 Iniciando validación de optimización de constantes...\n\n";
        
        // Validar archivos de vista
        $this->validateViewFiles();
        
        // Validar archivos de controlador
        $this->validateControllerFiles();
        
        // Validar que AppConstants.php contiene las constantes esperadas
        $this->validateAppConstants();
        
        // Generar reporte
        return $this->generateReport();
    }
    
    /**
     * Valida archivos de vista
     */
    private function validateViewFiles()
    {
        echo "📄 Validando archivos de vista...\n";
        
        $viewFiles = [
            '/src/App/Views/tasks/list.php',
            '/src/App/Views/tasks/create.php', 
            '/src/App/Views/tasks/edit.php',
            '/src/App/Views/tasks/form.php',
            '/src/App/Views/projects/list.php',
            '/src/App/Views/projects/create.php',
            '/src/App/Views/reports/list.php',
            '/src/App/Views/personas/list.php',
            '/src/App/Views/personas/create.php'
        ];
        
        foreach ($viewFiles as $file) {
            $this->validateFile($file, 'vista');
        }
    }
    
    /**
     * Valida archivos de controlador
     */
    private function validateControllerFiles()
    {
        echo "🎮 Validando archivos de controlador...\n";
        
        $controllerFiles = [
            '/src/App/Controllers/TaskController.php',
            '/src/App/Controllers/UserController.php',
            '/src/App/Controllers/ProjectController.php'
        ];
        
        foreach ($controllerFiles as $file) {
            $this->validateFile($file, 'controlador');
        }
    }
    
    /**
     * Valida un archivo específico
     */
    private function validateFile(string $filePath, string $type)
    {
        $fullPath = $this->basePath . $filePath;
        
        if (!file_exists($fullPath)) {
            $this->warnings[] = "⚠️  Archivo no encontrado: {$filePath}";
            return;
        }
        
        $content = file_get_contents($fullPath);
        $this->validatedFiles++;
        
        echo "  ✓ Validando: {$filePath}\n";
        
        // Validar uso de constantes
        $this->checkForAppConstantsUsage($filePath, $content, $type);
        
        // Validar strings forbidddos
        $this->checkForForbiddenStrings($filePath, $content);
        
        // Validar rutas hardcodeadas
        $this->checkForHardcodedRoutes($filePath, $content);
        
        // Validaciones específicas por tipo
        if ($type === 'vista') {
            $this->validateViewSpecific($filePath, $content);
        } elseif ($type === 'controlador') {
            $this->validateControllerSpecific($filePath, $content);
        }
    }
    
    /**
     * Verifica que el archivo use AppConstants correctamente
     */
    private function checkForAppConstantsUsage(string $filePath, string $content, string $type)
    {
        $usesAppConstants = strpos($content, 'AppConstants::') !== false;
        $hasUseStatement = strpos($content, 'use App\Constants\AppConstants') !== false;
        
        if ($usesAppConstants && !$hasUseStatement) {
            $this->errors[] = "❌ {$filePath}: Usa AppConstants pero no tiene la declaración 'use'";
        }
        
        if ($type === 'vista' && !$usesAppConstants) {
            // Para vistas, verificar si debería usar constantes basado en el contenido
            foreach ($this->forbiddenStrings as $string) {
                if (strpos($content, $string) !== false) {
                    $this->warnings[] = "⚠️  {$filePath}: Podría beneficiarse del uso de constantes";
                    break;
                }
            }
        }
    }
    
    /**
     * Verifica que no haya strings hardcodeados que deberían ser constantes
     */
    private function checkForForbiddenStrings(string $filePath, string $content)
    {
        foreach ($this->forbiddenStrings as $forbiddenString) {
            // Buscar el string tanto en comillas simples como dobles
            $patterns = [
                "/['\"]" . preg_quote($forbiddenString, '/') . "['\"]/",
                "/>" . preg_quote($forbiddenString, '/') . "</",
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $this->errors[] = "❌ {$filePath}: Contiene string hardcodeado: '{$forbiddenString}'";
                    break;
                }
            }
        }
    }
    
    /**
     * Verifica que no haya rutas hardcodeadas
     */
    private function checkForHardcodedRoutes(string $filePath, string $content)
    {
        foreach ($this->forbiddenRoutePatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $this->errors[] = "❌ {$filePath}: Contiene ruta hardcodeada: {$matches[0]}";
            }
        }
    }
    
    /**
     * Validaciones específicas para archivos de vista
     */
    private function validateViewSpecific(string $filePath, string $content)
    {
        // Verificar que use PHP tags correctamente para constantes
        if (strpos($content, 'AppConstants::') !== false) {
            if (strpos($content, '<?= AppConstants::') === false && 
                strpos($content, '<?php echo AppConstants::') === false) {
                $this->warnings[] = "⚠️  {$filePath}: Usa constantes pero podría mejorar la sintaxis de output";
            }
        }
        
        // Verificar estructura HTML básica para vistas principales
        if (strpos($filePath, 'list.php') !== false) {
            if (strpos($content, '<h1>') === false && strpos($content, '<h2>') === false) {
                $this->warnings[] = "⚠️  {$filePath}: Vista de lista sin título principal";
            }
        }
    }
    
    /**
     * Validaciones específicas para archivos de controlador
     */
    private function validateControllerSpecific(string $filePath, string $content)
    {
        // Verificar que use namespace correcto
        if (strpos($content, 'namespace App\Controllers') === false) {
            $this->warnings[] = "⚠️  {$filePath}: Podría necesitar namespace de controlador";
        }
        
        // Verificar uso de redirecciones con constantes
        if (preg_match('/header\(["\']Location:/', $content)) {
            if (strpos($content, 'AppConstants::buildSuccessUrl') === false &&
                strpos($content, 'AppConstants::buildErrorUrl') === false) {
                $this->warnings[] = "⚠️  {$filePath}: Usa redirecciones que podrían beneficiarse de métodos de utilidad";
            }
        }
    }
    
    /**
     * Valida que AppConstants.php contiene las constantes esperadas
     */
    private function validateAppConstants()
    {
        echo "🏗️  Validando AppConstants.php...\n";
        
        $reflection = new \ReflectionClass(AppConstants::class);
        $constants = $reflection->getConstants();
        
        // Constantes que deben existir después de la optimización
        $requiredConstants = [
            'UI_TASK_MANAGEMENT',
            'UI_PROJECT_MANAGEMENT',
            'UI_PERSONA_MANAGEMENT', 
            'UI_SYSTEM_REPORTS',
            'UI_NEW_TASK',
            'UI_NEW_PROJECT',
            'UI_NEW_PERSONA',
            'UI_BACK_TO_TASKS',
            'UI_BACK_TO_PROJECTS',
            'UI_BACK_TO_PERSONAS',
            'UI_CREATE_PROJECT_TITLE',
            'UI_EDIT_TASK_TITLE',
            'UI_BASIC_INFORMATION',
            'UI_TASK_INFORMATION'
        ];
        
        foreach ($requiredConstants as $constant) {
            if (!array_key_exists($constant, $constants)) {
                $this->errors[] = "❌ AppConstants.php: Falta la constante requerida: {$constant}";
            } else {
                echo "  ✓ Constante encontrada: {$constant}\n";
            }
        }
        
        // Verificar que los métodos de utilidad existen
        if (!method_exists(AppConstants::class, 'buildSuccessUrl')) {
            $this->errors[] = "❌ AppConstants.php: Falta el método buildSuccessUrl()";
        }
        
        if (!method_exists(AppConstants::class, 'buildErrorUrl')) {
            $this->errors[] = "❌ AppConstants.php: Falta el método buildErrorUrl()";
        }
        
        echo "  ✓ Total de constantes definidas: " . count($constants) . "\n";
    }
    
    /**
     * Genera el reporte final
     */
    private function generateReport(): array
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 REPORTE DE VALIDACIÓN DE CONSTANTES\n";
        echo str_repeat("=", 60) . "\n";
        
        $report = [
            'success' => empty($this->errors),
            'files_validated' => $this->validatedFiles,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'summary' => []
        ];
        
        // Estadísticas
        echo "📈 Estadísticas:\n";
        echo "  • Archivos validados: {$this->validatedFiles}\n";
        echo "  • Errores encontrados: " . count($this->errors) . "\n";
        echo "  • Advertencias: " . count($this->warnings) . "\n";
        
        $report['summary'] = [
            'files_validated' => $this->validatedFiles,
            'errors_count' => count($this->errors),
            'warnings_count' => count($this->warnings)
        ];
        
        // Mostrar errores
        if (!empty($this->errors)) {
            echo "\n🚨 ERRORES ENCONTRADOS:\n";
            foreach ($this->errors as $error) {
                echo "  {$error}\n";
            }
        }
        
        // Mostrar advertencias
        if (!empty($this->warnings)) {
            echo "\n⚠️  ADVERTENCIAS:\n";
            foreach ($this->warnings as $warning) {
                echo "  {$warning}\n";
            }
        }
        
        // Resultado final
        echo "\n" . str_repeat("-", 40) . "\n";
        if (empty($this->errors)) {
            echo "✅ VALIDACIÓN EXITOSA: Todos los archivos están correctamente optimizados\n";
        } else {
            echo "❌ VALIDACIÓN FALLIDA: Se encontraron errores que deben corregirse\n";
        }
        echo str_repeat("-", 40) . "\n";
        
        return $report;
    }
    
    /**
     * Método estático para ejecución directa
     */
    public static function run(): array
    {
        $validator = new self();
        return $validator->validate();
    }
}

// Ejecutar validación si el script se ejecuta directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        ConstantsValidator::run();
    } catch (Exception $e) {
        echo "❌ Error durante la validación: " . $e->getMessage() . "\n";
        exit(1);
    }
}