<?php

namespace Maestroprog\Saw\Application;

use Maestroprog\Saw\Application\Context\ContextInterface;
use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\MultiThreadingInterface;
use Maestroprog\Saw\Thread\MultiThreadingProvider;
use Qwerty\Application\ApplicationInterface;

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

    public function context(): ContextInterface
    {
        return $this->contextPool;
    }

    /**
     * Описывает основной поток выполнения приложения.
     * Этот метод должен содержать запуск остальных потоков приложения.
     *
     * @param mixed $prepared Результаты выполнения метода prepare()
     * @return void
     */
    abstract protected function main($prepared);

    final public function run()
    {
        $this->main($this->prepare());

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

        $this->init();

        $this->end();
    }

    final public function thread(string $uniqueId, callable $code): AbstractThread
    {
        return $this->multiThreadingProvider->getThreadCreator()->thread($uniqueId, $code);
    }

    final public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->multiThreadingProvider->getThreadCreator()->threadArguments($uniqueId, $code, $arguments);
    }

    final public function synchronizeOne(AbstractThread $thread)
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
