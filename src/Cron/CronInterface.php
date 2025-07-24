<?php 
declare(strict_types=1);

namespace Core\Cron;

interface CronInterface {
    public function run(): void;
}