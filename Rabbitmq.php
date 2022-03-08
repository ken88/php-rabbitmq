<?php
/**
 * rabbitmq 操作类
 * Created by PhpStorm.
 * User: IT-yukai
 * Date: 2020/11/13
 * Time: 14:02
 */

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ
{

    # 内部通用变量
    public $_conn = null;

    public static function init() {
        # 1. 创建连接

        $host = "172.17.0.5"; # RabbitMQ服务器主机IP地址
        $port = 5672;           # RabbitMQ服务器端口
        $user = "guest";   # 连接RabbitMQ服务器的用户名
        $password = "guest";      # 连接RabbitMQ服务器的用户密码
//      $vhost = "";          # 连接RabbitMQ服务器的vhost（服务器可以有多个vhost，虚拟主机，类似nginx的vhost）

        $connection = new AMQPStreamConnection($host, $port, $user, $password);

        if (!$connection->isConnected()) {
            echo "链接错误 \n ";
            exit(0);
        }

        return $connection;

//        echo " 链接成功 ".PHP_EOL;
        # 2. 获取信道
//        $this->_channel = $this->_conn->channel();

    }

    public static function AMQPMessage($message) {
        return new AMQPMessage();
    }


    /**
     * 创建队列（为了保证正常订阅，避免消息丢失，生产者和消费则都要尝试创建队列：交换机和队列通过路由绑定一起）
     * @param string $exchange_name 交换机名字
     * @param string $route_key 路由名字
     * @param string $queue_name 队列名字
     */
    public function create_queue($exchange_name='', $route_key = '', $queue_name = '') {
        if ($exchange_name != '') {
            $this->exchange_name = $exchange_name;  # 更新交换机名称
            $this->queue_name = $exchange_name;     # 更新队列名称
        }

        if ($route_key != '') $this->route_key = $route_key;    # 更新路由
        if ($queue_name != '') $this->queue_name = $queue_name; # 独立更新队列名称

        # 创建exchange交换机
        $this->_exchange = $this->_channel->exchange_declare($this->exchange_name,'direct',false,false,false);

        # 创建队列
        $this->_queue = $this->_channel->queue_declare($this->queue_name, false, false, false, false);
        $this->_channel->queue_bind($this->queue_name,$this->exchange_name,$this->route_key);

    }

    /**
     * 发送信息
     * @param $msg  发送内容
     * @param string $exchange_name  交换机名字
     * @param string $route_key  路由
     * @param string $queue_name 队列名字
     */
    public function send($msg, $exchange_name = '', $route_key = '', $queue_name = '') {
        $this->create_queue($exchange_name, $route_key, $queue_name); # 创建exchange和queue根据route_key绑定一起
        if (is_array($msg)) {
            $msg = json_encode($msg);   # 将数组类型转换成JSON格式
        }else {
            $msg = trim(strval($msg));  # 简单处理一下要发送的消息内容
        }
        $message = new AMQPMessage($msg);
        $this->_channel->basic_publish($message, $this->exchange_name,$this->route_key);

        echo " Sent 发送消息： {$msg}\n";

        $this->_channel->close();
        $this->_conn->close();
    }
}