<?php

namespace Maestroprog\Saw\Command;

class ContainerOfCommands implements \Countable
{
    private $commands;

    public function __construct()
    {
        $this->commands = new \ArrayObject();
    }

    public function add(string $containerId, AbstractCommand $command): AbstractCommand
    {
        if (isset($this->commands[$containerId])) {
            throw new \RuntimeException('The command has already added.');
        }
        return $this->commands[$containerId] = $command;
    }

    public function get(string $containerId): AbstractCommand
    {
        return $this->commands[$containerId];
    }

    public function count()
    {
        return $this->commands->count();
    }
}
