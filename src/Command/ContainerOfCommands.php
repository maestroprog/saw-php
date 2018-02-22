<?php

namespace Maestroprog\Saw\Command;

class ContainerOfCommands implements \Countable
{
    /**
     * @var \ArrayObject|AbstractCommand[]
     */
    private $commands;

    public function __construct()
    {
        $this->commands = new \ArrayObject();
    }

    public function add(int $cmdId, AbstractCommand $command): AbstractCommand
    {
        if ($this->count() > 50) {
            $this->clean(); // AHAH TODO REMOVE IT!
        }
        if (isset($this->commands[$cmdId])) {
            throw new \RuntimeException('The command has already added.');
        }
        return $this->commands[$cmdId] = $command;
    }

    public function count(): int
    {
        return $this->commands->count();
    }

    /**
     * @todo отрефакторить эту костыльную логику
     * @return void
     */
    public function clean()
    {
        foreach ($this->commands as $id => $command) {
            if ($command->isAccomplished()) {
                unset($this->commands[$id]);
            }
        }
    }

    public function get(int $cmdId): AbstractCommand
    {
        if (!isset($this->commands)) {
            throw new \OutOfBoundsException('Out of bounds id "' . $cmdId . '".');
        }
        return $this->commands[$cmdId];
    }
}
