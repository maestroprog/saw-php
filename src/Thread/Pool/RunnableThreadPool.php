<?php

namespace Saw\Thread\Pool;

use Saw\Thread\AbstractThread;

class RunnableThreadPool extends AbstractThreadPool
{
    public function getThreadId(AbstractThread $thread)
    {
        return $thread->getId();
    }
}
