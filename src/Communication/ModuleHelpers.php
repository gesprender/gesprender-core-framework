<?php

/**
 * Helpers globales para comunicación entre módulos
 * 
 * Funciones de conveniencia que permiten usar el sistema de comunicación
 * entre módulos de forma sencilla desde cualquier parte del código.
 */

use GesPrender\Communication\ModuleEventDispatcher;
use GesPrender\Communication\ModuleServiceRegistry;
use GesPrender\Communication\ModuleHookSystem;
use GesPrender\Events\ModuleEvent;

/**
 * Referencia global al dispatcher de eventos
 */
global $moduleEventDispatcher, $moduleServiceRegistry, $moduleHookSystem;

if (!function_exists('dispatch')) {
    /**
     * Despacha un evento de módulo
     * 
     * @param ModuleEvent $event El evento a despachar
     * @return ModuleEvent El evento procesado
     */
    function dispatch(ModuleEvent $event): ModuleEvent
    {
        global $moduleEventDispatcher;
        
        if (!$moduleEventDispatcher instanceof ModuleEventDispatcher) {
            $moduleEventDispatcher = new ModuleEventDispatcher();
        }
        
        return $moduleEventDispatcher->dispatch($event);
    }
}

if (!function_exists('listen')) {
    /**
     * Registra un listener para un evento
     * 
     * @param string $eventClass Clase del evento a escuchar
     * @param callable $listener Función a ejecutar
     * @param int $priority Prioridad (mayor = se ejecuta antes)
     */
    function listen(string $eventClass, callable $listener, int $priority = 0): void
    {
        global $moduleEventDispatcher;
        
        if (!$moduleEventDispatcher instanceof ModuleEventDispatcher) {
            $moduleEventDispatcher = new ModuleEventDispatcher();
        }
        
        $moduleEventDispatcher->listen($eventClass, $listener, $priority);
    }
}

if (!function_exists('add_action')) {
    /**
     * Registra una acción (hook)
     * 
     * @param string $hook Nombre del hook
     * @param callable $callback Función a ejecutar
     * @param int $priority Prioridad (menor = mayor prioridad)
     */
    function add_action(string $hook, callable $callback, int $priority = 10): void
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            $moduleHookSystem = new ModuleHookSystem();
        }
        
        $moduleHookSystem->addAction($hook, $callback, $priority);
    }
}

if (!function_exists('do_action')) {
    /**
     * Ejecuta todas las acciones registradas para un hook
     * 
     * @param string $hook Nombre del hook
     * @param mixed ...$args Argumentos a pasar a las acciones
     */
    function do_action(string $hook, ...$args): void
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            $moduleHookSystem = new ModuleHookSystem();
        }
        
        $moduleHookSystem->doAction($hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    /**
     * Registra un filtro
     * 
     * @param string $filter Nombre del filtro
     * @param callable $callback Función a ejecutar
     * @param int $priority Prioridad (menor = mayor prioridad)
     */
    function add_filter(string $filter, callable $callback, int $priority = 10): void
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            $moduleHookSystem = new ModuleHookSystem();
        }
        
        $moduleHookSystem->addFilter($filter, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Aplica todos los filtros registrados a un valor
     * 
     * @param string $filter Nombre del filtro
     * @param mixed $value Valor a filtrar
     * @param mixed ...$args Argumentos adicionales
     * @return mixed Valor filtrado
     */
    function apply_filters(string $filter, $value, ...$args)
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            $moduleHookSystem = new ModuleHookSystem();
        }
        
        return $moduleHookSystem->applyFilters($filter, $value, ...$args);
    }
}

if (!function_exists('module_service')) {
    /**
     * Obtiene un servicio registrado por un módulo
     * 
     * @param string $serviceName Nombre del servicio
     * @return object|null El servicio o null si no existe
     */
    function module_service(string $serviceName): ?object
    {
        global $moduleServiceRegistry;
        
        if (!$moduleServiceRegistry instanceof ModuleServiceRegistry) {
            $moduleServiceRegistry = new ModuleServiceRegistry();
        }
        
        try {
            return $moduleServiceRegistry->get($serviceName);
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('has_service')) {
    /**
     * Verifica si un servicio está registrado
     * 
     * @param string $serviceName Nombre del servicio
     * @return bool True si existe, false si no
     */
    function has_service(string $serviceName): bool
    {
        global $moduleServiceRegistry;
        
        if (!$moduleServiceRegistry instanceof ModuleServiceRegistry) {
            $moduleServiceRegistry = new ModuleServiceRegistry();
        }
        
        return $moduleServiceRegistry->has($serviceName);
    }
}

if (!function_exists('register_service')) {
    /**
     * Registra un servicio en el registry
     * 
     * @param string $serviceName Nombre del servicio
     * @param string $moduleOrigin Módulo que registra el servicio
     * @param callable $factory Factory function
     * @param bool $singleton Si es singleton o no
     */
    function register_service(string $serviceName, string $moduleOrigin, callable $factory, bool $singleton = true): void
    {
        global $moduleServiceRegistry;
        
        if (!$moduleServiceRegistry instanceof ModuleServiceRegistry) {
            $moduleServiceRegistry = new ModuleServiceRegistry();
        }
        
        $moduleServiceRegistry->register($serviceName, $moduleOrigin, $factory, $singleton);
    }
}

if (!function_exists('remove_action')) {
    /**
     * Remueve una acción específica
     * 
     * @param string $hook Nombre del hook
     * @param callable $callback Callback a remover
     * @return bool True si se removió, false si no
     */
    function remove_action(string $hook, callable $callback): bool
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            return false;
        }
        
        return $moduleHookSystem->removeAction($hook, $callback);
    }
}

if (!function_exists('remove_filter')) {
    /**
     * Remueve un filtro específico
     * 
     * @param string $filter Nombre del filtro
     * @param callable $callback Callback a remover
     * @return bool True si se removió, false si no
     */
    function remove_filter(string $filter, callable $callback): bool
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            return false;
        }
        
        return $moduleHookSystem->removeFilter($filter, $callback);
    }
}

if (!function_exists('has_action')) {
    /**
     * Verifica si hay acciones registradas para un hook
     * 
     * @param string $hook Nombre del hook
     * @return bool True si hay acciones, false si no
     */
    function has_action(string $hook): bool
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            return false;
        }
        
        return $moduleHookSystem->hasActions($hook);
    }
}

if (!function_exists('has_filter')) {
    /**
     * Verifica si hay filtros registrados
     * 
     * @param string $filter Nombre del filtro
     * @return bool True si hay filtros, false si no
     */
    function has_filter(string $filter): bool
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            return false;
        }
        
        return $moduleHookSystem->hasFilters($filter);
    }
}

if (!function_exists('get_module_communication_stats')) {
    /**
     * Obtiene estadísticas del sistema de comunicación
     * 
     * @return array Estadísticas completas
     */
    function get_module_communication_stats(): array
    {
        global $moduleEventDispatcher, $moduleServiceRegistry, $moduleHookSystem;
        
        $stats = [
            'events' => [],
            'services' => [],
            'hooks' => []
        ];
        
        if ($moduleEventDispatcher instanceof ModuleEventDispatcher) {
            $stats['events'] = $moduleEventDispatcher->getStats();
        }
        
        if ($moduleServiceRegistry instanceof ModuleServiceRegistry) {
            $stats['services'] = $moduleServiceRegistry->getStats();
        }
        
        if ($moduleHookSystem instanceof ModuleHookSystem) {
            $stats['hooks'] = $moduleHookSystem->getStats();
        }
        
        return $stats;
    }
}

if (!function_exists('init_module_communication')) {
    /**
     * Inicializa el sistema de comunicación entre módulos
     * 
     * @param bool $enableLogging Si habilitar logging
     */
    function init_module_communication(bool $enableLogging = false): void
    {
        global $moduleEventDispatcher, $moduleServiceRegistry, $moduleHookSystem;
        
        if (!$moduleEventDispatcher instanceof ModuleEventDispatcher) {
            $moduleEventDispatcher = new ModuleEventDispatcher($enableLogging);
        }
        
        if (!$moduleServiceRegistry instanceof ModuleServiceRegistry) {
            $moduleServiceRegistry = new ModuleServiceRegistry();
        }
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            $moduleHookSystem = new ModuleHookSystem($enableLogging);
        }
    }
}

if (!function_exists('get_module_event_dispatcher')) {
    /**
     * Obtiene el dispatcher de eventos global
     * 
     * @return ModuleEventDispatcher
     */
    function get_module_event_dispatcher(): ModuleEventDispatcher
    {
        global $moduleEventDispatcher;
        
        if (!$moduleEventDispatcher instanceof ModuleEventDispatcher) {
            $moduleEventDispatcher = new ModuleEventDispatcher();
        }
        
        return $moduleEventDispatcher;
    }
}

if (!function_exists('get_module_service_registry')) {
    /**
     * Obtiene el registry de servicios global
     * 
     * @return ModuleServiceRegistry
     */
    function get_module_service_registry(): ModuleServiceRegistry
    {
        global $moduleServiceRegistry;
        
        if (!$moduleServiceRegistry instanceof ModuleServiceRegistry) {
            $moduleServiceRegistry = new ModuleServiceRegistry();
        }
        
        return $moduleServiceRegistry;
    }
}

if (!function_exists('get_module_hook_system')) {
    /**
     * Obtiene el sistema de hooks global
     * 
     * @return ModuleHookSystem
     */
    function get_module_hook_system(): ModuleHookSystem
    {
        global $moduleHookSystem;
        
        if (!$moduleHookSystem instanceof ModuleHookSystem) {
            $moduleHookSystem = new ModuleHookSystem();
        }
        
        return $moduleHookSystem;
    }
} 