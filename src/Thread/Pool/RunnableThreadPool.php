<?php

namespace Saw\Thread\Pool;

class RunnableThreadPool extends AbstractThreadPool
{
    public function __construct()
    {
    }

    public function runThreadById(string $threadId)
    {
        $this->threads[$threadId]->run();
    }
}
