<?php

namespace Saw\Thread\Runner;

use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\AbstractThreadPool;
use Saw\Thread\Pool\RunnableThreadPool;

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

    /**
     * @param AbstractThread[] $threads
     * @return bool
     */
    public function runThreads(array $threads): bool
    {
        foreach ($threads as $thread) {
            $this->threadPool->add($thread);
            $thread->run();
        }
        return true;
    }
}
