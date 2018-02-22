<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

class DummySynchronizer implements SynchronizerInterface
{
    public function synchronizeOne(SynchronizationThreadInterface $thread): \Generator
    {
        yield;
    }

    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        yield;
    }

    public function synchronizeAll(): \Generator
    {
        yield;
    }
}
