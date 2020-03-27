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

class BaseRedis
{
    protected $pool;

    protected $connection;

    public function __construct()
    {
        $config = config('redis', []);
        if (! empty($config)) {
            $this->pool = getInstance(Redis::class);
            $this->connection = $this->pool->getConnection();
        }
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }
}
