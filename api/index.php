<?php

use Config\Kernel;
use Core\Classes\Context;

require '../vendor/autoload.php';

$Context = new Context();

$Kernel = new Kernel();
$Kernel->run();