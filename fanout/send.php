<?php
# 通过 fanout 扇出交换发送数据
require_once '../vendor/autoload.php';

require_once '../Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;
$connection = RabbitMQ::init();

# 2. 获取信道
$channel = $connection->channel();

# 3. 创建交换机（Exchange）
$channel->exchange_declare('logs', 'fanout', false, false, false);

# 发送信息
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