<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
if (! function_exists('getInstance')) {
    function getInstance($class)
    {
        return ($class)::getInstance();
    }
}
if (! function_exists('config')) {
    function config($name, $default = null)
    {
        return getInstance('\Simps\Config')->get($name, $default);
    }
}
if (! function_exists('container')) {
    function container($key = null, $value = null)
    {
        if ($key == null) {
            return \Simps\Utils\Container::instance();
        }
        if ($value == null) {
            return \Simps\Utils\Container::instance()->singleton($key);
        }
        return \Simps\Utils\Container::instance()->set($key, $value);
    }
}
if (! function_exists('collection')) {
    function collection($data = [])
    {
        return new \Simps\Utils\Collection($data);
    }
}
