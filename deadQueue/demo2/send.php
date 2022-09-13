<?php
/**
 * 死信1.当消息被拒绝 生产者
 * direct  （直连模式）  也叫（路由模式）
 * 生产者 direct  直连交换器
 * 注释：direct Exchange – 处理路由键。需要将一个队列绑定到交换机上，要求该消息与一个特定的路由键完全匹配。
    消费者 下的交换机 与路由 必须要与 生产者一值，才可以接收到消息，否则任意一个不同，都接收不到消息
    例如：生产者 交换机叫excheng1 绑定的路由key 是orange
    那么消费者必须要 声明 交换机叫excheng1 ，路由key是orange才可以接收到消息
 */
require_once '../../vendor/autoload.php';

require_once  '../../Rabbitmq.php';

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
$exchangeName = 'task_exchange1';
$channel->exchange_declare($exchangeName,'direct',false,true,false);

/**
 * 4. 创建消费者队列
 * 非持久化队列,RabbitMQ退出或者崩溃时，该队列就不存在
 * 持久化队列（需要显示声明，第三个参数要设置为true），保存到磁盘，但不一定完全保证不丢失信息，因为保存总是要有时间的。
 * queue   : 队列名称
 * passive : 如果设置为true，存在则返回ok，否则就报错，设置false存在返回ok，不存在则自动创建
 * durable : 是否持久化，设置false是存放到内存当中的，rabbitmq 重启后会丢失
 * exclusive : 是否排他,指定该选项为true则队列只对当前链接有效，链接断开自动删除
 * auto_delete: 是否自删除
 * nowait  ：
 * */
$queueName = 'task_queue1';
// 声明业务队列的死信交换机
$args = new \PhpAmqpLib\Wire\AMQPTable();
$args->set('x-dead-letter-exchange', 'dl_exchange');  # 配置死信交换机
$args->set('x-dead-letter-routing-key', 'dl_route_key'); # 配置 Routing Key，路由到 dl_exchange
$channel->queue_declare($queueName, false, false, false, false,false,$args);

/**
 * 5. 绑定队列跟交换机
 * queue    ：队列名称
 * exchange ：交换机名称
 * routing_key ：路由key
 */
$routeName = 'task_route1';
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
    'message' => '我是生产者数据3'
];
//$msg = new AMQPMessage($message,array('delivery_mode'=>AMQPMESSAGE::DELIVERY_MODE_PERSISTENT));
$msg = new AMQPMessage(json_encode($data,JSON_UNESCAPED_UNICODE));

$channel->basic_publish($msg, $exchangeName, $routeName);


echo " end 已发送\n";


# 6. 关闭信道和链接
$channel->close();
$connection->close();



function dd($data) {
    echo "<pre>";
    print_r($data);
    exit;
}