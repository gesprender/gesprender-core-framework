<?php

namespace Core\Contracts;

use Core\Classes\Logger;
use Core\Services\Response;
use Throwable;

/**
 * The way in which Coreabstract reaches the modules respects the following hierarchy:
 * CoreAbstract -> DB -> RepositoryAbstract -> ModuleRepository -> Module
 */
abstract class CoreAbstract
{
    public static function ExceptionResponse(Throwable $exception, string $path = 'CoreAbstract'): string
    {
        Logger::error($path, $exception);
        if (getenv('APP_ENV') != 'Prod') {
            return Response::json([
                'message'   => $exception->getMessage(),
                'status' => false
            ], 500);
        }

        return Response::json([
            'message'   => 'Server Error',
            'status' => false
        ], 500);
    }

    public static function ExceptionCapture(Throwable $exception, string $path): void
    {
        Logger::error($path, $exception);
    }
}
