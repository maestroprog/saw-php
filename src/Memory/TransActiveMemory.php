<?php

namespace Maestroprog\Saw\Memory;

use Maestroprog\Saw\Command\VariableLink;
use Maestroprog\Saw\Command\VariableSearch;
use Maestroprog\Saw\Command\VariableUnlink;
use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Service\Commander;

final class TransActiveMemory implements MemoryInterface
{
    const TIMEOUT = 60;

    private $memory;
    private $commander;
    private $connector;

    public function __construct(
        MemoryInterface $memory,
        Commander $commander,
        ControllerConnectorInterface $connector
    )
    {
        $this->memory = $memory;
        $this->commander = $commander;
        $this->connector = $connector;
    }

    public function has(string $varName): bool
    {
        if (!$this->memory->has($varName)) {
            $cmd = $this->commander->runSync(new VariableSearch(
                $this->connector->getClient(),
                $varName,
                true
            ), self::TIMEOUT);
            if (!$cmd->isAccomplished() || !$cmd->isSuccessful()) {
                return false; // todo exception this?
            }
            return $cmd->getAccomplishedResult();
        }
        return true;
    }

    public function read(string $varName)
    {
        if (!$this->memory->has($varName)) {
            $cmd = $this->commander->runSync(
                new VariableSearch(
                    $this->connector->getClient(),
                    $varName,
                    true
                ),
                self::TIMEOUT
            );
            if (!$cmd->isAccomplished()) {
                throw new \LogicException('Command read not completed.');
            } elseif (!$cmd->isSuccessful()) {
                throw unserialize($cmd->getAccomplishedResult());
            }
            return $cmd->getAccomplishedResult();
        }
        return $this->memory->read($varName);
    }

    public function write(string $varName, $variable): bool
    {
        if (!$this->memory->write($varName, $variable)) {
            return false;
        }
        // todo async? really?
        $this->commander->runSync(new VariableLink($this->connector->getClient(), $varName), self::TIMEOUT);
        return true;
    }

    public function remove(string $varName)
    {
        $this->memory->remove($varName);
        $this->commander->runAsync(new VariableUnlink($this->connector->getClient(), $varName));
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
