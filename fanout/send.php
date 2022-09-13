<?php
/*
 * fanout （扇形模式） 也叫 （订阅模式）
 * 解读：
    1、1个生产者，多个消费者
    2、每一个消费者都有自己的一个队列
    3、生产者没有将消息直接发送到队列，而是发送到了交换机
    4、每个队列都要绑定到交换机
    5、生产者发送的消息，经过交换机，到达队列，实现，一个消息被多个消费者获取的目的
 * */
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