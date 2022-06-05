<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace Simps\Console;

use Simps\Application;

abstract class Command
{
    public $pid = BASE_PATH . '/runtime/%s.pid';

    /** @var Application */
    protected $app;

    protected $help = 'No description';

    protected $command = false;

    protected $params = [];

    protected $show = true;

    protected $coroutine = false;

    public function __construct(Application $app, $params = [])
    {
        $this->app = $app;
        $this->params = $params;
    }

    public function getCoroutine()
    {
        return $this->coroutine;
    }

    /**
     * @return bool
     */
    public function getShow()
    {
        return $this->show;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * 运行命令.
     */
    abstract public function handle();
}
