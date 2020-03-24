<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://swoole.com
 * @document https://wiki.swoole.com
 * @license  https://github.com/sy-records/simps/blob/master/LICENSE
 */

namespace Simps;

trait Singleton
{
    private static $instance;

    public static function getInstance(...$args)
    {
        if (! isset(self::$instance)) {
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }
}
