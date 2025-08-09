<?php 

namespace Core\Contracts\Abstracts;

use Core\Contracts\Traits\TraitRequest;
use Core\Contracts\Traits\TraitResponseVariants;
use Core\Contracts\Traits\TraitValidateForm;

abstract class UseCaseAbstract
{
    use TraitRequest;
    use TraitResponseVariants;
    use TraitValidateForm;
}