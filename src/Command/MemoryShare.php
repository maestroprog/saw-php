<?php

namespace Saw\Command;

class MemoryShare extends AbstractCommand
{
    const NAME = 'smem';

    protected $needData = ['key', 'data'];

    public function getKey(): string
    {
        return $this->data['key'];
    }

    public function getData(): array
    {
        return $this->data['data'];
    }
}
