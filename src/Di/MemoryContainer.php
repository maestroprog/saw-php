<?php

namespace Maestroprog\Saw\Di;

use Maestroprog\Container\HasContainerLinkInterface;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class MemoryContainer implements HasContainerLinkInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function get(string $id)
    {
        try {
            return $this->container->get($id);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return null;
        }
    }

    public function getLocalMemory(): MemoryInterface
    {
        return new LocalMemory();
    }

    public function getLocalShareableMemory(): LocalizedShareableMemory
    {
        return new LocalizedShareableMemory(
            $this->container->get(LocalMemory::class),
            $this->container->get(SharedMemoryOnSocket::class)
        );
    }

    public function getShortTermMemory(): ShortTermMemory
    {
        return new ShortTermMemory(
            $this->container->get(LocalizedShareableMemory::class),
            'global'
        );
    }

    public function getSharedMemoryServer(): SharedMemoryServer
    {
        return new SharedMemoryServer(
            $this->container->get(LocalMemory::class),
            $this->container->get(CommandDispatcher::class),
            $this->container->get(Commander::class)
        );
    }

    public function getSharedMemoryClient(): SharedMemoryInterface
    {
        return new SharedMemoryOnSocket(
            $this->container->get(Commander::class),
            $this->container->get('ControllerClient') // todo connector
        );
    }

    public function getPersistentMemory(): PersistentMemory
    {
        return new PersistentMemory(
            $this->container->get(SharedMemoryOnSocket::class),
            $this->container->get(LongTermMemory::class),
            $this->container->get(ShortTermMemory::class)
        );
    }

    public function getContextPool(): ContextPool
    {
        return new ContextPool($this->container->get(MemoryInterface::class));
    }
}
