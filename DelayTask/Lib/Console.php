<?php
namespace DelayTask\Lib;

use DelayTask\Exception\ConsoleException;

/**
 * 初始命令
 */
class Console
{

    /**
     * @var static
     */
    private static $_instance;

    /**
     * @var string
     */
    private $_console;

    /**
     * Console constructor.
     * @param $pidFile
     * @param $console
     * @param null $callback
     * @throws ConsoleException
     */
    public function __construct($pidFile, $console, $callback = null)
    {
        $serverPid = is_file($pidFile) ? file_get_contents($pidFile) : 0;
        $this->_console = $console;
        switch ($this->_console) {
            case 'start':
                return $this->_start($serverPid, $callback);
            case 'stop':
                return $this->_stop($serverPid);
            case 'reload':
                return $this->_reload($serverPid);
            default:
                throw new ConsoleException(ConsoleException::CODE_SERVER_COMMAND_ERROR);
        }
    }

    /**
     * @param $serverPid
     * @param $callback
     * @return bool
     * @throws ConsoleException
     */
    protected function _start($serverPid, $callback)
    {
        if (!empty($serverPid) and posix_kill($serverPid, 0)) {
            throw new ConsoleException(ConsoleException::CODE_SERVER_IS_RUNNING);
        }

        if (!is_callable($callback)) {
            throw new ConsoleException(ConsoleException::CODE_SERVER_CALLBACK_NOT_CALLABLE);
        }

        if (is_callable($callback)) {
            call_user_func($callback);
        }

        return true;
    }

    /**
     * @param $serverPid
     * @return bool
     * @throws ConsoleException
     */
    protected function _stop($serverPid)
    {
        if (empty($serverPid)) {
            throw new ConsoleException(ConsoleException::CODE_SERVER_IS_NOT_RUNNING);
        }

        return posix_kill($serverPid, SIGTERM);
    }

    /**
     * @param $serverPid
     * @return bool
     * @throws ConsoleException
     */
    protected function _reload($serverPid)
    {
        if (empty($serverPid)) {
            throw new ConsoleException(ConsoleException::CODE_SERVER_IS_NOT_RUNNING);
        }

        return posix_kill($serverPid, SIGUSR1);
    }

    /**
     * @param $pidFile
     * @param $console
     * @param null $callback
     * @return Console
     * @throws ConsoleException
     */
    public static function register($pidFile, $console, $callback = null)
    {
        if (self::$_instance !== null) {
            return self::$_instance;
        }

        self::$_instance = new self($pidFile, $console, $callback);
        return self::$_instance;
    }

    /**
     * 设置进程名
     * @param $name
     * @return bool
     * @throws ConsoleException
     */
    public static function setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } elseif (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        } else {
            throw new ConsoleException(ConsoleException::CODE_SERVER_SET_PROCESS_NAME_FAILED);
        }

        return true;
    }

}
