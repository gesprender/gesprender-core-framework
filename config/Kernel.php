<?php

namespace Config;

use Core\Services\JsonResponse;
use Core\Services\LoggerServiceProvider;
use Core\Services\Request;
use Core\Services\Response;
use Core\Classes\Context;
use Dotenv\Dotenv;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final readonly class Kernel
{
    public function run(): void
    {
        Response::setHeaders();

        $this->getDotenv();
        # Initialize modern logging and debugging system
        $this->initializeLoggingSystem();
        
        if ($_ENV['MODE'] == 'prod') error_reporting(E_ALL & ~E_WARNING);
        
        if ($_ENV['MODE'] === 'dev') {
            error_log("Kernel loaded without sessions - using stateless authentication");
        }
        # Defines
        require  'defines.php';
        
        $this->leadFiles();
        $this->endpointNotFound();
        $this->Welcome();
    }

    private function getDotenv(): void
    {
        $loadCustomDefines = '../Backoffice/.env';
        if (file_exists($loadCustomDefines)) {
            $dotenv = Dotenv::createImmutable('./../Backoffice/');
            $dotenv->load();
        } else {
            $dotenv = Dotenv::createImmutable('./../');
            $dotenv->load();
        }
    }

    private function leadFiles()
    {
        # Load backoffice custom defines
        $loadCustomDefines = '../Backoffice/src/custom_defines.php';
        if (file_exists($loadCustomDefines)) {
            require $loadCustomDefines;
        }
        $backofficeRoutes = $this->scanBackofficeRoutes('..');
        # Load backoffice Endpoints
        $this->autoload_controllers('../Backoffice/src/Modules');
        $this->autoload_endpoints($backofficeRoutes);
    }

    private function autoload_controllers($directory): void
    {
        $modules = scandir($directory);
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') continue;

            $controllerPath = $directory . '/' . $module . '/Infrastructure/' . $module . 'Controller.php';
            if (file_exists($controllerPath)) {
                $controllerClass = 'Backoffice\\Modules\\' . $module . '\\Infrastructure\\' . $module . 'Controller';
                if (class_exists($controllerClass)) {
                    $controllerClass::endpoints();
                }
            }
        }
    }

    private function autoload_endpoints($directory): void
    {
        // Removido var_dump de debug
        if (empty($directory)) {
            error_log("No routes found to load");
            return;
        }
        foreach ($directory as $value) {
            try {
                // Verificar si el método HTTP es permitido
                $allowedMethods = $value['httpMethods'] ?? 'GET';
                $currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
                if (!empty($allowedMethods) && $allowedMethods !== $currentMethod) {
                    continue;
                }

                Request::Route($value['routes'], function () use ($value) {
                    $className = $value['class'];
                    $methodName = $value['method'] ?? 'run';
                    
                    if (!class_exists($className)) {
                        error_log("Class not found: $className");
                        return null;
                    }
                    
                    try {
                        // Usar ServiceContainer para auto-inyección de dependencias
                        $instance = \Core\Services\ServiceContainer::resolve($className);
                        
                        if (!method_exists($instance, $methodName)) {
                            error_log("Method $methodName not found in class $className");
                            return null;
                        }
                        
                        // Llamar método en la instancia
                        return call_user_func([$instance, $methodName]);
                        
                    } catch (\Throwable $e) {
                        error_log("Error resolving dependencies for $className: " . $e->getMessage());
                        return null;
                    }
                    
                }, $value['useMiddleware'] ?? false);
                
            } catch (\Throwable $e) {
                error_log("Error loading route {$value['routes']}: " . $e->getMessage());
            }
        }
    }

    private function endpointNotFound(): ?JsonResponse
    {
        if ($_REQUEST) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Route not found.'
            ], 404);
        }
        return null;
    }

    private function Welcome(): ?JsonResponse
    {
        return new JsonResponse([
            'status' => true,
            'message' => 'Welcom to Api.',
            'data' => []
        ], 200);
    }

    /**
     * Escanea rutas de Backoffice OPTIMIZADO PARA MEMORIA
     * Reemplaza RecursiveDirectoryIterator problemático
     */
    private function scanBackofficeRoutes($basePath): array
    {
        $startMemory = memory_get_usage(true);
        $modulesPath = "$basePath/Backoffice/src/Modules";
        $result = [];
        $filesProcessed = 0;
        $maxFiles = 1000; // Límite de archivos
        
        if (!is_dir($modulesPath)) {
            error_log("Modules directory not found: $modulesPath");
            return [];
        }

        try {
            // OPTIMIZADO: Escaneo manual por niveles en lugar de recursivo masivo
            $modules = scandir($modulesPath);
            
            foreach ($modules as $module) {
                if ($module === '.' || $module === '..') continue;
                
                $modulePath = "$modulesPath/$module";
                if (!is_dir($modulePath)) continue;
                
                // Buscar específicamente en Application directory
                $appPath = "$modulePath/Application";
                if (!is_dir($appPath)) continue;
                
                $appFiles = scandir($appPath);
                
                foreach ($appFiles as $file) {
                    if (++$filesProcessed > $maxFiles) {
                        error_log("Route scanning truncated: too many files ($maxFiles+)");
                        break 2;
                    }
                    
                    if (!str_ends_with($file, '.php')) continue;
                    
                    $filePath = "$appPath/$file";
                    
                    // PROTECCIÓN: Monitorear memoria cada 50 archivos
                    if ($filesProcessed % 50 === 0) {
                        $currentMemory = memory_get_usage(true);
                        $memoryIncrease = $currentMemory - $startMemory;
                        
                        if ($memoryIncrease > 50 * 1024 * 1024) { // 50MB
                            error_log("Route scanning stopped: high memory usage (" . round($memoryIncrease/1024/1024, 2) . "MB)");
                            break 2;
                        }
                    }
                    
                    // Procesar archivo para extraer rutas
                    $routes = $this->extractRoutesFromFile($filePath, $module);
                    if (!empty($routes)) {
                        $result = array_merge($result, $routes);
                    }
                    
                    // Yield control cada 10 archivos
                    if ($filesProcessed % 10 === 0) {
                        usleep(100); // 0.1ms pause
                    }
                }
            }
            
        } catch (\Throwable $e) {
            error_log("Error scanning routes: " . $e->getMessage());
            return [];
        }
        return $result;
    }

    /**
     * Extrae rutas de un archivo PHP de forma eficiente
     */
    private function extractRoutesFromFile(string $filePath, string $module): array
    {
        try {
            $content = file_get_contents($filePath);
            if ($content === false) return [];
            
            $routes = [];
            
            // Buscar comentarios de ruta (con o sin métodos HTTP)
            if (preg_match('/# \[Route\(\'([^\']+)\'[^\]]*\)\]/', $content, $matches)) {
                $routePath = $matches[1];
                
                // Extraer métodos HTTP si están definidos
                $httpMethods = 'GET'; // Default
                if (preg_match('/methods:\s*\'([^\']+)\'/', $content, $methodMatches)) {
                    $httpMethods = $methodMatches[1];
                }
                
                $className = $this->extractClassName($content, $module);
                
                if ($className) {
                    $routes[] = [
                        'routes' => $routePath,
                        'class' => $className,
                        'method' => $this->extractMethodName($content),
                        'useMiddleware' => strpos($content, '# useMiddleware') !== false,
                        'httpMethods' => $httpMethods
                    ];
                }
            }
            
            return $routes;
            
        } catch (\Throwable $e) {
            error_log("Error extracting routes from $filePath: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extrae nombre de clase de forma eficiente
     */
    private function extractClassName(string $content, string $module): ?string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $className = $matches[1];
            return "Backoffice\\Modules\\$module\\Application\\$className";
        }
        return null;
    }

    /**
     * Extrae nombre de método (por defecto 'run')
     */
    private function extractMethodName(string $content): string
    {
        // Por defecto usar 'run' o buscar el método público
        if (preg_match('/public function (\w+)\(\).*Route/', $content, $matches)) {
            return $matches[1];
        }
        return 'run';
    }

    /**
     * Inicializa el sistema moderno de logging y debugging
     */
    private function initializeLoggingSystem(): void
    {
        try {
            $loggerProvider = LoggerServiceProvider::getInstance();
            $loggerProvider->initialize();
            
            // Log de inicio del framework
            $logger = $loggerProvider->getLogger();
            $logger->info('GesPrender Framework initialized', [
                'version' => '0.0.1',
                'environment' => $_ENV['MODE'] ?? 'dev',
                'multi_tenant' => $_ENV['MULTI_TENANT_MODE'] ?? 'false',
                'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true)
            ]);
            
        } catch (\Throwable $e) {
            // Fallback silencioso si falla la inicialización
            error_log("Failed to initialize logging system: " . $e->getMessage());
        }
    }
}
