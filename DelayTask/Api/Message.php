<?php
namespace DelayTask\Api;

use DelayTask\DelayTaskServer;
use DelayTask\Lib\Common;
use DelayTask\Lib\Config;
use Throwable;

/**
 * 消息处理
 * Class Message
 * @package DelayTask\Api
 */
class Message
{

    /**
     * 接收消息
     * @param $messageBody
     * @param $delay
     * @param string $callbackProtocol
     * @param array $callback
     * @return array
     */
    public static function push($messageBody, $delay, $callbackProtocol = 'http', $callback = [])
    {
        $callbackProtocol = strtolower($callbackProtocol);
        if (empty(Config::get("callback-protocol.{$callbackProtocol}"))) {
            return Common::formatReturn(6005, 'protocol incorrect');
        }

        if (empty($callback)) {
            return Common::formatReturn(6006, 'callback incorrect');
        }

        if ($delay <= 0) {
            return Common::formatReturn(6001, 'delay second incorrect');
        }

        $time = strtotime('+' . $delay . ' seconds');
        if (!$time) {
            return Common::formatReturn(6001, 'delay second incorrect');
        }

        $uniqueTag = dk_get_next_id(1, (DelayTaskServer::$workerId + 1));
        $tmpFile = APP_PATH . Config::get('task.filepath.pending') . DelayTaskServer::$workerId . DIRECTORY_SEPARATOR . $time . '-' . $uniqueTag;
        $messageContent = [
            'messagebody'       => $messageBody,
            'callbackprotocol'  => $callbackProtocol,
            'callback'          => $callback,
            'uniquetag'         => $uniqueTag,
            'workerid'          => DelayTaskServer::$workerId,
        ];

        try {
            Common::writeToFile($tmpFile, serialize($messageContent));
        } catch (Throwable $e) {
            return Common::formatReturn($e->getCode() ?: 9999, $e->getMessage());
        }

        DelayTaskServer::$pendingMessages[$time][$uniqueTag] = $messageContent;

        return Common::formatReturn(0, 'success', [
            'message_id'    => $uniqueTag,
        ]);
    }

}