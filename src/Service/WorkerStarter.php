<?php

namespace Saw\Service;

use Saw\Entity\Worker;

/**
 * Сервис, организующий запуск воркера.
 */
final class WorkerStarter
{
    private $executor;
    private $cmd;
    private $pidFile;

    /**
     * ControllerStarter constructor.
     * @param Executor $executor
     * @param string $cmd
     * @internal param Client $client
     */
    public function __construct(Executor $executor, string $cmd)
    {
        $this->executor = $executor;
        $this->cmd = $cmd;
    }

    public function start(): Worker
    {
        $pid = $this->executor->exec($this->cmd);
        return new Worker($pid);
    }
}
