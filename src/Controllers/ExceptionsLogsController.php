<?php

namespace Core\Controllers;

use Core\Classes\Logger;
use Core\Services\Request;
use Core\Services\Response;

final class ExceptionsLogsController
{

    public static function Endpoints()
    {
        Request::On('error_fron_log', function () {
            self::getLogFrontException();
        });
    }

    public static function getLogFrontException()
    {
        try {
            $message = Request::getValue('message');
            Logger::registerLog("[FrontException] : $message");
            Response::json(['data' => [], 'message' => 'Ok'], 200);
        } catch (\Throwable $th) {
            Response::json(['data' => [], 'message' => 'No'], 400);
        }
    }
}
