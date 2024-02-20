<?php
namespace Core\Classes;

class Context {
    public $Entities = [];

    public static function getContext(): self
    {
        global $Context;
        return $Context;
    }

}