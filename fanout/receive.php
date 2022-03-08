<?php
require_once '../vendor/autoload.php';

require_once '../Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;
$connection = RabbitMQ::init();

# 创建信道
$channel = $connection->channel();

# 创建交换机
$channel->exchange_declare('logs', 'fanout', false, false, false);

/**
 *
 * $channel->queue_declare  参数注释：
 *  第一个参数 是队列的名字  （为空"" 默认会生成一个类似于amq.gen-DvTFerWvCSdkUIc4ie_M2g 的名字）
 * 获取当前交换机下所有的队列进行数据发送，receive1 绑定的队列abc  receive 绑定的队列eee 都会收到广播
 */
list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
echo '队列:'.$queue_name.'\n';

$channel->queue_bind($queue_name, 'logs');

echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] ', $msg->body, "\n";
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();

