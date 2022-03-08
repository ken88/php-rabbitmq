<?php
//require_once __DIR__ . '/vendor/autoload.php';
//
//
//# 1. 服务器的连接
//$host = "localhost"; # RabbitMQ服务器主机IP地址
//$port = 5672;        # RabbitMQ服务器端口
//$user = "guest";     # 连接RabbitMQ服务器的用户名
//$password = "guest"; # 连接RabbitMQ服务器的用户密码
//$vhost = "";         #连接RabbitMQ服务器的vhost（服务器可以有多个vhost，虚拟主机，类似nginx的vhost）
//配置信息
$conn_args = array(
    'host' => '172.17.0.5',
    'port' => '5672',
    'login' => 'guest',
    'password' => 'guest',
    'vhost'=>'/'
);
$e_name = 'e_linvo'; //交换机名
$q_name = 'q_linvo'; //队列名
$k_route = 'key_1'; //路由key

//创建连接和channel
$conn = new AMQPConnection($conn_args);
if (!$conn->connect()) {
    die("Cannot connect to the broker!\n");
}

# 创建通道
$channel = new AMQPChannel($conn);

//创建交换机
$ex = new AMQPExchange($channel);
$ex->setName($e_name);
$ex->setType(AMQP_EX_TYPE_DIRECT); //direct类型
$ex->setFlags(AMQP_DURABLE); //持久化
echo "Exchange Status:".$ex->declare()."\n";

//创建队列
$q = new AMQPQueue($channel);
$q->setName($q_name);
$q->setFlags(AMQP_DURABLE); //持久化
echo "Message Total:".$q->declare()."\n";

//绑定交换机与队列，并指定路由键
echo 'Queue Bind: '.$q->bind($e_name, $k_route)."\n";

//阻塞模式接收消息
echo "Message:\n";
while(True){
    $q->consume('processMessage');
    //$q->consume('processMessage', AMQP_AUTOACK); //自动ACK应答
}
$conn->disconnect();

/**
 * 消费回调函数
 * 处理消息
 */
function processMessage($envelope, $queue) {
    $msg = $envelope->getBody();
    echo $msg."\n"; //处理消息
    $queue->ack($envelope->getDeliveryTag()); //手动发送ACK应答
}

$channel->close();
$connection->close();