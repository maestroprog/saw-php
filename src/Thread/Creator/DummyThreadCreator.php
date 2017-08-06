<?php

namespace Maestroprog\Saw\Thread\Creator;

use Maestroprog\Saw\Saw;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\ThreadWithCode;

class DummyThreadCreator implements ThreadCreatorInterface
{
    public function thread(string $uniqueId, callable $code): AbstractThread
    {
        $thread = new ThreadWithCode(0, Saw::getCurrentApp()->getId(), $uniqueId, $code);
        $thread->run();
        return $thread;
    }

    public function threadArguments(string $uniqueId, callable $code, array $arguments): AbstractThread
    {
        $thread = new ThreadWithCode(0, Saw::getCurrentApp()->getId(), $uniqueId, $code);
        $thread->setArguments($arguments)->run();
        return $thread;
    }
}
