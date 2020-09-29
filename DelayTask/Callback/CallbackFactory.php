<?php
namespace DelayTask\Callback;

use Exception;
use DelayTask\Lib\Config;
use DelayTask\Lib\Common;

/**
 * Class CallbackFactory
 * @package DelayTask\Callback
 */
abstract class CallbackFactory
{

    /**
     * @param array $callback
     * @return mixed
     */
    abstract public static function handler(array $callback);

    /**
     * 1. 将冗余文件移至待回收文件夹
     * 2. 调用回调
     * 3. 处理后续
     *
     * @param array $callback
     * @return mixed
     * @throws Exception
     */
    final public static function send(array $callback)
    {
        $time = $callback['time']; $uniqueTag = $callback['uniquetag']; $workerId = $callback['workerid'];
        $recycleFile = APP_PATH . Config::get('task.filepath.recycle') . $workerId . DIRECTORY_SEPARATOR . $time . '-' . $uniqueTag;
        $pendingFile = APP_PATH . Config::get('task.filepath.pending') . $workerId . DIRECTORY_SEPARATOR . $time . '-' . $uniqueTag;

        Common::moveFile($pendingFile, $recycleFile);
        $res = static::handler($callback);
        if (empty($res['res']) or !isset($res['res']['code']) or $res['res']['code'] != 0) {
            Common::moveFile($recycleFile, $pendingFile);
        }

        return $res;
    }
}
