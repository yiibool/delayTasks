<?php
declare(strict_types = 1);

namespace DelayTask\Lib;

use DelayTask\Exception\RuntimeException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * 日志
 * @method static info(string $string)
 * @method static error(string $string)
 * @method static warning(string $string)
 */
class FLog
{

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var static
     */
    protected static $_instance;


    /**
     * FLog constructor.
     */
    protected function __construct()
    {
        $this->_logger = new Logger(Config::get('log.name'));
        $stream = new RotatingFileHandler(Config::get('log.file'), Config::get('log.max_files'));
        $this->_logger->pushHandler($stream);
    }

    /**
     * record logs
     * @param $method
     * @param array $params
     * @return mixed
     * @throws RuntimeException
     */
    public static function __callStatic($method, $params = [])
    {
        if (!static::$_instance) {
            static::$_instance = new static;
        }

        if (!isset(Logger::getLevels()[strtoupper($method)])) {
            throw new RuntimeException(RuntimeException::CODE_LOG_LEVEL_ERROR);
        }

        $method = strtolower($method);
        return static::$_instance->_logger->$method($params[0], array_slice($params, 1));
    }

}
