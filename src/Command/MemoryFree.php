<?php

namespace Saw\Command;

class MemoryFree extends AbstractCommand
{
    const NAME = 'fmem';

    protected $needData = ['key'];

}
