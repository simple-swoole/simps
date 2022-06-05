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

class StatusCommand extends \Simps\Console\Command
{
    protected $command = 'status';

    protected $help = 'Show all services list';

    public function handle()
    {
        $config = config('servers', []);
        foreach ($config as $ser => $v) {
            $status = $this->getServerStatus($ser) ? 'runtime' : 'stop';
            Application::echoSuccess("Service `{$ser}` {$status}");
        }
    }

    /**
     * @param string $ser
     * @return bool|mixed
     */
    protected function getServerStatus($ser)
    {
        $pid = (int) @file_get_contents(sprintf($this->pid, $ser));
        if ($pid > 0) {
            return \Swoole\Process::kill($pid, 0);
        }
        return false;
    }
}
