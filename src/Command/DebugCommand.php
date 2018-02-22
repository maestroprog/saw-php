<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

class DebugCommand extends AbstractCommand
{
    const NAME = 'dbgc';

    private $query;
    private $arguments;

    public function __construct(Client $client, string $query, array $arguments = null)
    {
        parent::__construct($client);
        $this->query = $query;
        $this->arguments = $arguments;
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['query'], $data['arguments']);
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getArguments(): array
    {
        return $this->arguments ?? [];
    }

    public function toArray(): array
    {
        return ['query' => $this->query, 'arguments' => $this->arguments];
    }
}
