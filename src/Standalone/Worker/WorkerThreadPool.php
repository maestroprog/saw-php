<?php

namespace Saw\Thread\Pool;

use Saw\Thread\AbstractThread;

class WorkerThreadPool extends AbstractThreadPool
{
    public function add(AbstractThread $thread)
    {
        if (!$this->existsThreadByUniqueId($thread->getUniqueId())) {
            parent::add($thread);
        }
    }
}
