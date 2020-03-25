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

use Swoole\Database\MysqliConfig;
use Swoole\Database\MysqliPool;

class MySQLi
{
    protected $pools;

    /**
     * @var array
     */
    protected $config = [
        'drive' => 'mysqli',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'test',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
    ];

    private static $instance;

    public function __construct(array $config)
    {
        if (empty($this->pools)) {
            $this->config = array_replace_recursive($this->config, $config);
            $this->pools = new MysqliPool(
                (new MysqliConfig())
                    ->withHost($this->config['host'])
                    ->withPort($this->config['port'])
                    ->withDbName($this->config['database'])
                    ->withCharset($this->config['charset'])
                    ->withUsername($this->config['username'])
                    ->withPassword($this->config['password'])
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
                throw new \RuntimeException('mysqli config empty');
            }
            self::$instance = new static($config);
        }

        return self::$instance;
    }

    public function getConnection()
    {
        $mysqli = $this->pools->get();
        \Swoole\Coroutine::defer(function () use ($mysqli) {
            $this->close($mysqli);
        });
        return $mysqli;
    }

    public function close($connection = null)
    {
        $this->pools->put($connection);
    }
}
