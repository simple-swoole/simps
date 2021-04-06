<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace Simps\Utils;

use Swoole\Coroutine;

class Container
{
    /** @var Container */
    protected static $instance;

    /** @var array */
    protected $singletons;

    /** @var array */
    protected $scope;

    public static function instance()
    {
        if (! static::$instance) {
            static::$instance = new Container();
        }
        return static::$instance;
    }

    /**
     * 获取协程上下文.
     * @return null|Container
     */
    public function scope()
    {
        $uid = Coroutine::getuid();
        if ($uid < 0) {
            return null;
        }
        if (! isset($this->scope[$uid])) {
            $this->scope[$uid] = new Container();
        }
        return $this->scope[$uid];
    }

    /**
     * 回收协程上下文.
     */
    public function deleteScope()
    {
        $uid = Coroutine::getuid();
        unset($this->scope[$uid]);
    }

    /*
     * @method singleton("key", new App\Controller)
     * @method singleton("key", App\Controller::class)
     * @method singleton("key", function() { return new Object; })
     */
    public function set($key, $object)
    {
        if (is_callable($object)) {
            return $this->singletons[$key] = $object();
        }
        if (is_object($object)) {
            return $this->singletons[$key] = $object;
        }
        return $this->singletons[$key] = $this->singleton($object);
    }

    public function has(string $id)
    {
        return isset($this->singletons[$id]);
    }

    public function get(string $id)
    {
        return $this->singletons[$id] ?? null;
    }

    /*
     * @method singleton(App\Controller::class, ...$args)
     * @method singleton(App\Controller::class, function() { return new Object; })
     * @method singleton(new App\Controller)
     */
    public function singleton($id, ...$args)
    {
        if (is_object($id)) {
            $this->singletons[get_class($id)] = $id;
            return $id;
        }
        if (! isset($this->singletons[$id])) {
            if (is_callable($args[0] ?? null)) {
                $this->singletons[$id] = $args[0]();
            } else {
                $this->singletons[$id] = new $id(...$args);
            }
        }
        return $this->singletons[$id];
    }

    /*
     * @method call("App\Controller@index", ...$args)
     * @method call(App\Controller::class, ...$args)
     * @method call(new App\Controller, ...$args)
     */
    public function call($id, ...$args)
    {
        if (is_string($id)) {
            if (strpos($id, '@') !== false) {
                [$id, $action] = explode('@', $id);
            } else {
                $action = 'handle';
            }
            $instance = $this->singleton($id);
        } else {
            $instance = $id;
            $action = 'handle';
        }
        return $instance->{$action}(...$args);
    }

    /*
     * @method injectionProperty(new Object, [ key => val ])
     * @method injectionProperty(App\Controller::class, [ key => val ])
     */
    public function injectionProperty($id, $args)
    {
        if (! is_object($id)) {
            $id = $this->get($id);
        }
        if (method_exists($id, '_property_injection_')) {
            $id->_property_injection_($args);
        }
    }
}
