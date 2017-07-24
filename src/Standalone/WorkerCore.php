<?php

namespace Saw\Standalone;

use Esockets\Client;
use Saw\Application\ApplicationContainer;
use Saw\Command\CommandHandler;
use Saw\Command\DebugCommand;
use Saw\Command\DebugData;
use Saw\Command\ThreadResult;
use Saw\Command\ThreadRun;
use Saw\Heading\ReportSupportInterface;
use Saw\Service\ApplicationLoader;
use Saw\Service\CommandDispatcher;
use Saw\Standalone\Controller\CycleInterface;
use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\RunnableThreadPool;
use Saw\Thread\StubThread;

/**
 * Ядро воркера.
 * Само по себе нужно только для изоляции приложения.
 */
final class WorkerCore implements CycleInterface, ReportSupportInterface
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

        $apps = $applicationLoader->instanceAllApps();
        foreach ($apps as $app) {
            $this->applicationContainer->add($app);
        }

        $this->threadPool = new RunnableThreadPool();

        $commandDispatcher->add([
            new CommandHandler(
                ThreadRun::class, function (ThreadRun $context) {
                // выполняем задачу
                $thread = (new StubThread(
                    $context->getRunId(),
                    $context->getApplicationId(),
                    $context->getUniqueId()
                ))->setArguments($context->getArguments());
                $this->threadPool->add($thread);
            }),
            new CommandHandler(DebugCommand::class, function (DebugCommand $context) {
                $this->commandDispatcher->create(DebugData::NAME, $context->getPeer())
                    ->run(['result' => $this->getFullReport(), 'type' => DebugData::TYPE_VALUE]);
                return true;
            }),
            new CommandHandler(DebugData::class),
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
                $thread->run()->setResult(
                    $this->applicationContainer
                        ->getThreadPools()
                        ->getCurrentPool()
                        ->getThreadById($thread->getUniqueId())
                        ->setArguments($thread->getArguments())
                        ->run()
                        ->getResult()
                );
                $this->commandDispatcher
                    ->create(ThreadResult::NAME, $this->client)
                    ->onError(function () {
                        // todo
                        throw new \RuntimeException('Cannot run tres command.');
                    })
                    ->onSuccess(function () use ($thread) {
//                        $this->threadPool->getThreadById($thread->getId());
                        $this->threadPool->remove($thread);
                    })
                    ->run(ThreadResult::serializeTask($thread));
            }
        }
    }

    public function getFullReport(): array
    {
        return [
            'AppsCount' => count($this->applicationContainer),
            'ThreadPoolsCount' => count($this->applicationContainer->getThreadPools()),
            'ThreadsCount' => $this->applicationContainer->getThreadPools()->threadsCount(),
            'WorkCount' => count($this->threadPool),
        ];
    }
}
