<?php
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

define('APP_PATH', __DIR__);
define('CONFIG_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'configs');

if (!function_exists('swoole_get_local_ip')) {
    exit('未安装 Swoole');
}

require_once APP_PATH . DIRECTORY_SEPARATOR . 'vendor/autoload.php';