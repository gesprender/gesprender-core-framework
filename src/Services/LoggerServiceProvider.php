<?php

declare(strict_types=1);

namespace Core\Services;

/**
 * LoggerServiceProvider - Proveedor de servicios de logging y debugging
 * 
 * Inicializa y configura el sistema de logging moderno del framework
 * Integra LoggerService y DebugService con el Kernel
 */
class LoggerServiceProvider
{
    private static ?self $instance = null;
    private LoggerService $logger;
    private DebugService $debug;
    private bool $isInitialized = false;

    private function __construct()
    {
        // Constructor privado para Singleton
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa los servicios de logging y debugging
     */
    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        // Configurar error reporting según el ambiente
        $this->configureErrorReporting();

        // Inicializar LoggerService
        $this->logger = LoggerService::getInstance();

        // Inicializar DebugService (solo en desarrollo)
        $this->debug = DebugService::getInstance();

        // Configurar handlers de errores nativos de PHP
        $this->setupPhpErrorHandlers();

        // Registrar shutdown handler para errores fatales
        $this->registerShutdownHandler();

        $this->isInitialized = true;

        // Log de inicialización
        $this->logger->info('Logging and debugging services initialized', [
            'environment' => $_ENV['MODE'] ?? 'dev',
            'logger_channels' => count($this->logger->getDebugInfo()['channels']),
            'debug_enabled' => $this->debug->getDebugInfo()['enabled'],
            'memory_usage' => memory_get_usage(true)
        ]);
    }

    /**
     * Configura error reporting según el ambiente
     */
    private function configureErrorReporting(): void
    {
        $environment = $_ENV['MODE'] ?? 'dev';

        if ($environment === 'prod') {
            // Producción: Solo errores fatales y logs
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else {
            // Desarrollo: Mostrar todos los errores
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('log_errors', '1');
        }

        // Configurar el log de errores de PHP
        $errorLogPath = dirname(__DIR__, 2) . '/Logs/php_errors.log';
        ini_set('error_log', $errorLogPath);
    }

    /**
     * Configura handlers personalizados para errores de PHP
     */
    private function setupPhpErrorHandlers(): void
    {
        // Handler para errores no fatales
        set_error_handler([$this, 'handlePhpError'], E_ALL);

        // Handler para excepciones no capturadas (solo si no está Whoops)
        if ($_ENV['MODE'] !== 'dev') {
            set_exception_handler([$this, 'handlePhpException']);
        }
    }

    /**
     * Handler personalizado para errores de PHP
     */
    public function handlePhpError(int $severity, string $message, string $file, int $line): bool
    {
        // No procesar errores si están deshabilitados
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];

        $errorType = $errorTypes[$severity] ?? 'UNKNOWN';

        $context = [
            'error_type' => $errorType,
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
        ];

        // Log según la severidad
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $this->logger->error("PHP {$errorType}: {$message}", $context);
        } elseif (in_array($severity, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING])) {
            $this->logger->warning("PHP {$errorType}: {$message}", $context);
        } else {
            $this->logger->notice("PHP {$errorType}: {$message}", $context);
        }

        // Retornar false permite que el handler por defecto también procese el error
        return false;
    }

    /**
     * Handler personalizado para excepciones no capturadas
     */
    public function handlePhpException(\Throwable $exception): void
    {
        $this->debug->logError($exception, [
            'uncaught' => true,
            'fatal' => true
        ]);

        // En producción, mostrar página de error genérica
        if ($_ENV['MODE'] === 'prod') {
            $this->showProductionErrorPage();
        }
    }

    /**
     * Registra handler para errores fatales (OPTIMIZADO)
     */
    private function registerShutdownHandler(): void
    {
        register_shutdown_function(function() {
            $error = error_get_last();
            
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                // ULTRA-SIMPLE: Solo log crítico sin contexto pesado
                try {
                    $this->logger->critical('Fatal PHP Error', [
                        'message' => $error['message'],
                        'file' => basename($error['file']), // Solo nombre del archivo
                        'line' => $error['line']
                        // Remover contexto pesado para evitar más memoria
                    ]);
                } catch (\Throwable $e) {
                    // Fallback ultra-simple
                    error_log("Fatal Error: " . $error['message'] . " in " . basename($error['file']) . ":" . $error['line']);
                }
            }
        });
    }

    /**
     * Muestra página de error para producción
     */
    private function showProductionErrorPage(): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del Servidor</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f4f4f4; }
        .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; margin-bottom: 20px; }
        p { color: #555; line-height: 1.6; }
        .error-code { font-size: 72px; font-weight: bold; color: #e74c3c; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <h1>Error del Servidor</h1>
        <p>Lo sentimos, se ha producido un error interno del servidor.</p>
        <p>El equipo técnico ha sido notificado y trabajamos para solucionarlo.</p>
        <p>Por favor, inténtalo de nuevo más tarde.</p>
    </div>
</body>
</html>';
        exit;
    }

    /**
     * Obtiene la instancia del LoggerService
     */
    public function getLogger(): LoggerService
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->logger;
    }

    /**
     * Obtiene la instancia del DebugService
     */
    public function getDebugService(): DebugService
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->debug;
    }

    /**
     * Logs de performance para requests
     */
    public function logRequest(string $method, string $path, int $statusCode, float $startTime): void
    {
        $duration = microtime(true) - $startTime;
        
        $this->logger->access($method, $path, $statusCode, (array)$duration);
        
        // Log de performance si la request tardó mucho
        if ($duration > 2.0) { // Más de 2 segundos
            $this->logger->warning('Slow request detected', [
                'method' => $method,
                'path' => $path,
                'duration_ms' => round($duration * 1000, 2),
                'memory_peak' => memory_get_peak_usage(true)
            ]);
        }
    }

    /**
     * Helper para logging de seguridad
     */
    public function logSecurityEvent(string $event, array $details = []): void
    {
        $this->logger->security($event, $details);
    }

    /**
     * Helper para debugging rápido (solo en desarrollo)
     */
    public function dd(...$vars): void
    {
        if ($_ENV['MODE'] === 'dev') {
            $this->debug->dump(...$vars);
            exit;
        }
    }

    /**
     * Obtiene estadísticas del sistema de logging
     */
    public function getStats(): array
    {
        return [
            'initialized' => $this->isInitialized,
            'environment' => $_ENV['MODE'] ?? 'dev',
            'logger_info' => $this->isInitialized ? $this->logger->getDebugInfo() : null,
            'debug_info' => $this->isInitialized ? $this->debug->getDebugInfo() : null,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }

    /**
     * Limpia y cierra los servicios de logging
     */
    public function shutdown(): void
    {
        if ($this->isInitialized && isset($this->logger)) {
            $this->logger->info('Logging services shutting down', [
                'peak_memory' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            ]);
        }
    }
} 