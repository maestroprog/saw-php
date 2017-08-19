<?php

namespace Maestroprog\Saw\Memory;

final class PersistentMemory implements MemoryInterface
{
    private $memory;
    private $longTermMemory;
    private $shortTermMemory;

    public function __construct(MemoryInterface $storage, LongTermMemory $longTermMemory, ShortTermMemory $cache)
    {
        $this->memory = $storage;
        $this->longTermMemory = $longTermMemory;
        $this->shortTermMemory = $cache;
        // todo copying/moving variables to longterm and shortterm memory
    }

    public function has(string $varName): bool
    {
        if ($this->shortTermMemory->has($varName)) {
            return true;
        }
        if ($this->longTermMemory->has($varName)) {
            return true;
        }
        return $this->memory->has($varName);
    }

    public function read(string $varName)
    {
        if ($this->shortTermMemory->has($varName)) {
            return $this->shortTermMemory->read($varName);
        }
        if ($this->longTermMemory->has($varName)) {
            return $this->longTermMemory->read($varName);
        }
        return $this->memory->read($varName);
    }

    public function write(string $varName, $variable): bool
    {
        return $this->memory->write($varName, $variable);
    }

    public function remove(string $varName)
    {
        if ($this->shortTermMemory->has($varName)) {
            $this->shortTermMemory->remove($varName);
        }
        if ($this->longTermMemory->has($varName)) {
            $this->longTermMemory->remove($varName);
        }
        $this->memory->remove($varName);
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
