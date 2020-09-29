<?php
declare(strict_types=1);

namespace DelayTask\Exception;

/**
 * Class ConsoleException
 * @package DelayTask\Exception
 */
class ConsoleException extends RuntimeException
{
    /**
     * 服务启动异常
     */
    const CODE_SERVER_IS_RUNNING = 5900; // 服务已启动
    const CODE_SERVER_IS_NOT_RUNNING = 5901; // 服务未启动
    const CODE_SERVER_COMMAND_ERROR = 5902; // 服务命令错误
    const CODE_SERVER_CALLBACK_NOT_CALLABLE = 5903; //回调不可用
    const CODE_SERVER_SET_PROCESS_NAME_FAILED = 5904; // 设置进程名失败

    /**
     * 异常文案
     * @var array
     */
    public static $errorMessages = [
        self::CODE_SERVER_IS_RUNNING                => '服务已启动',
        self::CODE_SERVER_IS_NOT_RUNNING            => '服务未启动',
        self::CODE_SERVER_COMMAND_ERROR             => '服务命令不支持. 可用: start|stop|reload',
        self::CODE_SERVER_CALLBACK_NOT_CALLABLE     => '回调不可用',
        self::CODE_SERVER_SET_PROCESS_NAME_FAILED   => '设置进程名失败',
    ];

}