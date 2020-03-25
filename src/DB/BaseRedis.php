<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://swoole.com
 * @document https://wiki.swoole.com
 * @license  https://github.com/sy-records/simps/blob/master/LICENSE
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
