<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace Simps\Server;

use Simps\Application;
use Simps\Listener;
use Simps\Server\Protocol\MQTT;
use Swoole\Server;

class MqttServer
{
    protected $_server;

    protected $_config;

    /**
     * Server constructor.
     */
    public function __construct()
    {
        $config = config('servers');
        $mqttConfig = $config['mqtt'];
        $this->_config = $mqttConfig;
        $this->_server = new Server($mqttConfig['ip'], $mqttConfig['port'], $config['mode'], $mqttConfig['sock_type'] ?? SWOOLE_SOCK_TCP);
        $this->_server->set($mqttConfig['settings']);

        $this->_server->on('Start', [$this, 'onStart']);
        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        $this->_server->on('Receive', [$this, 'onReceive']);
        foreach ($mqttConfig['callbacks'] as $eventKey => $callbackItem) {
            [$class, $func] = $callbackItem;
            $this->_server->on($eventKey, [$class, $func]);
        }
        $this->_server->start();
    }

    public function onStart($server)
    {
        Application::echoSuccess("Swoole MQTT Server running：mqtt://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('start', $server);
    }

    public function onWorkerStart(Server $server, int $workerId)
    {
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }

    public function onReceive($server, $fd, $fromId, $data)
    {
        try {
            $data = MQTT::decode($data);
            if (is_array($data) && isset($data['cmd'])) {
                switch ($data['cmd']) {
                    case MQTT::PINGREQ: // 心跳请求
                        [$class, $func] = $this->_config['receiveCallbacks'][MQTT::PINGREQ];
                        $obj = new $class();
                        if ($obj->{$func}($server, $fd, $fromId, $data)) {
                            // 返回心跳响应
                            $server->send($fd, MQTT::getAck(['cmd' => 13]));
                        }
                        break;
                    case MQTT::DISCONNECT: // 客户端断开连接
                        [$class, $func] = $this->_config['receiveCallbacks'][MQTT::DISCONNECT];
                        $obj = new $class();
                        if ($obj->{$func}($server, $fd, $fromId, $data)) {
                            if ($server->exist($fd)) {
                                $server->close($fd);
                            }
                        }
                        break;
                    case MQTT::CONNECT: // 连接
                    case MQTT::PUBLISH: // 发布消息
                    case MQTT::SUBSCRIBE: // 订阅
                    case MQTT::UNSUBSCRIBE: // 取消订阅
                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                }
            } else {
                $server->close($fd);
            }
        } catch (\Throwable $e) {
            $server->close($fd);
        }
    }
}
