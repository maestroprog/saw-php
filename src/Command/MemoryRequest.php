<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class MemoryRequest extends AbstractCommand
{
    const NAME = 'rmem';

    private $key;

    public function __construct(Client $client, string $key)
    {
        parent::__construct($client);
        $this->key = $key;
    }

    public function toArray(): array
    {
        return ['key' => $this->key];
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['key']);
    }
}
