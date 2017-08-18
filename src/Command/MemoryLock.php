<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class MemoryLock extends AbstractCommand
{
    const NAME = 'lmem';

    private $key;
    private $lock;

    public function __construct(Client $client, string $key, bool $lock)
    {
        parent::__construct($client);
        $this->key = $key;
        $this->lock = $lock;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isLock(): bool
    {
        return $this->lock;
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'lock' => $this->lock];
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['key'], $data['lock']);
    }
}
