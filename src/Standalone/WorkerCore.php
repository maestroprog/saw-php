<?php

namespace Saw\Standalone;

use Esockets\Client;
use Saw\Application\ApplicationContainer;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadRun;
use Saw\Service\ApplicationLoader;
use Saw\Service\CommandDispatcher;
use Saw\Standalone\Controller\CycleInterface;
use Saw\Thread\ControlledThread;
use Saw\Thread\Pool\WorkerThreadPool;

/**
 * Ядро воркера.
 * Само по себе нужно только для изоляции приложения.
 */
final class WorkerCore implements CycleInterface
{
    private $client;
    private $applicationContainer;

    private $threadPool;

    public function __construct(
        Client $peer,
        CommandDispatcher $commandDispatcher,
        ApplicationContainer $applicationContainer,
        ApplicationLoader $applicationLoader
    )
    {
        $this->client = $peer;
        $this->applicationContainer = $applicationContainer;

        $this->threadPool = new WorkerThreadPool();

        $commandDispatcher->add([
            new CommandHandler(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {
                    // выполняем задачу
                    $thread = new ControlledThread($context->getRunId(), $context->getUniqueId());
                    $thread->setArguments($context->getArguments());
                    $this->runTask($thread);
                }
            ),
        ]);
    }

    /**
     * Метод служит для запуска всех приложений внутри воркера.
     */
    public function run()
    {
        $this->applicationContainer->run(); // todo
    }

    public function work()
    {
    }
}
