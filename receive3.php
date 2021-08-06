<?php
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;
$connection = RabbitMQ::init();

# 创建信道
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, false, false);

/**
 * $channel->queue_declare  参数注释：
 *  第一个参数 是队列的名字  （为空"" 默认会生成一个类似于amq.gen-DvTFerWvCSdkUIc4ie_M2g 的名字）
 *
 */
list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

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

