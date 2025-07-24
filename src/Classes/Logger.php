<?php

declare(strict_types=1);

namespace Core\Classes;

class Logger
{
    public static function error(string $module, $message): void
    {
        try {
            $message = is_string($message) ? $message : json_encode($message);
            self::registerLog("[" . date("r") . "] Error en modulo $module : $message\r\n");
            if (defined('MODE') && MODE == 'Prod') {
                // EmailController::sendMessage(['JorgeEmilianoM@gmail.com'], 'Reporte de Error', $message);
            }
        } catch (\Throwable $th) {
            error_log($th->getMessage());
        }
    }
    public static function registerLog(string $errorLog): void
    {
        // Define la ruta y nombre del archivo de log
        $logFile = '../Logs/log_class.log';

        // Abre el archivo de log en modo 'append', o crea uno nuevo si no existe
        $fileHandle = fopen($logFile, 'a');

        if ($fileHandle) {
            // Escribe el mensaje de error con la fecha y hora actual
            fwrite($fileHandle, date('Y-m-d H:i:s') . " - " . $errorLog . PHP_EOL);
            // Cierra el archivo
            fclose($fileHandle);
        } else {
            error_log("No se pudo abrir el archivo de log: $logFile");
        }
    }
}
