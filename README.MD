# 延时任务

[![Latest Version](https://img.shields.io/github/release/lawsonzhang/delayTasks.svg)](https://github.com/lawsonzhang/delayTasks/releases)

**基于 swoole 实现的异步任务执行服务**

## 特性

#### 秒级延时

任务延时达到秒级

#### 主动消费

自动将到时任务转发给消费服务, 无需服务主动轮询检查任务


## 使用

#### 结构

`DTProtocol` 定义, 请求参数分两部分: `action` 和 `message`, `action` 表示接口, `message` 表示数据包

#### 可用接口

- `Message::push` 任务下发

#### 可用回调协议

- `http`

#### 下发任务示例
```php
$client = new Swoole\Client(SWOOLE_SOCK_TCP);
if (!$client->connect('127.0.0.1', 8847, -1)) {
    exit("connect failed. Error: {$client->errCode}\n");
}

$messageBody = DelayTask\Lib\DTProtocol::encode([
    'action'    => 'Message::push', // 操作类型
    'message'   => [
        $callbackMessage, // 回调消息体
        $delay, // 延迟时长 (s)
        $callbackProtocol, // 回调协议
        [
            'url'   => $callbackUrl, // http作为回调协议时候的回调地址, 默认post方式回调
        ]
    ]
]);

$client->send($messageBody);
$res = $client->recv();

$resBody = DelayTask\Lib\DTProtocol::decode($res);

$client->close();
```
