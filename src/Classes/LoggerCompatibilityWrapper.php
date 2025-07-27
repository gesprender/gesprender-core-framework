<?php

declare(strict_types=1);

namespace Core\Classes;

use Core\Services\LoggerService;

/**
 * LoggerCompatibilityWrapper - Wrapper para mantener compatibilidad
 * 
 * Mantiene el comportamiento del Logger::error() anterior mientras
 * migramos gradualmente al nuevo sistema LoggerService
 * 
 * @deprecated Este wrapper será eliminado en v2.0
 */
class LoggerCompatibilityWrapper
{
    private static ?LoggerService $modernLogger = null;
    private static bool $useModernLogger = true;

    /**
     * Inicializa el logger moderno
     */
    private static function initModernLogger(): void
    {
        if (self::$modernLogger === null) {
            try {
                self::$modernLogger = LoggerService::getInstance();
            } catch (\Throwable $e) {
                // Fallback al sistema antiguo si falla la inicialización
                self::$useModernLogger = false;
                error_log("Failed to initialize modern logger: " . $e->getMessage());
            }
        }
    }

    /**
     * Método de compatibilidad para Logger::error()
     * 
     * @param string $module Nombre del módulo
     * @param mixed $message Mensaje de error
     * @deprecated Use LoggerService::moduleError() instead
     */
    public static function error(string $module, $message): void
    {
        self::initModernLogger();

        if (self::$useModernLogger && self::$modernLogger) {
            // Usar el nuevo sistema
            self::$modernLogger->moduleError($module, $message);
        } else {
            // Fallback al comportamiento original
            self::legacyError($module, $message);
        }
    }

    /**
     * Comportamiento original del Logger (fallback)
     */
    private static function legacyError(string $module, $message): void
    {
        try {
            $message = is_string($message) ? $message : json_encode($message);
            self::legacyRegisterLog("[" . date("r") . "] Error en modulo $module : $message\r\n");
            
            if (defined('MODE') && MODE == 'Prod') {
                // EmailController::sendMessage(['JorgeEmilianoM@gmail.com'], 'Reporte de Error', $message);
            }
        } catch (\Throwable $th) {
            error_log($th->getMessage());
        }
    }

    /**
     * Registro de log original (fallback)
     */
    private static function legacyRegisterLog(string $errorLog): void
    {
        $logFile = dirname(__DIR__, 2) . '/Logs/log_class.log';

        $fileHandle = fopen($logFile, 'a');

        if ($fileHandle) {
            fwrite($fileHandle, date('Y-m-d H:i:s') . " - " . $errorLog . PHP_EOL);
            fclose($fileHandle);
        } else {
            error_log("No se pudo abrir el archivo de log: $logFile");
        }
    }

    /**
     * Obtiene estadísticas del wrapper de compatibilidad
     */
    public static function getCompatibilityStats(): array
    {
        return [
            'modern_logger_available' => self::$modernLogger !== null,
            'using_modern_logger' => self::$useModernLogger,
            'fallback_active' => !self::$useModernLogger
        ];
    }

    /**
     * Fuerza el uso del sistema legacy (para testing)
     */
    public static function forceLegacyMode(bool $force = true): void
    {
        self::$useModernLogger = !$force;
    }

    /**
     * Resetea el estado del wrapper (para testing)
     */
    public static function reset(): void
    {
        self::$modernLogger = null;
        self::$useModernLogger = true;
    }
} 