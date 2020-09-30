<?php
require_once __DIR__ . '/../init.php';

use DelayTask\Lib\Config;
use DelayTask\Lib\DTProtocol;
use DelayTask\Lib\SnowFlake;

Config::register();
function dk_get_next_id($dataCenterId = 1, $machineId = 1) {
    $snowFlake = new SnowFlake($dataCenterId, $machineId);
    return $snowFlake->generateID();
}

$client = new Swoole\Client(SWOOLE_SOCK_TCP);
if (!$client->connect(Config::get('server.listen.host'), Config::get('server.listen.port'), -1)) {
    exit("connect failed. Error: {$client->errCode}\n");
}

$messageBody = DTProtocol::encode([
    'action'    => 'Message::push',
    'message'   => [
        ['a' => 1, 'b' => 2],
        10,
        'http',
        [
            'url'   => 'http://baidu.com',
        ]
    ]
]);
// var_dump($messageBody, DTProtocol::decode($messageBody));
// $client->close();exit();

$client->send($messageBody);
$res = $client->recv();

$resBody = DTProtocol::decode($res);
var_dump($resBody);

$client->close();