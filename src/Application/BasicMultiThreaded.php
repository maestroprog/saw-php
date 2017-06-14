<?php

namespace Saw\Application;

use Saw\Application\Context\ContextPool;
use Saw\Memory\SharedMemoryInterface;
use Saw\Thread\AbstractThread;
use Saw\Thread\MultiThreadingInterface;
use Saw\Thread\Runner\ThreadRunnerInterface;

abstract class BasicMultiThreaded implements ApplicationInterface, MultiThreadingInterface
{
    private $id;
    private $threadRunner;

    public function __construct(
        string $id,
        ThreadRunnerInterface $threadRunner,
        SharedMemoryInterface $applicationMemory,
        ContextPool $contextPool
    )
    {
        $this->id = $id;
        $this->threadRunner = $threadRunner;
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

        if (!$this->runThreads()) {
            throw new \RuntimeException('Cannot run the threads.');
        }

        $this->end();
    }

    final public function thread(string $uniqueId, callable $code): AbstractThread
    {
        return $this->threadRunner->thread($uniqueId, $code);
    }

    final  public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->threadRunner->threadArguments($uniqueId, $code, $arguments);
    }

    final public function runThreads(): bool
    {
        return $this->threadRunner->runThreads();
    }

    final  public function synchronizeOne(AbstractThread $thread)
    {
        $this->threadRunner->synchronizeOne($thread);
    }

    final public function synchronizeThreads(array $threads)
    {
        $this->threadRunner->synchronizeThreads($threads);
    }

    final  public function synchronizeAll()
    {
        $this->threadRunner->synchronizeAll();
    }
}
