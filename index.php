<?php

use Core\Classes\DependencyConstructor;

# Load Endpoints
$loadAutoload = './vendor/autoload.php';
if(file_exists($loadAutoload)){
    require $loadAutoload;
}else{
    include_once  './src/Classes/PagesDefault.php';
    echo PagesDefault::CoreError('Debes instalar las dependencias de composer.');die;
}

if (!file_exists('./.env')) {
    include_once  './src/Classes/PagesDefault.php';
    echo PagesDefault::CoreError('Debes configurar el .env');die;
}

$dotenv = Dotenv\Dotenv::createImmutable('./');
$dotenv->load();
require './config/defines.php';

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= DependencyConstructor::BoostrapCDN() ?>
    <?= DependencyConstructor::React() ?>
    <title><?= $_ENV['NAME_PROJECT'] ?></title>
</head>

<body>
    <div id="root"></div>
    <?= DependencyConstructor::BoostrapLibs() ?>
</body>

</html>