<?php
declare(strict_types=1);

namespace Core\Contracts\Traits;

use Core\Services\Validations;
use Core\Services\Request;
use InvalidArgumentException;

trait TraitRequest
{
    public function getValue(string $key): mixed
    {
        return Request::getValue($key);
    }
}