<?php
declare(strict_types=1);

namespace DelayTask\Exception;

/**
 * Class ProtocolException
 * @package DelayTask\Exception
 */
class ProtocolException extends RuntimeException
{

    /**
     * 协议打包/解包异常
     */
    CONST CODE_PACKAGE_TOO_LARGE = 9001; // 数据包过大
    CONST CODE_PACKAGE_DECODE_FAILED = 9002; // 解包失败

    /**
     * 异常文案
     * @var string[]
     */
    public static $errorMessages = [
        self::CODE_PACKAGE_TOO_LARGE         => '数据包过大',
        self::CODE_PACKAGE_DECODE_FAILED     => '数据解包失败',
    ];
}