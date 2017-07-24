<?php

namespace Saw\Application\Context;

use Saw\Memory\MemoryInterface;

/**
 * Контекст.
 */
class Context implements ContextInterface
{
    private $id;
    private $sharedMemory;

    public function __construct(string $id, MemoryInterface $sharedMemory)
    {
        $this->id = $id;
        $this->sharedMemory = $sharedMemory;
    }

    public function has(string $varName): bool
    {
        return $this->sharedMemory->has($this->getKey($varName));
    }

    public function read(string $varName)
    {
        return $this->sharedMemory->read($this->getKey($varName));
    }

    public function write(string $varName, $variable): bool
    {
        return $this->sharedMemory->write($this->getKey($varName), $varName);
    }

    public function remove(string $varName)
    {
        $this->sharedMemory->remove($this->getKey($varName));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function update()
    {
        // TODO: Implement update() method.
    }

    public function canRemoved(): bool
    {
        // TODO: Implement canRemoved() method.
    }

    public function __sleep()
    {
        // TODO: Implement __sleep() method.
    }

    public function __wakeup($dump)
    {
        // TODO: Implement __wakeup() method.
    }

    protected function getKey(string $varName): string
    {
        return 'context.' . $this->id . '.' . $varName;
    }
}
