<?php

namespace Saw\Standalone\Controller;

use Saw\Entity\Worker;

class WorkerPool implements \Countable, \IteratorAggregate
{
    private $workers;

    public function __construct()
    {
        $this->workers = new \ArrayObject();
    }

    public function add(Worker $worker)
    {
        $workerId = $worker->getId();
        if (isset($this->workers[$workerId])) {
            throw new \LogicException('Can not add an already added id.');
        }
        $this->workers[$workerId] = $workerId;
    }

    public function isExistsById(int $workerId): bool
    {
        return isset($this->workers[$workerId]);
    }

    public function getById(int $workerId): Worker
    {
        if (!$this->isExistsById($workerId)) {
            throw new \LogicException('Cannot get unknown worker.');
        }
        return $this->workers[$workerId];
    }

    public function remove(Worker $worker)
    {
        if (!$this->isExistsById($worker->getId())) {
            throw new \LogicException('Can not remove an already removed.');
        }
        unset($this->workers[$worker->getId()]);
    }

    public function removeById(int $workerId)
    {
        if (!$this->isExistsById($workerId)) {
            throw new \LogicException('Can not remove an already removed.');
        }
        unset($this->workers[$workerId]);
    }

    public function getIterator()
    {
        return $this->workers->getIterator();
    }

    public function count()
    {
        return $this->workers->count();
    }
}
