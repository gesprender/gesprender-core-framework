<?php

namespace GesPrender\Events;

use DateTime;

/**
 * Clase base abstracta para eventos de comunicación entre módulos
 * 
 * Proporciona la estructura base para todos los eventos que los módulos
 * pueden disparar para comunicarse entre sí de forma desacoplada.
 */
abstract class ModuleEvent
{
    public readonly DateTime $timestamp;
    
    public function __construct(
        public readonly string $moduleOrigin,
        public readonly array $payload = []
    ) {
        $this->timestamp = new DateTime();
    }
    
    /**
     * Obtiene el nombre del evento basado en la clase
     */
    public function getEventName(): string
    {
        return static::class;
    }
    
    /**
     * Obtiene el payload como array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    /**
     * Obtiene un valor específico del payload
     */
    public function get(string $key, $default = null)
    {
        return $this->payload[$key] ?? $default;
    }
    
    /**
     * Verifica si el payload contiene una clave
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->payload);
    }
    
    /**
     * Convierte el evento a array para serialización
     */
    public function toArray(): array
    {
        return [
            'event' => static::class,
            'module_origin' => $this->moduleOrigin,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Crea un evento desde array (para deserialización)
     */
    public static function fromArray(array $data): static
    {
        return new static($data['module_origin'], $data['payload']);
    }
} 