<?php

namespace Maestroprog\Saw\Command;

use Esockets\Client;

final class MemoryRequest extends AbstractCommand
{
    const NAME = 'rmem';

    private $key;
    private $noResult;
    private $lock;

    public function __construct(Client $client, string $key, bool $noResult = false, bool $lock = false)
    {
        parent::__construct($client);
        $this->key = $key;
        $this->noResult = $noResult;
        $this->lock = $lock;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isNoResult(): bool
    {
        return $this->noResult;
    }

    public function isLock(): bool
    {
        return $this->lock;
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'no_result' => $this->noResult, 'lock' => $this->lock];
    }

    public static function fromArray(array $data, Client $client)
    {
        return new self($client, $data['key'], $data['no_result'], $data['lock']);
    }
}
