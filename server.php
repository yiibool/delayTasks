<?php
require_once __DIR__ . '/init.php';

use DelayTask\Lib\Config;
use DelayTask\Lib\Console;
use DelayTask\Lib\SnowFlake;
use DelayTask\DelayTaskServer;

try {
    Config::register();

    if (!function_exists('dk_get_next_id')) {
        function dk_get_next_id($dataCenterId = 1, $machineId = 1) {
            $snowFlake = new SnowFlake($dataCenterId, $machineId);
            return $snowFlake->generateID();
        }
    }

    Console::register(Config::get('server.settings.pid_file'), $argv[1], [(new DelayTaskServer), 'run']);
} catch (Exception $e) {
    exit("出错了: {$e->getMessage()} \n.");
}
