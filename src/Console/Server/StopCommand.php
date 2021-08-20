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

use Simps\Console\Command;

class StopCommand extends Command
{
    protected $command = 'stop';

    protected $help = 'Close the specified service';

    public function handle()
    {
        $ser = $this->params[0] ?? 'http';
        $pid = (int) @file_get_contents(sprintf($this->pid, $ser));
        if ($pid > 0) {
            \Swoole\Process::kill($pid, SIGTERM);
        }
    }
}
