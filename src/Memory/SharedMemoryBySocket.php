<?php

namespace Saw\Memory;


use Saw\Service\CommandDispatcher;

class SharedMemoryBySocket implements SharedMemoryInterface
{
    public function __construct(CommandDispatcher $commandDispatcher)
    {
    }

    public function has($varName): bool
    {
        // TODO: Implement has() method.
    }

    public function remove($varName)
    {
        // TODO: Implement remove() method.
    }

    public function read($varName, bool $withLocking = true)
    {
        // TODO: Implement read() method.
    }

    public function write($varName, $variable, bool $unlock = true): bool
    {
        // TODO: Implement write() method.
    }

    public function lock($varName)
    {
        // TODO: Implement lock() method.
    }

    public function unlock($varName)
    {
        // TODO: Implement unlock() method.
    }
}
