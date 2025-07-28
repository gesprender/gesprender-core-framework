<?php

declare(strict_types=1);

namespace Core\Services;

/**
 * MemoryMonitor - Servicio para monitorear y prevenir problemas de memoria
 * 
 * Proporciona herramientas para:
 * - Monitoreo continuo de memoria
 * - Alertas tempranas antes de agotamiento
 * - Estadísticas de uso
 * - Limpieza automática
 */
class MemoryMonitor
{
    private static ?self $instance = null;
    private array $checkpoints = [];
    private float $warningThreshold = 0.8; // 80% del límite
    private float $criticalThreshold = 0.9; // 90% del límite
    private LoggerService $logger;

    private function __construct()
    {
        $this->logger = LoggerService::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Crea un checkpoint de memoria
     */
    public function checkpoint(string $label): void
    {
        $this->checkpoints[$label] = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => microtime(true),
            'memory_limit' => $this->getMemoryLimitBytes()
        ];

        $this->checkMemoryStatus($label);
    }

    /**
     * Verifica el estado actual de la memoria
     */
    public function checkMemoryStatus(string $context = 'general'): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimitBytes();
        
        $usage_percentage = $limit > 0 ? ($current / $limit) * 100 : 0;
        $peak_percentage = $limit > 0 ? ($peak / $limit) * 100 : 0;

        $status = [
            'current_bytes' => $current,
            'current_formatted' => $this->formatBytes($current),
            'peak_bytes' => $peak,
            'peak_formatted' => $this->formatBytes($peak),
            'limit_bytes' => $limit,
            'limit_formatted' => $this->formatBytes($limit),
            'usage_percentage' => round($usage_percentage, 2),
            'peak_percentage' => round($peak_percentage, 2),
            'status' => $this->getStatusLevel($usage_percentage),
            'context' => $context
        ];

        // Alertas automáticas
        if ($usage_percentage >= $this->criticalThreshold * 100) {
            $this->logger->critical('Critical memory usage detected', $status);
            $this->emergencyCleanup();
        } elseif ($usage_percentage >= $this->warningThreshold * 100) {
            $this->logger->warning('High memory usage detected', $status);
            $this->performCleanup();
        }

        return $status;
    }

    /**
     * Obtiene diferencia entre checkpoints
     */
    public function getDifference(string $from, string $to): array
    {
        if (!isset($this->checkpoints[$from]) || !isset($this->checkpoints[$to])) {
            return ['error' => 'Checkpoint not found'];
        }

        $fromData = $this->checkpoints[$from];
        $toData = $this->checkpoints[$to];

        return [
            'memory_difference' => $toData['memory_usage'] - $fromData['memory_usage'],
            'memory_difference_formatted' => $this->formatBytes(abs($toData['memory_usage'] - $fromData['memory_usage'])),
            'time_difference' => $toData['timestamp'] - $fromData['timestamp'],
            'from' => $from,
            'to' => $to,
            'direction' => $toData['memory_usage'] > $fromData['memory_usage'] ? 'increase' : 'decrease'
        ];
    }

    /**
     * Limpieza preventiva de memoria
     */
    public function performCleanup(): void
    {
        // Forzar garbage collection
        $collected = gc_collect_cycles();
        
        // Limpiar buffers de salida si es posible
        if (ob_get_level() > 0) {
            ob_clean();
        }

        $this->logger->debug('Memory cleanup performed', [
            'cycles_collected' => $collected,
            'memory_after' => $this->formatBytes(memory_get_usage(true))
        ]);
    }

    /**
     * Limpieza de emergencia (más agresiva)
     */
    public function emergencyCleanup(): void
    {
        // Múltiples ciclos de GC
        for ($i = 0; $i < 3; $i++) {
            gc_collect_cycles();
        }

        // Limpiar todos los buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $this->logger->emergency('Emergency memory cleanup performed', [
            'memory_after' => $this->formatBytes(memory_get_usage(true)),
            'peak_usage' => $this->formatBytes(memory_get_peak_usage(true))
        ]);
    }

    /**
     * Obtiene estadísticas completas
     */
    public function getStats(): array
    {
        $status = $this->checkMemoryStatus('stats');
        
        return array_merge($status, [
            'checkpoints_count' => count($this->checkpoints),
            'checkpoints' => array_keys($this->checkpoints),
            'warning_threshold' => $this->warningThreshold * 100 . '%',
            'critical_threshold' => $this->criticalThreshold * 100 . '%'
        ]);
    }

    /**
     * Configura umbrales de alerta
     */
    public function setThresholds(float $warning, float $critical): void
    {
        $this->warningThreshold = $warning;
        $this->criticalThreshold = $critical;
    }

    /**
     * Obtiene el límite de memoria en bytes
     */
    private function getMemoryLimitBytes(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return 0; // Sin límite
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Obtiene nivel de estado basado en porcentaje
     */
    private function getStatusLevel(float $percentage): string
    {
        if ($percentage >= $this->criticalThreshold * 100) {
            return 'critical';
        } elseif ($percentage >= $this->warningThreshold * 100) {
            return 'warning';
        } elseif ($percentage >= 50) {
            return 'moderate';
        } else {
            return 'normal';
        }
    }

    /**
     * Formatea bytes en formato legible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        
        if ($factor >= count($units)) {
            $factor = count($units) - 1;
        }
        
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Método estático para monitoreo rápido
     */
    public static function check(string $context = 'quick_check'): array
    {
        return self::getInstance()->checkMemoryStatus($context);
    }

    /**
     * Wrapper estático para checkpoint
     */
    public static function mark(string $label): void
    {
        self::getInstance()->checkpoint($label);
    }
} 