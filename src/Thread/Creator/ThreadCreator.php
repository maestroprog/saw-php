<?php

namespace Saw\Thread\Creator;

use Saw\Saw;
use Saw\Thread\AbstractThread;
use Saw\Thread\Pool\AbstractThreadPool;
use Saw\Thread\Pool\ContainerOfThreadPools;
use Saw\Thread\ThreadWithCode;

class ThreadCreator implements ThreadCreatorInterface
{
    protected $pools;

    public function __construct(ContainerOfThreadPools $pools)
    {
        $this->pools = $pools;
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->pools->getCurrentPool();
    }

    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        static $threadId = 0;
        $thread = new ThreadWithCode(++$threadId, Saw::getCurrentApp()->getId(), $uniqueId, $code);
        $this->pools->getCurrentPool()->add($thread);
        return $thread;
    }

    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        return $this->thread($uniqueId, $code)->setArguments($arguments);
    }
}
