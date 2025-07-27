<?php

declare(strict_types=1);

namespace Core\Services;

use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;

/**
 * DebugService - Sistema de debugging avanzado para GesPrender Framework
 * 
 * Integra Whoops para pretty error pages y debugging mejorado
 * Solo activo en modo desarrollo
 */
class DebugService
{
    private static ?self $instance = null;
    private ?Run $whoops = null;
    private bool $isEnabled;
    private string $environment;
    private LoggerService $logger;

    private function __construct()
    {
        $this->environment = $_ENV['MODE'] ?? 'dev';
        $this->isEnabled = $this->environment === 'dev';
        $this->logger = LoggerService::getInstance();
        
        if ($this->isEnabled) {
            $this->setupWhoops();
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Configura Whoops para manejo de errores avanzado
     */
    private function setupWhoops(): void
    {
        $this->whoops = new Run();
        
        // Determinar tipo de handler según el contexto
        if ($this->isApiRequest()) {
            $this->setupApiHandler();
        } elseif ($this->isConsoleRequest()) {
            $this->setupConsoleHandler();
        } else {
            $this->setupWebHandler();
        }
        
        // Registrar como handler de errores
        $this->whoops->register();
        
        $this->logger->debug('Whoops debugging system initialized', [
            'environment' => $this->environment,
            'handler_type' => $this->getHandlerType()
        ]);
    }

    /**
     * Configura handler para requests web
     */
    private function setupWebHandler(): void
    {
        $handler = new PrettyPageHandler();
        
        // Personalizar título y información
        $handler->setPageTitle("🚨 GesPrender Framework - Error");
        $handler->setApplicationRootPath(dirname(__DIR__, 2));
        
        // Agregar información de contexto del framework
        $handler->addDataTable('GesPrender Context', [
            'Environment' => $this->environment,
            'Framework Version' => $this->getFrameworkVersion(),
            'PHP Version' => PHP_VERSION,
            'Memory Usage' => $this->formatBytes(memory_get_usage(true)),
            'Peak Memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'Request Time' => date('Y-m-d H:i:s'),
            'Request URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'Request Method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'
        ]);
        
        // Agregar información de configuración (sin datos sensibles)
        $handler->addDataTable('Configuration', [
            'Multi-tenant Mode' => $_ENV['MULTI_TENANT_MODE'] ?? 'false',
            'Current Domain' => $_SERVER['HTTP_HOST'] ?? 'N/A',
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
            'Session ID' => session_id() ?: 'No session'
        ]);
        
        // Mostrar variables de entorno (filtradas)
        $filteredEnv = $this->filterSensitiveEnvVars($_ENV ?? []);
        if (!empty($filteredEnv)) {
            $handler->addDataTable('Environment Variables', $filteredEnv);
        }
        
        $this->whoops->pushHandler($handler);
    }

    /**
     * Configura handler para requests API (JSON)
     */
    private function setupApiHandler(): void
    {
        $handler = new JsonResponseHandler();
        
        // Solo mostrar traces en desarrollo
        $handler->setJsonApi(true);
        $handler->addTraceToOutput(true);
        
        $this->whoops->pushHandler($handler);
    }

    /**
     * Configura handler para CLI/Console
     */
    private function setupConsoleHandler(): void
    {
        $handler = new PlainTextHandler();
        $handler->addTraceToOutput(true);
        
        $this->whoops->pushHandler($handler);
    }

    /**
     * Determina si es un request API
     */
    private function isApiRequest(): bool
    {
        // Verificar si el request es para API
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        
        return strpos($requestUri, '/api/') === 0 
            || strpos($acceptHeader, 'application/json') !== false
            || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    }

    /**
     * Determina si es un request de consola
     */
    private function isConsoleRequest(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Obtiene el tipo de handler activo
     */
    private function getHandlerType(): string
    {
        if ($this->isConsoleRequest()) return 'console';
        if ($this->isApiRequest()) return 'json';
        return 'web';
    }

    /**
     * Filtra variables de entorno sensibles
     */
    private function filterSensitiveEnvVars(array $env): array
    {
        $sensitiveKeys = [
            'password', 'secret', 'key', 'token', 'auth', 'private',
            'DDBB_PASSWORD', 'JWT_SECRET', 'SMTP_PASSWORD'
        ];
        
        $filtered = [];
        
        foreach ($env as $key => $value) {
            $keyLower = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strpos($keyLower, strtolower($sensitiveKey)) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            $filtered[$key] = $isSensitive ? '***HIDDEN***' : $value;
        }
        
        return $filtered;
    }

    /**
     * Obtiene la versión del framework
     */
    private function getFrameworkVersion(): string
    {
        $composerFile = dirname(__DIR__, 2) . '/composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? 'unknown';
        }
        return 'unknown';
    }

    /**
     * Formatea bytes en formato legible
     */
    private function formatBytes(int $size, int $precision = 2): string
    {
        if ($size == 0) return '0 B';
        
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    /**
     * Log personalizado para errores capturados por Whoops
     */
    public function logError(\Throwable $exception, array $context = []): void
    {
        $this->logger->error($exception->getMessage(), array_merge([
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], $context));
    }

    /**
     * Debugging helper para volcar variables
     */
    public function dump(...$vars): void
    {
        if (!$this->isEnabled) {
            return;
        }
        
        echo '<pre style="background: #f4f4f4; border: 1px solid #ddd; padding: 10px; margin: 10px; border-radius: 5px;">';
        
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n" . str_repeat('-', 50) . "\n";
        }
        
        echo '</pre>';
    }

    /**
     * Debugging helper con stack trace
     */
    public function trace(string $message = 'Debug Trace'): void
    {
        if (!$this->isEnabled) {
            return;
        }
        
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        
        $this->logger->debug($message, [
            'trace' => array_slice($trace, 1, 5) // Solo los primeros 5 niveles
        ]);
    }

    /**
     * Performance profiling helper
     */
    public function profile(string $operation, callable $callback, array $context = []): mixed
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            $result = $callback();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $this->logger->performance($operation, $endTime - $startTime, array_merge([
                'memory_delta' => $endMemory - $startMemory,
                'memory_start' => $startMemory,
                'memory_end' => $endMemory
            ], $context));
            
            return $result;
            
        } catch (\Throwable $e) {
            $this->logError($e, ['operation' => $operation]);
            throw $e;
        }
    }

    /**
     * Obtiene información de debugging
     */
    public function getDebugInfo(): array
    {
        return [
            'enabled' => $this->isEnabled,
            'environment' => $this->environment,
            'whoops_registered' => $this->whoops !== null,
            'handler_type' => $this->getHandlerType(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }

    /**
     * Desactiva Whoops (útil para testing)
     */
    public function disable(): void
    {
        if ($this->whoops) {
            $this->whoops->unregister();
            $this->whoops = null;
        }
        $this->isEnabled = false;
    }

    /**
     * Reactiva Whoops
     */
    public function enable(): void
    {
        if ($this->environment === 'dev' && !$this->isEnabled) {
            $this->isEnabled = true;
            $this->setupWhoops();
        }
    }
} 