<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Pool\RunnableThreadPool;

class DummyThreadRunner implements ThreadRunnerInterface
{
    private $threadPool;

    public function __construct()
    {
        $this->threadPool = new RunnableThreadPool();
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->threadPool;
    }

    public function broadcastThreads(BroadcastThread ...$threads): bool
    {
        return $this->runThreads(...$threads);
    }

    public function runThreads(AbstractThread ...$threads): bool
    {
        foreach ($threads as $thread) {
            $this->threadPool->add($thread);
            $thread->run();
        }

        return true;
    }
}
