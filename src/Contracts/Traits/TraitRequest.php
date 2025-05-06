<?php
declare(strict_types=1);

namespace Core\Contracts\Traits;

use Core\Classes\Context;
use Core\Services\Request;

trait TraitRequest
{
    public function getValue(string $key): mixed
    {
        return Request::getValue($key);
    }
    
    public function getContext(): Context
    {
        return Context::getContext();
    }
}