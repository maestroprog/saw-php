<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class VariableSearch extends AbstractCommand
{
    const NAME = 'vsrh';

    private $key;
    private $noResult;

    public function __construct(Client $client, string $key, bool $noResult = false)
    {
        parent::__construct($client);
        $this->key = $key;
        $this->noResult = $noResult;
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['key'], $data['no_result']);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isNoResult(): bool
    {
        return $this->noResult;
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'no_result' => $this->noResult];
    }
}
