<?php
/**
 * direct  （直连模式）  也叫（路由模式）
 * 生产者 direct  直连交换器
 * 注释：direct Exchange – 处理路由键。需要将一个队列绑定到交换机上，要求该消息与一个特定的路由键完全匹配。
    消费者 下的交换机 与路由 必须要与 生产者一值，才可以接收到消息，否则任意一个不同，都接收不到消息
    例如：生产者 交换机叫excheng1 绑定的路由key 是orange
    那么消费者必须要 声明 交换机叫excheng1 ，路由key是orange才可以接收到消息
 */
require_once '../../vendor/autoload.php';

require_once '../../Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;


# 1. 创建链接服务
$connection = RabbitMQ::init();


$channel = $connection->channel();


$channel->exchange_declare('exchange.normal', \PhpAmqpLib\Exchange\AMQPExchangeType::FANOUT, false, true);
$channel->exchange_declare('exchange.dlx', \PhpAmqpLib\Exchange\AMQPExchangeType::DIRECT, false, true);
$args = new \PhpAmqpLib\Wire\AMQPTable();
// 消息过期方式：设置 queue.normal 队列中消息10s之后过期 1000 =1秒
$args->set('x-message-ttl', 10000);
$args->set('x-dead-letter-exchange', 'exchange.dlx');
$args->set('x-dead-letter-routing-key', 'routingkey');
$channel->queue_declare('queue.normal', false, true, false, false, false, $args);
$channel->queue_declare('queue.dlx', false, true, false, false);

$channel->queue_bind('queue.normal', 'exchange.normal');
$channel->queue_bind('queue.dlx', 'exchange.dlx', 'routingkey');
$message = new AMQPMessage('死信队列message');
$channel->basic_publish($message, 'exchange.normal', 'rk');

$channel->close();
$connection->close();



function dd($data) {
    echo "<pre>";
    print_r($data);
    exit;
}