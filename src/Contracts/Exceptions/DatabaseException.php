<?php
declare (strict_types = 1);

namespace Core\Contracts\Exceptions;

use Exception;

final class DatabaseException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

}