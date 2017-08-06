<?php

namespace Maestroprog\Saw\Service;

use Maestroprog\Saw\ValueObject\ProcessStatus;

/**
 * Сервис, организующий запуск воркера.
 */
class WorkerStarter
{
    private $executor;
    private $cmd;

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

    /**
     * Запускает воркер.
     * Вернёт объект @see ProcessStatus.
     *
     * @return ProcessStatus
     */
    public function start(): ProcessStatus
    {
        return $this->executor->exec($this->cmd);
    }
}
