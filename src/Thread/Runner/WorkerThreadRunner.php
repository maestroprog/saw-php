<?php

namespace Saw\Thread\Runner;


use Saw\Thread\AbstractThread;

final class WorkerThreadRunner implements ThreadRunnerInterface
{
    public function __construct()
    {
    }

    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        // TODO: Implement thread() method.
    }

    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        // TODO: Implement threadArguments() method.
    }

    public function runThreads(): bool
    {
        // TODO: Implement runThreads() method.
    }

    public function synchronizeOne(AbstractThread $thread)
    {
        // TODO: Implement synchronizeOne() method.
    }

    public function synchronizeThreads(array $threads)
    {
        // TODO: Implement synchronizeThreads() method.
    }

    public function synchronizeAll()
    {
        // TODO: Implement synchronizeAll() method.
    }

    public function setResultByRunId(int $id, $data)
    {
        // TODO: Implement setResultByRunId() method.
    }
}
