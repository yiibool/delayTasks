<?php
namespace DelayTask\Lib;

use DelayTask\Exception\RuntimeException;

/**
 * 配置
 */
class Config
{

    /**
     * @var array
     */
    protected static $_configs = [];

    /**
     * 配置注册
     * @throws RuntimeException
     */
    public static function register()
    {
        $configPath = opendir(CONFIG_PATH);
        while (false !== ($configFile = readdir($configPath))) {
            if (in_array($configFile, ['.', '..'])) {
                continue;
            }

            self::_includeConfig(basename($configFile, '.php'));
        }

        closedir($configPath);
    }

    /**
     * 读取配置
     * @param $name
     * @return array|mixed|null
     */
    public static function get($name)
    {
        $configInfo = null;
        $configs = explode('.', $name);
        foreach ($configs as $_k => $_config) {
            $configInfo = $configInfo ?? self::$_configs;

            if (!isset($configInfo[$_config])) {
                return $configInfo;
            }

            $configInfo = $configInfo[$_config];
        }

        return $configInfo;
    }

    /**
     * 载入配置
     * @param $config
     * @throws RuntimeException
     */
    protected static function _includeConfig($config)
    {
        $configFile = CONFIG_PATH . DIRECTORY_SEPARATOR . $config . '.php';
        if (!self::_checkConfigFile($configFile)) {
            throw new RuntimeException(RuntimeException::CODE_FILE_NOT_EXISTS);
        }

        self::$_configs[$config] = include_once($configFile);
    }

    /**
     * 检查文件是否存在
     * @param $configFile
     * @return bool
     */
    protected static function _checkConfigFile($configFile)
    {
        return is_file($configFile);
    }
}
