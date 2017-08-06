<?php

namespace Maestroprog\Saw\Thread\Pool;

use Maestroprog\Saw\Thread\AbstractThread;

class ThreadLinker implements \Countable
{
    private $links;

    public function __construct()
    {
        $this->links = new \SplObjectStorage();
    }

    public function linkThreads(AbstractThread $thread1, AbstractThread $thread2)
    {
        $GLOBALS['log'][] = sprintf('Link thread %d to %d', $thread2->getId(), $thread1->getId());
        $this->links[$thread1] = $thread2;
    }

    public function getLinkedThread(AbstractThread $thread): AbstractThread
    {
        $GLOBALS['log'][] = sprintf('Get link of thread %d', $thread->getId());
        return $this->links[$thread];
    }

    public function unlinkThreads(AbstractThread $thread)
    {
        $GLOBALS['log'][] = sprintf('Unlink thread %d', $thread->getId());
        unset($this->links[$thread]);
    }

    public function count()
    {
        return $this->links->count();
    }
}
