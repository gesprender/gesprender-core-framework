<?php
declare(strict_types=1);

namespace Core\Contracts\Traits;

use Core\Services\Validations;
use InvalidArgumentException;

trait TraitValidateForm
{
    public static function validateForm(array $keys):? InvalidArgumentException
    {
        return Validations::validateIntegrityForm($keys);
    }
}