<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

class PacketCommand extends AbstractCommand
{
    const NAME = 'pckt';

    private $commands;

    public function __construct(Client $client, array $commands)
    {
        parent::__construct($client);
        $this->commands = $commands;
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['commands']);
    }

    public function toArray(): array
    {
        return ['commands' => $this->commands];
    }

    /**
     * @return AbstractCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
