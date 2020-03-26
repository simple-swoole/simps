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

class BaseModel
{
    protected $pool;
    /** @var \PDO | \mysqli */
    protected $connection;

    private $drive;

    public function __construct()
    {
        $config = config('database', []);
        if (! empty($config)) {
            $this->drive = $config['drive'];
            switch ($config['drive']) {
                case 'mysqli':
                    $class = MySQLi::class;
                    break;
                case 'pdo':
                    $class = PDO::class;
                    break;
            }
        }
        $this->pool = getInstance($class);
        $this->connection = $this->pool->getConnection();
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }

    public function beginTransaction()
    {
        if ($this->drive == "pdo") {
            $this->connection->beginTransaction();
        } else {
            $this->connection->autocommit(false);
        }
        $this->connection->is_transaction = true;
    }

    public function commit()
    {
        $this->connection->commit();
        if ($this->drive == "mysqli") {
            $this->connection->autocommit(true);
        }
        $this->connection->is_transaction = false;
    }

    public function rollBack()
    {
        $this->connection->rollBack();
        if ($this->drive == "mysqli") {
            $this->connection->autocommit(true);
        }
        $this->connection->is_transaction = false;
    }
}
