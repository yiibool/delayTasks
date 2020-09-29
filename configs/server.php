<?php
/**
 * 服务配置
 */
return [
    'server_name'   => 'DelayTask',
    'log_file'      => APP_PATH . '/logs/server.log',
    'listen'        => [
        'host'          => '127.0.0.1',
        'port'          => 8847,
    ],
    'settings'      => [
        'worker_num'            => 8,
        'task_worker_num'       => 8,
        'max_request'           => 1000,
        'dispatch_mode'         => 3,
        'daemonize'             => true,
        'pid_file'              => APP_PATH . '/logs/server.pid',
        'log_file'              => APP_PATH . '/logs/server.log',
        'open_length_check'     => 1,
        'package_max_length'    => 2465792,
        'package_length_type'   => 'N',
        'package_body_offset'   => 8,
        'package_length_offset' => 0,
    ],
];
