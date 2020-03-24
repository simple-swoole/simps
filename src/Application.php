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

    public static function println($strings)
    {
        echo $strings . PHP_EOL;
    }

    public static function echoSuccess($msg)
    {
        self::println("[" . date("Y-m-d H:i:s") . "] [INFO] " . "\033[32m{$msg}\033[0m");
    }

    public static function echoError($msg)
    {
        self::println("[" . date("Y-m-d H:i:s") . "] [ERROR] " . "\033[31m{$msg}\033[0m");
    }

    public static function run()
    {
        self::welcome();
        global $argv;
        $count = count($argv);
        $funcName = $argv[$count - 1];
        $command = explode(":", $funcName);
        switch ($command[0]) {
            case 'http':
                $className = \Simps\Server\HTTP::class;
                break;
            case 'ws':
                $className = \Simps\Server\WebSocket::class;
                break;
            default:
                exit(self::echoError("use $argv[0] [http:start, ws:start]"));
        }
        switch ($command[1]) {
            case 'start':
                new $className;
                break;
            default:
                self::echoError("use $argv[0] [http:start, ws:start]");
        }
    }
}
