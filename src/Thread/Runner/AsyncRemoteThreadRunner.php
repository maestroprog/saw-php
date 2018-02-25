<?php

namespace Maestroprog\Saw\Thread\Runner;

use Esockets\Debug\Log;
use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\ThreadBroadcast;
use Maestroprog\Saw\Command\ThreadResult;
use Maestroprog\Saw\Command\ThreadRun;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Service\AsyncBus;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Standalone\Controller\CycleInterface;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Pool\RunnableThreadPool;
use Maestroprog\Saw\ValueObject\SawEnv;

final class AsyncRemoteThreadRunner implements ThreadRunnerDisablingSupportInterface, CycleInterface
{
    private $connector;
    private $client;
    private $commandDispatcher;
    private $commander;

    private $applicationContainer;
    private $threadPool;
    private $disabled;
    /**
     * @var SawEnv
     */
    private $env;

    public function __construct(
        ControllerConnectorInterface $connector,
        CommandDispatcher $commandDispatcher,
        Commander $commander,
        ApplicationContainer $applicationContainer,
        SawEnv $env
    )
    {
        $this->connector = $connector;
        $this->client = $connector->getClient();
        $this->commandDispatcher = $commandDispatcher;
        $this->commander = $commander;
        $this->applicationContainer = $applicationContainer;
        $this->env = $env;
        if ($env->isWorker()) {
            $this->disable();
        }

        $this->threadPool = new RunnableThreadPool();

        $this->commandDispatcher
            ->addHandlers([
                new CommandHandler(ThreadResult::class, function (ThreadResult $context) {
                    $this->threadPool
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
            $commands = [];
            foreach ($threads as $thread) {
                $this->threadPool->add($thread);
                $commands[] = new ThreadRun(
                    $this->client,
                    $thread->getId(),
                    $thread->getApplicationId(),
                    $thread->getUniqueId(),
                    $thread->getArguments()
                );
            }
            try {
                $this
                    ->commander
                    ->runPacket(...$commands);
            } catch (\Throwable $e) {
                die($e->getMessage());
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
            $this->threadPool->add($thread);
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
        return $this->threadPool;
    }

    public function disable(): void
    {
        $this->disabled = true;
    }

    public function work(): \Generator
    {
        if ($this->env->isWeb()) {
            yield from $this->connector->work();
        } else {
            while (true) {
                yield AsyncBus::SIGNAL_PAUSE; // прерывания для воркера
            }
        }
    }
}
