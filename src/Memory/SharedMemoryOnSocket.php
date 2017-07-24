<?php

namespace Saw\Memory;

use Saw\Command\CommandHandler;
use Saw\Command\MemoryFree;
use Saw\Command\MemoryRequest;
use Saw\Command\MemoryShare;
use Saw\Connector\ControllerConnectorInterface;

class SharedMemoryOnSocket implements SharedMemoryInterface
{
    private $connector;
    private $memory;

    public function __construct(ControllerConnectorInterface $connector)
    {
        $this->connector = $connector;
        $connector
            ->getCommandDispatcher()
            ->add([
                new CommandHandler(MemoryRequest::class, function (MemoryRequest $context) {

                }),
                new CommandHandler(MemoryShare::class, function () {

                }),
                new CommandHandler(MemoryFree::class, function () {

                }),
            ]);
        $this->memory = new \SplDoublyLinkedList();
    }

    public function has(string $varName, bool $withLocking = false): bool
    {
        if ($withLocking) {
            
        }
        if ($this->memory->offsetExists($varName)) {

        }
    }

    public function remove(string $varName)
    {
        $this->memory->offsetUnset($varName);
    }

    public function read(string $varName, bool $withLocking = true)
    {
        // TODO: Implement read() method.
    }

    public function write(string $varName, $variable, bool $unlock = true): bool
    {
        // TODO: Implement write() method.
    }

    public function lock(string $varName)
    {
        // TODO: Implement lock() method.
    }

    public function unlock(string $varName)
    {
        // TODO: Implement unlock() method.
    }
}
