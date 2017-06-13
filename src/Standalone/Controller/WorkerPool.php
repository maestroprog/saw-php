<?php

namespace Saw\Standalone\Controller;

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
