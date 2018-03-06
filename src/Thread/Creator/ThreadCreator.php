<?php

namespace Maestroprog\Saw\Thread\Creator;

use Maestroprog\Saw\Application\ApplicationContainer;
use Maestroprog\Saw\Thread\Pool\AbstractThreadPool;
use Maestroprog\Saw\Thread\Pool\ContainerOfThreadPools;
use Maestroprog\Saw\Thread\ThreadWithCode;

class ThreadCreator implements ThreadCreatorInterface
{
    protected $pools;
    protected $container;

    public function __construct(ContainerOfThreadPools $pools, ApplicationContainer $container)
    {
        $this->pools = $pools;
        $this->container = $container;
    }

    public function getThreadPool(): AbstractThreadPool
    {
        return $this->pools->getCurrentPool();
    }

    public function thread(string $uniqueId, callable $code): ThreadWithCode
    {
        static $threadId = 0;

        $pool = $this->getThreadPool();
        if ($pool->exists($uniqueId)) {
            throw new \RuntimeException('It is not possible to create multiple threads with the same identifier.');
        }
        $thread = new ThreadWithCode(++$threadId, $this->container->getCurrentApp()->getId(), $uniqueId, $code);
        $pool->add($thread);

        return $thread;
    }

    public function threadArguments(string $uniqueId, callable $code, array $arguments): ThreadWithCode
    {
        return $this->thread($uniqueId, $code)->setArguments($arguments);
    }
}
