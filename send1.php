<?php
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;
$connection = RabbitMQ::init();

# 2. 获取信道
// $channel_id 信道id，不传则获取$channel[“”]信道，再无则循环$this->channle数组，下标从1到最大信道数找第一个不是AMQPChannel对象的下标，
//实例化并返回AMQPChannel对象，无则抛出异常No free channel ids
$channel = $connection->channel();



# 3. 声明消费者队列
$ex_queue = 'aaa';
/*
 * 非持久化队列,RabbitMQ退出或者崩溃时，该队列就不存在
 * 持久化队列（需要显示声明，第三个参数要设置为true），保存到磁盘，但不一定完全保证不丢失信息，因为保存总是要有时间的。
 * queue_declare 参数说明：
 *      第一个参数为队列名称
 *      第三个是否持久化  true=RabbitMQ退出或者崩溃时 ，重新启动，aaa的队列还会存在  false 则不存在
 *      第四个是否排他
 *      第5个为是否自删除
 * */
$channel->queue_declare($ex_queue, false, true, false, false);


# 4. 创建要发送的信息 ，可以创建多个消息
/*
 * $message  string类型 要发送的消息
 * $properties array类型 设置的属性，比如设置该消息持久化[‘delivery_mode’=>2]
 * AMQPMessage 参数说明：
 *      例如 AMQPMessage($message); 没有第二个参数的时候，当rebbitmq服务挂了，可以重启rabbitmq服务，这个时候aaa队列的消息就会清空
 *      当 AMQPMessage($message,array('delivery_mode'=>AMQPMESSAGE::DELIVERY_MODE_PERSISTENT)) 传递第二个参数的时候，当rebbitmq服务挂了，
 *      可以重启rabbitmq服务，这个时候 “aaa”队列的数据还是会存在
 * */

$message = "消息1"; # 发送的消息
//$msg = new AMQPMessage($message,array('delivery_mode'=>AMQPMESSAGE::DELIVERY_MODE_PERSISTENT));
$msg = new AMQPMessage($message);

# 5. 发送消息
# $msg object AMQPMessage对象
# $exchange string 交换机名字 （这里默认用default交换机）
# $routing_key string 路由键 如果交换机类型
# fanout：扇出交换 该值会被忽略，因为该类型的交换机会把所有它知道的队列发消息，无差别区别
# direct  直接交换 只有精确匹配该路由键的队列，才会发送消息到该队列
# topic   只有正则匹配到的路由键的队列，才会发送到该队列
$channel->basic_publish($msg, '', $ex_queue);


echo " Sent {$message}\n";

# 6. 关闭信道和链接
$channel->close();
$connection->close();

function dd($data) {
    echo "<pre>";
    print_r($data);
    exit;
}