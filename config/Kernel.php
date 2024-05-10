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

        # Load Session
        if (!isset($_SESSION)) {
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
        $loadFileEndpointsController = '../Backoffice/Endpoints.php';
        if (file_exists($loadFileEndpointsController)) {
            require $loadFileEndpointsController;
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
