<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class MemoryShare extends AbstractCommand
{
    const NAME = 'smem';

    private $key;
    private $data;

    public function __construct(Client $client, string $key, $data)
    {
        parent::__construct($client);
        $this->key = $key;
        $this->data = $data;
    }

    public function toArray(): array
    {
        return ['key' => $this->key];
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['key'], $data);
    }
}
