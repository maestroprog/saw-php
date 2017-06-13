<?php

namespace Saw\Standalone;

use Esockets\Server;
use Saw\Command\AbstractCommand;
use Saw\Command\CommandHandler as EntityCommand;
use Saw\Command\ThreadKnow;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Command\WorkerAdd;
use Saw\Command\WorkerDelete;
use Saw\Config\ControllerConfig;
use Saw\Service\CommandDispatcher;
use Saw\Service\WorkerStarter;
use Saw\Standalone\Controller\CycleInterface;
use Saw\Standalone\Controller\ThreadDistributor;
use Saw\Standalone\Controller\WorkerBalance;

/**
 * Ядро контроллера.
 * В обязанности ядра входит управление
 * подсистемами воркеров, разделяемой памяти, и прочее.
 */
final class ControllerCore implements CycleInterface
{
    private $server;
    private $commandDispatcher;

    private $workerBalance;
    private $threadDistributor;

    public function __construct(
        Server $server,
        CommandDispatcher $commandDispatcher,
        WorkerStarter $workerStarter,
        ControllerConfig $config
    )
    {
        $this->server = $server;
        $this->commandDispatcher = $commandDispatcher;

        $this->workerBalance = new WorkerBalance($workerStarter, $config->getWorkerMaxCount());
        $this->threadDistributor = new ThreadDistributor();

        $commandDispatcher->add([
            new EntityCommand(
                WorkerAdd::NAME,
                WorkerAdd::class,
                function (AbstractCommand $context) {
                    return $this->workerBalance->addWorker((int)$context->getPeer()->getConnectionResource()->getResource());
                }
            ),
            new EntityCommand(
                WorkerDelete::NAME,
                WorkerDelete::class,
                function (AbstractCommand $context) {
                    $this->workerBalance->removeWorker((int)$context->getPeer()->getConnectionResource()->getResource());
                }
            ),
            new EntityCommand(
                ThreadKnow::NAME,
                ThreadKnow::class,
                function (AbstractCommand $context) {
                    $this->threadDistributor->tAdd((int)$context->getPeer()->getConnectionResource()->getResource(), $context->getData()['name']);
                }
            ),
            new EntityCommand(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {
                    $this->threadDistributor->tRun($context->getRunId(), (int)$context->getPeer()->getConnectionResource()->getResource(), $context->getName());
                }
            ),
            new EntityCommand(
                ThreadResult::NAME,
                ThreadResult::class,
                function (ThreadResult $context) {
                    $this->threadDistributor->tRes(
                        $context->getRunId(),
                        (int)$context->getPeer()->getConnectionResource()->getResource(),
                        $context->getFromDsc(),
                        $context->getResult()
                    );
                }
            ),
        ]);
    }

    public function work()
    {
        $this->workerBalance->work();
        $this->threadDistributor->work();
    }
}
