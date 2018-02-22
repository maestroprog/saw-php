<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

use Maestroprog\Saw\Connector\ControllerConnectorInterface;
use Maestroprog\Saw\Thread\Runner\ThreadRunnerInterface;

class WebThreadSynchronizer implements SynchronizerInterface
{
    private $threadRunner;
    private $connector;

    public function __construct(ThreadRunnerInterface $threadRunner, ControllerConnectorInterface $connector)
    {
        $this->threadRunner = $threadRunner;
        $this->connector = $connector;
    }

    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        $generator = $this->connector->work();
        do {
            $synchronized = true;
            foreach ($threads as $thread) {
                $synchronized = $synchronized && $thread->isSynchronized();
                if (!$synchronized) {
                    break;
                }
            }
            if (!$synchronized) {
                yield from $generator;
            }
        } while (!$synchronized);
    }

    public function synchronizeAll(): \Generator
    {
        yield from $this->synchronizeThreads($this->threadRunner->getThreadPool()->getThreads());
    }
}
