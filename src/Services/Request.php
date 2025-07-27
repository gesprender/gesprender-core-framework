<?php
declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\CoreAbstract;

/**
 * Request - Facade estático para RequestService
 * 
 * Mantiene compatibilidad 100% con módulos existentes mientras
 * delega internamente al nuevo RequestService con DI.
 * 
 * @deprecated Los métodos estáticos serán eliminados en v2.0
 * @see RequestService Para el nuevo sistema con DI
 */
class Request extends CoreAbstract
{
    private static ?RequestService $service = null;

    // Propiedades legacy para compatibilidad con instanciación directa
    public $url;
    public $method;
    public $headers;
    public $body;
    public $queryParams;

    /**
     * Constructor legacy - mantiene compatibilidad
     * @deprecated Use RequestService instead
     */
    public function __construct() 
    {
        $service = self::getService();
        $this->url = $service->getUrl();
        $this->method = $service->getMethod();
        $this->headers = $service->getHeaders();
        $this->body = $service->getBody();
        $this->queryParams = $service->getQueryParams();
    }

    /**
     * Obtiene instancia del RequestService
     */
    private static function getService(): RequestService
    {
        if (self::$service === null) {
            try {
                // Intentar resolver desde ServiceContainer
                self::$service = ServiceContainer::resolve(RequestService::class);
            } catch (\Throwable $e) {
                // Fallback: crear instancia directamente
                self::$service = new RequestService();
            }
        }
        return self::$service;
    }

    /**
     * Registra una ruta con callback
     * 
     * @param string $path Ruta a registrar
     * @param callable $callback Función a ejecutar
     * @param bool $UseSecurityMiddleware Si usar middleware de seguridad
     * @deprecated Use RequestService::route() instead
     */
    public static function Route(string $path, $callback, bool $UseSecurityMiddleware = false): void
    {
        self::getService()->route($path, $callback, $UseSecurityMiddleware);
    }

    /**
     * Alias para Route()
     * 
     * @param string $key Ruta a registrar
     * @param callable $callback Función a ejecutar  
     * @param bool $UseSecurityMiddleware Si usar middleware de seguridad
     * @deprecated Use RequestService::on() instead
     */
    public static function On(string $key, $callback, bool $UseSecurityMiddleware = false): void
    {
        self::getService()->on($key, $callback, $UseSecurityMiddleware);
    }

    /**
     * Obtiene un valor del request
     * 
     * CRÍTICO: Este método es usado extensivamente por los módulos
     * 
     * @param string $key Clave a buscar
     * @param mixed $default Valor por defecto
     * @return string|bool|null|int|array
     * @deprecated Use RequestService::getValue() instead
     */
    public static function getValue($key, $default = false): string|bool|null|int|array
    {
        return self::getService()->getValue($key, $default);
    }

    /**
     * Obtiene valor sin middleware de seguridad
     * 
     * @param string $key Clave a buscar
     * @param mixed $default Valor por defecto
     * @return string|bool|null|int|array
     * @deprecated Use RequestService::getValueByPass() instead
     */
    public static function getValueByPass($key, $default = false): string|bool|null|int|array
    {
        return self::getService()->getValueByPass($key, $default);
    }

    /**
     * Método legacy para compatibilidad (usado en algunos contextos)
     */
    public function get($key, $default = false): string|bool|null|int|array
    {
        return self::getService()->getValue($key, $default);
    }

    /**
     * Métodos legacy adicionales para compatibilidad
     */
    private function getHeaders(): array
    {
        return self::getService()->getHeaders();
    }

    /**
     * Obtiene estadísticas del facade
     */
    public static function getFacadeStats(): array
    {
        return [
            'service_instance' => self::$service !== null,
            'using_service_container' => ServiceContainer::getInstance()->bound(RequestService::class),
            'legacy_mode' => self::$service === null,
            'memory_usage' => memory_get_usage(true)
        ];
    }

    /**
     * Resetea el facade (útil para testing)
     */
    public static function resetFacade(): void
    {
        self::$service = null;
    }

    /**
     * Fuerza el uso de una instancia específica (útil para testing)
     */
    public static function setService(RequestService $service): void
    {
        self::$service = $service;
    }
} 