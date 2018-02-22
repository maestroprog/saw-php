<?php

namespace Maestroprog\Saw\Thread\Runner;

use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\BroadcastThread;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;

class ThreadRunner implements ThreadRunnerInterface
{
    public function getThreadPool(): AbstractThreadPool
    {
        return new class extends AbstractThreadPool
        {
            /**
             * @param AbstractThread $thread
             *
             * @return string|int
             */
            public function getThreadId(AbstractThread $thread)
            {
                return 0;
            }
        };
    }

    public function broadcastThreads(BroadcastThread ...$threads): bool
    {
        return $this->runThreads(...$threads);
    }

    public function runThreads(AbstractThread ...$threads): bool
    {
        foreach ($threads as $thread) {
            $thread->run();
        }

        return true;
    }
}
