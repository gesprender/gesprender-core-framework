<?php
namespace Core\Classes;

class Logger
{
    public static function error(string $module, $message)
    {
        $message = is_string($message) ? $message : json_encode($message);
        self::registerLog("[" . date("r") . "] Error en modulo $module : $message\r\n");
        if (defined('MODE') && MODE == 'Prod') {
            // EmailController::sendMessage(['JorgeEmilianoM@gmail.com'], 'Reporte de Error', $message);
        }
    }
    public static function registerLog(string $errorLog)
    {
        $pathLog = __DIR__.'/../../Logs/errors.log';
        $file_errors = is_writable($pathLog) ? fopen($pathLog, 'a') : false;
        if ($file_errors !== false) {
            fwrite($file_errors, $errorLog);
            fclose($file_errors);
            unset($_SESSION['LoggError']);
        }
    }

    public static function logFront()
    {
        if (!isset($_REQUEST['module']) || !isset($_REQUEST['message'])) return;

        $module = $_REQUEST['module'];
        $message = $_REQUEST['message'];
        self::error("[Front] " . $module, $message);
    }
}
