<?php

namespace Maestroprog\Saw\Di;

use Maestroprog\Container\AbstractBasicContainer;
use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Memory\LocalizedShareableMemory;
use Maestroprog\Saw\Memory\LocalMemory;
use Maestroprog\Saw\Memory\LongTermMemory;
use Maestroprog\Saw\Memory\MemoryInterface;
use Maestroprog\Saw\Memory\PersistentMemory;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Memory\SharedMemoryOnSocket;
use Maestroprog\Saw\Memory\ShortTermMemory;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;
use Maestroprog\Saw\Standalone\Controller\SharedMemoryServer;

class MemoryContainer extends AbstractBasicContainer
{
    public function getLocalMemory(): MemoryInterface
    {
        return new LocalMemory();
    }

    public function getLocalShareableMemory(): LocalizedShareableMemory
    {
        return new LocalizedShareableMemory(
            $this->get(LocalMemory::class),
            $this->get(SharedMemoryOnSocket::class)
        );
    }

    public function getShortTermMemory(): ShortTermMemory
    {
        return new ShortTermMemory(
            $this->get(LocalizedShareableMemory::class),
            'global'
        );
    }

    public function getSharedMemoryServer(): SharedMemoryServer
    {
        return new SharedMemoryServer(
            $this->get(LocalMemory::class),
            $this->get(CommandDispatcher::class),
            $this->get(Commander::class)
        );
    }

    public function getSharedMemoryClient(): SharedMemoryInterface
    {
        return new SharedMemoryOnSocket(
            $this->get(Commander::class),
            $this->get('ControllerClient') // todo connector
        );
    }

    public function getPersistentMemory(): PersistentMemory
    {
        return new PersistentMemory(
            $this->get(SharedMemoryOnSocket::class),
            $this->get(LongTermMemory::class),
            $this->get(ShortTermMemory::class)
        );
    }

    public function getContextPool(): ContextPool
    {
        return new ContextPool();
    }
}
