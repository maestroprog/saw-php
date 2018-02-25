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
    }

    public function synchronizeThreads(SynchronizationThreadInterface ...$threads): \Generator
    {
        do {
            /* Генератор ThreadRunner-а
             * Выполняет асинхронный код потоков и рекурсивных синхронизаторов. */
            if ($this->syncGenerator->valid()) {
                if (!$this->generatorExec) {
                    $this->generatorExec = true;
                    yield __METHOD__ . '.' . $this->syncGenerator->current();
                    $this->syncGenerator->next();
                    $this->generatorExec = false;
                } else {
                    yield __METHOD__ . '.endless';
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
