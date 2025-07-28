<?php

namespace GesPrender\Communication;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Exception;

/**
 * Manager de módulos para auto-descubrimiento y carga
 * 
 * Busca automáticamente en Backoffice/src/Modules/ los módulos que implementen
 * ModuleCommunicationInterface y los registra en el sistema.
 */
class ModuleManager
{
    private ModuleServiceRegistry $serviceRegistry;
    private ModuleEventDispatcher $eventDispatcher;
    private ModuleHookSystem $hookSystem;
    private array $loadedModules = [];
    private bool $enableLogging = false;
    
    public function __construct(
        ModuleServiceRegistry $serviceRegistry,
        ModuleEventDispatcher $eventDispatcher,
        ModuleHookSystem $hookSystem,
        bool $enableLogging = false
    ) {
        $this->serviceRegistry = $serviceRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->hookSystem = $hookSystem;
        $this->enableLogging = $enableLogging;
    }
    
    /**
     * Auto-descubre y carga todos los módulos disponibles
     */
    public function discoverAndLoadModules(): array
    {
        $modulesPath = $this->getModulesPath();
        
        if (!is_dir($modulesPath)) {
            $this->log("Modules path not found: {$modulesPath}");
            return [];
        }
        
        $discoveredModules = $this->discoverModules($modulesPath);
        $loadOrder = $this->resolveDependencies($discoveredModules);
        
        $this->log("Found " . count($discoveredModules) . " modules, loading in order: " . implode(', ', $loadOrder));
        
        foreach ($loadOrder as $moduleName) {
            $this->loadModule($moduleName, $discoveredModules[$moduleName]);
        }
        
        return $this->loadedModules;
    }
    
    /**
     * Carga módulos específicos desde configuración
     */
    public function loadModules(array $moduleConfig): void
    {
        $loadOrder = $this->resolveDependencies($moduleConfig);
        
        foreach ($loadOrder as $moduleName) {
            $config = $moduleConfig[$moduleName];
            
            if (!$config['enabled']) {
                continue;
            }
            
            $this->loadModuleFromConfig($moduleName, $config);
        }
    }
    
    /**
     * Descubre módulos automáticamente (optimizado para memoria)
     */
    private function discoverModules(string $modulesPath, int $maxDepth = 5): array
    {
        $modules = [];
        $fileCount = 0;
        $maxFiles = 5000; // Límite de archivos a procesar
        $startMemory = memory_get_usage(true);
        
        if (!is_dir($modulesPath)) {
            $this->log("Modules path not found: {$modulesPath}");
            return [];
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($modulesPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            // PROTECCIÓN: Limitar profundidad
            $iterator->setMaxDepth($maxDepth);
            
            foreach ($iterator as $file) {
                // PROTECCIÓN: Limitar archivos procesados
                if (++$fileCount > $maxFiles) {
                    $this->log("Module discovery truncated due to file limit: {$modulesPath}, files processed: {$fileCount}, modules found: " . count($modules));
                    break;
                }
                
                // PROTECCIÓN: Monitorear memoria cada 500 archivos
                if ($fileCount % 500 === 0) {
                    $currentMemory = memory_get_usage(true);
                    $memoryIncrease = $currentMemory - $startMemory;
                    
                    if ($memoryIncrease > 30 * 1024 * 1024) { // 30MB
                        $this->log("Module discovery stopped due to high memory usage: {$modulesPath}, files processed: {$fileCount}, memory increase: " . number_format($memoryIncrease / 1024 / 1024, 2) . 'MB');
                        break;
                    }
                }
                
                if ($file->isFile() && $file->getFilename() === 'ModuleCommunication.php' && 
                    strpos($file->getPath(), '/Infrastructure/Communication') !== false) {
                    
                    // Extraer nombre del módulo desde la ruta
                    $pathParts = explode('/', $file->getPath());
                    $moduleIndex = array_search('Modules', $pathParts);
                    $moduleName = $moduleIndex !== false ? $pathParts[$moduleIndex + 1] : basename(dirname($file->getPath(), 2));
                    
                    // Construir namespace para Infrastructure/Communication
                    $namespace = 'Backoffice\\Modules\\' . $moduleName . '\\Infrastructure\\Communication\\ModuleCommunication';
                
                    // Path del módulo es el directorio padre de Infrastructure
                    $modulePath = dirname(dirname($file->getPath()));
                    
                    $modules[$moduleName] = [
                        'name' => $moduleName,
                        'path' => $modulePath,
                        'namespace' => $namespace,
                        'file' => $file->getPathname(),
                        'enabled' => true,
                        'dependencies' => [] // Se resolverán al cargar
                    ];
                    
                    $this->log("Discovered module: {$moduleName} at {$file->getPath()}");
                }
            }
        } catch (Exception $e) {
            $this->logError("Error discovering modules: " . $e->getMessage());
        }
        
        return $modules;
    }
    
    /**
     * Carga un módulo específico
     */
    private function loadModule(string $moduleName, array $moduleInfo): void
    {
        try {
            // Incluir el archivo del módulo
            if (!class_exists($moduleInfo['namespace'])) {
                require_once $moduleInfo['file'];
            }
            
            // Crear instancia del módulo
            $moduleClass = $moduleInfo['namespace'];
            
            if (!class_exists($moduleClass)) {
                throw new Exception("Module class not found: {$moduleClass}");
            }
            
            $module = new $moduleClass();
            
            if (!$module instanceof ModuleCommunicationInterface) {
                throw new Exception("Module {$moduleName} does not implement ModuleCommunicationInterface");
            }
            
            // Registrar el módulo
            $this->registerModule($moduleName, $module);
            
            $this->log("Successfully loaded module: {$moduleName}");
            
        } catch (Exception $e) {
            $this->logError("Failed to load module {$moduleName}: " . $e->getMessage());
        }
    }
    
    /**
     * Carga módulo desde configuración específica
     */
    private function loadModuleFromConfig(string $moduleName, array $config): void
    {
        try {
            $module = new $config['class'];
            
            if (!$module instanceof ModuleCommunicationInterface) {
                throw new Exception("Module {$moduleName} does not implement ModuleCommunicationInterface");
            }
            
            $this->registerModule($moduleName, $module);
            
        } catch (Exception $e) {
            $this->logError("Failed to load module {$moduleName} from config: " . $e->getMessage());
        }
    }
    
    /**
     * Registra un módulo en el sistema
     */
    private function registerModule(string $moduleName, ModuleCommunicationInterface $module): void
    {
        // Registrar servicios
        $module->registerServices($this->serviceRegistry);
        
        // Registrar event listeners
        $module->registerEventListeners($this->eventDispatcher);
        
        // Registrar hooks
        $module->registerHooks($this->hookSystem);
        
        // Inicializar módulo
        $module->boot();
        
        // Guardar información del módulo
        $this->loadedModules[$moduleName] = [
            'instance' => $module,
            'info' => $module->getModuleInfo(),
            'loaded_at' => date('Y-m-d H:i:s')
        ];
        
        $this->log("Module {$moduleName} registered successfully");
    }
    
    /**
     * Resuelve dependencias entre módulos
     */
    private function resolveDependencies(array $modules): array
    {
        $resolved = [];
        $unresolved = [];
        
        foreach ($modules as $name => $config) {
            $this->resolveDependency($name, $modules, $resolved, $unresolved);
        }
        
        return $resolved;
    }
    
    /**
     * Resuelve dependencia específica (algoritmo topológico)
     */
    private function resolveDependency(string $name, array $modules, array &$resolved, array &$unresolved): void
    {
        if (in_array($name, $resolved)) {
            return;
        }
        
        if (in_array($name, $unresolved)) {
            throw new Exception("Circular dependency detected: {$name}");
        }
        
        $unresolved[] = $name;
        
        $dependencies = $modules[$name]['dependencies'] ?? [];
        
        foreach ($dependencies as $dependency) {
            if (isset($modules[$dependency])) {
                $this->resolveDependency($dependency, $modules, $resolved, $unresolved);
            }
        }
        
        $resolved[] = $name;
        $unresolved = array_diff($unresolved, [$name]);
    }
    
    /**
     * Obtiene información de todos los módulos cargados
     */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }
    
    /**
     * Obtiene información de un módulo específico
     */
    public function getModuleInfo(string $moduleName): ?array
    {
        return $this->loadedModules[$moduleName] ?? null;
    }
    
    /**
     * Verifica si un módulo está cargado
     */
    public function isModuleLoaded(string $moduleName): bool
    {
        return isset($this->loadedModules[$moduleName]);
    }
    
    /**
     * Habilita un módulo
     */
    public function enableModule(string $moduleName): bool
    {
        // TODO: Implementar habilitación dinámica de módulos
        return false;
    }
    
    /**
     * Deshabilita un módulo
     */
    public function disableModule(string $moduleName): bool
    {
        if (!$this->isModuleLoaded($moduleName)) {
            return false;
        }
        
        try {
            $moduleData = $this->loadedModules[$moduleName];
            $module = $moduleData['instance'];
            
            // Llamar shutdown del módulo
            $module->shutdown();
            
            // Remover servicios del registry
            $this->serviceRegistry->removeModuleServices($moduleName);
            
            // TODO: Remover event listeners y hooks
            
            unset($this->loadedModules[$moduleName]);
            
            $this->log("Module {$moduleName} disabled successfully");
            return true;
            
        } catch (Exception $e) {
            $this->logError("Failed to disable module {$moduleName}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene estadísticas del manager
     */
    public function getStats(): array
    {
        $moduleStats = [];
        
        foreach ($this->loadedModules as $name => $data) {
            $info = $data['info'];
            $moduleStats[$name] = [
                'services_count' => count($info['services_exposed'] ?? []),
                'events_count' => count($info['events_dispatched'] ?? []),
                'hooks_count' => count($info['hooks_provided'] ?? []),
                'dependencies' => $info['dependencies'] ?? [],
                'loaded_at' => $data['loaded_at']
            ];
        }
        
        return [
            'total_modules' => count($this->loadedModules),
            'modules' => $moduleStats
        ];
    }
    
    /**
     * Obtiene la ruta base de módulos
     */
    private function getModulesPath(): string
    {
        // Buscar desde la raíz del proyecto
        $basePath = dirname(__DIR__, 2); // desde src/Communication/ subir 2 niveles
        return $basePath . '/Backoffice/src/Modules';
    }
    
    /**
     * Log genérico
     */
    private function log(string $message): void
    {
        if (!$this->enableLogging) {
            return;
        }
        
        if (defined('MODE') && MODE === 'dev') {
            error_log("[ModuleManager] " . $message);
        }
    }
    
    /**
     * Log de errores
     */
    private function logError(string $message): void
    {
        if (!$this->enableLogging) {
            return;
        }
        
        if (class_exists('Core\Classes\Logger')) {
            \Core\Classes\Logger::error('ModuleManager', $message);
        } else {
            error_log("[ModuleManager ERROR] " . $message);
        }
    }
} 