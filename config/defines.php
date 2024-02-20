<?php
require 'alias.php';

define('VERSION', getVersion());
define('VERSION_NUM', str_replace('.', '', VERSION));   //Evita conflictos en React (no acepta agregar ?v=1.8.3)

define('MODE', $_ENV['MODE']);

define('PROTOCOLE_SECURE', (bool)$_ENV['PROTOCOLE_SECURE']);
define('PROTOCOLE', PROTOCOLE_SECURE ? 'https://': 'http://');

define('HOST', PROTOCOLE . $_ENV['HOST']);
define('HOST_REACT', "{$_ENV['HOST_REACT']}:{$_ENV['REACT_PORT']}");
define('HOST_API', $_ENV['HOST_API']);
define('HOST_MARKET', $_ENV['HOST_MARKET']);
define('HOST_PRECIOS', $_ENV['HOST_LISTA_PRECIOS']);
define('REACT_PORT', $_ENV['REACT_PORT']);


if(MODE == 'Prod'){
    # Errors Display
    error_reporting(1);
}

# Paths
define('PATH_SRC', HOST.'/src/');
define('PATH_LOGS', HOST.'/logs/');
define('PATH_REACT', HOST.'/themes/default/');
define('PATH_CONFIG', HOST.'/config/');
define('PATH_VENDOR', HOST.'/vendor/');
define('PATH_UPLOAD', HOST.'/upload/');


#   Definimos zona horaria ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');