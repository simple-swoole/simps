<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */

namespace Simps\DB;

use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

class Redis
{
    protected $pools;

    protected $config = [
        'host' => 'localhost',
        'port' => 6379,
        'auth' => '',
        'db_index' => 0,
        'time_out' => 1,
    ];

    private static $instance;

    public function __construct(array $config)
    {
        if (empty($this->pools)) {
            $this->config = array_replace_recursive($this->config, $config);
            $this->pools = new RedisPool(
                (new RedisConfig())
                    ->withHost($this->config['host'])
                    ->withPort($this->config['port'])
                    ->withAuth($this->config['auth'])
                    ->withDbIndex($this->config['db_index'])
                    ->withTimeout($this->config['time_out'])
            );
        }
    }

    public function __call($name, $arguments)
    {
        return $this->getConnection()->{$name}(...$arguments);
    }

    public static function getInstance($config = null)
    {
        if (empty(self::$instance)) {
            if (empty($config)) {
                throw new \RuntimeException('redis config empty');
            }
            self::$instance = new static($config);
        }

        return self::$instance;
    }

    public function getConnection()
    {
        $redis = $this->pools->get();
        \Swoole\Coroutine::defer(function () use ($redis) {
            $this->close($redis);
        });
        return $redis;
    }

    public function close($connection = null)
    {
        $this->pools->put($connection);
    }
}
