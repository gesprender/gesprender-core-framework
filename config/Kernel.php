<?php

namespace Config;

use Core\Controllers\ExceptionsLogsController;
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

        # Core Endpoints default
        ExceptionsLogsController::Endpoints();

        if ($_REQUEST) {
            Response::json([
                'status' => false,
                'message' => 'Route not found.'
            ], 404);
        }

        Response::json([
            'status' => true,
            'message' => 'Welcom to Api.',
            'data' => []
        ], 200);
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
        $loadCustomDefines = '../Backoffice/backend/custom_defines.php';
        if (file_exists($loadCustomDefines)) {
            require $loadCustomDefines;
        }

        # Load backoffice Endpoints
        $loadFileEndpointsController = '../Backoffice/Endpoints.php';
        if (file_exists($loadFileEndpointsController)) {
            require $loadFileEndpointsController;
        }
        
    }
}
