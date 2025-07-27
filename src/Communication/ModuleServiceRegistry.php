<?php

namespace GesPrender\Communication;

use Exception;
use ReflectionClass;
use ReflectionException;

/**
 * Excepción para servicios no encontrados
 */
class ServiceNotFoundException extends Exception
{
    public function __construct(string $serviceName)
    {
        parent::__construct("Service '{$serviceName}' not found in registry");
    }
}

/**
 * Registro de servicios para comunicación entre módulos
 * 
 * Permite que los módulos expongan servicios para que otros módulos
 * puedan usarlos sin conocer su implementación específica.
 */
class ModuleServiceRegistry
{
    private array $services = [];
    private array $instances = [];
    private array $singletons = [];
    
    /**
     * Registra un servicio en el registry
     */
    public function register(string $serviceName, string $moduleOrigin, callable $factory, bool $singleton = true): void
    {
        $this->services[$serviceName] = [
            'module' => $moduleOrigin,
            'factory' => $factory,
            'singleton' => $singleton,
            'registered_at' => date('Y-m-d H:i:s')
        ];
        
        // Si no es singleton, limpiar instancia existente
        if (!$singleton && isset($this->instances[$serviceName])) {
            unset($this->instances[$serviceName]);
        }
    }
    
    /**
     * Registra una clase como servicio con auto-resolución
     */
    public function registerClass(string $serviceName, string $className, string $moduleOrigin, bool $singleton = true): void
    {
        $this->register($serviceName, $moduleOrigin, function($registry) use ($className) {
            return $this->resolveClass($className);
        }, $singleton);
    }
    
    /**
     * Registra un servicio como singleton
     */
    public function singleton(string $serviceName, string $moduleOrigin, callable $factory): void
    {
        $this->register($serviceName, $moduleOrigin, $factory, true);
    }
    
    /**
     * Registra un servicio transient (nueva instancia cada vez)
     */
    public function transient(string $serviceName, string $moduleOrigin, callable $factory): void
    {
        $this->register($serviceName, $moduleOrigin, $factory, false);
    }
    
    /**
     * Obtiene un servicio del registry
     */
    public function get(string $serviceName): object
    {
        if (!$this->has($serviceName)) {
            throw new ServiceNotFoundException($serviceName);
        }
        
        $service = $this->services[$serviceName];
        
        // Si es singleton y ya existe instancia, devolverla
        if ($service['singleton'] && isset($this->instances[$serviceName])) {
            return $this->instances[$serviceName];
        }
        
        // Crear nueva instancia
        $instance = call_user_func($service['factory'], $this);
        
        // Guardar instancia si es singleton
        if ($service['singleton']) {
            $this->instances[$serviceName] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Verifica si un servicio está registrado
     */
    public function has(string $serviceName): bool
    {
        return isset($this->services[$serviceName]);
    }
    
    /**
     * Obtiene información de un servicio
     */
    public function getServiceInfo(string $serviceName): ?array
    {
        return $this->services[$serviceName] ?? null;
    }
    
    /**
     * Obtiene todos los servicios disponibles
     */
    public function getAvailableServices(): array
    {
        return array_keys($this->services);
    }
    
    /**
     * Obtiene servicios por módulo
     */
    public function getServicesByModule(string $module): array
    {
        $result = [];
        
        foreach ($this->services as $serviceName => $serviceInfo) {
            if ($serviceInfo['module'] === $module) {
                $result[$serviceName] = $serviceInfo;
            }
        }
        
        return $result;
    }
    
    /**
     * Remueve un servicio del registry
     */
    public function remove(string $serviceName): bool
    {
        if (!$this->has($serviceName)) {
            return false;
        }
        
        unset($this->services[$serviceName]);
        unset($this->instances[$serviceName]);
        
        return true;
    }
    
    /**
     * Remueve todos los servicios de un módulo
     */
    public function removeModuleServices(string $module): int
    {
        $removed = 0;
        
        foreach ($this->services as $serviceName => $serviceInfo) {
            if ($serviceInfo['module'] === $module) {
                $this->remove($serviceName);
                $removed++;
            }
        }
        
        return $removed;
    }
    
    /**
     * Limpia todas las instancias cacheadas
     */
    public function clearInstances(): void
    {
        $this->instances = [];
    }
    
    /**
     * Limpia todo el registry
     */
    public function clear(): void
    {
        $this->services = [];
        $this->instances = [];
        $this->singletons = [];
    }
    
    /**
     * Resuelve una clase con auto-wiring de dependencias
     */
    private function resolveClass(string $className): object
    {
        try {
            $reflector = new ReflectionClass($className);
            
            if (!$reflector->isInstantiable()) {
                throw new Exception("Class {$className} is not instantiable");
            }
            
            $constructor = $reflector->getConstructor();
            
            // Si no tiene constructor, crear instancia directamente
            if (is_null($constructor)) {
                return new $className;
            }
            
            // Resolver dependencias del constructor
            $dependencies = [];
            foreach ($constructor->getParameters() as $parameter) {
                $dependencies[] = $this->resolveDependency($parameter);
            }
            
            return $reflector->newInstanceArgs($dependencies);
            
        } catch (ReflectionException $e) {
            throw new Exception("Cannot resolve class {$className}: " . $e->getMessage());
        }
    }
    
    /**
     * Resuelve una dependencia específica
     */
    private function resolveDependency(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        
        // Si no tiene tipo, verificar si tiene valor por defecto
        if (is_null($type)) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw new Exception("Cannot resolve parameter {$parameter->getName()} without type hint");
        }
        
        $typeName = $type->getName();
        
        // Intentar resolver como servicio registrado
        if ($this->has($typeName)) {
            return $this->get($typeName);
        }
        
        // Intentar resolver como clase
        if (class_exists($typeName)) {
            return $this->resolveClass($typeName);
        }
        
        // Si tiene valor por defecto, usarlo
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        
        throw new Exception("Cannot resolve dependency {$typeName} for parameter {$parameter->getName()}");
    }
    
    /**
     * Obtiene estadísticas del registry
     */
    public function getStats(): array
    {
        $moduleStats = [];
        $singletonCount = 0;
        $instanceCount = count($this->instances);
        
        foreach ($this->services as $serviceName => $serviceInfo) {
            $module = $serviceInfo['module'];
            
            if (!isset($moduleStats[$module])) {
                $moduleStats[$module] = 0;
            }
            $moduleStats[$module]++;
            
            if ($serviceInfo['singleton']) {
                $singletonCount++;
            }
        }
        
        return [
            'total_services' => count($this->services),
            'total_modules' => count($moduleStats),
            'singleton_services' => $singletonCount,
            'transient_services' => count($this->services) - $singletonCount,
            'cached_instances' => $instanceCount,
            'modules' => $moduleStats
        ];
    }
    
    /**
     * Debug: Lista todos los servicios registrados
     */
    public function debug(): array
    {
        return [
            'services' => $this->services,
            'instances' => array_keys($this->instances),
            'stats' => $this->getStats()
        ];
    }
} 