<?php
namespace DelayTask;

use Exception;
use Throwable;
use Swoole\Server;
use DelayTask\Lib\Console;
use DelayTask\Lib\DTProtocol;
use DelayTask\Lib\Config;
use DelayTask\Lib\FLog;
use DelayTask\Lib\Common;
use DelayTask\Exception\RuntimeException;

/**
 * 延时任务
 * Class DelayTaskServer
 * @package DelayTask
 */
class DelayTaskServer
{

    /**
     * @var Server
     */
    public static $swooleServer;

    /**
     * 待处理消息
     * @var array
     */
    static public $pendingMessages = [];

    /**
     * @var int
     */
    static public $workerId = 0;

    /**
     * 循环间隔时长
     * @var float|int
     */
    protected $_tickLimit = 1000*10;

    /**
     * 消息处理错误次数上限
     * @var int
     */
    protected $_failLimit = 5;

    /**
     * 消息处理错误次数
     * @var array
     */
    protected $_failTimes = [];


    /**
     * @param Server $server
     * @param $fd
     * @param $reactorId
     * @param $data
     * @return mixed
     */
    public function onReceive(Server $server, $fd, $reactorId, $data)
    {
        unset($reactorId);
        try {
            $decodeData = DTProtocol::decode($data);
            $res = $this->call($decodeData['body'], $decodeData['header']['reqid']);
            return $server->send($fd, DTProtocol::encode([
                'code'      => $res['code'],
                'message'   => $res['message'],
            ], $decodeData['header']['reqid']));
        } catch (Throwable $e) {
            return $server->send($fd, DTProtocol::encode([
                'code'      => $e->getCode() ?: RuntimeException::CODE_UNKNOWN,
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ], $decodeData['header']['reqid'] ?? dk_get_next_id()));
        }
    }

    /**
     * 连接断开
     * @param Server $server
     * @param $fd
     */
    public function onClose(Server $server, $fd)
    {
        unset($server);
        FLog::info("connection {$fd} closed.");
    }

    /**
     * worker进程启动回调
     * @param $server
     * @param $workerId
     * @throws Exception
     */
    public function onWorkerStart($server, $workerId)
    {
        $tag = ($server->taskworker === true) ? 'taskWorker' : 'worker';
        Console::setProcessName(Config::get('server.server_name') . ": {$tag}");
        self::$workerId = $workerId; self::$swooleServer = $server;
        if (!$server->taskworker) {
            // 每次进程启动需要从磁盘中读取未执行完成的任务
            $pendingTaskPath = APP_PATH . Config::get('task.filepath.pending') . $workerId . DIRECTORY_SEPARATOR;
            if (is_dir($pendingTaskPath)) {
                $messageFiles = Common::recurseDir($pendingTaskPath);
                if (!empty($messageFiles)) {
                    foreach ($messageFiles as $messageFile) {
                        $filePath = rtrim($pendingTaskPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $messageFile;
                        try {
                            $tmp = explode('-', $messageFile);
                            $time = $tmp[0]; $uniqueTag = $tmp[1];
                            if (empty($time) or empty($uniqueTag)) {
                                unlink($filePath);
                                continue;
                            }
                            $messageBody = unserialize(Common::getFileContent($filePath));
                            if (!is_array($messageBody) or empty($messageBody)) {
                                unlink($filePath);
                                continue;
                            }

                            self::$pendingMessages[$time][$uniqueTag] = $messageBody;
                            FLog::info("{$filePath} loaded");
                        } catch (Throwable $e) {
                            FLog::error("loading {$filePath} failed. {$e->getCode()}|{$e->getMessage()}|trace:{$e->getTraceAsString()}");
                        }
                    }
                }
            }

            $server->tick($this->_tickLimit, [$this, 'timer']);
        }
    }

    /**
     * receive request
     * @param $requestBody
     * @param $requestId
     * @return array |integer
     */
    protected function call($requestBody, $requestId)
    {
        try {
            FLog::info("request: {$requestId}|body:" . json_encode($requestBody));
            $action = "\DelayTask\Api\\{$requestBody['action']}";
            $message = $requestBody['message'];
            if (!is_callable($action)) {
                throw new RuntimeException(RuntimeException::CODE_ACTION_ERROR);
            }

            $response = call_user_func_array($action, $message);

            FLog::info("response: {$requestId}|body:" . json_encode($response));
            return $response;
        } catch (Throwable $e) {
            $response = [
                'code'      => $e->getCode() ?: RuntimeException::CODE_UNKNOWN,
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ];
            FLog::info("response: {$requestId}|body:" . json_encode($response));
            return $response;
        }
    }

    /**
     * @param $interval
     * @return bool
     */
    public function timer($interval)
    {
        unset($interval);
        if (empty(self::$pendingMessages)) {
            return false;
        }

        // 根据时间戳排序 取出可以消费的消息
        ksort(self::$pendingMessages);
        foreach (self::$pendingMessages as $time => $messages) {
            if ($time > time() or empty($messages)) {
                break;
            }

            foreach ($messages as $uniqueTag => $message) {
                if (
                    !isset($message['callbackprotocol']) or
                    empty(Config::get("callback-protocol." . strtolower($message['callbackprotocol']))) or
                    empty($message['callback'])
                ) {
                    FLog::error("message: {$uniqueTag} callback protocol incorrect.");
                    $failureFile = $this->getMessagePath($time, $message['uniquetag'], 'failure');
                    $pendingFile = $this->getMessagePath($time, $message['uniquetag'], 'pending');
                    try {
                        Common::moveFile($pendingFile, $failureFile);
                        unset(self::$pendingMessages[$time][$uniqueTag]);
                    } catch (Throwable $e) {
                        FLog::error("message: {$uniqueTag} move file {$pendingFile} to {$failureFile} failed. {$e->getCode()}|{$e->getMessage()}");
                    }

                    continue;
                }

                $message['time'] = $time;
                self::$swooleServer->task([
                    'func'  => Config::get("callback-protocol.{$message['callbackprotocol']}") . '::send',
                    'data'  => $message
                ]);
            }
        }

        return true;
    }

    /**
     * 异步任务处理
     * @param Server $server swoole server
     * @param integer $taskId task id
     * @param integer $fromId from work id
     * @param array $taskData task data
     * @return bool|mixed
     */
    public function onTask($server, $taskId, $fromId, $taskData)
    {
        unset($server);
        $logMessage = "task:{$taskId}, fromId:{$fromId} ";
        if ( empty($taskData['func']) or empty($taskData['data']) ) {
            return false;
        }

        FLog::info($logMessage . ' params:' . json_encode($taskData));
        try {
            $res = call_user_func_array(
                $taskData['func'],
                [$taskData['data']]
            );
        } catch (Throwable $e) {
            FLog::error("{$logMessage} call failed. {$e->getCode()}|{$e->getMessage()}");
            $res = [
                'code'      => $e->getCode(),
                'message'   => $e->getMessage(),
            ];
        }
        FLog::info($logMessage . ' res:' . json_encode($res));

        // swoole 在调用 finish 和 return 的时候都会触发 onFinish 方法, 所以不可重复使用两者
        return $res;
    }

    /**
     * 异步任务结束回调方法
     * @param Server $server swoole server
     * @param integer $taskId task id
     * @param array $data task data
     * @return bool
     */
    public function onFinish($server, $taskId, $data)
    {
        unset($server, $taskId);
        try {
            if (empty($data['res']) or !isset($data['res']['code']) or $data['res']['code'] != 0) {
                FLog::error("onFinish message:{$data['uniqueTag']} callback failed, res:" . json_encode($data));
                isset($this->_failTimes[$data['uniqueTag']]) ? $this->_failTimes[$data['uniqueTag']]++ : $this->_failTimes[$data['uniqueTag']] = 1;
                if ($this->_failTimes[$data['uniqueTag']] >= $this->_failLimit) {
                    Common::moveFile(
                        $this->getMessagePath($data['time'], $data['uniqueTag'], 'pending'),
                        $this->getMessagePath($data['time'], $data['uniqueTag'], 'failure')
                    );
                    unset(self::$pendingMessages[$data['time']][$data['uniqueTag']]);
                    FLog::error("message:{$data['uniqueTag']} time:{$data['time']} has failed {$this->_failLimit} times, move to failed tasks");
                }
            } else {
                unlink($this->getMessagePath($data['time'], $data['uniqueTag'], 'recycle'));
                unset(self::$pendingMessages[$data['time']][$data['uniqueTag']]);
            }
        } catch (Throwable $e) {
            FLog::error("message:{$data['uniqueTag']} over. rsp:" . json_encode($data) . "something failed: {$e->getCode()}|{$e->getMessage()}|{$e->getTraceAsString()}");
        }

        return true;
    }

    /**
     * 获取消息文件
     * @param $time
     * @param $uniqueTag
     * @param string $type
     * @return string
     */
    protected function getMessagePath($time, $uniqueTag, $type = 'pending')
    {
        $configKey = "task.filepath.{$type}";
        $config = Config::get($configKey);
        if (!empty($config)) {
            return APP_PATH . $config . self::$workerId . DIRECTORY_SEPARATOR . $time . '-' . $uniqueTag;
        }

        return '';
    }

    /**
     * 启动服务
     */
    public function run()
    {
        $server = new Server(Config::get('server.listen.host'), Config::get('server.listen.port'));
        $server->on('Receive', [$this, 'onReceive']);
        $server->on('Close', [$this, 'onClose']);
        $server->on('Finish', [$this, 'onFinish']);
        $server->on('Task', [$this, 'onTask']);
        $server->on('WorkerStart', [$this, 'onWorkerStart']);
        $server->on('Start', function () {
            Console::setProcessName(Config::get('server.server_name') . ': master ' . Config::get('server.listen.host') . ':' . Config::get('server.listen.port'));
        });
        $server->on('ManagerStart', function () {
            Console::setProcessName(Config::get('server.server_name') . ': manager');
        });
        $server->set(Config::get('server.settings'));
        $server->start();
    }
}
