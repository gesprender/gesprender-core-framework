<?php

use Core\Controllers\ExceptionsLogsController;
use Core\Services\Response;

require '../vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");


$dotenv = Dotenv\Dotenv::createImmutable('./../');
$dotenv->load();
require  '../config/defines.php';

# Load Session
if(!isset($_SESSION)){
    session_start();
}

# Load custom defines
$loadCustomDefines = '../Project/src/custom_defines.php';
if(file_exists($loadCustomDefines)){
    require $loadCustomDefines;
}

# Load Endpoints
$loadFileEndpointsController = '../Project/Endpoints.php';
if(file_exists($loadFileEndpointsController)){
    require $loadFileEndpointsController;
}

# Core Endpoints default
ExceptionsLogsController::Endpoints();

if($_REQUEST) {
    Response::json([
        'status' => false,
        'message' => 'Route not found.'
    ], 404);
}

Response::json([
    'status' => true,
    'message' => 'Welcom to Api.'
], 200);