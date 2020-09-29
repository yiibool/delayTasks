<?php
declare(strict_types=1);

namespace DelayTask\Lib;

use DelayTask\Exception\ProtocolException;

/**
 * DT数据协议
 * Class DTProtocol
 * @package DelayTask\Lib
 */
class DTProtocol
{

    /**
     * 打包格式
     */
    const PACK_FORMAT = 'NN';

    /**
     * 解包格式
     */
    const HEADER_STRUCT = 'Nlength/Nreqid';

    /**
     * 消息打包
     * @param $data
     * @param int $requestId
     * @return string
     */
    public static function encode($data, $requestId = 0)
    {
        $requestId = $requestId ?: dk_get_next_id();
        $encodeData = json_encode($data);
        return pack(self::PACK_FORMAT, strlen($encodeData), $requestId) . $encodeData;
    }

    /**
     * 数据解包
     * @param $data
     * @return array
     * @throws ProtocolException
     */
    public static function decode($data)
    {
        if (strlen($data) > Config::get('server.settings.package_max_length')) {
            throw new ProtocolException(ProtocolException::CODE_PACKAGE_TOO_LARGE);
        }

        $header = substr($data, Config::get('server.settings.package_length_offset'), Config::get('server.settings.package_body_offset'));
        $body = substr($data, Config::get('server.settings.package_body_offset'));
        $decodeRes = unpack(self::HEADER_STRUCT, $header);
        if ($decodeRes === false) {
            throw new ProtocolException(ProtocolException::CODE_PACKAGE_DECODE_FAILED);
        }

        if ($decodeRes['length'] - Config::get('server.settings.package_body_offset') > Config::get('server.settings.package_max_length')) {
            throw new ProtocolException(ProtocolException::CODE_PACKAGE_TOO_LARGE);
        }

        return [
            'header'    => $decodeRes,
            'body'      => json_decode($body, true),
        ];
    }
}
