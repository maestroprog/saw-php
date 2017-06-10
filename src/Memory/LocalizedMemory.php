<?php

namespace Saw\Memory;


class LocalizedMemory implements LocalizedMemoryInterface
{
    private $sharedMemory;

    private $memory;

    public function __construct(SharedMemoryInterface $sharedMemory)
    {
        $this->sharedMemory = $sharedMemory;
        $this->memory = new \SplDoublyLinkedList();
    }

    public function share($varName)
    {
        $this->sharedMemory->write($varName, $this->read($varName));
    }

    public function request($varName)
    {
        $variable = $this->sharedMemory->read($varName, false);
        $this->write($varName, $variable);
        return $variable;
    }

    public function has($varName): bool
    {
        return $this->memory->offsetExists($varName);
    }

    public function read($varName)
    {
        return $this->memory->offsetGet($varName);
    }

    public function write($varName, $variable): bool
    {
        $this->memory->offsetSet($varName, $variable);
        return true;
    }

    public function remove($varName)
    {
        $this->memory->offsetUnset($varName);
    }
}
