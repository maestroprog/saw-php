<?php

namespace Saw\Command;

class DebugCommand extends AbstractCommand
{
    const NAME = 'dbgc';

    public $needData = [
        'query'
    ];

    public function getQuery(): string
    {
        return $this->data['query'];
    }
}
