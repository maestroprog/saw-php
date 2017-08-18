<?php

namespace Maestroprog\Saw\Memory;

use Esockets\Client;
use Maestroprog\Saw\Command\MemoryFree;
use Maestroprog\Saw\Command\MemoryLock;
use Maestroprog\Saw\Command\MemoryRequest;
use Maestroprog\Saw\Command\MemoryShare;
use Maestroprog\Saw\Service\CommandDispatcher;
use Maestroprog\Saw\Service\Commander;

final class SharedMemoryOnSocket implements SharedMemoryInterface
{
    private $dispatcher;
    private $commander;
    private $client;

    public function __construct(
        CommandDispatcher $dispatcher,
        Commander $commander,
        Client $client
    )
    {
        $this->dispatcher = $dispatcher;
        $this->commander = $commander;
    }

    public function has(string $varName, bool $withLocking = false): bool
    {
        // todo withLocking without value
        $cmd = new MemoryRequest($this->client, $varName);

        return $this
            ->commander
            ->runSync($cmd, self::READ_TIMEOUT)
            ->getAccomplishedResult();
    }

    public function remove(string $varName)
    {
        $cmd = new MemoryFree($this->client, $varName);
        $this->commander->runAsync($cmd);
    }

    public function read(string $varName, bool $withLocking = true)
    {
        // todo withLocking
        $cmd = new MemoryRequest($this->client, $varName, false, $withLocking);

        return $this
            ->commander
            ->runSync($cmd, self::READ_TIMEOUT)
            ->getAccomplishedResult();
    }

    public function write(string $varName, $variable, bool $unlock = true): bool
    {
        $cmd = new MemoryShare($this->client, $varName, $variable, $unlock);

        try {
            //todo async! thinking -- really?
            $cmd = $this->commander->runSync($cmd, self::WRITE_TIMEOUT);
            if (!$cmd->isAccomplished() || !$cmd->getAccomplishedResult()) {
                return false;
            }
        } catch (\RuntimeException $e) {
            return false;
        }
        return true;
    }

    public function lock(string $varName)
    {
        $message = null;
        $cmd = $this
            ->commander
            ->runSync(
                (new MemoryLock($this->client, $varName, true))
                    ->onError(function (MemoryLock $context) use (&$message) {
                        $message = $context->getAccomplishedResult();
                    }),
                self::LOCK_TIMEOUT
            );
        if (!$cmd->isAccomplished() || !$cmd->isSuccessful()) {
            throw new MemoryLockException($message ?? 'Unknown MemoryLock error.');
        }
    }

    public function unlock(string $varName)
    {
        //todo async! thinking -- really?
        $message = null;
        $cmd = $this
            ->commander
            ->runSync(
                (new MemoryLock($this->client, $varName, false))
                    ->onError(function (MemoryLock $context) use (&$message) {
                        $message = $context->getAccomplishedResult();
                    }),
                self::LOCK_TIMEOUT
            );
        if (!$cmd->isAccomplished() || !$cmd->isSuccessful()) {
            throw new MemoryLockException($message ?? 'Unknown MemoryLock error.');
        }
    }

    public function list(string $prefix = null): array
    {
        // TODO: Implement list() method.
    }

    public function free()
    {
        // TODO: Implement free() method.
    }
}
