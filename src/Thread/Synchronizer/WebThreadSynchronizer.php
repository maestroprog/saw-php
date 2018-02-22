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

    public function synchronizeOne(SynchronizationThreadInterface $thread): \Generator
    {
        while (!$thread->isSynchronized()) {
            yield from $this->connector->work();
        }
    }

    public function synchronizeAll(): \Generator
    {
        yield $this->synchronizeThreads($this->threadRunner->getThreadPool()->getThreads());
    }

    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        $synchronized = false;
        $generator = $this->connector->work();
        do {
            $synchronizeOk = true;
            foreach ($threads as $thread) {
                $synchronizeOk = $synchronizeOk && $thread->isSynchronized();
                if (!$synchronizeOk) {
                    break;
                }
            }
            if ($synchronizeOk) {
                $synchronized = true;
            } else {
                yield from $generator;
            }
        } while (!$synchronized);
    }
}
