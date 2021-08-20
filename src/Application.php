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

use Simps\Console\Command;
use Simps\Utils\Env;
use Swoole\Coroutine;

class Application
{
    /**
     * @var string
     */
    protected static $version = '2.0.0-dev';

    protected $console = [
        // Server相关
        \Simps\Console\Server\StatusCommand::class,
        \Simps\Console\Server\StartCommand::class,
        \Simps\Console\Server\StopCommand::class,
        \Simps\Console\Server\RestartCommand::class,

        // 其他命令
        \Simps\Console\Other\HelpCommand::class,
    ];

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
        self::println('[' . date('Y-m-d H:i:s') . '] [INFO] ' . "\033[32m{$msg}\033[0m");
    }

    public static function echoError($msg)
    {
        self::println('[' . date('Y-m-d H:i:s') . '] [ERROR] ' . "\033[31m{$msg}\033[0m");
    }

    /**
     * @return string[]
     */
    public function getConsole()
    {
        return $this->console;
    }

    public function start()
    {
        // 初始化环境变量
        container(Env::class);
        // 获取所有命令
        $this->console = array_merge($this->console, config('console', []));

        self::welcome();
        global $argv;
        $command = $argv[1] ?? 'help';
        $params = isset($argv[2]) ? array_slice($argv, 2) : [];
        foreach ($this->console as $cls) {
            $obj = new $cls($this, $params);
            if ($obj instanceof Command) {
                if ($command == $obj->getCommand()) {
                    if ($obj->getCoroutine()) {
                        Coroutine\run(function () use ($obj) {
                            $obj->handle();
                        });
                    } else {
                        $obj->handle();
                    }
                    return;
                }
            }
        }
        self::echoError("Command `{$command}` not found");
    }
}
