<?php

namespace Maestroprog\Saw\Thread\Pool;

use Maestroprog\Saw\Thread\AbstractThread;

class PoolOfUniqueThreads extends AbstractThreadPool
{
    public function getThreadId(AbstractThread $thread): string
    {
        return $thread->getUniqueId();
    }
}
