<?php

namespace Saw\Thread\Runner;

use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\AbstractThreadPool;

class ThreadRunner implements ThreadRunnerInterface
{

    /**
     * @inheritdoc
     * @param AbstractThread[] $threads
     * @return bool
     */
    public function runThreads(array $threads): bool
    {
        foreach ($threads as $thread) {
            $thread->run();
        }
    }

    public function getThreadPool(): AbstractThreadPool
    {
        // TODO: Implement getThreadPool() method.
    }
}
