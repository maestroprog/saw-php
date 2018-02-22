<?php

namespace Maestroprog\Saw\Standalone;

use Esockets\Server;
use Maestroprog\Saw\Config\ControllerConfig;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Service\ControllerRunner;
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
    private $commander;

    private $workerPool;
    private $workerBalance;
    private $threadDistributor;
    private $debugger;

    public function __construct(
        Server $server,
        CommandDispatcher $commandDispatcher,
        Commander $commander,
        WorkerStarter $workerStarter,
        ControllerConfig $config,
        ControllerRunner $runner
    )
    {
        $this->server = $server;
        $this->commandDispatcher = $commandDispatcher;
        $this->commander = $commander;

        $this->workerPool = new WorkerPool();
        $this->workerBalance = new WorkerBalance(
            $workerStarter,
            $commandDispatcher,
            $this->commander,
            $this->workerPool,
            $config->getWorkerMaxCount(),
            $config->getWorkerMultiplier()
        );
        $this->threadDistributor = new ThreadDistributor(
            $this->commandDispatcher,
            $this->commander,
            $this->workerPool,
            $this->workerBalance
        );
        $this->debugger = new ControllerDebugger(
            $commandDispatcher,
            $this->commander,
            $this->threadDistributor,
            $runner
        );
    }

    /**
     * @return \Generator
     * @throws \Exception
     */
    public function work(): \Generator
    {
        yield from $this->workerBalance->work();
        yield from $this->threadDistributor->work();
    }

    public function stop(): void
    {
        $workerBalance = $this->threadDistributor->getWorkerBalance();
        foreach ($this->threadDistributor->getWorkerPool() as $worker) {
            $workerBalance->removeWorker($worker);
        }
    }
}
