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
        // SOLUCIÓN ULTRA-MINIMALISTA PARA EVITAR ERRORES DE MEMORIA
        try {
            // Verificar memoria disponible antes de procesar
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->getMemoryLimitBytes();
            
            // Si estamos cerca del límite, usar logging mínimo
            if ($memoryLimit > 0 && $memoryUsage > ($memoryLimit * 0.8)) {
                $this->emergencyLog($level, $message);
                return;
            }
            
            // Logging normal pero ultra-simplificado
            $timestamp = date('Y-m-d H:i:s');
            
            // NO procesar contexto si es muy grande para evitar problemas
            $contextStr = '';
            if (!empty($context) && count($context) < 5) {
                $contextStr = ' ' . substr(json_encode($context, JSON_PARTIAL_OUTPUT_ON_ERROR), 0, 200);
            }
            
            $logLine = "[$timestamp] $level: $message$contextStr\n";
            
            // Escribir directamente sin verificaciones adicionales
            $logFile = $this->logPath . 'app.log';
            @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
            // Solo errores críticos a archivo separado
            if ($level === 'ERROR' || $level === 'CRITICAL') {
                $errorFile = $this->logPath . 'critical.log';
                @file_put_contents($errorFile, $logLine, FILE_APPEND | LOCK_EX);
            }
            
            // Liberar inmediatamente
            unset($contextStr, $logLine);
            
        } catch (\Throwable $e) {
            // Fallback ultra-simple
            $this->emergencyLog($level, $message);
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
    
    /**
     * Formatea el contexto de forma segura limitando su tamaño
     */
    private function formatContextSafe(array $context): string
    {
        if (empty($context)) {
            return '';
        }
        
        try {
            // Limitar profundidad y tamaño del contexto para evitar problemas de memoria
            $limitedContext = $this->limitContextSize($context, 100); // Max 100 elementos
            
            $json = json_encode($limitedContext, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            
            // Limitar longitud del string final
            if (strlen($json) > 1000) {
                $json = substr($json, 0, 997) . '...';
            }
            
            return ' ' . $json;
            
        } catch (\Throwable $e) {
            return ' [context-error: ' . $e->getMessage() . ']';
        }
    }
    
    /**
     * Limita el tamaño del array de contexto
     */
    private function limitContextSize(array $data, int $maxItems): array
    {
        $limited = [];
        $count = 0;
        
        foreach ($data as $key => $value) {
            if ($count >= $maxItems) {
                $limited['...'] = 'truncated';
                break;
            }
            
            if (is_array($value)) {
                $limited[$key] = $this->limitContextSize($value, 10); // Submáximo para arrays anidados
            } elseif (is_string($value) && strlen($value) > 500) {
                $limited[$key] = substr($value, 0, 497) . '...';
            } else {
                $limited[$key] = $value;
            }
            
            $count++;
        }
        
        return $limited;
    }
    
    /**
     * Rota el archivo de log si es muy grande
     */
    private function rotateLogIfNeeded(string $logFile): void
    {
        if (!file_exists($logFile)) {
            return;
        }
        
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        if (filesize($logFile) > $maxSize) {
            $backupFile = $logFile . '.' . date('Y-m-d-H-i-s') . '.bak';
            rename($logFile, $backupFile);
            
            // Comprimir el backup si es posible
            if (function_exists('gzopen')) {
                $this->compressLogFile($backupFile);
            }
        }
    }
    
    /**
     * Escribe al log de forma segura
     */
    private function writeLogSafe(string $logFile, string $logLine): void
    {
        $maxRetries = 3;
        $retryDelay = 100000; // 100ms en microsegundos
        
        for ($i = 0; $i < $maxRetries; $i++) {
            if (@file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX) !== false) {
                return; // Éxito
            }
            
            if ($i < $maxRetries - 1) {
                usleep($retryDelay);
            }
        }
        
        // Si falló, usar error_log como fallback
        error_log("Failed to write to log file: $logFile");
    }
    
    /**
     * Comprime un archivo de log
     */
    private function compressLogFile(string $file): void
    {
        try {
            $data = file_get_contents($file);
            $compressed = gzencode($data, 9);
            file_put_contents($file . '.gz', $compressed);
            unlink($file); // Eliminar original después de comprimir
        } catch (\Throwable $e) {
            // Ignorar errores de compresión
        }
    }
    
    /**
     * Parsea límites de memoria y los convierte a bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        if ($limit === '-1') {
            return -1; // Sin límite
        }
        
        $last = strtolower($limit[strlen($limit)-1]);
        $value = (int) $limit;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Obtiene el límite de memoria en bytes - versión ultra-simple
     */
    private function getMemoryLimitBytes(): int
    {
        $limit = ini_get('memory_limit');
        return $this->parseMemoryLimit($limit);
    }
    
    /**
     * Logging de emergencia ultra-minimalista cuando hay problemas de memoria
     */
    private function emergencyLog(string $level, string $message): void
    {
        try {
            // Solo usar error_log nativo de PHP - no consume memoria adicional
            $timestamp = date('H:i:s');
            error_log("[$timestamp] $level: $message");
            
            // Intentar escribir a un archivo mínimo también
            $emergencyFile = $this->logPath . 'emergency.log';
            $line = "[$timestamp] $level: $message\n";
            @file_put_contents($emergencyFile, $line, FILE_APPEND);
            
        } catch (\Throwable $e) {
            // Si incluso esto falla, solo usar error_log
            error_log("EMERGENCY: $level: $message");
        }
    }
} 