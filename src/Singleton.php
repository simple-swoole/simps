<?php
/**
 * User: lufei
 * Date: 2020/3/24
 * Email: lufei@swoole.com
 */

namespace Simps;

trait Singleton
{
    private static $instance;

    static function getInstance(...$args)
    {
        if(!isset(self::$instance)){
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }
}