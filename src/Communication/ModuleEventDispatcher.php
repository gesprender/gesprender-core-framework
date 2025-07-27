<?php

namespace GesPrender\Communication;

use GesPrender\Events\ModuleEvent;
use Core\Classes\Logger;
use Exception;

/**
 * Dispatcher de eventos para comunicación entre módulos
 * 
 * Permite a los módulos registrar listeners para eventos y despachar eventos
 * de forma desacoplada. Compatible con PSR-14 Event Dispatcher.
 */
class ModuleEventDispatcher
{
    private array $listeners = [];
    private bool $async = false;
    private bool $enableLogging = true;
    
    public function __construct(bool $enableLogging = true)
    {
        $this->enableLogging = $enableLogging;
    }
    
    /**
     * Registra un listener para un evento específico
     */
    public function listen(string $eventClass, callable $listener, int $priority = 0): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }
        
        $this->listeners[$eventClass][] = [
            'callback' => $listener,
            'priority' => $priority
        ];
        
        // Ordenar por prioridad (mayor prioridad = se ejecuta antes)
        usort($this->listeners[$eventClass], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }
    
    /**
     * Despacha un evento a todos los listeners registrados
     */
    public function dispatch(ModuleEvent $event): ModuleEvent
    {
        $eventClass = get_class($event);
        
        $this->log("Dispatching event: {$eventClass} from {$event->moduleOrigin} with " . count($event->payload) . " payload items");
        
        if (!isset($this->listeners[$eventClass])) {
            $this->log("No listeners registered for event: {$eventClass}");
            return $event;
        }
        
        $listenersCount = count($this->listeners[$eventClass]);
        $this->log("Executing {$listenersCount} listeners for event: {$eventClass}");
        
        foreach ($this->listeners[$eventClass] as $index => $listener) {
            try {
                $this->executeListener($listener['callback'], $event, $index);
            } catch (Exception $e) {
                $this->logError("Event listener failed for {$eventClass}[{$index}]: " . $e->getMessage());
                
                // No re-lanzamos la excepción para no interrumpir otros listeners
                continue;
            }
        }
        
        return $event;
    }
    
    /**
     * Ejecuta un listener específico
     */
    private function executeListener(callable $listener, ModuleEvent $event, int $index): void
    {
        $startTime = microtime(true);
        
        if ($this->async && $this->isBackgroundCapable()) {
            $this->dispatchToBackground($listener, $event);
        } else {
            call_user_func($listener, $event);
        }
        
        $executionTime = microtime(true) - $startTime;
        
        $this->log("Listener {$index} executed for " . get_class($event) . " in " . round($executionTime * 1000, 2) . "ms");
    }
    
    /**
     * Despacha listener a background (si el sistema lo soporta)
     */
    private function dispatchToBackground(callable $listener, ModuleEvent $event): void
    {
        // TODO: Implementar queue system cuando esté disponible
        // Por ahora ejecutamos síncronamente
        call_user_func($listener, $event);
    }
    
    /**
     * Verifica si el sistema puede ejecutar en background
     */
    private function isBackgroundCapable(): bool
    {
        // TODO: Verificar si hay queue driver disponible
        return false;
    }
    
    /**
     * Habilita/deshabilita ejecución asíncrona
     */
    public function setAsync(bool $async): void
    {
        $this->async = $async;
    }
    
    /**
     * Obtiene todos los listeners registrados para un evento
     */
    public function getListeners(string $eventClass): array
    {
        return $this->listeners[$eventClass] ?? [];
    }
    
    /**
     * Obtiene todos los eventos con listeners registrados
     */
    public function getRegisteredEvents(): array
    {
        return array_keys($this->listeners);
    }
    
    /**
     * Verifica si hay listeners para un evento
     */
    public function hasListeners(string $eventClass): bool
    {
        return isset($this->listeners[$eventClass]) && count($this->listeners[$eventClass]) > 0;
    }
    
    /**
     * Remueve todos los listeners para un evento
     */
    public function removeListeners(string $eventClass): void
    {
        unset($this->listeners[$eventClass]);
    }
    
    /**
     * Remueve un listener específico
     */
    public function removeListener(string $eventClass, callable $listener): bool
    {
        if (!isset($this->listeners[$eventClass])) {
            return false;
        }
        
        foreach ($this->listeners[$eventClass] as $index => $registeredListener) {
            if ($registeredListener['callback'] === $listener) {
                unset($this->listeners[$eventClass][$index]);
                $this->listeners[$eventClass] = array_values($this->listeners[$eventClass]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Limpia todos los listeners registrados
     */
    public function clearListeners(): void
    {
        $this->listeners = [];
    }
    
    /**
     * Obtiene estadísticas del dispatcher
     */
    public function getStats(): array
    {
        $totalListeners = 0;
        $eventStats = [];
        
        foreach ($this->listeners as $eventClass => $listeners) {
            $count = count($listeners);
            $totalListeners += $count;
            $eventStats[$eventClass] = $count;
        }
        
        return [
            'total_events' => count($this->listeners),
            'total_listeners' => $totalListeners,
            'async_enabled' => $this->async,
            'events' => $eventStats
        ];
    }
    
    /**
     * Log genérico para debugging
     */
    private function log(string $message): void
    {
        if (!$this->enableLogging) {
            return;
        }
        
        // En desarrollo, loggear a archivo
        if (defined('MODE') && MODE === 'dev') {
            error_log("[ModuleEventDispatcher] " . $message);
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
        
        Logger::error('ModuleEventDispatcher', $message);
    }
} 