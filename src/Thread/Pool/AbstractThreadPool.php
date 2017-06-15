<?php

namespace Saw\Thread\Pool;

use Saw\Thread\AbstractThread;

abstract class AbstractThreadPool
{
    /**
     * @var AbstractThread[]
     */
    protected $threads = [];
    protected $threadUniqueIds = [];

    public function add(AbstractThread $thread)
    {
        $this->threads[$thread->getId()] = $thread;
        $this->threadUniqueIds[$thread->getUniqueId()] = $thread->getId();
    }

    public function existsThreadById(string $threadId): bool
    {
        return array_key_exists($threadId, $this->threads);
    }

    public function existsThreadByUniqueId(string $uniqueId): bool
    {
        return array_key_exists($uniqueId, $this->threadUniqueIds);
    }

    public function runThreadById(string $threadId)
    {
        $this->threads[$threadId]->run();
    }
}
