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
use Swoole\Http\Server;

class HTTP
{
    protected $_server;

    protected $_config;

    /** @var \Simps\Route $_route */
    protected $_route;

    public function __construct()
    {
        $config = config('servers');
        $httpConfig = $config['http'];
        $this->_config = $httpConfig;
        $this->_server = new Server($httpConfig['ip'], $httpConfig['port'], $config['mode'], $httpConfig['sock_type']);
        $this->_server->set($httpConfig['settings']);

        $this->_server->on('start', [$this, 'onStart']);
        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        $this->_server->on('request', [$this, 'onRequest']);
        foreach ($httpConfig['callbacks'] as $eventKey => $callbackItem) {
            list($class, $func) = $callbackItem;
            $this->_server->on($eventKey, [$class, $func]);
        }
        $this->_server->start();
    }

    public function onStart(\Swoole\Server $server)
    {
        Application::echoSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('start', $server);
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId)
    {
        $this->_route = Route::getInstance();
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $this->_route->dispatch($request, $response);
    }
}
