<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

use Maestroprog\Saw\Thread\Runner\ThreadRunnerInterface;

/**
 * @deprecated
 */
class RemoteThreadSynchronizer implements SynchronizerInterface
{
    private $threadRunner;
    private $syncGenerator;

    public function __construct(ThreadRunnerInterface $threadRunner, \Generator $syncGenerator)
    {
        $this->threadRunner = $threadRunner;
        $this->syncGenerator = $syncGenerator;
    }

    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        do {
            $synchronized = true;
            foreach ($threads as $thread) {
                $synchronized = $synchronized && $thread->isSynchronized();
                if (!$synchronized) {
                    break;
                }
            }
            if (!$synchronized && $this->syncGenerator->valid()) {
                yield $this->syncGenerator->current();
                $this->syncGenerator->next();
            }
        } while (!$synchronized);
    }

    public function synchronizeAll(): \Generator
    {
        yield from $this->synchronizeThreads(...$this->threadRunner->getThreadPool()->getThreads());
    }
}
