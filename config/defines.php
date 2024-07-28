<?php
require 'alias.php';

define('VERSION', getVersion());
define('VERSION_NUM', str_replace('.', '', VERSION));   //Evita conflictos en React (no acepta agregar ?v=1.8.3)

define('MODE', $_ENV['MODE']);

define('PROTOCOLE_SECURE', (bool)$_ENV['PROTOCOLE_SECURE']);
define('PROTOCOLE', PROTOCOLE_SECURE ? 'https://': 'http://');

define('HOST', PROTOCOLE . $_ENV['HOST']);
define('HOST_REACT', $_ENV['HOST_REACT']);

if(MODE == 'Prod'){
    # Errors Display
    error_reporting(1);
}

# Use global defines
define('USE_MIDDLEWARE', true);

# Paths
define('PATH_SRC', HOST.'/src/');
define('PATH_LOGS', HOST.'/logs/');
define('PATH_CONFIG', HOST.'/config/');
define('PATH_VENDOR', HOST.'/vendor/');
define('PATH_UPLOAD', HOST.'/upload/');


#   Definimos zona horaria ARGENTINA
date_default_timezone_set('America/Argentina/Buenos_Aires');


# Redis
define('CUSTOM_REDIS_ENDPOINT', 'RedisContainer');
define('CUSTOM_REDIS_TTL', '5');
define('CUSTOM_REDIS_ENABLE', true);
define('CUSTOM_REDIS_USEFS', 1);