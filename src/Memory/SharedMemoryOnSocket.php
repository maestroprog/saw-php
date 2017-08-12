<?php

namespace Maestroprog\Saw\Memory;

use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\MemoryFree;
use Maestroprog\Saw\Command\MemoryRequest;
use Maestroprog\Saw\Command\MemoryShare;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;

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
    }

    public function has(string $varName, bool $withLocking = false): bool
    {
        // todo await end of running command! SERIOUSLY! THIS IS VERY IMPORTANT!
    }

    public function remove(string $varName)
    {
        $this
            ->connector
            ->getCommandDispatcher()
            ->create(MemoryFree::class, $this->connector->getClient())
            ->run(['key' => $varName]);
    }

    public function read(string $varName, bool $withLocking = true)
    {
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
