<?php

namespace Maestroprog\Saw\Standalone;

use Esockets\Client;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\DebugCommand;
use Maestroprog\Saw\Command\DebugData;
use Maestroprog\Saw\Command\ThreadResult;
use Maestroprog\Saw\Command\ThreadRun;
use Maestroprog\Saw\Heading\ReportSupportInterface;
use Maestroprog\Saw\Service\ApplicationLoader;
use Maestroprog\Saw\Service\AsyncBus;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Standalone\Controller\CycleInterface;
use Maestroprog\Saw\Thread\Pool\RunnableThreadPool;
use Maestroprog\Saw\Thread\StatefulThread;
use Maestroprog\Saw\Thread\StubThread;
use Maestroprog\Saw\Thread\ThreadWithCode;

/**
 * Ядро воркера.
 * Само по себе нужно только для изоляции приложения.
 */
final class WorkerCore implements CycleInterface, ReportSupportInterface
{
    private $client;
    private $commander;
    private $applicationContainer;

    private $threadPool;
    private $generators;

    public function __construct(
        Client $peer,
        CommandDispatcher $commandDispatcher,
        Commander $commander,
        ApplicationContainer $applicationContainer,
        ApplicationLoader $applicationLoader
    )
    {
        $this->client = $peer;
        $this->commander = $commander;
        $this->applicationContainer = $applicationContainer;

        $apps = $applicationLoader->instanceAllApps();
        foreach ($apps as $app) {
            $this->applicationContainer->add($app);
        }

        $this->threadPool = new RunnableThreadPool();
        $this->generators = new \SplObjectStorage();

        $commandDispatcher->addHandlers([
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
                $this->commander->runAsync(new DebugData(
                    $context->getClient(),
                    DebugData::TYPE_VALUE,
                    $this->getFullReport()
                ));
            }),
        ]);
    }

    /**
     * Метод служит для запуска всех приложений внутри воркера.
     */
    public function run()
    {
        $this->applicationContainer->run();
    }

    public function work(): \Generator
    {
        while (true) {
            $active = false;
            /** @var $thread StatefulThread */
            foreach ($this->threadPool as $thread) {
                if ($thread->isSynchronized()) {
                    continue;
                }
                $this->applicationContainer->switchTo($this->applicationContainer->get($thread->getApplicationId()));

                /** @var ThreadWithCode $realThread */
                $realThread = $this->applicationContainer
                    ->getThreadPools()
                    ->getCurrentPool()
                    ->getThreadById($thread->getUniqueId());

                if ($thread->getCurrentState() === StatefulThread::STATE_NEW) {
                    $realThread->setArguments($thread->getArguments());
                    $thread->run(); // Запомним, что этот поток находится в стадии запуска, чтобы его запуск не зациклился.
                    $active = true;
                }

                /** @var ThreadWithCode $realThread */
                $realThread = $this->applicationContainer
                    ->getThreadPools()
                    ->getCurrentPool()
                    ->getThreadById($thread->getUniqueId())
                    ->setArguments($thread->getArguments());

                $rewound = false;
                if (!$this->generators->contains($thread)) {
                    $generator = $realThread->run();
                    $generator->rewind();
                    $rewound = true;
                    $this->generators->attach($thread, $generator);
                } else {
                    $generator = $this->generators[$thread];
                }
                if ($generator->valid()) {
                    $signal = $generator->current();
                    if ($signal !== AsyncBus::SIGNAL_PAUSE) {
                        $active = true;
                        yield __METHOD__ . '.' . $signal;
                    } else {
                        yield __METHOD__ . '.' . 'BEFORE_PAUSE'; // todo сделать нормальные сигналы
                    }
                    if (!$rewound) {
                        $generator->next();
                    }
                }
                if (!$generator->valid()) {
                    $thread->setResult($generator->getReturn());

                    $resultCommand = new ThreadResult(
                        $this->client,
                        $thread->getId(),
                        $thread->getApplicationId(),
                        $thread->getUniqueId(),
                        $thread->getResult()
                    );

                    $resultCommand
                        ->onSuccess(function () use ($thread) {
                            // $this->threadPool->getThreadById($thread->getId());
                            $this->generators->detach($thread);
                            $this->threadPool->remove($thread);
                        })
                        ->onError(function () {
                            throw new \RuntimeException('Cannot run tres command.');
                        });
                    $this->commander->runAsync($resultCommand);
                }
            }

            if ($active) {
                yield 'WORKER_CORE';
            } else {
                yield 'WORKER_CORE' => AsyncBus::SIGNAL_PAUSE;
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
