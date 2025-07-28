<?php
namespace Core\Classes;
use Backoffice\Modules\Business\Domain\Business;
use Backoffice\Modules\User\Domain\User;
use Core\Services\DatabaseService;
use Core\Services\ServiceContainer;

class Context {
    public $Entities = [];
    public ?User $User = null;
    public ?Business $Business = null;
    public ?DatabaseService $DatabaseService = null;
    
    private static ?self $instance = null;

    public function __construct()
    {
        $this->DatabaseService = ServiceContainer::resolve(DatabaseService::class);
    }

    public static function getContext(): self
    {
        // Usar singleton pattern en lugar de variable global
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        // Mantener compatibilidad con variable global
        global $Context;
        if ($Context === null) {
            $Context = self::$instance;
        }
        
        return self::$instance;
    }

}