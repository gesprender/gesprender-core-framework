<?php

namespace Config;

use Core\Services\JsonResponse;
use Core\Services\Response;
use Dotenv\Dotenv;

final readonly class Kernel
{
    public function run(): void
    {
        Response::setHeaders();

        $this->getDotenv();

        
        if($_ENV['MODE'] == 'prod') error_reporting(E_ALL & ~E_WARNING);

        # Load Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        # Defines
        require  'defines.php';

        $this->leadFiles();
        $this->endpointNotFound();
        $this->Welcome();
        
    }

    private function getDotenv(): void
    {
        $loadCustomDefines = '../Backoffice/.env';
        if (file_exists($loadCustomDefines)) {
            $dotenv = Dotenv::createImmutable('./../Backoffice/');
            $dotenv->load();
        }else{
            $dotenv = Dotenv::createImmutable('./../');
            $dotenv->load();
        }
    }

    private function leadFiles()
    {

        # Load backoffice custom defines
        $loadCustomDefines = '../Backoffice/src/custom_defines.php';
        if (file_exists($loadCustomDefines)) {
            require $loadCustomDefines;
        }

        # Load backoffice Endpoints
        $this->autoload_controllers('../Backoffice/src/Modules');
        
    }

    private function autoload_controllers($directory): void
    {
        $modules = scandir($directory);
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') continue;
            
            $controllerPath = $directory . '/' . $module . '/Infrastructure/' . $module . 'Controller.php';
            if (file_exists($controllerPath)) {
                $controllerClass = 'Backoffice\\Modules\\' . $module . '\\Infrastructure\\' . $module . 'Controller';
                if (class_exists($controllerClass)) {
                    $controllerClass::endpoints();
                }
            }
        }
    }

    private function endpointNotFound():? JsonResponse
    {
        if ($_REQUEST) {
            return new JsonResponse([
                'status' => false,
                'message' => 'Route not found.'
            ], 404);
        }
        return null;
    }

    private function Welcome():? JsonResponse
    {
        return new JsonResponse([
            'status' => true,
            'message' => 'Welcom to Api.',
            'data' => []
        ], 200);
    }
    
}
