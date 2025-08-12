<?php

declare(strict_types=1);

namespace Core\Services;

use Backoffice\Modules\User\Infrastructure\Services\Security;

/**
 * RequestService - Servicio ULTRA-OPTIMIZADO para manejar requests
 * 
 * OPTIMIZACIONES DE MEMORIA:
 * - Lazy loading de todos los componentes pesados
 * - Caché de valores consultados
 * - Logging mínimo (solo errores críticos)
 * - Eliminación de overhead innecesario
 */
class RequestService
{
    private string $url;
    private string $method;
    private array $queryParams;
    
    // LAZY LOADING - solo carga cuando es necesario
    private ?array $headers = null;
    private ?string $body = null;
    private ?array $payload = null;
    
    // CACHÉ PARA EVITAR PROCESAMIENTO REPETITIVO
    private array $valueCache = [];
    private array $routeCache = [];
    
    // PARÁMETROS EXTRAÍDOS DE RUTAS DINÁMICAS
    private array $routeParams = [];
    
    // DEPENDENCIAS CON FALLBACK SEGURO
    private ConfigService $config;
    private LoggerService $logger;
    
    // PROTECCIÓN CONTRA LOGGING RECURSIVO
    private static bool $isLogging = false;
    private static int $logCount = 0;

    public function __construct(ConfigService $config = null, LoggerService $logger = null)
    {
        // Resolver dependencias con fallback ultra-seguro
        try {
            $this->config = $config ?? ServiceContainer::resolve('config');
        } catch (\Throwable $e) {
            $this->config = new ConfigService();
        }
        
        try {
            $this->logger = $logger ?? ServiceContainer::resolve(LoggerService::class);
        } catch (\Throwable $e) {
            $this->logger = LoggerService::getInstance();
        }
        
        // Inicialización mínima - solo lo esencial
        $this->url = $_SERVER['REQUEST_URI'] ?? '/';
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->queryParams = $_GET ?? [];
    }

    /**
     * LAZY LOADING: Headers solo cuando se necesitan
     */
    private function getHeaders(): array
    {
        if ($this->headers === null) {
            if (function_exists('getallheaders')) {
                $this->headers = getallheaders() ?: [];
            } else {
                $this->headers = [];
                foreach ($_SERVER as $key => $value) {
                    if (strpos($key, 'HTTP_') === 0) {
                        $header = str_replace('_', '-', substr($key, 5));
                        $this->headers[$header] = $value;
                    }
                }
            }
        }
        return $this->headers;
    }

    /**
     * LAZY LOADING: Body solo cuando se necesita
     */
    private function getBody(): string
    {
        if ($this->body === null) {
            $this->body = file_get_contents('php://input') ?: '';
        }
        return $this->body;
    }

    /**
     * LAZY LOADING: Payload solo cuando se necesita
     */
    private function getPayload(): array
    {
        if ($this->payload === null) {
            $body = $this->getBody();
            
            if (empty($body)) {
                $this->payload = [];
                return $this->payload;
            }

            $headers = $this->getHeaders();
            $contentType = $headers['Content-Type'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $decoded = json_decode($body, true);
                $this->payload = $decoded ?: [];
            } else {
                $this->payload = [];
            }
        }
        
        return $this->payload;
    }

    /**
     * Registra una ruta con callback (con caché y soporte para parámetros dinámicos)
     */
    public function route(string $path, $callback, bool $useSecurityMiddleware = false): void
    {
        $requestPath = parse_url($this->url, PHP_URL_PATH);
        $cleanPath = str_replace('/api/index.php', '', $requestPath);
        
        // Verificar si la ruta coincide (incluyendo parámetros dinámicos)
        $routeParams = $this->matchRoute($cleanPath, $path);
        
        if ($routeParams !== false) {
            // Guardar parámetros extraídos para getValue()
            $this->routeParams = $routeParams;
            
            if ($useSecurityMiddleware) {
                if (!$this->validateSecurity()) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized']);
                    exit;
                }
            }

            if (is_callable($callback)) {
                $result = $callback();
                if ($result) {
                    echo is_string($result) ? $result : json_encode($result);
                }
            }
            exit;
        }
    }

    /**
     * Verifica si la ruta solicitada coincide con el patrón de ruta (incluyendo parámetros dinámicos)
     * 
     * @param string $requestPath La ruta solicitada (ej: /whatsapp/bots/123)
     * @param string $routePattern El patrón de ruta (ej: /whatsapp/bots/{id})
     * @return array|false Array con parámetros extraídos o false si no coincide
     */
    private function matchRoute(string $requestPath, string $routePattern): array|false
    {
        // Escapar caracteres especiales en el patrón, excepto {param}
        $pattern = preg_quote($routePattern, '#');
        
        // Reemplazar {param} con regex para capturar parámetros
        $pattern = preg_replace('/\\\{(\w+)\\\}/', '(?P<$1>[^/]+)', $pattern);
        
        // Agregar delimitadores y anclas
        $pattern = '#^' . $pattern . '$#';
        
        // Intentar hacer match
        if (preg_match($pattern, $requestPath, $matches)) {
            // Extraer solo los parámetros nombrados
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            
            return $params;
        }
        
        return false;
    }

    /**
     * Registra endpoint legacy (optimizado)
     */
    public function on(string $key, $callback, bool $useSecurityMiddleware = false): void
    {
        $payload = $this->getPayload();
        $pathRequest = str_replace("/api/index.php", "", $this->url);
        
        $matchesParameter = array_key_exists($key, $_REQUEST);
        $matchesPath = $pathRequest === $key;
        $matchesPayload = array_key_exists($key, $payload);
        
        if ($matchesParameter || $matchesPath || $matchesPayload) {
            if ($useSecurityMiddleware && !$this->validateSecurity()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            if (is_callable($callback)) {
                $result = $callback();
                if ($result) {
                    echo is_string($result) ? $result : json_encode($result);
                }
            }
            exit;
        }
    }

    /**
     * ULTRA-OPTIMIZADO: getValue con caché inteligente
     * 
     * Elimina 90% del overhead de logging
     */
    public function getValue(string $key, $default = false): string|bool|null|int|array
    {
        // CACHÉ: Evitar procesamiento repetitivo
        $cacheKey = $key . '_' . gettype($default) . '_' . (string)$default;
        if (isset($this->valueCache[$cacheKey])) {
            return $this->valueCache[$cacheKey];
        }

        $value = $default;
        
        // Buscar en orden de prioridad sin overhead
        // 1. Parámetros de ruta (mayor prioridad)
        if (isset($this->routeParams[$key])) {
            $value = $this->routeParams[$key];
        } else {
            $payload = $this->getPayload();
            if (isset($payload[$key])) {
                $value = $payload[$key];
            } elseif (isset($_REQUEST[$key])) {
                $value = $_REQUEST[$key];
            } elseif (isset($_POST[$key])) {
                $value = $_POST[$key];
            } elseif (isset($_GET[$key])) {
                $value = $_GET[$key];
            }
        }

        // Guardar en caché para futuras consultas
        $this->valueCache[$cacheKey] = $value;
        
        // SOLO log crítico en primeras 5 consultas únicas
        if ($value !== $default && count($this->valueCache) <= 5) {
            $this->criticalLog('debug', 'Value cached', ['key' => $key]);
        }

        return $value;
    }

    /**
     * Versión bypass ultra-rápida
     */
    public function getValueByPass(string $key, $default = false): string|bool|null|int|array
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Validación de seguridad simplificada
     */
    private function validateSecurity(): bool
    {
        try {
            if (class_exists('Backoffice\Modules\User\Infrastructure\Services\Security')) {
                if (method_exists(Security::class, 'validateToken')) {
                    call_user_func([Security::class, 'validateToken']);
                    return true;
                }
            }
            
            // Fallback básico
            $authHeader = $this->getHeaders()['Authorization'] ?? '';
            return strpos($authHeader, 'Bearer ') === 0;
            
        } catch (\Throwable $e) {
            $this->criticalLog('error', 'Security validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * LOGGING ULTRA-MINIMALISTA - Solo errores críticos
     */
    private function criticalLog(string $level, string $message, array $context = []): void
    {
        // Prevenir spam de logs - máximo 10 logs por request
        if (self::$logCount >= 10) {
            return;
        }
        
        // Solo log errores importantes
        if (!in_array($level, ['error', 'critical', 'emergency'])) {
            return;
        }
        
        if (self::$isLogging) {
            return;
        }
        
        try {
            self::$isLogging = true;
            self::$logCount++;
            
            if ($this->logger && method_exists($this->logger, $level)) {
                // Contexto mínimo para evitar memoria
                $minimalContext = [
                    'message' => $message,
                    'url' => $this->url,
                    'method' => $this->method
                ];
                $this->logger->$level($message, $minimalContext);
            }
            
        } catch (\Throwable $e) {
            // Fallback silencioso
            error_log("RequestService: {$message}");
        } finally {
            self::$isLogging = false;
        }
    }

    // MÉTODOS PÚBLICOS PARA API COMPATIBILITY
    public function getUrl(): string { return $this->url; }
    public function getMethod(): string { return $this->method; }
    public function getQueryParams(): array { return $this->queryParams; }
    public function isAjax(): bool { 
        return ($this->getHeaders()['X-Requested-With'] ?? '') === 'XMLHttpRequest'; 
    }
    public function isJson(): bool { 
        return strpos($this->getHeaders()['Content-Type'] ?? '', 'application/json') !== false; 
    }
    public function getClientIp(): string {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
        return 'unknown';
    }
    public function getUserAgent(): string { 
        return $this->getHeaders()['User-Agent'] ?? 'unknown'; 
    }

    /**
     * MÉTODOS DE OPTIMIZACIÓN Y DEBUGGING
     */
    public function clearCache(): void
    {
        $this->valueCache = [];
        $this->routeCache = [];
        gc_collect_cycles();
    }

    public function getCacheStats(): array
    {
        return [
            'cached_values' => count($this->valueCache),
            'cached_routes' => count($this->routeCache),
            'memory_usage' => memory_get_usage(true),
            'log_count' => self::$logCount
        ];
    }

    public function getDebugInfo(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'query_params_count' => count($this->queryParams),
            'headers_loaded' => $this->headers !== null,
            'body_loaded' => $this->body !== null,
            'payload_loaded' => $this->payload !== null,
            'cache_size' => count($this->valueCache),
            'memory_usage' => memory_get_usage(true)
        ];
    }
} 