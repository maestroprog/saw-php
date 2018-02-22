<?php

namespace Maestroprog\Saw\Standalone\Worker;

use Esockets\Client;
use Esockets\Debug\Log;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\ThreadBroadcast;
use Maestroprog\Saw\Command\ThreadResult;
use Maestroprog\Saw\Command\ThreadRun;
use function Maestroprog\Saw\iterateGenerator;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Pool\PoolOfUniqueThreads;
use Maestroprog\Saw\Thread\Pool\RunnableThreadPool;
use Maestroprog\Saw\Thread\Runner\ThreadRunnerDisablingSupportInterface;

final class WorkerThreadRunner implements ThreadRunnerDisablingSupportInterface
{
    private $client;
    private $commandDispatcher;
    private $commander;
    private $applicationContainer;

    private $threadPool;
    private $runThreadPool;
    private $disabled = true;

    public function __construct(
        Client $client,
        CommandDispatcher $commandDispatcher,
        Commander $commander,
        ApplicationContainer $applicationContainer
    )
    {
        $this->client = $client;
        $this->commandDispatcher = $commandDispatcher;
        $this->commander = $commander;
        $this->applicationContainer = $applicationContainer;

        $this->threadPool = new PoolOfUniqueThreads();
        $this->runThreadPool = new RunnableThreadPool(); // пул именно "работающих" потоков

        $this->commandDispatcher
            ->addHandlers([
                new CommandHandler(ThreadResult::class, function (ThreadResult $context) {
                    $this->runThreadPool
                        ->getThreadById($context->getRunId())
                        ->setResult($context->getResult());
                }),
            ]);
    }

    /**
     * Воркер не должен запускать потоки из приложения.
     * Метод нужен для совместимости работы приложений из скрипта и на воркерах.
     *
     * @param AbstractThread[] $threads
     *
     * @return bool
     */
    public function runThreads(AbstractThread ...$threads): bool
    {
        if (!$this->disabled) {
            foreach ($threads as $thread) {
                $this->runThreadPool->add($thread);
                try {
                    $this->commander->runAsync(new ThreadRun(
                        $this->client,
                        $thread->getId(),
                        $thread->getApplicationId(),
                        $thread->getUniqueId(),
                        $thread->getArguments()
                    ));
                } catch (\Throwable $e) {
                    $thread->run();
                    Log::log($e->getMessage());
                }
            }
        } else {
            $this->enable();
        }

        return true;
    }

    public function enable(): void
    {
        $this->disabled = false;
    }

    public function broadcastThreads(BroadcastThread ...$threads): bool
    {
        $result = false;

        foreach ($threads as $thread) {
            $this->runThreadPool->add($thread);
            try {
                $this
                    ->commander
                    ->runAsync(new ThreadBroadcast(
                        $this->client,
                        $thread->getId(),
                        $thread->getApplicationId(),
                        $thread->getUniqueId(),
                        $thread->getArguments()
                    ));
                $result = true;
            } catch (\Throwable $e) {
                try {
                    $thread->run();
                    $result = true;
                } catch (\Throwable $e) {
                    Log::log($e->getMessage());
                }
            }
        }

        return $result;
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->runThreadPool;
    }

    public function disable(): void
    {
        $this->disabled = true;
    }
}
