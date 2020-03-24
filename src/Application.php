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

class Application
{
    /**
     * @var string
     */
    protected static $version = '1.0.0';

    public static function welcome()
    {
        $appVersion = self::$version;
        $swooleVersion = SWOOLE_VERSION;
        echo <<<EOL
  ____    _                           
 / ___|  (_)  _ __ ___    _ __    ___ 
 \\___ \\  | | | '_ ` _ \\  | '_ \\  / __|
  ___) | | | | | | | | | | |_) | \\__ \\
 |____/  |_| |_| |_| |_| | .__/  |___/
                         |_|           Version: {$appVersion}, Swoole: {$swooleVersion}


EOL;
    }
}
