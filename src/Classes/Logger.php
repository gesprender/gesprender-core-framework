<?php
declare(strict_types=1);

namespace Core\Classes;

class Logger
{
    public static function error(string $module, $message): void
    {
        $message = is_string($message) ? $message : json_encode($message);
        self::registerLog("[" . date("r") . "] Error en modulo $module : $message\r\n");
        if (defined('MODE') && MODE == 'Prod') {
            // EmailController::sendMessage(['JorgeEmilianoM@gmail.com'], 'Reporte de Error', $message);
        }
    }
    public static function registerLog(string $errorLog): void
    {
        $dirPath = __DIR__ . '/../../Logs';
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        $pathLog = $dirPath . '/errors.log';
        $file_errors = fopen($pathLog, 'a');
        if ($file_errors !== false) {
            fwrite($file_errors, $errorLog);
            fclose($file_errors);
        }
    }
}
