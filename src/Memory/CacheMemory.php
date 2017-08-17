<?php

namespace Maestroprog\Saw\Memory;

abstract class CacheMemory implements MemoryInterface
{
    use PrefixMemoryTrait;

    const VAR_LIFETIME = 3600;

    protected $storage;

    public function __construct(MemoryInterface $storage)
    {
        $this->storage = $storage;
    }

    public function has(string $varName): bool
    {
        if ($thereIs = $this->storage->has($varName)) {
            $this->access($varName);
        }
        return $thereIs;
    }

    public function read(string $varName)
    {
        $value = $this->storage->read($varName);
        $this->access($varName);
        return $value;
    }

    public function write(string $varName, $variable): bool
    {
        return $this->storage->write($varName, $variable);
    }

    public function remove(string $varName)
    {
        $this->storage->remove($varName);
    }

    public function free()
    {
        $this->storage->free();
    }

    protected function access(string $varName)
    {
        $this->storage->write('lifetime.' . $varName, time());
    }
}
