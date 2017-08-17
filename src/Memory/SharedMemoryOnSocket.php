<?php

namespace Maestroprog\Saw\Memory;

use Esockets\Client;
use Maestroprog\Saw\Command\CommandHandler;
use Maestroprog\Saw\Command\MemoryFree;
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

        $this
            ->dispatcher
            ->addHandlers([
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
        $cmd = new MemoryRequest($this->client, $varName);

        return $this
            ->commander
            ->runSync($cmd, self::READ_TIMEOUT)
            ->getAccomplishedResult();
    }

    public function write(string $varName, $variable, bool $unlock = true): bool
    {
        // todo unlocking
        $cmd = new MemoryShare($this->client, $varName, $variable);

        try {
            //todo async! thinking -- really?
            $cmd = $this->commander->runSync($cmd, self::LOCK_TIMEOUT);
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
        // TODO: Implement lock() method.
    }

    public function unlock(string $varName)
    {
        // TODO: Implement unlock() method.
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
