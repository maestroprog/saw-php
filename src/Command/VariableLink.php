<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class VariableLink extends AbstractCommand
{
    const NAME = 'vlnk';

    private $key;

    public function __construct(Client $client, string $key)
    {
        parent::__construct($client);
        $this->key = $key;
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['key']);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function toArray(): array
    {
        return ['key' => $this->key];
    }
}
