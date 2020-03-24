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

use Swoole\Coroutine;

trait CoroutineSingleTon
{
    private static $instance = [];

    public static function getInstance(...$args)
    {
        $cid = Coroutine::getCid();
        if (! isset(self::$instance[$cid])) {
            self::$instance[$cid] = new static(...$args);
            if ($cid > 0) {
                Coroutine::defer(
                    function () use ($cid) {
                        unset(self::$instance[$cid]);
                    }
                );
            }
        }
        return self::$instance[$cid];
    }

    public function destroy(int $cid = null)
    {
        if ($cid === null) {
            $cid = Coroutine::getCid();
        }
        unset(self::$instance[$cid]);
    }
}
