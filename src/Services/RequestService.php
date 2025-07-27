<?php

declare(strict_types=1);

namespace Core\Services;

use Backoffice\Modules\User\Infrastructure\Services\Security;
use Core\Contracts\CoreAbstract;

/**
 * RequestService - Servicio de manejo de requests con Dependency Injection
 * 
 * Versión NO estática del servicio Request que mantiene toda la funcionalidad
 * pero permite dependency injection y testing.
 */
class RequestService
{
    private string $url;
    private string $method;
    private array $headers;
    private string $body;
    private array $queryParams;
    private array $payload;
    private ConfigService $config;
    private LoggerService $logger;

    public function __construct(ConfigService $config = null, LoggerService $logger = null)
    {
        $this->config = $config ?? ServiceContainer::resolve('config');
        $this->logger = $logger ?? ServiceContainer::resolve(LoggerService::class);
        
        $this->initialize();
    }

    /**
     * Inicializa los datos del request
     */
    private function initialize(): void
    {
        $this->url = $_SERVER['REQUEST_URI'] ?? '/';
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->headers = $this->extractHeaders();
        $this->body = file_get_contents('php://input') ?: '';
        $this->queryParams = $_GET ?? [];
        $this->payload = $this->parsePayload();
        
        $this->logger->debug('Request initialized', [
            'method' => $this->method,
            'url' => $this->url,
            'content_type' => $this->headers['Content-Type'] ?? 'unknown'
        ]);
    }

    /**
     * Extrae headers del request
     */
    private function extractHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Parsea el payload del request
     */
    private function parsePayload(): array
    {
        if (empty($this->body)) {
            return [];
        }

        $contentType = $this->headers['Content-Type'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $decoded = json_decode($this->body, true);
            return $decoded ?: [];
        }

        return [];
    }

    /**
     * Registra una ruta con callback
     * 
     * Maneja rutas REST como /login, /register, etc.
     * Quita automáticamente el prefijo /api/index.php para compatibilidad
     */
    public function route(string $path, $callback, bool $useSecurityMiddleware = false): void
    {
        $requestPath = parse_url($this->url, PHP_URL_PATH);
        
        // Quitar prefijo /api/index.php para compatibilidad con rutas REST
        $cleanPath = str_replace('/api/index.php', '', $requestPath);
        
        if ($cleanPath === $path) {
            $this->logger->access($this->method, $path, 200);
            
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
     * Registra endpoint basado en parámetro GET o path - COMPORTAMIENTO ORIGINAL
     * 
     * Este método mantiene la compatibilidad con endpoints legacy que usan
     * parámetros GET como ?business_config=1 en lugar de rutas REST
     */
    public function on(string $key, $callback, bool $useSecurityMiddleware = false): void
    {
        $payload = $this->parsePayload();
        $pathRequest = str_replace("/api/index.php", "", $this->url);
        
        // Comportamiento original: verificar parámetro GET, path o payload JSON
        $matchesParameter = array_key_exists($key, $_REQUEST);
        $matchesPath = $pathRequest === $key;
        $matchesPayload = array_key_exists($key, (array)$payload);
        
        if ($matchesParameter || $matchesPath || $matchesPayload) {
            $this->logger->access($this->method, "param:$key", 200);
            
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
     * Obtiene un valor del request (payload JSON o $_REQUEST)
     * 
     * COMPATIBLE con comportamiento original: busca en payload JSON, luego $_REQUEST
     * Para máxima compatibilidad, también busca en $_POST y $_GET directamente
     */
    public function getValue(string $key, $default = false): string|bool|null|int|array
    {
        // Prioridad 1: payload JSON (nuevo comportamiento)
        if (isset($this->payload[$key])) {
            $value = $this->payload[$key];
            $this->logger->debug('Request value obtained from payload', [
                'key' => $key,
                'type' => gettype($value)
            ]);
            return $value;
        }

        // Prioridad 2: $_REQUEST (comportamiento original)
        if (isset($_REQUEST[$key])) {
            $value = $_REQUEST[$key];
            $this->logger->debug('Request value obtained from $_REQUEST', [
                'key' => $key,
                'type' => gettype($value)
            ]);
            return $value;
        }

        // Prioridad 3: $_POST (para compatibilidad total)
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
            $this->logger->debug('Request value obtained from $_POST', [
                'key' => $key,
                'type' => gettype($value)
            ]);
            return $value;
        }

        // Prioridad 4: $_GET (para compatibilidad total)
        if (isset($_GET[$key])) {
            $value = $_GET[$key];
            $this->logger->debug('Request value obtained from $_GET', [
                'key' => $key,
                'type' => gettype($value)
            ]);
            return $value;
        }

        $this->logger->debug('Request value not found, returning default', [
            'key' => $key,
            'default' => $default
        ]);

        return $default;
    }

    /**
     * Obtiene valor sin middleware de seguridad
     */
    public function getValueByPass(string $key, $default = false): string|bool|null|int|array
    {
        if (!isset($_REQUEST[$key])) {
            return $default;
        }

        return $_REQUEST[$key];
    }

    /**
     * Valida seguridad del request
     */
    private function validateSecurity(): bool
    {
        try {
            if (class_exists('Backoffice\Modules\User\Infrastructure\Services\Security')) {
                // Llamar directamente a validateToken que es el método que existe
                if (method_exists(Security::class, 'validateToken')) {
                    call_user_func([Security::class, 'validateToken']);
                    return true; // Si no lanza excepción, el token es válido
                }
            }
            
            // Fallback básico si no existe la clase o método Security
            return $this->basicSecurityCheck();
            
        } catch (\Throwable $e) {
            $this->logger->security('security_validation_failed', [
                'error' => $e->getMessage(),
                'url' => $this->url,
                'method' => $this->method
            ]);
            return false;
        }
    }

    /**
     * Validación básica de seguridad
     */
    private function basicSecurityCheck(): bool
    {
        // Verificar Authorization header
        $authHeader = $this->headers['Authorization'] ?? '';
        
        if (empty($authHeader)) {
            return false;
        }

        // Aquí iría la validación JWT básica
        // Por ahora, simplemente verificar que existe
        return strpos($authHeader, 'Bearer ') === 0;
    }

    /**
     * Getters para acceso a propiedades
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Verifica si el request es AJAX
     */
    public function isAjax(): bool
    {
        return isset($this->headers['X-Requested-With']) && 
               $this->headers['X-Requested-With'] === 'XMLHttpRequest';
    }

    /**
     * Verifica si el request es JSON
     */
    public function isJson(): bool
    {
        $contentType = $this->headers['Content-Type'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }

    /**
     * Obtiene IP del cliente
     */
    public function getClientIp(): string
    {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }

        return 'unknown';
    }

    /**
     * Obtiene User Agent
     */
    public function getUserAgent(): string
    {
        return $this->headers['User-Agent'] ?? 'unknown';
    }

    /**
     * Debug información del request
     */
    public function getDebugInfo(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'headers_count' => count($this->headers),
            'body_size' => strlen($this->body),
            'query_params_count' => count($this->queryParams),
            'payload_count' => count($this->payload),
            'is_ajax' => $this->isAjax(),
            'is_json' => $this->isJson(),
            'client_ip' => $this->getClientIp()
        ];
    }
} 