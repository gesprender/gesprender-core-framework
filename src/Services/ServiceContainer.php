<?php

declare(strict_types=1);

namespace Core\Services;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * ServiceContainer - Sistema de Dependency Injection para GesPrender Framework
 * 
 * Implementa un container básico de DI que soporta:
 * - Auto-wiring de constructores
 * - Singleton services
 * - Factory methods
 * - Service binding manual
 */
class ServiceContainer
{
    private static ?self $instance = null;
    private array $bindings = [];
    private array $instances = [];
    private array $singletons = [];
    
    // PROTECCIÓN CONTRA BUCLES DE DEPENDENCIAS
    private array $resolving = [];
    private int $maxResolutionDepth = 10;

    private function __construct()
    {
        // Private constructor for singleton
        $this->registerCoreServices();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registra los servicios core del framework
     */
    private function registerCoreServices(): void
    {
        // Logger services (ya existentes)
        $this->singleton(LoggerService::class, function() {
            return LoggerService::getInstance();
        });

        $this->singleton(LoggerServiceProvider::class, function() {
            return LoggerServiceProvider::getInstance();
        });

        $this->singleton(DebugService::class, function() {
            return DebugService::getInstance();
        });

        // Servicios que serán refactorizados
        $this->singleton('config', function() {
            return new ConfigService();
        });

        // Database service (NUEVO)
        $this->singleton('Core\Contracts\DatabaseConnectionInterface', function($container) {
            return new DatabaseService(
                $container->get('config'),
                $container->get(LoggerService::class)
            );
        });

        // Alias para fácil acceso
        $this->singleton('database', function($container) {
            return $container->get('Core\Contracts\DatabaseConnectionInterface');
        });

        // Request service (refactorizado)
        $this->singleton(RequestService::class, function($container) {
            return new RequestService(
                $container->get('config'),
                $container->get(LoggerService::class)
            );
        });

        // Helper service (refactorizado)
        $this->singleton(HelperService::class, function($container) {
            return new HelperService(
                $container->get(LoggerService::class),
                $container->get('config')
            );
        });
    }

    /**
     * Registra un binding en el container
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];

        if ($shared) {
            $this->singletons[$abstract] = true;
        }
    }

    /**
     * Registra un singleton
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Registra una instancia existente
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resuelve un servicio del container
     */
    public function make(string $abstract): mixed
    {
        // PROTECCIÓN: Detectar bucles de dependencias
        if (isset($this->resolving[$abstract])) {
            throw new InvalidArgumentException("Circular dependency detected for service: $abstract");
        }
        
        // PROTECCIÓN: Limitar profundidad de resolución
        if (count($this->resolving) >= $this->maxResolutionDepth) {
            throw new InvalidArgumentException("Maximum resolution depth exceeded. Possible circular dependency.");
        }

        // Si ya hay una instancia, devolverla
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Si es singleton y ya se resolvió, devolverlo
        if (isset($this->singletons[$abstract]) && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Marcar como resolviéndose
        $this->resolving[$abstract] = true;
        
        try {
            // Resolver el binding
            $concrete = $this->getConcrete($abstract);
            $instance = $this->build($concrete);

            // Si es singleton, guardarlo para futuras resoluciones
            if (isset($this->singletons[$abstract])) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
            
        } finally {
            // CRÍTICO: Siempre limpiar el flag de resolución
            unset($this->resolving[$abstract]);
        }
    }

    /**
     * Obtiene la implementación concreta
     */
    private function getConcrete(string $abstract): mixed
    {
        // Si no está registrado, asumir que es una clase
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * Construye una instancia resolviendo dependencias
     */
    private function build($concrete): mixed
    {
        // Si es un closure, ejecutarlo
        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        // Si es una string, es una clase - usar reflexión
        if (is_string($concrete)) {
            return $this->buildClass($concrete);
        }

        throw new InvalidArgumentException("Invalid concrete type for service resolution");
    }

    /**
     * Construye una clase usando auto-wiring
     */
    private function buildClass(string $className): mixed
    {
        try {
            $reflector = new ReflectionClass($className);

            if (!$reflector->isInstantiable()) {
                throw new InvalidArgumentException("Class $className is not instantiable");
            }

            $constructor = $reflector->getConstructor();

            // Si no hay constructor, crear instancia directamente
            if ($constructor === null) {
                return new $className;
            }

            // Resolver dependencias del constructor
            $dependencies = $this->resolveDependencies($constructor->getParameters());

            return $reflector->newInstanceArgs($dependencies);

        } catch (ReflectionException $e) {
            throw new InvalidArgumentException("Unable to build class $className: " . $e->getMessage());
        }
    }

    /**
     * Resuelve las dependencias de un constructor
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $this->resolveDependency($parameter);
            $dependencies[] = $dependency;
        }

        return $dependencies;
    }

    /**
     * Resuelve una dependencia específica
     */
    private function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        // Si no hay type hint, buscar valor por defecto
        if ($type === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new InvalidArgumentException("Unable to resolve parameter {$parameter->getName()}");
        }

        // Si es un tipo built-in (string, int, etc.), usar valor por defecto
        if ($type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new InvalidArgumentException("Unable to resolve built-in parameter {$parameter->getName()}");
        }

        // Resolver como clase
        $className = $type->getName();
        return $this->make($className);
    }

    /**
     * Verifica si un servicio está registrado
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Alias para make() - API más familiar
     */
    public function get(string $abstract): mixed
    {
        return $this->make($abstract);
    }

    /**
     * Registra múltiples servicios a la vez
     */
    public function registerServices(array $services): void
    {
        foreach ($services as $abstract => $concrete) {
            if (is_numeric($abstract)) {
                // Si no hay key, usar la clase como abstract
                $this->bind($concrete);
            } else {
                $this->bind($abstract, $concrete);
            }
        }
    }

    /**
     * Obtiene estadísticas del container
     */
    public function getStats(): array
    {
        return [
            'bindings' => count($this->bindings),
            'instances' => count($this->instances),
            'singletons' => count($this->singletons),
            'currently_resolving' => array_keys($this->resolving),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'services' => array_keys($this->bindings)
        ];
    }

    /**
     * Limpia el container (útil para testing)
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->singletons = [];
        $this->resolving = []; // Limpiar estado de resolución
        $this->registerCoreServices();
    }

    /**
     * Helper global para resolver servicios
     */
    public static function resolve(string $abstract): mixed
    {
        return self::getInstance()->make($abstract);
    }
}

/**
 * ConfigService temporal - será expandido en futuras iteraciones
 */
class ConfigService
{
    private array $config = [];

    public function __construct()
    {
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        // Cargar configuración desde $_ENV
        $this->config = [
            'database' => [
                'host' => $_ENV['DDBB_HOST'] ?? 'localhost',
                'user' => $_ENV['DDBB_USER'] ?? 'root',
                'password' => $_ENV['DDBB_PASSWORD'] ?? '',
                'name' => $_ENV['DDBB_DBNAME'] ?? 'test'
            ],
            'app' => [
                'mode' => $_ENV['MODE'] ?? 'dev',
                'multi_tenant' => $_ENV['MULTI_TENANT_MODE'] ?? 'false'
            ]
        ];
    }

    public function get(string $key, $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    public function all(): array
    {
        return $this->config;
    }
} 