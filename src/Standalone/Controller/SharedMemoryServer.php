<?php

namespace Maestroprog\Saw\Standalone\Controller;

use Esockets\Client;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\VariableFree;
use Maestroprog\Saw\Command\VariableLock;
use Maestroprog\Saw\Command\VariableRequest;
use Maestroprog\Saw\Command\VariableShare;
use Maestroprog\Saw\Memory\MemoryInterface;
use Maestroprog\Saw\Memory\MemoryLockException;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;

final class SharedMemoryServer implements SharedMemoryInterface
{
    const LOCK_LIMITER = 100; // todo use it

    private $dispatcher;
    private $commander;

    private $memory;
    private $locked;

    public function __construct(MemoryInterface $memory, CommandDispatcher $dispatcher, Commander $commander)
    {
        $this->memory = $memory;
        $this->dispatcher = $dispatcher;
        $this->commander = $commander;

        $this->locked = new \ArrayObject();

        $this
            ->dispatcher
            ->addHandlers([
                new CommandHandler(VariableRequest::class, function (VariableRequest $context) {
                    $this->dispatchClient($context->getClient());

                    if ($context->isNoResult()) {
                        $result = $this->has($context->getKey(), $context->isLock());
                    } else {
                        $result = $this->read($context->getKey(), $context->isLock());
                    }
                    return $result;
                }),
                new CommandHandler(VariableShare::class, function (VariableShare $context) {
                    $this->dispatchClient($context->getClient());

                    return $this->write($context->getKey(), $context->getVariable(), $context->isUnlock());
                }),
                new CommandHandler(VariableFree::class, function (VariableShare $context) {
                    $this->dispatchClient($context->getClient());

                    $this->remove($context->getKey());
                }),
                new CommandHandler(VariableLock::class, function (VariableLock $context) {
                    $this->dispatchClient($context->getClient());

                    if ($context->isLock()) {
                        $this->lock($context->getKey());
                    } else {
                        $this->unlock($context->getKey());
                    }
                }),
            ]);
    }

    private $ccIndex;

    private function dispatchClient(Client $client)
    {
        $this->ccIndex = $client->getConnectionResource()->getId();
    }

    public function has(string $varName, bool $withLocking = false): bool
    {
        $thereIs = $this->memory->has($varName);
        if ($withLocking) {
            $this->lock($varName);
        }
        return $thereIs;
    }

    public function read(string $varName, bool $withLocking = true)
    {
        if ($this->locked($varName) && !$this->lockedByThisUser($varName)) {
            throw new MemoryLockException('Cannot read currently locked "' . $varName . '".');
        }
        return $this->memory->read($varName);
    }

    public function write(string $varName, $variable, bool $unlock = true): bool
    {
        if ($this->locked($varName) && !$this->lockedByThisUser($varName)) {
            return false;
        }
        $result = $this->memory->write($varName, $variable);
        if ($unlock) {
            $this->unlock($varName);
        }
        return $result;
    }

    public function remove(string $varName)
    {
        if ($this->locked($varName) && !$this->lockedByThisUser($varName)) {
            throw new MemoryLockException('Cannot remove currently locked "' . $varName . '".');
        }
        $this->memory->remove($varName);
    }

    public function lock(string $varName)
    {
        if ($this->locked->offsetExists($varName)) {
            throw new MemoryLockException('Cannot lock currently locked "' . $varName . '".');
        }
        $this->locked->offsetSet($varName, $this->ccIndex);
    }

    public function unlock(string $varName)
    {
        if (!$this->locked($varName)) {
            return;
        }
        if (!$this->lockedByThisUser($varName)) {
            throw new MemoryLockException('Cannot unlock currently locked "' . $varName . '" by other user.');
        }
        $this->locked->offsetUnset($varName);
    }

    public function list(string $prefix = null): array
    {
        return $this->memory->list($prefix);
    }

    public function free()
    {
        $this->memory = new \ArrayObject();
    }

    private function locked(string $varName): bool
    {
        return $this->locked->offsetExists($varName);
    }

    private function lockedByThisUser(string $varName): bool
    {
        return $this->ccIndex === $this->locked[$varName] ?? 0;
    }
}
