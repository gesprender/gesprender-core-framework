<?php
require '../vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$dotenv = Dotenv\Dotenv::createImmutable('./../');
$dotenv->load();
require  '../config/defines.php';

# Load Session
if(!isset($_SESSION)){
    session_start();
}

# Load Endpoints
$loadFileEndpointsController = '../Project/Endpoints.php';
if(file_exists($loadFileEndpointsController)){
    require $loadFileEndpointsController;
}
echo json_encode([
    'message' => 'Welcome GesPrender Framework API',
    'data' => []
]);
