<?php
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;
$connection = RabbitMQ::init();

# 创建信道
$channel = $connection->channel();

# 队列名
$ex_queue = "aaa";

# 队列声明
$channel->queue_declare($ex_queue, false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] Received '. $msg->body. "\n";

    // basic_consume 第四个参数设置为false ，要手动确认ack
    $msg->ack();
};

$channel->basic_qos(null, 1, null);
/*
 * basic_consume 参数：
 * 第四个参数设置为false （true 表示没有 ack）
 * */
$channel->basic_consume($ex_queue, '', false, false, false, false, $callback);

# 监听消息，一有消息，立马就处理
while ($channel->is_consuming()) {
    echo "等待中....";
    $channel->wait();
}


$channel->close();
$connection->close();