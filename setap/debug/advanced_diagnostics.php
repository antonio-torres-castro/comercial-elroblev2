<?php
/**
 * Herramientas Avanzadas de Diagnóstico
 * Complementa las herramientas básicas de debug con funcionalidades adicionales
 * Autor: MiniMax Agent
 */

require_once 'config.php';

class AdvancedDiagnosticTools
{
    private $logger;
    
    public function __construct()
    {
        $this->logger = AdvancedLogger::getInstance();
    }
    
    /**
     * Ejecutar análisis completo de seguridad
     */
    public function runSecurityAudit()
    {
        $this->logger->info('Iniciando auditoría de seguridad');
        
        $audit = [
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => [],
            'score' => 0,
            'max_score' => 100
        ];
        
        // Verificar permisos de archivos
        $filePerms = $this->checkFilePermissions();
        $audit['checks']['file_permissions'] = $filePerms;
        $audit['score'] += $filePerms['score'];
        
        // Verificar configuración PHP
        $phpConfig = $this->checkPHPSecurityConfig();
        $audit['checks']['php_config'] = $phpConfig;
        $audit['score'] += $phpConfig['score'];
        
        // Verificar exposición de información
        $infoExposure = $this->checkInfoExposure();
        $audit['checks']['info_exposure'] = $infoExposure;
        $audit['score'] += $infoExposure['score'];
        
        // Verificar directorios sensibles
        $sensitiveDirs = $this->checkSensitiveDirectories();
        $audit['checks']['sensitive_directories'] = $sensitiveDirs;
        $audit['score'] += $sensitiveDirs['score'];
        
        // Calcular porcentaje de seguridad
        $audit['security_percentage'] = round(($audit['score'] / $audit['max_score']) * 100, 1);
        
        $this->logger->info('Auditoría de seguridad completada', ['score' => $audit['security_percentage']]);
        
        return $audit;
    }
    
    /**
     * Verificar permisos de archivos
     */
    private function checkFilePermissions()
    {
        $results = [
            'score' => 0,
            'max_score' => 25,
            'checks' => []
        ];
        
        $criticalFiles = [
            '../config/database.php' => 600, // Solo owner read/write
            '../.env' => 600,
            '../.htaccess' => 644,
            '../composer.json' => 644,
            '../vendor' => 755
        ];
        
        foreach ($criticalFiles as $file => $expectedPerms) {
            if (file_exists($file)) {
                $currentPerms = substr(sprintf('%o', fileperms($file)), -3);
                $isSecure = (int)$currentPerms <= $expectedPerms;
                
                $results['checks'][] = [
                    'file' => $file,
                    'current_permissions' => $currentPerms,
                    'expected_permissions' => $expectedPerms,
                    'secure' => $isSecure,
                    'status' => $isSecure ? 'OK' : 'WARNING'
                ];
                
                if ($isSecure) {
                    $results['score'] += 5;
                }
            } else {
                $results['checks'][] = [
                    'file' => $file,
                    'status' => 'MISSING',
                    'secure' => false
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Verificar configuración de seguridad de PHP
     */
    private function checkPHPSecurityConfig()
    {
        $results = [
            'score' => 0,
            'max_score' => 25,
            'checks' => []
        ];
        
        $checks = [
            'expose_php' => [
                'value' => ini_get('expose_php'),
                'expected' => '0',
                'secure' => ini_get('expose_php') === '0',
                'score' => 5
            ],
            'allow_url_fopen' => [
                'value' => ini_get('allow_url_fopen'),
                'expected' => '0',
                'secure' => ini_get('allow_url_fopen') === '0',
                'score' => 5
            ],
            'allow_url_include' => [
                'value' => ini_get('allow_url_include'),
                'expected' => '0',
                'secure' => ini_get('allow_url_include') === '0',
                'score' => 5
            ],
            'display_errors' => [
                'value' => ini_get('display_errors'),
                'expected' => '0',
                'secure' => ini_get('display_errors') === '0',
                'score' => 5
            ],
            'session.cookie_httponly' => [
                'value' => ini_get('session.cookie_httponly'),
                'expected' => '1',
                'secure' => ini_get('session.cookie_httponly') === '1',
                'score' => 5
            ]
        ];
        
        foreach ($checks as $setting => $check) {
            $results['checks'][] = [
                'setting' => $setting,
                'current_value' => $check['value'],
                'expected_value' => $check['expected'],
                'secure' => $check['secure'],
                'status' => $check['secure'] ? 'OK' : 'WARNING'
            ];
            
            if ($check['secure']) {
                $results['score'] += $check['score'];
            }
        }
        
        return $results;
    }
    
    /**
     * Verificar exposición de información
     */
    private function checkInfoExposure()
    {
        $results = [
            'score' => 0,
            'max_score' => 25,
            'checks' => []
        ];
        
        // Verificar headers de seguridad
        $securityHeaders = [
            'X-Frame-Options' => 'Debe estar presente para prevenir clickjacking',
            'X-XSS-Protection' => 'Protección contra XSS',
            'X-Content-Type-Options' => 'Prevenir MIME type sniffing',
            'Strict-Transport-Security' => 'Fuerza HTTPS'
        ];
        
        foreach ($securityHeaders as $header => $description) {
            $isPresent = $this->checkSecurityHeader($header);
            $results['checks'][] = [
                'header' => $header,
                'present' => $isPresent,
                'description' => $description,
                'status' => $isPresent ? 'OK' : 'WARNING'
            ];
            
            if ($isPresent) {
                $results['score'] += 6;
            }
        }
        
        return $results;
    }
    
    /**
     * Verificar si un header de seguridad está presente
     */
    private function checkSecurityHeader($headerName)
    {
        // En un entorno real, esto verificaría los headers HTTP
        // Por ahora retornamos false para indicar que no se puede verificar
        return false;
    }
    
    /**
     * Verificar directorios sensibles
     */
    private function checkSensitiveDirectories()
    {
        $results = [
            'score' => 0,
            'max_score' => 25,
            'checks' => []
        ];
        
        $sensitiveDirs = [
            '../.git' => 'Control de versiones',
            '../.svn' => 'Control de versiones',
            '../backup' => 'Respaldos',
            '../admin' => 'Panel administrativo',
            '../config' => 'Configuraciones',
            '../logs' => 'Logs del sistema'
        ];
        
        foreach ($sensitiveDirs as $dir => $description) {
            $exists = is_dir($dir);
            $isProtected = !$exists || $this->isDirectoryProtected($dir);
            
            $results['checks'][] = [
                'directory' => $dir,
                'exists' => $exists,
                'protected' => $isProtected,
                'description' => $description,
                'status' => $isProtected ? 'OK' : 'WARNING'
            ];
            
            if ($isProtected) {
                $results['score'] += 4;
            }
        }
        
        return $results;
    }
    
    /**
     * Verificar si un directorio está protegido
     */
    private function isDirectoryProtected($dir)
    {
        $htaccessFile = $dir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            return false;
        }
        
        $content = file_get_contents($htaccessFile);
        return strpos($content, 'deny from all') !== false || 
               strpos($content, 'Require all denied') !== false;
    }
    
    /**
     * Ejecutar análisis de rendimiento detallado
     */
    public function runPerformanceAnalysis()
    {
        $this->logger->info('Iniciando análisis de rendimiento');
        
        $analysis = [
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics' => [],
            'recommendations' => []
        ];
        
        // Análisis de memoria
        $memoryAnalysis = $this->analyzeMemoryUsage();
        $analysis['metrics']['memory'] = $memoryAnalysis;
        
        // Análisis de base de datos
        $dbAnalysis = $this->analyzeDatabasePerformance();
        $analysis['metrics']['database'] = $dbAnalysis;
        
        // Análisis de disco
        $diskAnalysis = $this->analyzeDiskUsage();
        $analysis['metrics']['disk'] = $diskAnalysis;
        
        // Generar recomendaciones
        $analysis['recommendations'] = $this->generatePerformanceRecommendations($analysis['metrics']);
        
        $this->logger->info('Análisis de rendimiento completado');
        
        return $analysis;
    }
    
    /**
     * Analizar uso de memoria
     */
    private function analyzeMemoryUsage()
    {
        $memory = [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'usage_percentage' => 0,
            'status' => 'OK'
        ];
        
        $limitBytes = $this->parseSize(ini_get('memory_limit'));
        $memory['usage_percentage'] = round(($memory['current_usage'] / $limitBytes) * 100, 1);
        
        if ($memory['usage_percentage'] > 80) {
            $memory['status'] = 'WARNING';
        } elseif ($memory['usage_percentage'] > 90) {
            $memory['status'] = 'CRITICAL';
        }
        
        return $memory;
    }
    
    /**
     * Analizar rendimiento de base de datos
     */
    private function analyzeDatabasePerformance()
    {
        $dbPerf = [
            'connection_time' => 0,
            'query_performance' => [],
            'table_stats' => [],
            'status' => 'OK'
        ];
        
        try {
            if (file_exists('../config/database.php')) {
                include_once '../config/database.php';
                if (defined('DB_HOST')) {
                    // Medir tiempo de conexión
                    $startTime = microtime(true);
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                    $connectionTime = (microtime(true) - $startTime) * 1000;
                    $dbPerf['connection_time'] = round($connectionTime, 2);
                    
                    // Analizar tablas críticas
                    $criticalTables = ['usuarios', 'clientes', 'proyectos', 'tareas'];
                    foreach ($criticalTables as $table) {
                        try {
                            $startTime = microtime(true);
                            $pdo->query("SELECT COUNT(*) FROM `$table`");
                            $queryTime = (microtime(true) - $startTime) * 1000;
                            
                            $dbPerf['query_performance'][] = [
                                'table' => $table,
                                'query_time_ms' => round($queryTime, 2),
                                'status' => $queryTime > 100 ? 'SLOW' : 'OK'
                            ];
                        } catch (Exception $e) {
                            // Tabla no existe o error
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $dbPerf['status'] = 'ERROR';
            $dbPerf['error'] = $e->getMessage();
        }
        
        return $dbPerf;
    }
    
    /**
     * Analizar uso de disco
     */
    private function analyzeDiskUsage()
    {
        $diskUsage = [
            'total_space' => 0,
            'free_space' => 0,
            'used_space' => 0,
            'usage_percentage' => 0,
            'status' => 'OK'
        ];
        
        if (function_exists('disk_total_space') && function_exists('disk_free_space')) {
            $diskUsage['total_space'] = disk_total_space(__DIR__);
            $diskUsage['free_space'] = disk_free_space(__DIR__);
            $diskUsage['used_space'] = $diskUsage['total_space'] - $diskUsage['free_space'];
            $diskUsage['usage_percentage'] = round(($diskUsage['used_space'] / $diskUsage['total_space']) * 100, 1);
            
            if ($diskUsage['usage_percentage'] > 90) {
                $diskUsage['status'] = 'CRITICAL';
            } elseif ($diskUsage['usage_percentage'] > 80) {
                $diskUsage['status'] = 'WARNING';
            }
        }
        
        return $diskUsage;
    }
    
    /**
     * Generar recomendaciones de rendimiento
     */
    private function generatePerformanceRecommendations($metrics)
    {
        $recommendations = [];
        
        // Recomendaciones de memoria
        if (isset($metrics['memory'])) {
            $mem = $metrics['memory'];
            if ($mem['usage_percentage'] > 80) {
                $recommendations[] = [
                    'category' => 'Memory',
                    'priority' => 'HIGH',
                    'recommendation' => 'Optimizar uso de memoria o aumentar memory_limit',
                    'impact' => 'Alto'
                ];
            }
        }
        
        // Recomendaciones de base de datos
        if (isset($metrics['database'])) {
            $db = $metrics['database'];
            if ($db['connection_time'] > 500) {
                $recommendations[] = [
                    'category' => 'Database',
                    'priority' => 'MEDIUM',
                    'recommendation' => 'Optimizar conexión a base de datos',
                    'impact' => 'Medio'
                ];
            }
            
            foreach ($db['query_performance'] as $query) {
                if ($query['status'] === 'SLOW') {
                    $recommendations[] = [
                        'category' => 'Database',
                        'priority' => 'HIGH',
                        'recommendation' => "Optimizar consulta en tabla {$query['table']}",
                        'impact' => 'Alto'
                    ];
                }
            }
        }
        
        // Recomendaciones de disco
        if (isset($metrics['disk'])) {
            $disk = $metrics['disk'];
            if ($disk['usage_percentage'] > 85) {
                $recommendations[] = [
                    'category' => 'Storage',
                    'priority' => 'HIGH',
                    'recommendation' => 'Liberar espacio en disco o expandir almacenamiento',
                    'impact' => 'Crítico'
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Analizar logs para detectar patrones de errores
     */
    public function analyzeErrorPatterns()
    {
        $this->logger->info('Iniciando análisis de patrones de error');
        
        $patterns = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error_frequencies' => [],
            'common_errors' => [],
            'recommendations' => []
        ];
        
        // Analizar log de errores PHP
        $phpErrors = $this->analyzePHPErrors();
        $patterns['error_frequencies']['php'] = $phpErrors;
        
        // Analizar logs de aplicación
        $appErrors = $this->analyzeApplicationErrors();
        $patterns['error_frequencies']['application'] = $appErrors;
        
        // Identificar errores más comunes
        $patterns['common_errors'] = $this->identifyCommonErrors();
        
        // Generar recomendaciones
        $patterns['recommendations'] = $this->generateErrorRecommendations($patterns);
        
        $this->logger->info('Análisis de patrones de error completado');
        
        return $patterns;
    }
    
    /**
     * Analizar errores de PHP
     */
    private function analyzePHPErrors()
    {
        $errorLog = ini_get('error_log');
        $errors = [];
        
        if ($errorLog && file_exists($errorLog)) {
            $lines = file($errorLog);
            $recentLines = array_slice($lines, -100); // Últimas 100 líneas
            
            foreach ($recentLines as $line) {
                if (preg_match('/\[(.*?)\] (.*)/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $message = $matches[2];
                    
                    $errors[] = [
                        'timestamp' => $timestamp,
                        'message' => trim($message),
                        'type' => $this->classifyError($message)
                    ];
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Clasificar tipo de error
     */
    private function classifyError($message)
    {
        if (stripos($message, 'fatal') !== false) {
            return 'FATAL';
        } elseif (stripos($message, 'warning') !== false) {
            return 'WARNING';
        } elseif (stripos($message, 'notice') !== false) {
            return 'NOTICE';
        } elseif (stripos($message, 'parse error') !== false) {
            return 'PARSE';
        } else {
            return 'UNKNOWN';
        }
    }
    
    /**
     * Analizar errores de aplicación
     */
    private function analyzeApplicationErrors()
    {
        $appLogFile = DEBUG_LOG_DIR . '/debug_system.log';
        $errors = [];
        
        if (file_exists($appLogFile)) {
            $lines = file($appLogFile);
            $recentLines = array_slice($lines, -50);
            
            foreach ($recentLines as $line) {
                if (strpos($line, '[ERROR]') !== false) {
                    $errors[] = trim($line);
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Identificar errores comunes
     */
    private function identifyCommonErrors()
    {
        $commonErrors = [];
        
        // Análisis básico de frecuencia
        $phpErrors = $this->analyzePHPErrors();
        $errorMessages = array_column($phpErrors, 'message');
        $messageCounts = array_count_values($errorMessages);
        
        // Obtener los 5 errores más frecuentes
        arsort($messageCounts);
        $topErrors = array_slice($messageCounts, 0, 5, true);
        
        foreach ($topErrors as $message => $count) {
            $commonErrors[] = [
                'message' => $message,
                'frequency' => $count,
                'percentage' => round(($count / count($errorMessages)) * 100, 1)
            ];
        }
        
        return $commonErrors;
    }
    
    /**
     * Generar recomendaciones basadas en errores
     */
    private function generateErrorRecommendations($patterns)
    {
        $recommendations = [];
        
        foreach ($patterns['common_errors'] as $error) {
            if ($error['frequency'] > 5) {
                $recommendations[] = [
                    'error' => $error['message'],
                    'frequency' => $error['frequency'],
                    'recommendation' => 'Este error es frecuente, requiere atención inmediata'
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Función auxiliar para convertir tamaños
     */
    private function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        return round($size);
    }
    
    /**
     * Generar reporte técnico completo
     */
    public function generateTechnicalReport()
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'system_info' => $this->getSystemInfo(),
            'security_audit' => $this->runSecurityAudit(),
            'performance_analysis' => $this->runPerformanceAnalysis(),
            'error_patterns' => $this->analyzeErrorPatterns(),
            'recommendations' => []
        ];
        
        // Consolidar recomendaciones
        $report['recommendations'] = array_merge(
            $report['security_audit']['recommendations'] ?? [],
            $report['performance_analysis']['recommendations'] ?? [],
            $report['error_patterns']['recommendations'] ?? []
        );
        
        return $report;
    }
    
    /**
     * Obtener información del sistema
     */
    private function getSystemInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => date_default_timezone_get(),
            'extensions' => get_loaded_extensions(),
            'disk_space' => $this->analyzeDiskUsage()
        ];
    }
}
?>