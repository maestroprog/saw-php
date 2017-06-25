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

    public function existsThreadByUniqueId(string $uniqueId): bool
    {
        return array_key_exists($uniqueId, $this->threadUniqueIds);
    }

    public function getThreadByUniqueId(string $uniqueId): AbstractThread
    {
        return $this->threads[$this->threadUniqueIds[$uniqueId]];
    }

    public function getThreadById(int $id): AbstractThread
    {
        return $this->threads[$id];
    }
}
