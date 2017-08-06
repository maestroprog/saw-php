<?php

namespace Maestroprog\Saw\Standalone;

use Esockets\Server;
use Maestroprog\Saw\Config\ControllerConfig;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\WorkerStarter;
use Maestroprog\Saw\Standalone\Controller\ControllerDebugger;
use Maestroprog\Saw\Standalone\Controller\CycleInterface;
use Maestroprog\Saw\Standalone\Controller\ThreadDistributor;
use Maestroprog\Saw\Standalone\Controller\WorkerBalance;
use Maestroprog\Saw\Standalone\Controller\WorkerPool;

/**
 * Ядро контроллера.
 * В обязанности ядра входит управление
 * подсистемами воркеров, разделяемой памяти, и прочее.
 */
final class ControllerCore implements CycleInterface
{
    private $server;
    private $commandDispatcher;

    private $workerPool;
    private $workerBalance;
    private $threadDistributor;
    private $debugger;

    public function __construct(
        Server $server,
        CommandDispatcher $commandDispatcher,
        WorkerStarter $workerStarter,
        ControllerConfig $config
    )
    {
        $this->server = $server;
        $this->commandDispatcher = $commandDispatcher;

        $this->workerPool = new WorkerPool();
        $this->workerBalance = new WorkerBalance(
            $workerStarter,
            $commandDispatcher,
            $this->workerPool,
            $config->getWorkerMaxCount()
        );
        $this->threadDistributor = new ThreadDistributor($commandDispatcher, $this->workerPool, $this->workerBalance);
        $this->debugger = new ControllerDebugger($commandDispatcher, $this->threadDistributor);

        $commandDispatcher->add([
        ]);
    }

    public function work()
    {
        $this->workerBalance->work();
        $this->threadDistributor->work();
    }
}
