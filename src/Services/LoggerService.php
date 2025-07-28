<?php

declare(strict_types=1);

namespace Core\Services;

/**
 * LoggerService - EMERGENCY VERSION without PSR-3 dependencies
 * 
 * Simple logging implementation that works without Monolog/PSR-3
 * This is a temporary fallback to fix the production issue.
 */
class LoggerService
{
    private static ?self $instance = null;
    private string $logPath;

    private function __construct()
    {
        $this->logPath = dirname(__DIR__, 2) . '/Logs/';
        
        // Ensure log directory exists
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // PSR-3 compatible methods (basic implementation)
    public function emergency($message, array $context = array()): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert($message, array $context = array()): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical($message, array $context = array()): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error($message, array $context = array()): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning($message, array $context = array()): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice($message, array $context = array()): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info($message, array $context = array()): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug($message, array $context = array()): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function log($level, $message, array $context = array()): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[$timestamp] $level: $message$contextStr\n";
        
        // Write to app.log
        $logFile = $this->logPath . 'app.log';
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also write errors to error log
        if (in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])) {
            $errorLogFile = $this->logPath . 'errors-' . date('Y-m-d') . '.log';
            @file_put_contents($errorLogFile, $logLine, FILE_APPEND | LOCK_EX);
        }
    }

    // Specialized methods for framework compatibility
    /**
     * Legacy method compatibility - for module errors with context
     * 
     * @param string $module Module name
     * @param string $message Error message  
     * @param array $context Additional context
     */
    public function moduleError(string $module, string $message, array $context = []): void
    {
        $this->error("[$module] $message", $context);
    }

    public function performance(string $operation, float $duration, array $context = []): void
    {
        $context['duration'] = $duration;
        $this->info("Performance - $operation", $context);
    }

    public function access(string $method, string $path, int $statusCode, array $context = []): void
    {
        $context['method'] = $method;
        $context['path'] = $path;
        $context['status'] = $statusCode;
        $this->info("Access log", $context);
    }

    public function security(string $event, array $context = []): void
    {
        $securityLogFile = $this->logPath . 'security-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[$timestamp] SECURITY: $event$contextStr\n";
        @file_put_contents($securityLogFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to main log
        $this->warning("Security event: $event", $context);
    }

    /**
     * Gets debug information about the logger
     */
    public function getDebugInfo(): array
    {
        return [
            'channels' => ['default'], // Simple implementation - single channel
            'log_path' => $this->logPath,
            'log_files' => $this->getLogFiles(),
            'memory_usage' => memory_get_usage(true),
            'service_type' => 'emergency_logger',
            'php_version' => PHP_VERSION
        ];
    }

    /**
     * Gets available log files
     */
    private function getLogFiles(): array
    {
        $files = [];
        if (is_dir($this->logPath)) {
            $logFiles = glob($this->logPath . '*.log');
            foreach ($logFiles as $file) {
                $files[] = [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'modified' => filemtime($file)
                ];
            }
        }
        return $files;
    }

    // Dummy channel method for compatibility
    public function channel(string $name): self
    {
        return $this;
    }
} 