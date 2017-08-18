<?php

namespace Maestroprog\Saw\Memory;

class TransActiveMemory implements MemoryInterface
{
    private $memory;

    public function __construct()
    {
        $this->memory = new \ArrayObject();
    }

    public function has(string $varName): bool
    {
        // TODO: Implement has() method.
    }

    public function read(string $varName)
    {
        // TODO: Implement read() method.
    }

    public function write(string $varName, $variable): bool
    {
        // TODO: Implement write() method.
    }

    public function remove(string $varName)
    {
        // TODO: Implement remove() method.
    }

    public function list(string $prefix = null): array
    {
        // TODO: Implement list() method.
    }

    public function free()
    {
        // TODO: Implement free() method.
    }
}
