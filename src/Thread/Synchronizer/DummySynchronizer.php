<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

use Maestroprog\Saw\Thread\AbstractThread;

class DummySynchronizer implements SynchronizerInterface
{
    public function synchronizeOne(AbstractThread $thread)
    {
        ;
    }

    public function synchronizeThreads(array $threads)
    {
        ;
    }

    public function synchronizeAll()
    {
        ;
    }
}
