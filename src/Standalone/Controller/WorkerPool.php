<?php

namespace Saw\Standalone\Controller;

use Esockets\base\AbstractConnectionResource;
use Saw\Entity\Worker;

class WorkerPool implements \Countable, \Iterator
{
    private $workers;

    private $iterationKey;

    public function add(Worker $worker)
    {
        $workerId = $worker->getId();
        if (isset($this->workers[$workerId])) {
            throw new \LogicException('Can not add an already added id.');
        }
        $this->workers[$workerId] = $workerId;
    }

    public function remove(Worker $worker)
    {
        if (!isset($this->workers[$worker->getId()])) {
            throw new \LogicException('Can not remove an already removed.');
        }
        unset($this->workers[$worker->getId()]);
    }

    public function removeById(int $workerId)
    {
        if (!isset($this->workers[$workerId])) {
            throw new \LogicException('Can not remove an already removed.');
        }
        unset($this->workers[$workerId]);
    }

    public function removeByConnectionResource(AbstractConnectionResource $connectionResource)
    {

    }

    public function count()
    {
        return count($this->workers);
    }

    public function current()
    {

    }

    public function next()
    {
        // TODO: Implement next() method.
    }

    public function key()
    {
        // TODO: Implement key() method.
    }

    public function valid()
    {
        // TODO: Implement valid() method.
    }

    public function rewind()
    {
        // TODO: Implement rewind() method.
    }
}
