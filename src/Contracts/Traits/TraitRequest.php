<?php
declare(strict_types=1);

namespace Core\Contracts\Traits;

use Core\Classes\Context;
use Core\Services\RequestService;

trait TraitRequest
{
    public function getValue(string $key, mixed $default = null): mixed
    {
        $request = new RequestService();
        return $request->getValue($key, $default);
    }
    
    public function getContext(): Context
    {
        return Context::getContext();
    }
}