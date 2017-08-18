<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class MemoryShare extends AbstractCommand
{
    const NAME = 'smem';

    private $key;
    private $variable;
    private $unlock;

    public function __construct(Client $client, string $key, $variable, bool $unlock = false)
    {
        parent::__construct($client);
        $this->key = $key;
        $this->variable = $variable;
        $this->unlock = $unlock;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getVariable()
    {
        return $this->variable;
    }

    public function isUnlock(): bool
    {
        return $this->unlock;
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'variable' => $this->variable, 'unlock' => $this->unlock];
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['key'], $data['variable'], $data['unlock']);
    }
}
