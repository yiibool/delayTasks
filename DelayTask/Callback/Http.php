<?php
namespace DelayTask\Callback;

use DelayTask\Exception\RuntimeException;
use DelayTask\Lib\CURL;
use DelayTask\Lib\FLog;

/**
 * Class Http
 * @package DelayTask\Callback
 */
class Http extends CallbackFactory
{

    /**
     * @var CURL
     */
    protected static $_curl;

    /**
     * @param array $callback
     * @return array|mixed
     */
    public static function handler(array $callback)
    {
        $url = $callback['callback']['url'];
        $dns = isset($callback['callback']['dns']) ? $callback['callback']['dns'] : null;
        $messageBody = $callback['messagebody'];
        $uniqueTag = $callback['uniquetag'];

        if (empty($url)) {
            $res = [];
            goto response;
        }

        self::$_curl = !empty(self::$_curl) ? self::$_curl : new CURL();
        $res = self::$_curl->post($url, $messageBody, $dns);
        // FLog::info(__CLASS__ . " tag:{$uniqueTag} url:{$url} dns:{$dns} response:" . json_encode($res));

        response:
        return [
            'time'      => $callback['time'],
            'uniqueTag' => $uniqueTag,
            'res'       => is_string($res) ? json_decode($res, true) : $res,
        ];
    }

}
