<?php

namespace GesPrender\Communication;

use Core\Classes\Logger;

/**
 * Sistema de Hooks para extensibilidad entre módulos
 * 
 * Permite que los módulos se "enganchen" a puntos específicos del código
 * para modificar comportamiento o ejecutar acciones adicionales.
 * Inspirado en el sistema de hooks de WordPress.
 */
class ModuleHookSystem
{
    private array $actions = [];
    private array $filters = [];
    private array $currentFilters = [];
    private bool $enableLogging = false;
    
    public function __construct(bool $enableLogging = false)
    {
        $this->enableLogging = $enableLogging;
    }
    
    /**
     * Registra una acción (hook) que se ejecutará en un punto específico
     */
    public function addAction(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->actions[$hookName])) {
            $this->actions[$hookName] = [];
        }
        
        $this->actions[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority,
            'registered_at' => microtime(true)
        ];
        
        // Ordenar por prioridad (menor número = mayor prioridad)
        usort($this->actions[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
        
        $this->log("Action registered: {$hookName} with priority {$priority}");
    }
    
    /**
     * Ejecuta todas las acciones registradas para un hook
     */
    public function doAction(string $hookName, ...$args): void
    {
        if (!isset($this->actions[$hookName])) {
            $this->log("No actions registered for hook: {$hookName}");
            return;
        }
        
        $actionsCount = count($this->actions[$hookName]);
        $this->log("Executing {$actionsCount} actions for hook: {$hookName}");
        
        foreach ($this->actions[$hookName] as $index => $action) {
            try {
                $startTime = microtime(true);
                call_user_func_array($action['callback'], $args);
                $executionTime = microtime(true) - $startTime;
                
                $this->log("Action {$index} executed for {$hookName} in " . round($executionTime * 1000, 2) . "ms");
                
            } catch (\Throwable $e) {
                $this->logError("Action failed for {$hookName}[{$index}]: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Registra un filtro que puede modificar un valor
     */
    public function addFilter(string $filterName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->filters[$filterName])) {
            $this->filters[$filterName] = [];
        }
        
        $this->filters[$filterName][] = [
            'callback' => $callback,
            'priority' => $priority,
            'registered_at' => microtime(true)
        ];
        
        // Ordenar por prioridad (menor número = mayor prioridad)
        usort($this->filters[$filterName], fn($a, $b) => $a['priority'] <=> $b['priority']);
        
        $this->log("Filter registered: {$filterName} with priority {$priority}");
    }
    
    /**
     * Aplica todos los filtros registrados a un valor
     */
    public function applyFilters(string $filterName, $value, ...$args)
    {
        if (!isset($this->filters[$filterName])) {
            $this->log("No filters registered for: {$filterName}");
            return $value;
        }
        
        $filtersCount = count($this->filters[$filterName]);
        $this->log("Applying {$filtersCount} filters to: {$filterName}");
        
        // Evitar recursión infinita
        if (in_array($filterName, $this->currentFilters)) {
            $this->logError("Recursive filter detected: {$filterName}");
            return $value;
        }
        
        $this->currentFilters[] = $filterName;
        $originalValue = $value;
        
        foreach ($this->filters[$filterName] as $index => $filter) {
            try {
                $startTime = microtime(true);
                $value = call_user_func_array($filter['callback'], array_merge([$value], $args));
                $executionTime = microtime(true) - $startTime;
                
                $this->log("Filter {$index} applied to {$filterName} in " . round($executionTime * 1000, 2) . "ms");
                
            } catch (\Throwable $e) {
                $this->logError("Filter failed for {$filterName}[{$index}]: " . $e->getMessage());
                // En caso de error, mantener el valor anterior
                $value = $originalValue;
            }
        }
        
        // Remover del stack de filtros actuales
        array_pop($this->currentFilters);
        
        return $value;
    }
    
    /**
     * Remueve una acción específica
     */
    public function removeAction(string $hookName, callable $callback): bool
    {
        if (!isset($this->actions[$hookName])) {
            return false;
        }
        
        foreach ($this->actions[$hookName] as $index => $action) {
            if ($action['callback'] === $callback) {
                unset($this->actions[$hookName][$index]);
                $this->actions[$hookName] = array_values($this->actions[$hookName]);
                $this->log("Action removed from hook: {$hookName}");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Remueve un filtro específico
     */
    public function removeFilter(string $filterName, callable $callback): bool
    {
        if (!isset($this->filters[$filterName])) {
            return false;
        }
        
        foreach ($this->filters[$filterName] as $index => $filter) {
            if ($filter['callback'] === $callback) {
                unset($this->filters[$filterName][$index]);
                $this->filters[$filterName] = array_values($this->filters[$filterName]);
                $this->log("Filter removed from: {$filterName}");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Remueve todas las acciones de un hook
     */
    public function removeAllActions(string $hookName): int
    {
        if (!isset($this->actions[$hookName])) {
            return 0;
        }
        
        $count = count($this->actions[$hookName]);
        unset($this->actions[$hookName]);
        $this->log("All actions removed from hook: {$hookName} ({$count} actions)");
        
        return $count;
    }
    
    /**
     * Remueve todos los filtros de un filter
     */
    public function removeAllFilters(string $filterName): int
    {
        if (!isset($this->filters[$filterName])) {
            return 0;
        }
        
        $count = count($this->filters[$filterName]);
        unset($this->filters[$filterName]);
        $this->log("All filters removed from: {$filterName} ({$count} filters)");
        
        return $count;
    }
    
    /**
     * Verifica si hay acciones registradas para un hook
     */
    public function hasActions(string $hookName): bool
    {
        return isset($this->actions[$hookName]) && count($this->actions[$hookName]) > 0;
    }
    
    /**
     * Verifica si hay filtros registrados
     */
    public function hasFilters(string $filterName): bool
    {
        return isset($this->filters[$filterName]) && count($this->filters[$filterName]) > 0;
    }
    
    /**
     * Obtiene todas las acciones registradas para un hook
     */
    public function getActions(string $hookName): array
    {
        return $this->actions[$hookName] ?? [];
    }
    
    /**
     * Obtiene todos los filtros registrados
     */
    public function getFilters(string $filterName): array
    {
        return $this->filters[$filterName] ?? [];
    }
    
    /**
     * Obtiene todos los hooks registrados
     */
    public function getRegisteredHooks(): array
    {
        return array_keys($this->actions);
    }
    
    /**
     * Obtiene todos los filtros registrados
     */
    public function getRegisteredFilters(): array
    {
        return array_keys($this->filters);
    }
    
    /**
     * Limpia todos los hooks y filtros
     */
    public function clear(): void
    {
        $this->actions = [];
        $this->filters = [];
        $this->currentFilters = [];
        $this->log("All hooks and filters cleared");
    }
    
    /**
     * Habilita/deshabilita logging
     */
    public function setLogging(bool $enable): void
    {
        $this->enableLogging = $enable;
    }
    
    /**
     * Obtiene estadísticas del sistema de hooks
     */
    public function getStats(): array
    {
        $totalActions = 0;
        $totalFilters = 0;
        
        foreach ($this->actions as $actions) {
            $totalActions += count($actions);
        }
        
        foreach ($this->filters as $filters) {
            $totalFilters += count($filters);
        }
        
        return [
            'total_hooks' => count($this->actions),
            'total_filters' => count($this->filters),
            'total_actions' => $totalActions,
            'total_filter_callbacks' => $totalFilters,
            'current_filter_stack' => $this->currentFilters,
            'hooks_list' => $this->getRegisteredHooks(),
            'filters_list' => $this->getRegisteredFilters()
        ];
    }
    
    /**
     * Debug: Obtiene información detallada de hooks y filtros
     */
    public function debug(): array
    {
        return [
            'actions' => $this->actions,
            'filters' => $this->filters,
            'current_filters' => $this->currentFilters,
            'stats' => $this->getStats()
        ];
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
            error_log("[ModuleHookSystem] " . $message);
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
        
        Logger::error('ModuleHookSystem', $message);
    }
} 