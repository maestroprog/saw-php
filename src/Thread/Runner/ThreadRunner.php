<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;

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
