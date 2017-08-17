<?php

namespace Maestroprog\Saw\Memory;

class LocalizedShareableMemory implements MemoryInterface, ShareableMemoryInterface
{
    private $sharedMemory;

    /**
     * @var \ArrayObject
     */
    private $memory;

    public function __construct(SharedMemoryInterface $sharedMemory)
    {
        $this->sharedMemory = $sharedMemory;
        $this->free();
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
