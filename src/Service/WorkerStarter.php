<?php

namespace Saw\Service;

use Esockets\base\AbstractAddress;
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
     * @param string $pidFile
     * @internal param Client $client
     */
    public function __construct(Executor $executor, string $cmd, string $pidFile)
    {
        $this->executor = $executor;
        $this->cmd = $cmd;
        $this->pidFile = $pidFile;
    }

    public function start(AbstractAddress $address): Worker
    {
        $pid = $this->executor->exec($this->cmd);
        if (false === file_put_contents($this->pidFile, $pid)) {
            throw new \RuntimeException('Cannot save the pid in pid file.');
        }
        return new Worker($pid);
    }
}
