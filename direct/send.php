<?php
/**
 * 生产者 direct  直连交换器
 */
require_once '../vendor/autoload.php';

require_once  '../Rabbitmq.php';

use PhpAmqpLib\Message\AMQPMessage;

# 1. 创建链接服务
$connection = RabbitMQ::init();

/**
 * 2. 创建信道
 * $channel_id 信道id，不传则获取$channel[“”]信道，再无则循环$this->channle数组，
 * 下标从1到最大信道数找第一个不是AMQPChannel对象的下标，
 * 实例化并返回AMQPChannel对象，无则抛出异常No free channel ids
 */
$channel = $connection->channel();

/**
 * 3. 创建交换机（Exchange）
 * exchange : task  交换机名字
 * type     : 交换机类型
 *          fanout  扇形交换器 会发送消息到它所知道的所有队列，每个消费者获取的消息都是一致的
 *          direct  直连交换器，该交换机将会对绑定键（binding key）和路由键（routing key）进行精确匹配
 *          topic   话题交换器 该交换机会对路由键正则匹配，必须是*(一个单词)、#(多个单词，以.分割) 、 user.key .abc.* 类型的key
 * passive = false, 如果设置为true，存在则返回ok，否则就报错，设置false存在返回ok，不存在则自动创建
 * durable = false, 是否持久化，设置false是存放到内存当中的，rabbitmq 重启后会丢失
 * auto_delete = true, 是否自动删除，当最后一个消费者断开链接之后队列是否自动被删除
 */
$exchangeName = 'task_exchange';
$channel->exchange_declare($exchangeName,'direct',false,true,false);

/**
 * 4. 创建消费者队列
 * 非持久化队列,RabbitMQ退出或者崩溃时，该队列就不存在
 * 持久化队列（需要显示声明，第三个参数要设置为true），保存到磁盘，但不一定完全保证不丢失信息，因为保存总是要有时间的。
 * queue   : 队列名称
 * passive : 如果设置为true，存在则返回ok，否则就报错，设置false存在返回ok，不存在则自动创建
 * durable : 是否持久化，设置false是存放到内存当中的，rabbitmq 重启后会丢失
 * exclusive : 是否排他,指定该选项为true则队列只对当前链接有效，链接断开自动删除
 * nowait  ：  是否自删除
 * */
$queueName = 'task_queue';
$channel->queue_declare($queueName, false, true, false, false);

/**
 * 5. 绑定队列跟交换机
 * queue    ：队列名称
 * exchange ：交换机名称
 * routing_key ：路由key
 */
$routeName = 'task_route';
$channel->queue_bind($queueName,$exchangeName,$routeName);


# 4. 创建要发送的信息 ，可以创建多个消息
/*
 * $message  string类型 要发送的消息
 * $properties array类型 设置的属性，比如设置该消息持久化[‘delivery_mode’=>2]
 * AMQPMessage 参数说明：
 *      例如 AMQPMessage($message); 没有第二个参数的时候，当rebbitmq服务挂了，可以重启rabbitmq服务，这个时候aaa队列的消息就会清空
 *      当 AMQPMessage($message,array('delivery_mode'=>AMQPMESSAGE::DELIVERY_MODE_PERSISTENT)) 传递第二个参数的时候，当rebbitmq服务挂了，
 *      可以重启rabbitmq服务，这个时候 “aaa”队列的数据还是会存在
 * */

/**
 * 5. 发送消息
 * body ：string类型，要发送的消息
 * properties ：array类型 ，比如设置该消息持久化[‘delivery_mode’=>2]
 *      例如 AMQPMessage($message); 没有第二个参数的时候，当rebbitmq服务挂了，可以重启rabbitmq服务，这个时候aaa队列的消息就会清空
 *      当 AMQPMessage($message,array('delivery_mode'=>AMQPMESSAGE::DELIVERY_MODE_PERSISTENT))
 *      传递第二个参数的时候，当rebbit，可以重启rabbitmq服务，这个时候 “aaa”队列的数据还是会存在
 */
# 发送的消息
$data = [
    'id' => uniqid(),
    'create_time' => time(),
    'message' => '我是生产者数据'
];
//$msg = new AMQPMessage($message,array('delivery_mode'=>AMQPMESSAGE::DELIVERY_MODE_PERSISTENT));
$msg = new AMQPMessage(json_encode($data,JSON_UNESCAPED_UNICODE));

$channel->basic_publish($msg, $exchangeName, $routeName);


echo " end 已发送\n";

/*******开启回调start********/
# 只开启一个消费者可以使用，否则大于一个 确认会发送到另外一个消费者队列中
# 异步回调消息确认
$channel->set_ack_handler(
    function (AMQPMessage $message) {
        echo '消费者已经消费：' . $message->body . PHP_EOL;
    }
);
# 异步回调,消息丢失处理
$channel->set_nack_handler(
    function (AMQPMessage $message) {
        echo '消息丢失' . $message->body . PHP_EOL;
    }
);

#开启消息确认 ,
$channel->confirm_select();
for ($i = 0; $i < 1; $i++) {
    $pushData = "确认";
    $msg = new AMQPMessage($pushData);
    $channel->basic_publish($msg, '', $queueName);
}
//阻塞等待消息确认 监听成功或失败返回结束
$channel->wait_for_pending_acks();
/*******开启回调end********/

# 6. 关闭信道和链接
$channel->close();
$connection->close();



function dd($data) {
    echo "<pre>";
    print_r($data);
    exit;
}