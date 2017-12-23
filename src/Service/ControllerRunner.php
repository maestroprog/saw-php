<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\ValueObject\ProcessStatus;

class ControllerRunner
{
    private $executor;
    private $cmd;

    public function __construct(Executor $executor, string $cmd)
    {
        $this->executor = $executor;
        $this->cmd = $cmd;
    }

    /**
     * @throws \RuntimeException
     */
    public function start(): ProcessStatus
    {
        return $this->executor->exec($this->cmd);
    }
}
