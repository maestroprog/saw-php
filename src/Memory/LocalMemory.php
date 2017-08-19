<?php

namespace Maestroprog\Saw\Memory;

class LocalMemory implements MemoryInterface
{
    private $memory;

    public function __construct()
    {
        $this->memory = new \ArrayObject();
    }

    public function has(string $varName): bool
    {
        return $this->memory->offsetExists($varName);
    }

    public function read(string $varName)
    {
        if (!$this->memory->offsetExists($varName)) {
            throw new \OutOfBoundsException('Cannot read undefined "' . $varName . '".');
        }
        return $this->memory->offsetGet($varName);
    }

    public function write(string $varName, $variable, bool $unlock = true): bool
    {
        $this->memory[$varName] = $variable;
        return true;
    }

    public function remove(string $varName)
    {
        if (!$this->memory->offsetExists($varName)) {
            throw new \OutOfBoundsException('Cannot remove undefined "' . $varName . '".');
        }
        $this->memory->offsetUnset($varName);
    }

    public function list(string $prefix = null): array
    {
        if (null !== $prefix) {
            return array_filter($this->memory->getArrayCopy(), function ($key) use ($prefix) {
                if (0 !== strpos($key, $prefix)) {
                    return false;
                }
                return true;
            }, ARRAY_FILTER_USE_KEY);
        }
        return $this->memory->getArrayCopy();
    }

    public function free()
    {
        $this->memory = new \ArrayObject();
    }
}
