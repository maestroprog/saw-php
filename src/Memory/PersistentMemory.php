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
            $value = $this->longTermMemory->read($varName);
            $this->shortTermMemory->write($varName, $value);

            return $value;
        }
        $value = $this->memory->read($varName);
        $this->longTermMemory->write($varName, $value);

        return $value;
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
        return $this->memory->list($prefix);
    }

    public function free()
    {
        $this->shortTermMemory->free();
        $this->longTermMemory->free();
        $this->memory->free();
    }
}
