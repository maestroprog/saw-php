<?php

namespace Saw\Memory;


use Saw\Connector\ControllerConnector;
use Saw\Service\CommandDispatcher;

class SharedMemoryBySocket implements SharedMemoryInterface
{
    private $connector;
    private $memory;

    public function __construct(ControllerConnector $connector)
    {
        $this->connector = $connector;
        $this->memory = new \SplDoublyLinkedList();
    }

    public function has($varName): bool
    {
        return $this->memory->offsetExists($varName);
    }

    public function remove($varName)
    {
        $this->memory->offsetUnset($varName);
        //todo
    }

    public function read($varName, bool $withLocking = true)
    {
        // TODO: Implement read() method.
    }

    public function write($varName, $variable, bool $unlock = true): bool
    {
        // TODO: Implement write() method.
    }

    public function lock($varName)
    {
        // TODO: Implement lock() method.
    }

    public function unlock($varName)
    {
        // TODO: Implement unlock() method.
    }
}
