<?php

namespace Saw\Thread\Pool;

use Saw\Thread\AbstractThread;

class PoolOfUniqueThreads extends AbstractThreadPool
{
    public function getThreadId(AbstractThread $thread)
    {
        return $thread->getUniqueId();
    }
}
