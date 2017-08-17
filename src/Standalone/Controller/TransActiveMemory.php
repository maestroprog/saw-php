<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\MemoryRequest;
use Maestroprog\Saw\Memory\MemoryLockException;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;

final class TransActiveMemory implements SharedMemoryInterface
{
    const SIZE_LIMITER = 1000;
    const LOCK_LIMITER = 100;

    private $dispatcher;
    private $commander;

    private $currentSize;

    private $clients;
    private $index;
    private $locked;

    public function __construct(CommandDispatcher $dispatcher, Commander $commander)
    {
        $this->dispatcher = $dispatcher;
        $this->commander = $commander;

        $this->currentSize = self::SIZE_LIMITER;

        $this->clients = new \SplDoublyLinkedList();
        $this->index = new \ArrayObject();
        $this->locked = new \ArrayObject();

        $this
            ->dispatcher
            ->addHandlers([
                new CommandHandler(MemoryRequest::class, function (MemoryRequest $context) {
                    try {
                        if ($context->isNoResult()) {
                            $result = $this->has($context->getKey(), $context->isLock());
                        } else {
                            $result = $this->read($context->getKey(), $context->isLock());
                        }
                    } catch (MemoryLockException $e) {
                        return false;
                    }
                    return $result;
                }),
            ]);
    }

    public function has(string $varName, bool $withLocking = false): bool
    {
        $thereIs = $this->index->offsetExists($varName);
        if ($withLocking) {
            $this->lock($varName);
        }
        return $thereIs;
    }

    public function read(string $varName, bool $withLocking = true)
    {
        if ($this->locked($varName)) {
            throw new MemoryLockException('Cannot read currently locked "' . $varName . '".');
        }
        if (!$this->index->offsetExists($varName)) {
            throw new \OutOfBoundsException('Cannot read undefined "' . $varName . '".');
        }
        $client = $this->index->offsetGet($varName);
        return $this->commander->runSync(
            new MemoryRequest($this->clients->offsetGet($client), $varName, false, $withLocking),
            self::READ_TIMEOUT
        )->getAccomplishedResult();
    }

    public function write(string $varName, $variable, bool $unlock = true): bool
    {
        // TODO: Implement write() method. todo random share!!! Not current worker(client)
    }

    public function lock(string $varName)
    {
        if ($this->locked->offsetExists($varName)) {
            throw new MemoryLockException('Cannot lock currently locked "' . $varName . '".');
        }
        $this->locked->offsetSet($varName, true);
    }

    public function unlock(string $varName)
    {
        if ($this->locked->offsetExists($varName)) {
            $this->locked->offsetUnset($varName);
        }
    }

    public function remove(string $varName)
    {
        // TODO: Implement remove() method.
    }

    public function list(string $prefix = null): array
    {
        // TODO: Implement list() method.
    }

    public function free()
    {
        // TODO: Implement free() method.
    }

    private function locked(string $varName): bool
    {
        return $this->locked->offsetExists($varName);
    }
}
