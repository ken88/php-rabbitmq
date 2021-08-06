<?php

# 通过 direct 直接交换发送数据
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;
$connection = RabbitMQ::init();

# 2. 获取信道
# $channel_id 信道id，不传则获取$channel[“”]信道，再无则循环$this->channle数组，下标从1到最大信道数找第一个不是AMQPChannel对象的下标，
# 实例化并返回AMQPChannel对象，无则抛出异常No free channel ids
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, false, false);

$data = "info: Hello World!";
$msg = new AMQPMessage($data);

$channel->basic_publish($msg, 'logs');

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();


function dd($data) {
    echo "<pre>";
    print_r($data);
    exit;
}