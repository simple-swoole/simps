<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */

namespace Simps;

use Swoole\Coroutine;

class Context
{
    protected static $nonCoContext = [];

    public static function set(string $id, $value)
    {
        if (Context::inCoroutine()) {
            Coroutine::getContext()[$id] = $value;
        } else {
            static::$nonCoContext[$id] = $value;
        }
        return $value;
    }

    public static function get(string $id, $default = null, $coroutineId = null)
    {
        if (Context::inCoroutine()) {
            if ($coroutineId !== null) {
                return Coroutine::getContext($coroutineId)[$id] ?? $default;
            }
            return Coroutine::getContext()[$id] ?? $default;
        }

        return static::$nonCoContext[$id] ?? $default;
    }

    public static function has(string $id, $coroutineId = null)
    {
        if (Context::inCoroutine()) {
            if ($coroutineId !== null) {
                return isset(Coroutine::getContext($coroutineId)[$id]);
            }
            return isset(Coroutine::getContext()[$id]);
        }

        return isset(static::$nonCoContext[$id]);
    }

    /**
     * Release the context when you are not in coroutine environment.
     */
    public static function destroy(string $id)
    {
        unset(static::$nonCoContext[$id]);
    }

    /**
     * Retrieve the value and override it by closure.
     */
    public static function override(string $id, \Closure $closure)
    {
        $value = null;
        if (self::has($id)) {
            $value = self::get($id);
        }
        $value = $closure($value);
        self::set($id, $value);
        return $value;
    }

    public static function inCoroutine(): bool
    {
        return Coroutine::getCid() > 0;
    }
}
