<?php

namespace Maestroprog\Saw\Memory;

final class LocalizedShareableMemory implements MemoryInterface, ShareableMemoryInterface
{
    private $memory;
    private $sharedMemory;

    public function __construct(LocalMemory $localMemory, SharedMemoryInterface $sharedMemory)
    {
        $this->memory = $localMemory;
        $this->sharedMemory = $sharedMemory;
    }

    public function share(string $varName)
    {
        $this->sharedMemory->write($varName, $this->read($varName));
    }

    public function read(string $varName)
    {
        return $this->memory->read($varName);
    }

    public function request(string $varName)
    {
        $variable = $this->sharedMemory->read($varName, false);
        $this->write($varName, $variable);
        return $variable;
    }

    public function write(string $varName, $variable): bool
    {
        return $this->memory->write($varName, $variable);
    }

    public function has(string $varName): bool
    {
        return $this->memory->has($varName);
    }

    public function remove(string $varName)
    {
        $this->memory->remove($varName);
    }

    public function list(string $prefix = null): array
    {
        return $this->memory->list($prefix);
    }

    public function free()
    {
        $this->memory->free();
    }
}
