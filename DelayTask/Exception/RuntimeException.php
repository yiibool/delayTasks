<?php
declare(strict_types=1);

namespace DelayTask\Exception;

use Exception;

/**
 * 异常处理基类
 */
class RuntimeException extends Exception
{

    /**
     * 参数错误 4001 ~ 4099
     */
    const CODE_PARAMETER_MISSING = 4001; // 参数缺失
    const CODE_PARAMETER_ERROR = 4002; // 参数错误
    const CODE_RECORD_NOT_FOUND = 4004; // 记录未找到

    /**
     * 服务端错误 5001 ~ 5099
     */
    const CODE_MYSQL_SAVED_FAILED = 5001; // MySQL保存失败
    const CODE_DIRPATH_CREATE_FAILED = 5002; // 目录创建失败
    const CODE_FILE_OPEN_FAILED = 5003; // 文件打开失败
    const CODE_FILE_LOCK_FAILED = 5004; // 文件加锁失败
    const CODE_FILE_WRITE_FAILED = 5005; // 文件写入失败
    const CODE_FILE_NOT_EXISTS = 5006; // 文件不存在
    const CODE_DIR_NOT_EXISTS = 5007; // 目录不存在
    const CODE_FILE_UNLINK_FAILED = 5008; // 文件移除失败
    const CODE_FILE_RENAME_FAILED = 5009; // 文件移动失败
    const CODE_LOG_LEVEL_ERROR = 5010; // 日志层级错误
    const CODE_ACTION_ERROR = 5011; // 操作类型错误
    const CODE_REQUEST_HOST_MISSING = 5012; // 缺少请求地址

    /**
     * 未知错误
     */
    const CODE_UNKNOWN = 9999; // 未知错误

    /**
     * 异常文案
     * @var array
     */
    public static $errorMessages = [
        self::CODE_PARAMETER_MISSING        => '参数缺失',
        self::CODE_PARAMETER_ERROR          => '参数错误',
        self::CODE_RECORD_NOT_FOUND         => '记录未找到',
        self::CODE_MYSQL_SAVED_FAILED       => 'MySQL保存失败',
        self::CODE_DIRPATH_CREATE_FAILED    => '目录 %s 创建失败',
        self::CODE_FILE_OPEN_FAILED         => '文件 %s 打开失败',
        self::CODE_FILE_LOCK_FAILED         => '文件 %s 加锁失败',
        self::CODE_FILE_WRITE_FAILED        => '文件 %s 写入失败',
        self::CODE_FILE_NOT_EXISTS          => '文件 %s 不存在',
        self::CODE_DIR_NOT_EXISTS           => '目录 %s 不存在',
        self::CODE_FILE_UNLINK_FAILED       => '文件 %s 移除失败',
        self::CODE_FILE_RENAME_FAILED       => '文件 %s 移动到 %s 失败',
        self::CODE_LOG_LEVEL_ERROR          => '日志层级错误',
        self::CODE_ACTION_ERROR             => '操作类型错误',
        self::CODE_REQUEST_HOST_MISSING     => '缺少请求地址',
        self::CODE_UNKNOWN                  => '未知错误',
    ];

    /**
     * 构造函数
     * @param integer $code error code
     * @param array $extendParams
     * @param string $message
     */
    public function __construct($code, $message = '', $extendParams = [])
    {
        if (is_array(static::$errorMessages) and !empty(static::$errorMessages)) {
            self::$errorMessages += static::$errorMessages;
        }

        $message = !empty($message) ? $message : (
            isset(self::$errorMessages[$code]) ? self::$errorMessages[$code] : self::$errorMessages[self::CODE_UNKNOWN]
        );

        if (!empty($extendParams)) {
            $message = vsprintf($message, $extendParams);
        }

        parent::__construct($message, $code);
    }

}
