<?php

namespace Saw\Thread\Creator;

use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\AbstractThreadPool;
use Saw\Thread\Pool\WorkerThreadPool;
use Saw\Thread\ThreadWithCode;

class ThreadCreator implements ThreadCreatorInterface
{
    protected $threadPool = [];

    public function __construct()
    {
        $this->threadPool = new WorkerThreadPool();
    }

    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        static $threadId = 0;
        $thread = new ThreadWithCode(++$threadId, $uniqueId, $code);
        $this->threadPool->add($thread);
        return $thread;
    }

    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->thread($uniqueId, $code)->setArguments($arguments);
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->threadPool;
    }
}
