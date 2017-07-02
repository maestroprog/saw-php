<?php

namespace Saw\Application;

use Saw\Application\Context\ContextPool;
use Saw\Memory\SharedMemoryInterface;
use Saw\Thread\AbstractThread;
use Saw\Thread\MultiThreadingInterface;
use Saw\Thread\MultiThreadingProvider;

abstract class BasicMultiThreaded implements ApplicationInterface, MultiThreadingInterface
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

    /**
     * Описывает основной поток выполнения приложения.
     * Этот метод должен содержать запуск остальных потоков приложения.
     *
     * @return void
     */
    abstract protected function main();

    final public function run()
    {
        $this->main();

        $runningResult = $this->multiThreadingProvider
            ->getThreadRunner()
            ->runThreads(
                $this->multiThreadingProvider
                    ->getThreadPools()
                    ->getCurrentPool()
                    ->getThreads()
            );
        if (!$runningResult) {
            throw new \RuntimeException('Cannot run the threads.');
        }

        $this->end();
    }

    final public function thread(string $uniqueId, callable $code): AbstractThread
    {
        return $this->multiThreadingProvider->getThreadCreator()->thread($uniqueId, $code);
    }

    final  public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->multiThreadingProvider->getThreadCreator()->threadArguments($uniqueId, $code, $arguments);
    }

    final  public function synchronizeOne(AbstractThread $thread)
    {
        $this->multiThreadingProvider->getSynchronizer()->synchronizeOne($thread);
    }

    final public function synchronizeThreads(array $threads)
    {
        $this->multiThreadingProvider->getSynchronizer()->synchronizeThreads($threads);
    }

    final public function synchronizeAll()
    {
        $this->multiThreadingProvider->getSynchronizer()->synchronizeAll();
    }
}
