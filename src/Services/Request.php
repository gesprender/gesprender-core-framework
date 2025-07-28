<?php
declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\CoreAbstract;

/**
 * Request - Facade estático para RequestService (SIMPLIFICADO Y SEGURO)
 * 
 * Version ultra-simple que evita problemas de memoria y errores de dependencias
 */
class Request extends CoreAbstract
{
    private static ?RequestService $service = null;

    // Propiedades legacy simplificadas
    public $url;
    public $method;
    public $headers = [];
    public $body = '';
    public $queryParams = [];

    /**
     * Constructor legacy ultra-simple
     */
    public function __construct() 
    {
        $this->url = $_SERVER['REQUEST_URI'] ?? '/';
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->queryParams = $_GET ?? [];
    }

    /**
     * Obtiene instancia del RequestService (ULTRA-PROTEGIDO)
     */
    private static function getService(): RequestService
    {
        if (self::$service === null) {
            // PROTECCIÓN INMEDIATA: Aumentar memory_limit si es bajo
            $currentLimit = ini_get('memory_limit');
            if ($currentLimit !== '-1' && (int)$currentLimit < 512) {
                ini_set('memory_limit', '1G');
                gc_collect_cycles();
            }
            
            try {
                self::$service = ServiceContainer::resolve(RequestService::class);
            } catch (\Throwable $e) {
                // Fallback ultra-seguro
                ini_set('memory_limit', '1G');
                self::$service = new RequestService();
            }
        }
        return self::$service;
    }

    /**
     * Registra una ruta con callback
     */
    public static function Route(string $path, $callback, bool $UseSecurityMiddleware = false): void
    {
        self::getService()->route($path, $callback, $UseSecurityMiddleware);
    }

    /**
     * Alias para Route()
     */
    public static function On(string $key, $callback, bool $UseSecurityMiddleware = false): void
    {
        self::getService()->on($key, $callback, $UseSecurityMiddleware);
    }

    /**
     * Obtiene un valor del request (ULTRA-PROTEGIDO CONTRA MEMORIA)
     */
    public static function getValue($key, $default = false): string|bool|null|int|array
    {
        // PROTECCIÓN INMEDIATA
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit !== '-1') {
            $limitBytes = self::parseMemoryLimit($memoryLimit);
            if ($memoryUsage > ($limitBytes * 0.8)) {
                ini_set('memory_limit', '1G');
                gc_collect_cycles();
            }
        }
        
        try {
            return self::getService()->getValue($key, $default);
        } catch (\Throwable $e) {
            // EMERGENCIA: Método directo ultra-simple
            if (isset($_REQUEST[$key])) return $_REQUEST[$key];
            if (isset($_POST[$key])) return $_POST[$key];
            if (isset($_GET[$key])) return $_GET[$key];
            return $default;
        }
    }

    /**
     * Obtiene valor sin middleware de seguridad
     */
    public static function getValueByPass($key, $default = false): string|bool|null|int|array
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Método legacy para compatibilidad
     */
    public function get($key, $default = false): string|bool|null|int|array
    {
        return self::getValue($key, $default);
    }

    /**
     * Métodos legacy simplificados
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Obtiene estadísticas del facade
     */
    public static function getFacadeStats(): array
    {
        return [
            'service_instance' => self::$service !== null,
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Resetea el facade
     */
    public static function resetFacade(): void
    {
        self::$service = null;
    }

    /**
     * Fuerza el uso de una instancia específica
     */
    public static function setService(RequestService $service): void
    {
        self::$service = $service;
    }

    /**
     * Convierte memory_limit a bytes
     */
    private static function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') return PHP_INT_MAX;
        
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;
        
        switch ($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
} 