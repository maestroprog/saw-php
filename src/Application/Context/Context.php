<?php

namespace Saw\Application\Context;

use Saw\Memory\SharedMemoryInterface;

/**
 * Контекст
 */
class Context implements ContextInterface
{
    private $id;
    private $sharedMemory;

    public function __construct(string $id, SharedMemoryInterface $sharedMemory)
    {
        $this->id = $id;
        $this->sharedMemory = $sharedMemory;
    }

    public function has($varName): bool
    {
        // TODO: Implement has() method.
    }

    public function read($varName)
    {
        // TODO: Implement read() method.
    }

    public function write($varName, $variable): bool
    {
        // TODO: Implement write() method.
    }

    public function remove($varName)
    {
        // TODO: Implement remove() method.
    }

    public function getId(): string
    {
        // TODO: Implement getId() method.
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
}
