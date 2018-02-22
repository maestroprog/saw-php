<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

use Maestroprog\Saw\Thread\Runner\ThreadRunnerInterface;
use function Maestroprog\Saw\iterateGenerator;

class DummySynchronizer implements SynchronizerInterface
{
    private $threadRunner;

    public function __construct(ThreadRunnerInterface $threadRunner)
    {
        $this->threadRunner = $threadRunner;
    }

    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        yield iterateGenerator((function () use ($threads): \Generator {
            do {
                $synchronized = true;
                foreach ($threads as $thread) {
                    $synchronized = $synchronized && $thread->isSynchronized();
                    if (!$synchronized) {
                        break;
                    }
                }
            } while (!$synchronized);

            yield;
        })());
    }

    public function synchronizeAll(): \Generator
    {
        yield from $this->synchronizeThreads($this->threadRunner->getThreadPool()->getThreads());
    }
}
