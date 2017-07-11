<?php

namespace Saw\Thread\Runner;

class DummyThreadRunner implements ThreadRunnerInterface
{
    public function runThreads(array $threads): bool
    {
        return true;
    }
}
