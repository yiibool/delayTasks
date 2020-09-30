<?php
declare(strict_types=1);

namespace DelayTask\Lib;

use DTProtocol\Exception\ProtocolException;
use DTProtocol\Packet;

/**
 * DT数据协议
 * Class DTProtocol
 * @package DelayTask\Lib
 */
class DTProtocol
{

    /**
     * @var Packet
     */
    protected static $packet;

    /**
     * 消息打包
     * @param $data
     * @param int $requestId
     * @return string
     */
    public static function encode($data, $requestId = 0)
    {
        $requestId = $requestId ?: dk_get_next_id();
        if (empty(self::$packet)) {
            self::$packet = new Packet();
        }

        return self::$packet->encode(json_encode($data), $requestId);
    }

    /**
     * 数据解包
     * @param $data
     * @return array
     * @throws ProtocolException
     */
    public static function decode($data)
    {
        if (empty(self::$packet)) {
            self::$packet = new Packet();
        }

        self::$packet->set('package_max_length', Config::get('server.settings.package_max_length'));
        self::$packet->set('package_length_offset', Config::get('server.settings.package_length_offset'));
        self::$packet->set('package_body_offset', Config::get('server.settings.package_body_offset'));
        return self::$packet->decode($data);
    }
}
