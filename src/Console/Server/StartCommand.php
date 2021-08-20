<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace Simps\Console\Server;

use Simps\Application;
use Simps\Console\Command;

class StartCommand extends Command
{
    protected $command = 'start';

    protected $help = 'Start the specified service';

    public function handle()
    {
        $ser = $this->params[0] ?? 'http';
        $met = 'start';

        // 检测服务是否已经启动
        $pid = (int) @file_get_contents(sprintf($this->pid, $ser));
        if ($pid > 0) {
            $r = \Swoole\Process::kill($pid, 0);
            if ($r) {
                Application::echoError("Service `{$ser}` is running");
                return;
            }
        }

        // 获取服务配置并启动
        $config = config('servers', []);
        $config = $config[$ser] ?? null;
        if (! $config) {
            Application::echoError('Service does not exist');
            exit;
        }
        $config['name'] = $ser;
        $cls = $config['provider'];
        $obj = new $cls($config);
        if (method_exists($obj, $met)) {
            $obj->{$met}();
        } else {
            Application::echoError('Service does not exist');
        }
    }
}
