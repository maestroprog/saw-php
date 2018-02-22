<?php

namespace Maestroprog\Saw\Application;

use Maestroprog\Saw\Application\Context\ContextInterface;
use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
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
    private $applicationMemory;
    private $contextPool;

    public function __construct(
        string $id,
        MultiThreadingProvider $multiThreadingProvider,
        SharedMemoryInterface $applicationMemory,
        ContextPool $contextPool
    )
    {
        $this->id = $id;
        $this->multiThreadingProvider = $multiThreadingProvider;
        $this->applicationMemory = $applicationMemory;
        $this->contextPool = $contextPool;
    }

    final public function getId(): string
    {
        return $this->id;
    }

    public function context(): ContextInterface
    {
        return $this->contextPool;
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
    final public function synchronizeOne(SynchronizationThreadInterface $thread): \Generator
    {
        yield $this->multiThreadingProvider->getSynchronizer()->synchronizeOne($thread);
    }

    /**
     * @inheritdoc
     */
    final public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        yield $this->multiThreadingProvider->getSynchronizer()->synchronizeThreads(...$threads);
    }

    /**
     * @inheritdoc
     */
    final public function synchronizeAll(): \Generator
    {
        yield $this->multiThreadingProvider->getSynchronizer()->synchronizeAll();
    }
}
