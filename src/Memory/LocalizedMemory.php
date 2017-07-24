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

    public function share(string $varName)
    {
        $this->sharedMemory->write($varName, $this->read($varName));
    }

    public function request(string $varName)
    {
        $variable = $this->sharedMemory->read($varName, false);
        $this->write($varName, $variable);
        return $variable;
    }

    public function has(string $varName): bool
    {
        return $this->memory->offsetExists($varName);
    }

    public function read(string $varName)
    {
        return $this->memory->offsetGet($varName);
    }

    public function write(string $varName, $variable): bool
    {
        $this->memory->offsetSet($varName, $variable);
        return true;
    }

    public function remove(string $varName)
    {
        $this->memory->offsetUnset($varName);
    }
}
