<?php

namespace Maestroprog\Saw\Memory;

class PersistentMemory implements MemoryInterface
{
    private $memory;
    private $longTermMemory;
    private $shortTermMemory;

    public function __construct(MemoryInterface $storage, LongTermMemory $longTermMemory, ShortTermMemory $cache)
    {
        $this->memory = $storage;
        $this->longTermMemory = $longTermMemory;
        $this->shortTermMemory = $cache;
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
