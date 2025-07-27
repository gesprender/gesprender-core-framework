<?php
namespace Core\Classes;
use Backoffice\Modules\Business\Domain\Business;
use Backoffice\Modules\User\Domain\User;

class Context {
    public $Entities = [];
    public ?User $User = null;
    public ?Business $Business = null;

    public static function getContext(): self
    {
        global $Context;
        return $Context;
    }

}