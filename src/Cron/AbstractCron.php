<?php
namespace Core\Cron;

use Core\Classes\Logger;
use Dotenv\Dotenv;
use Exception;

abstract class AbstractCron
{
    abstract public function run();

    public function loadEnv()
    {
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        } catch (Exception $e) {
            require_once(__DIR__ . "/../Class/Logger.php");
            Logger::error('CORE', 'Error in loadEnv -> ' . $e->getMessage());
        }
    }
}