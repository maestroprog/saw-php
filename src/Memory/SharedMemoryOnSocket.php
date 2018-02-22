<?php

namespace Maestroprog\Saw\Memory;

use Esockets\Client;
use Maestroprog\Saw\Command\MemoryFree;
use Maestroprog\Saw\Command\VariableFree;
use Maestroprog\Saw\Command\VariableList;
use Maestroprog\Saw\Command\VariableLock;
use Maestroprog\Saw\Command\VariableRequest;
use Maestroprog\Saw\Command\VariableShare;
use Maestroprog\Saw\Service\Commander;

final class SharedMemoryOnSocket implements SharedMemoryInterface
{
    private $commander;
    private $client;

    public function __construct(Commander $commander, Client $client)
    {
        $this->commander = $commander;
        $this->client = $client;
    }

    public function has(string $varName, bool $withLocking = false): bool
    {
        $cmd = new VariableRequest($this->client, $varName, true, $withLocking);

        return $this
            ->commander
            ->runSync($cmd, self::READ_TIMEOUT)
            ->getAccomplishedResult();
    }

    public function read(string $varName, bool $withLocking = true)
    {
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
        $lockCommand = (new VariableLock($this->client, $varName, true))
            ->onError(function (VariableLock $context) use (&$message) {
                $message = $context->getAccomplishedResult();
            });
        $cmd = $this->commander->runSync($lockCommand, self::LOCK_TIMEOUT);
        if (!$cmd->isAccomplished() || !$cmd->isSuccessful()) {
            throw new MemoryLockException($message ?? 'Unknown MemoryLock error.');
        }
    }

    public function unlock(string $varName)
    {
        //todo async! thinking -- really?
        $message = null;
        $unlockCommand = (new VariableLock($this->client, $varName, false))
            ->onError(function (VariableLock $context) use (&$message) {
                $message = $context->getAccomplishedResult();
            });
        $cmd = $this->commander->runSync($unlockCommand, self::LOCK_TIMEOUT);
        if (!$cmd->isAccomplished() || !$cmd->isSuccessful()) {
            throw new MemoryLockException($message ?? 'Unknown MemoryLock error.');
        }
    }

    public function list(string $prefix = null): array
    {
        $message = null;
        $listCommand = (new VariableList($this->client, $prefix))
            ->onError(function (VariableList $context) use (&$message) {
                $message = $context->getAccomplishedResult();
            });
        $cmd = $this->commander->runSync($listCommand, self::READ_TIMEOUT * 10);
        if (!$cmd->isAccomplished() || !$cmd->isSuccessful()) {
            throw new MemoryLockException($message ?? 'Unknown MemoryLock error.');
        }

        return $cmd->getAccomplishedResult();
    }

    public function free()
    {
        $message = null;
        $freeCommand = (new MemoryFree($this->client))
            ->onError(function (VariableList $context) use (&$message) {
                $message = $context->getAccomplishedResult();
            });
        $cmd = $this->commander->runSync($freeCommand, self::WRITE_TIMEOUT);
        if (!$cmd->isAccomplished() || !$cmd->isSuccessful()) {
            throw new MemoryLockException($message ?? 'Unknown MemoryLock error.');
        }
    }
}
