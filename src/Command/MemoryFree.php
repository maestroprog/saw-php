<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class MemoryFree extends AbstractCommand
{
    const NAME = 'fmem';

    public function __construct(Client $client)
    {
        parent::__construct($client);
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client);
    }

    public function toArray(): array
    {
        return [];
    }
}
