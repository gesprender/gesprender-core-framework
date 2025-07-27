<?php

namespace GesPrender\Communication;

/**
 * Interfaz que deben implementar los módulos para habilitar comunicación
 * 
 * Define los métodos estándar que permiten a los módulos registrar servicios,
 * eventos, hooks y exponer su funcionalidad al resto del sistema.
 */
interface ModuleCommunicationInterface
{
    /**
     * Registra los servicios que expone el módulo
     * 
     * Permite que el módulo registre servicios que otros módulos pueden usar.
     * Los servicios se registran en el ModuleServiceRegistry con su nombre,
     * factory function y si son singleton o transient.
     * 
     * @param ModuleServiceRegistry $registry El registry donde registrar servicios
     */
    public function registerServices(ModuleServiceRegistry $registry): void;
    
    /**
     * Registra los event listeners del módulo
     * 
     * Permite que el módulo se suscriba a eventos disparados por otros módulos.
     * Los listeners se ejecutarán cuando se despache el evento correspondiente.
     * 
     * @param ModuleEventDispatcher $dispatcher El dispatcher de eventos
     */
    public function registerEventListeners(ModuleEventDispatcher $dispatcher): void;
    
    /**
     * Registra hooks y filters del módulo
     * 
     * Permite que el módulo se enganche a puntos específicos del código
     * para ejecutar acciones adicionales o modificar datos.
     * 
     * @param ModuleHookSystem $hooks El sistema de hooks
     */
    public function registerHooks(ModuleHookSystem $hooks): void;
    
    /**
     * Retorna los eventos que puede dispatchar el módulo
     * 
     * Lista todos los eventos que este módulo puede disparar para que
     * otros módulos sepan a qué eventos pueden suscribirse.
     * 
     * @return array Array de nombres de clases de eventos
     */
    public function getDispatchableEvents(): array;
    
    /**
     * Retorna información del módulo
     * 
     * Metadatos sobre el módulo como nombre, versión, descripción,
     * dependencias, etc.
     * 
     * @return array Array con información del módulo
     */
    public function getModuleInfo(): array;
    
    /**
     * Método llamado cuando el módulo se inicializa
     * 
     * Se ejecuta después de que todos los servicios, eventos y hooks
     * han sido registrados. Ideal para lógica de inicialización.
     */
    public function boot(): void;
    
    /**
     * Método llamado cuando el módulo se desactiva
     * 
     * Permite limpiar recursos, cancelar tareas, etc.
     */
    public function shutdown(): void;
} 