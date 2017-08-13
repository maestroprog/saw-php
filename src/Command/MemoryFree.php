<?php

namespace Maestroprog\Saw\Command;

class MemoryFree extends AbstractCommand implements AsyncCommandInterface
{
    const NAME = 'fmem';

    protected $needData = ['key'];

    public function __construct(string $key)
    {
        $this->data['key'] = $key;
    }

    public function toArray(): array
    {
        return ['key' => $this->key];
    }
}

// идея - коммандхендлер создавать и в нем делать run runasync
