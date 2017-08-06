<?php

namespace Maestroprog\Saw\Command;

class DebugCommand extends AbstractCommand
{
    const NAME = 'dbgc';

    public $needData = [
        'query',
    ];

    public function getQuery(): string
    {
        return $this->data['query'];
    }

    public function getArguments(): array
    {
        return (array)$this->data['arguments'] ?? [];
    }
}
