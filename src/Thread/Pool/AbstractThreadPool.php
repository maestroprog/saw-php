<?php

namespace Maestroprog\Saw\Thread\Pool;

use Maestroprog\Saw\Thread\AbstractThread;

abstract class AbstractThreadPool implements \IteratorAggregate, \Countable
{
    /**
     * @var AbstractThread[]
     */
    protected $threads;

    public function __construct()
    {
        $this->threads = [];
    }

    public function add(AbstractThread $thread)
    {
        $id = $this->getThreadId($thread);
        if (!$this->exists($id)) {
            $this->threads[$id] = $thread;
        }
    }

    public function remove(AbstractThread $thread)
    {
        $id = $this->getThreadId($thread);
        if ($this->exists($id)) {
            unset($this->threads[$id]);
        }
    }

    public function exists($id): bool
    {
        return isset($this->threads[$id]);
    }

    /**
     * @param AbstractThread $thread
     * @return string|int
     */
    abstract public function getThreadId(AbstractThread $thread);

    public function getThreadById($id): AbstractThread
    {
        return $this->threads[$id];
    }

    /**
     * @return AbstractThread[]
     */
    public function getThreads(): array
    {
        return $this->threads;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->threads);
    }

    public function count()
    {
        return count($this->threads);
    }
}
