<?php

namespace Saw\Thread;

abstract class Pool
{
    protected $threads = [];

    public function add(Thread $thread)
    {
        $this->threads[$thread->getId()] = $thread;
    }
}
