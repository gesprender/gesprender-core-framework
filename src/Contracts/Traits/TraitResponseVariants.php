<?php
declare(strict_types=1);

namespace Core\Contracts\Traits;

use Core\Contracts\Exceptions\AuthException;
use Core\Services\JsonResponse;
use InvalidArgumentException;

trait TraitResponseVariants
{
    public static function ExceptionResponse(\Throwable $exception, string $path = 'CoreAbstract'): JsonResponse
    {
        if($exception instanceof InvalidArgumentException) return self::invalidArgument($exception->getMessage());

        if($exception instanceof AuthException) return self::invalidAuthorization($exception->getMessage());

        return self::serverError($exception->getMessage());
    }

    private static function serverError(string $message): JsonResponse
    {
        if (getenv('MODE') != 'Prod') $message = 'Internal Server error';
        return new JsonResponse([
            'message' => $message,
            'status' => false,
            'data' => []
        ], 500, ['Content-Type' => 'application/json']);
    }

    private static function invalidArgument(string $message): JsonResponse
    {
        if (getenv('MODE') != 'Prod') $message = 'Missing argument';
        return new JsonResponse([
            'message'   => $message,
            'status' => false,
            'data' => []
        ], 400);
    }

    private static function invalidAuthorization(string $message): JsonResponse
    {
        if (getenv('MODE') != 'Prod') $message = 'Not authorized';

        return new JsonResponse([
            'message'   => $message,
            'status' => false,
            'data' => []
        ], 401);
    }
}
