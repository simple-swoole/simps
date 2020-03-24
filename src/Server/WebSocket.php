<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://swoole.com
 * @document https://wiki.swoole.com
 * @license  https://github.com/sy-records/simps/blob/master/LICENSE
 */

namespace Simps\Server;

use Simps\Application;
use Simps\Listener;
use Simps\Route;
use Swoole\WebSocket\Server;

class WebSocket
{
    protected $_server;

    protected $_config;

    /** @var \Simps\Route $_route */
    protected $_route;

    public function __construct()
    {
        $config = config('servers');
        $wsConfig = $config['ws'];
        $this->_config = $wsConfig;
        $this->_server = new Server($wsConfig['ip'], $wsConfig['port'], $config['mode']);
        $this->_server->set($wsConfig['setting']);

        $this->_server->on('start', [$this, 'onStart']);
        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        $this->_server->on('open', [$this, 'onOpen']);

        $this->_server->on('message', [$this, 'onmessage']);
//        $this->_server->on('handShake', [$this, 'onHandShake']);
        $this->_server->on('request', [$this, 'onRequest']);
        $this->_server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });
        $this->_server->start();
    }

    public function onStart(\Swoole\Server $server)
    {
        Application::welcome();
        echo "Swoole WebSocket Server running：ws://{$this->_config['ip']}:{$this->_config['port']}" . PHP_EOL;
        Listener::getInstance()->listen('start', $server);
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId)
    {
        $this->_route = Route::getInstance();
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }

    public function onOpen(Server $server, $request)
    {
        echo "server: handshake success with fd{$request->fd}\n";
    }

    public function onMessage(Server $server, $frame)
    {
        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        $server->push($frame->fd, 'this is server');
    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $this->_route->dispatch($request, $response);
    }
}
