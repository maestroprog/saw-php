<?php

namespace Maestroprog\Saw\Thread\Pool;

final class ContainerOfThreadPools implements \Countable
{
    private $pools;
    private $currentPool;

    public function __construct()
    {
        $this->pools = new \ArrayObject();
    }

    public function add(string $containerId, AbstractThreadPool $threadPool): AbstractThreadPool
    {
        if (isset($this->pools[$containerId])) {
            throw new \RuntimeException('The thread pool has already added.');
        }
        return $this->pools[$containerId] = $this->switchTo($threadPool);
    }

    public function switchTo(AbstractThreadPool $threadPool): AbstractThreadPool
    {
        return $this->currentPool = $threadPool;
    }

    public function get(string $containerId): AbstractThreadPool
    {
        return $this->pools[$containerId];
    }

    public function getCurrentPool(): AbstractThreadPool
    {
        return $this->currentPool;
    }

    public function count(): int
    {
        return $this->pools->count();
    }

    public function threadsCount(): int
    {
        return array_sum(array_map(function (AbstractThreadPool $pool) {
            return $pool->count();
        }, $this->pools->getArrayCopy()));
    }
}
