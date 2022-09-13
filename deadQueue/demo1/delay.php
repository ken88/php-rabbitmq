<?php
/**
 * 消费者 direct  直连交换器
 */
require_once '../../vendor/autoload.php';

require_once '../../Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;

$deadExchangeName = "orderDelayExchange"; # 死信交换机
$deadQueueName = "orderDelayQueue"; # 死信队列
$orderRouteName = "order_dead_route"; # 死信路由


# 1. 创建链接服务
$connection = RabbitMQ::init();

#  2. 创建信道
$channel = $connection->channel();


# 3. 创建交换机
$channel->exchange_declare($deadExchangeName,'direct',false,true,false);

# 4. 创建队列
$channel->queue_declare($deadQueueName, false, true, false, false);

# 5. 绑定队列跟交换机
$channel->queue_bind($deadQueueName,$deadExchangeName,$orderRouteName);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

/*
 * 获取队列当中的消息
 * $queue = '', 被消费的队列名
 * consumer_tag = '', 消费者客户端身份标识，用于区分多个客户端
 * no_local = false,  这个功能属于AMQP标准，但是rabbitmq并没有实现
 * no_ack = false,    收到消息后，是否不需要回复确认，即被认为被消费
 * exclusive = false, 是否排他，即这个队列只能由一个消费者消费，适用于任务不允许进行并发处理的情况下
 * nowait = false,    不反悔执行结果，但是如果排它开启的话，则必须等待结果，如果两个一起开就会报错
 * callback = null,   回调逻辑处理函数
 * */
$channel->basic_consume($deadQueueName, '', false, false, false, false, function ($msg) {

    echo ' 接收到消息： '. $msg->body. "\n";

    // basic_consume 第四个参数设置为false ，要手动确认ack
    $msg->ack();

});

# 监听消息，一有消息，立马就处理
while ($channel->is_consuming()) {
//    echo "等待中....\n";
    $channel->wait();
}