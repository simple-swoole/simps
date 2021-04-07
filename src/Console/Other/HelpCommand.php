<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace Simps\Console\Other;

use Simps\Console\Command;

class HelpCommand extends Command
{
    protected $command = 'help';

    protected $help = 'Show all commands';

    public function handle()
    {
        $commands = [];
        $list = $this->app->getConsole();
        foreach ($list as $cls) {
            $obj = new $cls($this->app);
            if ($obj->getShow()) {
                $k = $obj->getCommand();
                $v = $obj->getHelp();
                $commands[$k] = $v;
            }
        }
        foreach ($commands as $command => $help) {
            echo "{$command}\t\t`{$help}`\n";
        }
    }
}
