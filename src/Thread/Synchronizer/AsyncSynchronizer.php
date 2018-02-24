<?php

namespace Maestroprog\Saw\Thread\Synchronizer;

use Maestroprog\Saw\Thread\Runner\ThreadRunnerInterface;

class AsyncSynchronizer implements SynchronizerInterface
{
    private $threadRunner;
    private $syncGenerator;
    private $generatorExec = false;

    public function __construct(ThreadRunnerInterface $threadRunner, \Generator $syncGenerator)
    {
        $this->threadRunner = $threadRunner;
        $this->syncGenerator = $syncGenerator;
        $syncGenerator->rewind();
    }

    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        do {
            /* Генератор ThreadRunner-а
             * Выполняет асинхронный код потоков и рекурсивных синхронизаторов. */
            if ($this->syncGenerator->valid()) {
                yield $this->syncGenerator->current();
                if (!$this->generatorExec) {
                    $this->generatorExec = true;
                    $this->syncGenerator->next();
                    $this->generatorExec = false;
                }
            }
            $synchronized = true;
            foreach ($threads as $thread) {
                $synchronized = $synchronized && $thread->isSynchronized();
                if (!$synchronized) {
                    break;
                }
            }
        } while (!$synchronized);
    }

    public function synchronizeAll(): \Generator
    {
        yield from $this->synchronizeThreads(...$this->threadRunner->getThreadPool()->getThreads());
    }
}
