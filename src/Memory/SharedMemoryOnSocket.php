<?php

namespace Maestroprog\Saw\Memory;

use Esockets\Client;
use Maestroprog\Saw\Command\VariableFree;
use Maestroprog\Saw\Command\VariableLock;
use Maestroprog\Saw\Command\VariableRequest;
use Maestroprog\Saw\Command\VariableShare;
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
        $cmd = new VariableRequest($this->client, $varName);

        return $this
            ->commander
            ->runSync($cmd, self::READ_TIMEOUT)
            ->getAccomplishedResult();
    }

    public function read(string $varName, bool $withLocking = true)
    {
        // todo withLocking
        $cmd = new VariableRequest($this->client, $varName, false, $withLocking);

        return $this
            ->commander
            ->runSync($cmd, self::READ_TIMEOUT)
            ->getAccomplishedResult();
    }

    public function write(string $varName, $variable, bool $unlock = true): bool
    {
        $cmd = new VariableShare($this->client, $varName, $variable, $unlock);

        try {
            //todo async! thinking -- really?
            $cmd = $this->commander->runSync($cmd, self::WRITE_TIMEOUT);
            if (!$cmd->isAccomplished() || !$cmd->isSuccessful()) {
                return false;
            }
        } catch (\RuntimeException $e) {
            return false;
        }
        return true;
    }

    public function remove(string $varName)
    {
        $cmd = new VariableFree($this->client, $varName);
        $this->commander->runAsync($cmd);
    }

    public function lock(string $varName)
    {
        $message = null;
        $cmd = $this
            ->commander
            ->runSync(
                (new VariableLock($this->client, $varName, true))
                    ->onError(function (VariableLock $context) use (&$message) {
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
                (new VariableLock($this->client, $varName, false))
                    ->onError(function (VariableLock $context) use (&$message) {
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
