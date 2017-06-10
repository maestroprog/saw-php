<?php

namespace Saw\Thread\Pool;

use Saw\Thread\AbstractThread;

abstract class Pool
{
    /**
     * @var AbstractThread[]
     */
    protected $threads = [];

    public function add(AbstractThread $thread)
    {
        $this->threads[$thread->getId()] = $thread;
    }

    public function existsThreadById(string $threadId): bool
    {
        return array_key_exists($threadId, $this->threads);
    }

    public function runThreadById(string $threadId)
    {
        $this->threads[$threadId]->run();
    }
}
