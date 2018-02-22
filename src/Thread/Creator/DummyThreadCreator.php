<?php

namespace Maestroprog\Saw\Thread\Creator;

use Maestroprog\Saw\Saw;
use Maestroprog\Saw\Thread\ThreadWithCode;

class DummyThreadCreator implements ThreadCreatorInterface
{
    public function thread(string $uniqueId, callable $code): ThreadWithCode
    {
        $thread = new ThreadWithCode(0, Saw::getCurrentApp()->getId(), $uniqueId, $code);
        $thread->run();

        return $thread;
    }

    public function threadArguments(string $uniqueId, callable $code, array $arguments): ThreadWithCode
    {
        $thread = new ThreadWithCode(0, Saw::getCurrentApp()->getId(), $uniqueId, $code);
        $thread->setArguments($arguments)->run();

        return $thread;
    }
}
