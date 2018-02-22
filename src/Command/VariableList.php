<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class VariableList extends AbstractCommand
{
    const NAME = 'vlst';

    private $prefix;

    public function __construct(Client $client, string $prefix = null)
    {
        parent::__construct($client);
        $this->prefix = $prefix;
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['prefix'] ?? null);
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function toArray(): array
    {
        return ['prefix' => $this->prefix];
    }
}
