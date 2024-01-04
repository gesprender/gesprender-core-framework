<?php
declare(strict_types=1);

namespace Core\Controllers;

use Core\Classes\Logger;
use Core\Contracts\CoreAbstract;
use Core\Services\Request;
use Core\Services\Response;

final class ExceptionsLogsController extends CoreAbstract
{

    public static function Endpoints(): void
    {
        Request::On('error_fron_log', function () {
            self::getLogFrontException();
        });
    }

    public static function getLogFrontException(): void
    {
        try {
            $message = Request::getValue('message');
            Logger::registerLog("[FrontException] : $message");
            Response::json(['data' => [], 'message' => 'Ok'], 200);
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'ExceptionsLogsController::getLogFrontException');
            Response::json(['data' => [], 'message' => 'No'], 400);
        }
    }
}
