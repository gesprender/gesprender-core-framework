<?php
declare(strict_types=1);

namespace Core\Contracts;

use Core\Classes\Logger;
use Core\Contracts\Traits\TraitResponseVariants;
use Core\Contracts\Traits\TraitValidateForm;
use Throwable;

/**
 * The way in which Coreabstract reaches the modules respects the following hierarchy:
 * CoreAbstract -> DB -> RepositoryAbstract -> ModuleRepository -> Module
 */
abstract class CoreAbstract
{
    use TraitResponseVariants;
    use TraitValidateForm;

    public static function ExceptionCapture(Throwable $exception, string $path): void
    {
        Logger::error($path, $exception->getMessage());
    }
}
