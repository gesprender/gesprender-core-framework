<?php

declare(strict_types=1);

namespace Core\Classes;

/**
 * Logger - Clase de compatibilidad con el sistema anterior
 * 
 * Esta clase mantiene la API original pero usa internamente
 * el nuevo sistema LoggerService moderno.
 * 
 * @deprecated Los métodos estáticos serán eliminados en v2.0
 * @see \Core\Services\LoggerService Para el nuevo sistema PSR-3
 */
class Logger
{
    /**
     * Log de errores por módulo
     * 
     * @param string $module Nombre del módulo
     * @param mixed $message Mensaje de error
     * @deprecated Use LoggerService::moduleError() instead
     */
    public static function error(string $module, $message): void
    {
        // Delegar al wrapper de compatibilidad
        LoggerCompatibilityWrapper::error($module, $message);
    }

    /**
     * Registro directo de log (mantener para compatibilidad)
     * 
     * @param string $errorLog Mensaje a registrar
     * @deprecated Use LoggerService methods instead
     */
    public static function registerLog(string $errorLog): void
    {
        // Para backward compatibility, mantener funcionalidad básica
        try {
            $logFile = dirname(__DIR__, 2) . '/Logs/log_class.log';
            $fileHandle = fopen($logFile, 'a');

            if ($fileHandle) {
                fwrite($fileHandle, date('Y-m-d H:i:s') . " - " . $errorLog . PHP_EOL);
                fclose($fileHandle);
            } else {
                error_log("No se pudo abrir el archivo de log: $logFile");
            }
        } catch (\Throwable $e) {
            error_log("Logger::registerLog failed: " . $e->getMessage());
        }
    }
}
