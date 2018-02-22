<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Maestroprog\Saw\Entity\Worker;

class WorkerPool implements \Countable, \IteratorAggregate
{
    private $workers;

    public function __construct()
    {
        $this->workers = [];
    }

    public function add(Worker $worker): void
    {
        $workerId = $worker->getId();
        if (isset($this->workers[$workerId])) {
            throw new \LogicException('Can not add an already added id.');
        }
        $this->workers[$workerId] = $worker;
    }

    public function getById(int $workerId): Worker
    {
        if (!$this->isExistsById($workerId)) {
            throw new \LogicException('Cannot get unknown worker.');
        }
        return $this->workers[$workerId];
    }

    public function isExistsById(int $workerId): bool
    {
        return isset($this->workers[$workerId]);
    }

    public function remove(Worker $worker): void
    {
        if (!$this->isExistsById($worker->getId())) {
            throw new \LogicException('Can not remove an already removed.');
        }
        unset($this->workers[$worker->getId()]);
    }

    public function removeById(int $workerId): void
    {
        if (!$this->isExistsById($workerId)) {
            throw new \LogicException('Can not remove an already removed.');
        }
        unset($this->workers[$workerId]);
    }

    /**
     * @return \Generator|Worker[]
     */
    public function getIterator(): \Generator
    {
        static $start = 0;

        $other = [];

        $i = 0;
        foreach ($this->workers as $worker) {
            if ($i >= $start) {
                yield $worker;
            } else {
                $other[] = $worker;
            }
        }

        $start++;

        yield from $other;
    }

    public function count(): int
    {
        return count($this->workers);
    }
}
