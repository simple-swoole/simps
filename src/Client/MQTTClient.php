<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace Simps\Client;

use Simps\Exception\Protocol\MQTTException;
use Simps\Server\Protocol\MQTT;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

class MQTTClient
{
    private $client;

    private $config = [
        'host' => '127.0.0.1',
        'port' => 1883,
        'time_out' => 0.5,
        'username' => '',
        'password' => '',
        'client_id' => '',
        'keepalive' => 0,
        'debug' => false,
    ];

    private $msgId = 0;

    /**
     * MQTTClient constructor.
     *
     * @throws \Exception
     */
    public function __construct(array $config, array $swConfig = [])
    {
        $this->config = array_replace_recursive($this->config, $config);
        $this->client = new Client(SWOOLE_SOCK_TCP);
        if (! empty($swConfig)) {
            $this->client->set($swConfig);
        }
        if (! $this->client->connect($this->config['host'], $this->config['port'], $this->config['time_out'])) {
            //尝试重连
            $this->reConnect();
        }
    }

    /**
     * 连接.
     *
     * @param bool $clean 是否清除会话
     * @param array $will 遗嘱消息
     * @return mixed
     */
    public function connect(bool $clean = true, array $will = [])
    {
        $data = [
            'cmd' => MQTT::CONNECT, // 1
            'protocol_name' => 'MQTT',
            'protocol_level' => 4,
            'clean_session' => $clean ? 0 : 1,
            'client_id' => $this->config['client_id'],
            'keepalive' => $this->config['keepalive'] ?? 0,
        ];
        if (isset($this->config['username'])) {
            $data['username'] = $this->config['username'];
        }
        if (isset($this->config['password'])) {
            $data['password'] = $this->config['password'];
        }
        if (! empty($will)) {
            $data['will'] = $will;
        }
        return $this->sendBuffer($data);
    }

    /**
     * 订阅主题.
     *
     * @param array $topics 主题列表
     * @return mixed
     */
    public function subscribe(array $topics)
    {
        $data = [
            'cmd' => MQTT::SUBSCRIBE, // 8
            'message_id' => $this->getMsgId(),
            'topics' => $topics,
        ];
        return $this->sendBuffer($data);
    }

    /**
     * 取消订阅主题.
     *
     * @param array $topics 主题列表
     * @return mixed
     */
    public function unSubscribe(array $topics)
    {
        $data = [
            'cmd' => MQTT::UNSUBSCRIBE, // 10
            'message_id' => $this->getMsgId(),
            'topics' => $topics,
        ];
        return $this->sendBuffer($data);
    }

    /**
     * 客户端发布消息.
     *
     * @param string $topic 主题
     * @param string $content 消息内容
     * @param int $qos 服务质量等级
     * @param int $dup
     * @param int $retain 保留标志
     * @return mixed
     */
    public function publish($topic, $content, $qos = 0, $dup = 0, $retain = 0)
    {
        $response = ($qos > 0) ? true : false;
        return $this->sendBuffer([
            'cmd' => MQTT::PUBLISH, // 3
            'message_id' => $this->getMsgId(),
            'topic' => $topic,
            'content' => $content,
            'qos' => $qos,
            'dup' => $dup,
            'retain' => $retain,
        ], $response);
    }

    /**
     * 接收订阅的消息.
     *
     * @return array|bool|string
     */
    public function recv()
    {
        $response = $this->client->recv();
        if (strlen($response) > 0) {
            return MQTT::decode($response);
        }
        if ($response === '') {
            $this->reConnect();
        } elseif ($response === false) {
            if ($this->client->errCode !== SOCKET_ETIMEDOUT) {
                throw new MQTTException($this->client->errMsg, $this->client->errCode);
            }
        }

        return true;
    }

    /**
     * 发送心跳包.
     *
     * @return mixed
     */
    public function ping()
    {
        return $this->sendBuffer(['cmd' => MQTT::PINGREQ]); // 12
    }

    /**
     * 断开连接.
     *
     * @return mixed
     */
    public function close()
    {
        $this->sendBuffer(['cmd' => MQTT::DISCONNECT], false); // 14
        return $this->client->close();
    }

    /**
     * 获取当前消息id条数.
     *
     * @return int
     */
    public function getMsgId()
    {
        return ++$this->msgId;
    }

    /**
     * 发送数据信息.
     *
     * @param array $data
     * @param bool $response 需要响应
     * @return mixed
     */
    public function sendBuffer($data, $response = true)
    {
        $buffer = MQTT::encode($data);
        $this->client->send($buffer);
        if ($response) {
            $response = $this->client->recv();
            if ($this->config['debug']) {
                MQTT::printStr($response);
            }
            return MQTT::decode($response);
        }
        return true;
    }

    /**
     * 重连.
     */
    private function reConnect()
    {
        $reConnectTime = 1;
        $result = false;
        while (! $result) {
            Coroutine::sleep(3);
            $this->client->close();
            $result = $this->client->connect($this->config['host'], $this->config['port'], $this->config['time_out']);
            ++$reConnectTime;
        }
        $this->connect();
    }
}
