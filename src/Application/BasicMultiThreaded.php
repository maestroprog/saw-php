<?php

namespace Maestroprog\Saw\Application;

use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\MultiThreadingInterface;
use Maestroprog\Saw\Thread\MultiThreadingProvider;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Runner\ThreadRunnerInterface;
use Maestroprog\Saw\Thread\Synchronizer\SynchronizationThreadInterface;
use Maestroprog\Saw\Thread\ThreadWithCode;
use Qwerty\Application\ApplicationInterface;

abstract class BasicMultiThreaded implements
    ApplicationInterface,
    ThreadRunnerInterface,
    MultiThreadingInterface
{
    private $id;
    private $multiThreadingProvider;

    public function __construct(string $id, MultiThreadingProvider $multiThreadingProvider)
    {
        $this->id = $id;
        $this->multiThreadingProvider = $multiThreadingProvider;
    }

    final public function getId(): string
    {
        return $this->id;
    }

    final public function run()
    {
        $this->init();

        $this->main($this->prepare());

        $runningResult = $this
            ->multiThreadingProvider
            ->getThreadRunner()
            ->runThreads(
                ...$this->multiThreadingProvider
                ->getThreadPools()
                ->getCurrentPool()
                ->getThreads()
            );
        if (!$runningResult) {
            throw new \RuntimeException('Cannot run the threads.');
        }

        $this->end();
    }

    /**
     * Описывает основной поток выполнения приложения.
     * Этот метод должен содержать запуск остальных потоков приложения.
     *
     * @param mixed $prepared Результаты выполнения метода prepare()
     *
     * @return void
     */
    abstract protected function main($prepared);

    final public function thread(string $uniqueId, callable $code): ThreadWithCode
    {
        return $this->multiThreadingProvider->getThreadCreator()->thread($uniqueId, $code);
    }

    final public function threadArguments(string $uniqueId, callable $code, array $arguments): ThreadWithCode
    {
        return $this->multiThreadingProvider->getThreadCreator()->threadArguments($uniqueId, $code, $arguments);
    }

    public function runThreads(AbstractThread ...$threads): bool
    {
        return $this->multiThreadingProvider->getThreadRunner()->runThreads(...$threads);
    }

    public function broadcastThreads(BroadcastThread ...$threads): bool
    {
        return $this->multiThreadingProvider->getThreadRunner()->broadcastThreads(...$threads);
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->multiThreadingProvider->getThreadRunner()->getThreadPool();
    }

    /**
     * @inheritdoc
     */
    final public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        yield from $this->multiThreadingProvider->getSynchronizer()->synchronizeThreads(...$threads);
    }

    /**
     * @inheritdoc
     */
    final public function synchronizeAll(): \Generator
    {
        yield from $this->multiThreadingProvider->getSynchronizer()->synchronizeAll();
    }
}
