<?php

namespace Saw\Standalone;

use Esockets\Client;
use Saw\Application\ApplicationContainer;
use Saw\Command\CommandHandler;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Service\ApplicationLoader;
use Saw\Service\CommandDispatcher;
use Saw\Standalone\Controller\CycleInterface;
use Saw\Thread\AbstractThread;
use Saw\Thread\ControlledThread;
use Saw\Thread\Pool\RunnableThreadPool;
use Saw\Thread\StubThread;

/**
 * Ядро воркера.
 * Само по себе нужно только для изоляции приложения.
 */
final class WorkerCore implements CycleInterface
{
    private $client;
    private $commandDispatcher;
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
        $this->commandDispatcher = $commandDispatcher;
        $this->applicationContainer = $applicationContainer;

        $this->threadPool = new RunnableThreadPool();

        $commandDispatcher->add([
            new CommandHandler(
                ThreadRun::NAME,
                ThreadRun::class,
                function (ThreadRun $context) {
                    // выполняем задачу
                    $thread = (new StubThread(
                        $context->getRunId(),
                        $context->getApplicationId(),
                        $context->getUniqueId()
                    ))->setArguments($context->getArguments());
                    $this->threadPool->add($thread);
                }
            ),
            new CommandHandler(ThreadResult::NAME, ThreadResult::class),
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
        foreach ($this->threadPool as $thread) {
            /**
             * @var $thread AbstractThread
             */
            if ($thread->getCurrentState() === AbstractThread::STATE_NEW) {
                $this->applicationContainer->switchTo($this->applicationContainer->get($thread->getApplicationId()));
                $thread = $this->applicationContainer
                    ->getThreadPools()
                    ->getCurrentPool()
                    ->getThreadById($thread->getUniqueId())
                    ->setArguments($thread->getArguments())
                    ->run();
                $this->commandDispatcher
                    ->create(ThreadResult::NAME, $this->client)
                    ->onError(function () {
                        // todo
                        throw new \RuntimeException('Cannot run tres command.');
                    })
                    ->onSuccess(function () use ($thread) {
                        $this->threadPool->getThreadById($thread->getId());
                    })
                    ->run(ThreadResult::serializeTask($thread));
            }
        }
    }
}
