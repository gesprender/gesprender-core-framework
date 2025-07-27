<?php

declare(strict_types=1);

namespace Core\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * LoggerService - Sistema de logging moderno para GesPrender Framework
 * 
 * Implementa PSR-3 LoggerInterface usando Monolog
 * Soporte para múltiples canales, structured logging y configuración por ambiente
 */
class LoggerService implements LoggerInterface
{
    private static ?self $instance = null;
    private array $loggers = [];
    private array $config;
    private string $logPath;
    private string $environment;

    private function __construct()
    {
        $this->environment = $_ENV['MODE'] ?? 'dev';
        $this->logPath = $this->getLogPath();
        $this->config = $this->getDefaultConfig();
        $this->setupLoggers();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Configuración por defecto del sistema de logging
     */
    private function getDefaultConfig(): array
    {
        return [
            'default_channel' => 'app',
            'channels' => [
                'app' => [
                    'level' => $this->environment === 'prod' ? LogLevel::INFO : LogLevel::DEBUG,
                    'handlers' => ['file', 'console'],
                    'file' => 'app.log'
                ],
                'error' => [
                    'level' => LogLevel::ERROR,
                    'handlers' => ['rotating_file'],
                    'file' => 'errors.log',
                    'max_files' => 30
                ],
                'access' => [
                    'level' => LogLevel::INFO,
                    'handlers' => ['file'],
                    'file' => 'access.log'
                ],
                'performance' => [
                    'level' => LogLevel::DEBUG,
                    'handlers' => ['file'],
                    'file' => 'performance.log'
                ],
                'security' => [
                    'level' => LogLevel::WARNING,
                    'handlers' => ['rotating_file'],
                    'file' => 'security.log',
                    'max_files' => 90
                ]
            ],
            'formatters' => [
                'simple' => [
                    'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                    'date_format' => 'Y-m-d H:i:s'
                ],
                'detailed' => [
                    'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                    'date_format' => 'Y-m-d H:i:s T',
                    'include_stacktraces' => true
                ],
                'json' => [
                    'type' => 'json'
                ]
            ]
        ];
    }

    /**
     * Configura todos los loggers según la configuración
     */
    private function setupLoggers(): void
    {
        foreach ($this->config['channels'] as $channelName => $channelConfig) {
            $logger = new Logger($channelName);
            
            foreach ($channelConfig['handlers'] as $handlerType) {
                $handler = $this->createHandler($handlerType, $channelConfig);
                $formatter = $this->createFormatter($handlerType);
                
                if ($formatter) {
                    $handler->setFormatter($formatter);
                }
                
                $logger->pushHandler($handler);
            }
            
            $this->loggers[$channelName] = $logger;
        }
    }

    /**
     * Crea handlers según el tipo especificado
     */
    private function createHandler(string $type, array $config): mixed
    {
        $level = $config['level'] ?? LogLevel::DEBUG;
        $file = $config['file'] ?? 'app.log';
        $filePath = $this->logPath . '/' . $file;

        return match ($type) {
            'file' => new StreamHandler($filePath, $level),
            'rotating_file' => new RotatingFileHandler(
                $filePath, 
                $config['max_files'] ?? 30, 
                $level
            ),
            'console' => new StreamHandler('php://stdout', $level),
            'error_log' => new StreamHandler('php://stderr', $level),
            default => new StreamHandler($filePath, $level)
        };
    }

    /**
     * Crea formatters según el tipo
     */
    private function createFormatter(string $handlerType): mixed
    {
        $config = $this->config['formatters'];
        
        return match ($handlerType) {
            'console' => new LineFormatter(
                $config['simple']['format'],
                $config['simple']['date_format']
            ),
            'file', 'rotating_file' => new LineFormatter(
                $config['detailed']['format'],
                $config['detailed']['date_format'],
                true,
                true
            ),
            'json' => new JsonFormatter(),
            default => new LineFormatter(
                $config['simple']['format'],
                $config['simple']['date_format']
            )
        };
    }

    /**
     * Obtiene o crea un logger para un canal específico
     */
    public function channel(string $channel = null): LoggerInterface
    {
        $channel = $channel ?? $this->config['default_channel'];
        
        if (!isset($this->loggers[$channel])) {
            // Si el canal no existe, usar el canal por defecto
            $channel = $this->config['default_channel'];
        }
        
        return $this->loggers[$channel];
    }

    /**
     * Ruta donde se almacenan los logs
     */
    private function getLogPath(): string
    {
        $basePath = dirname(__DIR__, 2); // desde src/Services subir 2 niveles
        $logPath = $basePath . '/Logs';
        
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        return $logPath;
    }

    // ==========================================
    // Implementación PSR-3 LoggerInterface
    // ==========================================

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // Usar canal 'error' para errores críticos
        $channel = in_array($level, [LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::EMERGENCY]) 
            ? 'error' 
            : 'app';
            
        $this->channel($channel)->log($level, $message, $context);
    }

    // ==========================================
    // Métodos auxiliares para compatibilidad
    // ==========================================

    /**
     * Log de errores por módulo (compatibilidad con Logger::error anterior)
     * 
     * @param string $module Nombre del módulo
     * @param mixed $message Mensaje de error (string o cualquier tipo)
     * @param array $context Contexto adicional
     */
    public function moduleError(string $module, mixed $message, array $context = []): void
    {
        $message = is_string($message) ? $message : json_encode($message);
        $context['module'] = $module;
        $context['timestamp'] = date('r');
        
        $this->channel('error')->error($message, $context);
        
        // En producción, enviar notificación por email si está configurado
        if ($this->environment === 'prod') {
            $this->notifyProductionError($module, $message, $context);
        }
    }

    /**
     * Log de performance con métricas
     */
    public function performance(string $operation, float $duration, array $metrics = []): void
    {
        $context = array_merge([
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ], $metrics);
        
        $this->channel('performance')->info("Performance: {$operation}", $context);
    }

    /**
     * Log de acceso/request
     */
    public function access(string $method, string $path, int $statusCode, float $duration = null): void
    {
        $context = [
            'method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        if ($duration !== null) {
            $context['duration_ms'] = round($duration * 1000, 2);
        }
        
        $this->channel('access')->info("Request: {$method} {$path}", $context);
    }

    /**
     * Log de seguridad
     */
    public function security(string $event, array $details = []): void
    {
        $context = array_merge([
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('c'),
            'session_id' => session_id()
        ], $details);
        
        $this->channel('security')->warning("Security Event: {$event}", $context);
    }

    /**
     * Notificación de errores en producción
     */
    private function notifyProductionError(string $module, string $message, array $context): void
    {
        // TODO: Implementar notificación por email
        // Placeholder para futuro EmailService
        // EmailController::sendMessage(['admin@domain.com'], 'Error en Producción', $message);
    }

    /**
     * Debug de estado del logger
     */
    public function getDebugInfo(): array
    {
        return [
            'environment' => $this->environment,
            'log_path' => $this->logPath,
            'channels' => array_keys($this->loggers),
            'config' => $this->config
        ];
    }
} 