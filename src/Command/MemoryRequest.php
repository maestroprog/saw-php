<?php

namespace Maestroprog\Saw\Command;

class MemoryRequest extends AbstractCommand
{
    const NAME = 'rmem';

    protected $needData = ['key'];

    public function getKey(): string
    {
        return $this->data['key'];
    }
}
