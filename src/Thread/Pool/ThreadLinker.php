<?php

namespace Saw\Thread\Pool;

use Saw\Thread\AbstractThread;

class ThreadLinker implements \Countable
{
    private $links;

    public function __construct()
    {
        $this->links = new \SplObjectStorage();
    }

    public function linkThreads(AbstractThread $thread1, AbstractThread $thread2)
    {
        $this->links[$thread1] = $thread2;
    }

    public function getLinkedThread(AbstractThread $thread): AbstractThread
    {
        return $this->links[$thread];
    }

    public function unlinkThreads(AbstractThread $thread)
    {
        unset($this->links[$thread]);
    }

    public function count()
    {
        return $this->links->count();
    }
}
